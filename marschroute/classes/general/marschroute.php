<?php
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Application,
    \Bitrix\Main\Web\Uri,
    \Bitrix\Main\Web\HttpClient;
use  \Bitrix\Sale\Internals\OrderTable;

use Bitrix\Main\Config\Configuration;
use Bitrix\Sale\Order;

CModule::IncludeModule('sale');
CModule::IncludeModule('marschroute');

class CMarschroute
{
	const MODULE_ID = "marschroute";
	const MODULE_CODE = "MARSCHROUTE";
	// Список статусов
	protected static $smStatuses = array(10, 11, 12, 13, 14, 15, 16, 18, 20, 21, 25, 35, 36, 50, 51) ;
	// Список НДС
	protected static $nds = array(0, 10, 18);
	// Ссылка на функцию запуска агента
	protected static $myself = "CMarschroute::sync();";

	protected static $api_key;
	// Базовый URL для запроса
	protected static $base_url;

	// Настройка http-клиента битрикс
	protected static $httpClientOptions = array(
		"waitResponse" => true,
		"socketTimeout" => 30,
		"streamTimeout" => 60,
		"version" => HttpClient::HTTP_1_1
	);

	// Получение массива заполненных параметров заказа по статусу
    protected function getBitrixOrders($filter='all', $limit = 0) {

		$status_for_send =   Option::get(self::MODULE_ID, 'status_for_send');
		$pay_systems = Option::get( self::MODULE_ID, 'pay_systems' );

		// Формирование фильтра
    	switch ($filter):
			case 'all':
				$filter = array(
					'STATUS_ID' => $status_for_send
				);
				break;
			case 'not_sended':
				$filter = array(
					'STATUS_ID' => $status_for_send,
					'PROPERTY.CODE' => 'MARSCHROUTE_ORDER_ID',
					'=PROPERTY.VALUE' => ''
				);
				break;
			default: return false;
		endswitch;


		// Получение списка заказа с выбраным статусом
        $settingsGetList = array(
            'select' => array(
                'ID'
            ),
            'filter' => $filter,
        );

        // Если лимит ненулевой, то устанавливаем его
        if ($limit!==0) $settingsGetList['limit'] = $limit;

		$rsOrdersStatus = Order::getList( $settingsGetList );

		$ordersByStatus = array();
		$nds = Option::get(self::MODULE_ID, 'nds');

		// Формирование массива параметров заказа
		while ($rsOrder = $rsOrdersStatus->Fetch()) {

			$order = Order::load($rsOrder['ID']);

			$propertyCollection = $order->loadPropertyCollection();

			$arPropColl = $propertyCollection->getArray();

			$arProp = array();
			foreach ($arPropColl['properties'] as $key => $val) {
				$arProp[$val['CODE']] = $val['VALUE'][0];
			}

			// payment_type
			$arProp['PAYMENT_TYPE'] = in_array($pay_systems[0], $pay_systems ) ? 1 : 2 ;
			// customer . id
			$arProp['CASTOMER_ID'] = $order->getUserId();

			$basket = Bitrix\Sale\Basket::loadItemsForOrder($order);

			$arProp['ITEMS'] = array();
			foreach ($basket as $item){
				array_push($arProp['ITEMS'], array(
					'item_id' => $item->getField('ID'),
					'name' => $item->getField('NAME'),
					'nds' => (int)$nds,
					'price' => (int)$item->getField('PRICE'),
					'quantity' => (int)$item->getField('QUANTITY')
				));
			}
			$ordersByStatus[$rsOrder['ID']] = $arProp;
		}
		return $ordersByStatus;
	}

	// Массив json для отправки
    protected function mapBitrixOrders() {

        $limit = Option::get( self::MODULE_ID, 'limit', 10 );

        $orders = self::getBitrixOrders('not_sended', $limit ); // в параметр

        $mapOrders = array();

        foreach ( $orders as $id => $val ) {
            list($firstname, $middlename, $lastname) = preg_split('/\s+/', $val['FIO']);
            if (empty($lastname)) $lastname = $middlename;
            $mapOrders[$id] =
                json_encode(
                array(
                    'order' => array(
                        'id' => $id,
                        'delivery_sum' => $val['MARSCHROUTE_DELIVERY_COST'],
                        'payment_type' => $val['PAYMENT_TYPE'], //++
                        'weight' => 1000,
                        'city_id' => $val['MARSCHROUTE_DELIVERY_KLDR'],
                        'place_id' => $val['MARSCHROUTE_PLACE_ID'],
                        'street' => $val['MARSCHROUTE_STREET'],
                        'building_1' => $val['MARSCHROUTE_HOUSE'],
                        'building_2' => $val['MARSCHROUTE_BULDING'],
                        'room' => $val['MARSCHROUTE_ROOM'],
                        'comment' => $val['MARSCHROUTE_DELIVERY_COMMENT'],
						'send_date' => $val['MARSCHROUTE_SEND_DATE'],
						'sm_order_id' => $val['MARSCHROUTE_ORDER_ID'],
                        'index' => $val['MARSCHROUTE_INDEX']

                    ),
                    'customer' => array(
                        'id' => $val['CASTOMER_ID'],
                        'firstname'  => $firstname,
                        'middlename' => $middlename,
                        'lastname' => $lastname,
						'phone' => $val['PHONE']

                    ),
                    'items' => $val['ITEMS']
                )
            );

        }

        return $mapOrders;
    }

