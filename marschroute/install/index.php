<?php
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));
IncludeModuleLangFile($PathInstall."/install.php");
IncludeModuleLangFile(__FILE__);

if(class_exists("marschroute")) return;
class marschroute extends CModule
{
    var $MODULE_ID = 'marschroute';
    var $MODULE_VERSION = '0.0.1';
    var $MODULE_VERSION_DATE = '2016-12-02 16:25:00';
    var $MODULE_NAME = 'Marschroute';
    var $MODULE_DESCRIPTION = 'Marschroute delivery module';
    var $MODULE_GROUP_RIGHTS = "N";

    const MARSCHROUTE_HANDLER_CLASSNAME = '\Sale\Handlers\Delivery\MarschrouteHandler';

    /**
     * Необходимые поля заказа
     * @var array
     */
    private $arRequiredPropertiesList = array(
        'MARSCHROUTE_PLACE_ID' => array(
            'NAME'          => 'Идентификатор доставки Marschroute',
            'TYPE'          => 'NUMBER',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => 0,
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',

        ),
        'MARSCHROUTE_DELIVERY_COST' => array(
            'NAME'          => 'Стоимость доставки Marschroute',
            'TYPE'          => 'NUMBER',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => 0,
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_DELIVERY_KLDR' => array(
            'NAME'          => 'КЛАДР',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_DELIVERY_COMMENT' => array(
            'NAME'          => 'Комментарий к доставке Marschroute',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),

        'MARSCHROUTE_STREET' => array(
            'NAME'          => 'Улица',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_HOUSE' => array(
            'NAME'          => 'Дом',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_BULDING' => array(
            'NAME'          => 'Строение/корпус',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_ROOM' => array(
            'NAME'          => 'Квартира/офис',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_INDEX' => array(
            'NAME'          => 'Почтовый индекс',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_METRO' => array(
            'NAME'          => 'Идентификатор метро',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
        'MARSCHROUTE_SEND_DATE' => array(
            'NAME'          => 'Диапазон доставки',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),
		'MARSCHROUTE_ORDER_ID' => array(
			'NAME' 			=> 'Индентификатор заказа МАРШРУТ',
			'TYPE'          => 'STRING',
			'REQUIRED'      => 'N',
			'DEFAULT_VALUE' => '',
			'SORT'          => 500,
			'USER_PROPS'    => 'N',
			'IS_LOCATION'   => 'N',
			'UTIL'          => 'N',
		),
        'MARSCHROUTE_ERROR' => array(
            'NAME' 			=> 'Ошибка при отправке заказа',
            'TYPE'          => 'STRING',
            'REQUIRED'      => 'N',
            'DEFAULT_VALUE' => '',
            'SORT'          => 500,
            'USER_PROPS'    => 'N',
            'IS_LOCATION'   => 'N',
            'UTIL'          => 'N',
        ),

    );

    public function marschroute()
    {

    }

    public function DoInstall()
    {
        global $APPLICATION, $step;
        if ( $step < 2 ) {
            $APPLICATION->IncludeAdminFile("Установка модуля Marschroute", $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/marschroute/install/step.php");
        } elseif ( $step == 2 ) {
            CModule::IncludeModule('sale');
            $this->InstallFiles();
            $this->InstallDB();
        }

        RegisterModule("marschroute");

        CAgent::AddAgent(
            "CMarschroute::sync();",
            "marschroute",
            "N",
            "5",
            "",
            "Y",
            "",
            "1000"
        );
    }

    public function DoUninstall()
    {
        $this->UnInstallFiles();
        $this->UnInstallDB();
        UnRegisterModule("marschroute");
    }

    public function InstallFiles()
    {
        if( $_ENV["COMPUTERNAME"] != 'BX' )
        {
            CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/marschroute/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
            CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/marschroute/install/sale_delivery", $_SERVER["DOCUMENT_ROOT"]."/bitrix/php_interface/include/sale_delivery", true, true);
        }
        return true;
    }

    public function UnInstallFiles()
    {
        if( $_ENV["COMPUTERNAME"] != 'BX' )
        {
            //DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/marschroute/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components");
        }
        return true;
    }

    public function InstallDB()
    {
        \Bitrix\Main\Config\Option::set($this->MODULE_ID, 'base_url', 'https://api.marschroute.ru/');
        /**
         * Блок работы с LOCATIONS
         */
        $iLocationId = 0;
        if ( !empty($_REQUEST['INSTALL_MARSCHROUTE_LOCATION']) && 'Y' == $_REQUEST['INSTALL_MARSCHROUTE_LOCATION'] ) {
            $resLocationsList = \Bitrix\Sale\Location\LocationTable::getList(array(
                'filter' => array(
                    'CODE' => 'marschroute',
                ),
            ));
            if ( $resLocationsList->getSelectedRowsCount() < 1 ) {
                $iLocationId = \Bitrix\Sale\Location\LocationTable::add(array(
                    'CODE'          => 'marschroute',
                    'DEPTH_LEVEL'   => 1,
                    'PARENT_ID'     => 0,
                    'TYPE_ID'       => 1,
                    'NAME' => array( // языковые названия
                        'ru' => array(
                            'NAME' => 'Marschroute'
                        ),
                        'en' => array(
                            'NAME' => 'Marschroute'
                        ),
                    )
                ));

            } else {
                $arLocation = $resLocationsList->fetch();
                $iLocationId = (int)$arLocation['ID'];
            }
        }

        /**
         * Блок добавления типа доставки, свойств заказа и ограничений на эти свойства.
         */
        if ( !empty($_REQUEST['INSTALL_ORDER_PROPERTIES_PERSONS']) ) {
            // Проверяем, есть ли доставка Marschroute
            $arHandlersList = \Bitrix\Sale\Delivery\Services\Manager::getHandlersList();
            if ( !in_array(self::MARSCHROUTE_HANDLER_CLASSNAME, $arHandlersList) ) {
                // У нас проблемы с установкой delivery handler
                return false;
            }

            if (class_exists('\Bitrix\Sale\DeliveryService'))
            {
                $resDeliveries = \Bitrix\Sale\DeliveryService::getList(array());
            }
            if (class_exists( '\Bitrix\Sale\Delivery\Services\Table'))
            {
                $resDeliveries = \Bitrix\Sale\Delivery\Services\Table::getList(array());
            }

            $arDeliveries = array();
            while ( $arDelivery = $resDeliveries->fetch() ) {
                $arDelivery = \Bitrix\Sale\Delivery\Services\Manager::getById($arDelivery['ID']);
                if ( $arDelivery['CLASS_NAME'] == self::MARSCHROUTE_HANDLER_CLASSNAME ) {
                    $arDeliveries[] = $arDelivery;
                }
            }
            // Если нет ни одной доставки Marschroute - создадим
            if ( empty($arDeliveries) ) {
                // Загрузим логотип
                $arFile = CFile::MakeFileArray($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/marschroute/install/images/m_logo_500.png");
                $arFile['MODULE_ID'] = 'marschroute';
                $iFileID = CFile::SaveFile($arFile,'marschroute');

                $arDelivery = array(
                    'CODE'                  => 'MARSCHROUTE',
                    'PARENT_ID'             => 0,
                    'NAME'                  => 'Доставка Marschroute',
                    'ACTIVE'                => 'N', // не активная, пока не включат сами
                    'DESCRIPTION'           => 'Доставка Marschroute',
                    'SORT'                  => 1, // мы - сама скромность
                    'LOGOTIP'               => $iFileID,
                    'CLASS_NAME'            => self::MARSCHROUTE_HANDLER_CLASSNAME,
                    'CURRENCY'              => 'RUB',
                    'ALLOW_EDIT_SHIPMENT'   => 'N',
                    'CONFIG'                => array(),
                );
                $iDeliveryServiceID = \Bitrix\Sale\Delivery\Services\Manager::add($arDelivery)->getId();

                if ( $iDeliveryServiceID > 0 ) {
                    $arDelivery['ID'] = $iDeliveryServiceID;
                    $arDeliveries[] = $arDelivery;
                }
            }

            if ( empty($arDeliveries) ) {
                // Нет ни одной подходящей службы доставки
                // И создать тоже не получилось
                return;
            }
            // Выбираем первый попавшийся обработчик, ПОТОМУ ЧТО
            $arCurrentDelivery = array_shift($arDeliveries);

            foreach ($_REQUEST['INSTALL_ORDER_PROPERTIES_PERSONS'] as $personHash) {
                // Для разных сайтов может быть один и тот же тип плательщика, но
                // мы будем проверять всё заново каждый раз просто потому что можем.
                list($LID, $personID) = explode('_', $personHash, 2);
                // Проверим, есть ли на самом деле такой сайт и тип клиента
                $resSite = \Bitrix\Main\SiteTable::getById($LID);
                $arSite = $resSite->Fetch();

                $arPersonType = \Bitrix\Sale\PersonType::load($LID, $personID);
                // Неправильный сайт или неправильный тип клиента
                if ( empty($arSite) || empty($arPersonType) ) {
                    continue;
                }

                // Проверим, есть ли группа свойств заказа для этого типа клиента
                $resOrderGroupList = \Bitrix\Sale\Internals\OrderPropsGroupTable::getList(array(
                    'filter' => array(
                        'PERSON_TYPE_ID' => $personID,
                        'NAME' => 'Marschroute',
                    ),
                ));

                $iGroupId = 0;
                if ( $resOrderGroupList->getSelectedRowsCount() > 0 ) {
                    $arOrderGroup = $resOrderGroupList->Fetch();
                    $iGroupId = (int)$arOrderGroup['ID'];
                } else {
                    // Если нет - создадим
                    $iGroupId = \Bitrix\Sale\Internals\OrderPropsGroupTable::add(array(
                        'PERSON_TYPE_ID' => $personID,
                        'NAME' => 'Marschroute',
                        'SORT' => '500',
                    ))->getId();
                }
                // По какой-то причине группу не нашли и не создали
                if ( !$iGroupId ) {
                    continue;
                }

                // Теперь проверим наличие необходимых свойств в группе
                $resOrderPropsList = \Bitrix\Sale\Internals\OrderPropsTable::getList(array(
                    'filter' => array(
                        'PERSON_TYPE_ID' => $personID,
                        'PROPS_GROUP_ID' => $iGroupId,
                    ),
                ));

                $arProperties = array();
                while ( $arProperty = $resOrderPropsList->Fetch() ) {
                    $arProperties[$arProperty['CODE']] = $arProperty;
                }

                // Добавим несуществующие доселе свойства
                $arNotExistingProperties = array_diff(array_keys($this->arRequiredPropertiesList), array_keys($arProperties));
                if ( !empty($arNotExistingProperties) ) {
                    foreach ( $arNotExistingProperties as $sNotExistingPropertyKey ) {
                        $arProperty = $this->arRequiredPropertiesList[$sNotExistingPropertyKey];
                        $arProperty['PERSON_TYPE_ID'] = $personID;
                        $arProperty['PROPS_GROUP_ID'] = $iGroupId;
                        $arProperty['CODE'] = $sNotExistingPropertyKey;
                        // Добавляем в БД
                        $iPropertyID = \Bitrix\Sale\Internals\OrderPropsTable::add($arProperty)->getId();
                        $arProperty['ID'] = $iPropertyID;
                        // Добавим в список, будет нужно позже
                        $arProperties[$arProperty['CODE']] = $arProperty;
                    }
                }

                // Добавим свойствам связки
                foreach ($arProperties as $arProperty) {
                    // Проверяем, есть ли связка свойства с нашим способом доставки
                    $resRelations = \Bitrix\Sale\Internals\OrderPropsRelationTable::getList(array(
                        'filter' => array(
                            'PROPERTY_ID' => $arProperty['ID'],
                            'ENTITY_TYPE' => 'D', // delivery
                            'ENTITY_ID' => (int)$arCurrentDelivery['ID'],
                        ),
                    ));
                    // Если нет - добавим
                    if ( $resRelations->getSelectedRowsCount() < 1 ) {
                        $iPropRelationId = \Bitrix\Sale\Internals\OrderPropsRelationTable::add(array(
                            'PROPERTY_ID' => $arProperty['ID'],
                            'ENTITY_TYPE' => 'D',
                            'ENTITY_ID' => (int)$arCurrentDelivery['ID'],
                        ))->getId();
                    }
                }
            }
        }
    }

    public function UnInstallDB()
    {

    }
}