    // Функция синхронизации
    public static function sync() {

        self::$api_key = Option::get(self::MODULE_ID, "api_key");
        self::$base_url = Option::get( self::MODULE_ID, "base_url");
        // Отправка заказов
		self::sendOrders();
		// Получение статусов при условии, что нет дублей статутсов доствки в настройках
		if ( Option::get( self::MODULE_ID, 'delivery_statuses_error' )) {
			self::takePutStatuses();
		}
		//echo 'sync OK!!!';
		return self::$myself;
    }

    // Получение списка ID статусов
    public static function getSmStatuses() {
		return self::$smStatuses;
	}

	// Получение списка восможных НДС
	public static function getNds() {
    	return self::$nds;
	}

	// Получение и установка статуса для заказов
	protected function takePutStatuses() {
		// Даты для выгрузки статусов
		$start = Option::get( self::MODULE_ID, 'last_update' );
		$end = date('d.m.Y');

		//Сравнение дат
		if (!empty( $start )) {
			$_start = new DateTime($start);
			$_end = new DateTime($end);
			// Если последнее обновление больше тридцати дней, то выставляем дату на 30 дней позже
			$start = ($_start->diff($_end)->days > 30) ? $_end->modify('-30 days')->format('d.m.Y') : $start;
		}
		// Если пустая 'last_update', то начальная и конечечная дата равна текущей
		else $start = $end;

		// Получение массива карты статусов из настроек модуля
		$delivery_statuses = json_decode( Option::get(self::MODULE_ID, 'delivery_statuses'), true );
		// Формирование URL-запроса с датами
		$url = self::$base_url . self::$api_key . "/orders?filter[date_status]=$start%20-%20$end";

		try {
			// Создание http-клиента и отправка запроса
			$httpClient = new HttpClient(self::$httpClientOptions);
			$httpClient->query( HttpClient::HTTP_GET, $url );

			// Результат ответа
			$result = json_decode( $httpClient->getResult(), true );

			// Если ответ не JSON
			if (json_last_error()!=JSON_ERROR_NONE)
				throw new Exception('Сервер возвращает неверные данные');

			// Обработка
			if (!$result['success'])
				throw new Exception( $result['comment'] );

			// Если тело ответа не пустое, то обрабатываем
			if ( !empty($result['data'])) {

				// Обработка результатов запроса

				// ID нашей доставки
				$our_delivery_id = \Bitrix\Sale\Delivery\Services\Manager::getIdByCode('MARSCHROUTE');

				foreach ( $result['data'] as $data_item ) {
					// Если пустой ID на стороне СМ, то его пропускаем
					if (empty($data_item['id'])) continue; ///

					// Загрука заказа по id
					$order = Order::load( $data_item['id'] );

					// Если нет заказа на стороне Битрикс, то его пропускаем
					if (!$order) continue;

					// Установка статусов //////

					// Получение отгрузок у заказа
					$shipmentCollection = $order->getShipmentCollection();

					// Обход отгрузок
					foreach ($shipmentCollection as $shipment) {
						// Находим первую отгрузку с нашим DELIVERY_ID
						if ($shipment->getField( 'DELIVERY_ID' ) == $our_delivery_id )	{

							// Выбор нужного статуса из настроек модуля

							foreach ($delivery_statuses as $status_id => $status_item)	{
								// Если в настройках есть статус, то его и ставим
								if (in_array($data_item['status'], $status_item))	{
									$shipment->setField('STATUS_ID', $status_id);
									$order->save();
									break;
								}
							}
							break;
						}
					}
				}
			}
			// Установка последней даты обновления
			Option::set( self::MODULE_ID, 'last_update', $end);

		}
		catch (Exception $e){
			//echo $e->getMessage()."\n";
		}
	}

	// Отправка заказов
	protected function sendOrders(){
		//Формирование URL-запроса
		$url = self::$base_url . self::$api_key . '/order';

		// Обход массива с put_body
		foreach (self::mapBitrixOrders() as $nOrder => $put_body){
			try {

				// Создание http-клиента и отправка запроса
				$httpClient = new HttpClient(self::$httpClientOptions);
				$httpClient->query(HttpClient::HTTP_PUT, $url, $put_body);
				// Результат ответа
				$result = json_decode( $httpClient->getResult(), true );

				// Обработка ошибки
				if (!$result['success']) {
                    // Текст ошибки
				    self::setOrderProp($nOrder, 'MARSCHROUTE_ERROR', "Ошибка [code]:".$result['code']."\n".
                        $result['comment'] . "\n" .
                        json_encode( $result['errors']));

				    continue;
				}

				// Если существует [id] в теле ответа
				if (isset($result['id'])) {

                    // Номера заказа
                    self::setOrderProp($result['id'], 'MARSCHROUTE_ORDER_ID', $result['order_id'] );
                    // Очистка ошибки
                    self::setOrderProp($result['id'], 'MARSCHROUTE_ERROR', '' );
				}
			}

			catch (Exception $e) {
				//echo $e->getMessage();
				//echo $e->getLine();
			}
		}
	}

	// Установка Значения поля по Коду и Номеру заказа
    protected function setOrderProp($id_order, $code, $value ) {
	    $order = Order::load($id_order);
		$props = \Bitrix\Sale\Internals\OrderPropsTable::getList(array(
				'filter'=> array(
					'CODE' => $code,
					'PERSON_TYPE_ID' => $order->getPersonTypeId()
				)
			)
		);

		$prop = $props->fetchAll();

        $id_prop = $prop[0]['ID'];
        $propertyCollection = $order->getPropertyCollection();
        $propValue = $propertyCollection->getItemByOrderPropertyId($id_prop);
        $propValue->setValue($value);
        $order->save();

	}
}
