<?php
use Bitrix\Main\Config\Option;
use Bitrix\Sale\Order;
use Bitrix\Sale\Internals\PaySystemActionTable;


global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/marschroute/lang","/options.php"));

$module_id = 'marschroute';
CModule::IncludeModule( $module_id );
CModule::IncludeModule( 'sale');


$MOD_RIGHT = $APPLICATION->GetGroupRight( $module_id );


$arListStatuses = CSaleStatus::GetList()->arResult; ///?
$arPaySystems = PaySystemActionTable::getList()->fetchAll();

$url_action = $APPLICATION->GetCurPage()."?mid=".htmlspecialcharsbx($mid)."&lang=".LANGUAGE_ID;

//*********************************************************************************************
// N SM status
$nSmStatus = CMarschroute::getSmStatuses();
$arOrderStatus = Bitrix\Sale\OrderStatus::getAllStatusesNames();
$arDeliveryStatus = Bitrix\Sale\DeliveryStatus::getAllStatusesNames();


if (!empty($_POST)) {

    //
    if ( !isset($_POST['Restore']) || $_POST['Restore']=='N' ) {

		if (isset($_POST['nds']))
			Option::set($module_id, 'nds', $_POST['nds']);

		
		if (isset($_POST['api_key']))
			Option::set($module_id, 'api_key', $_POST['api_key']);
		
		if (isset($_POST['status_for_send']))
			Option::set($module_id, 'status_for_send', $_POST['status_for_send']);
		
		if (isset($_POST['pay_systems'])) {
			Option::set($module_id, 'pay_systems', json_encode($_POST['pay_systems']));
		}

		if (isset($_POST['limit']))
		    Option::set($module_id, 'limit', $_POST['limit']);

		if (isset($_POST['base_url']))
		    Option::set($module_id, 'base_url', $_POST['base_url']);

		$delivery_statuses = array();
		foreach ($arDeliveryStatus as $status_ID => $description)
			if (isset($_POST['delivery_status_' . $status_ID])) {
				$delivery_statuses[$status_ID] = $_POST['delivery_status_' . $status_ID];
			}
		Option::set($module_id, 'delivery_statuses', json_encode($delivery_statuses));
	}
	else {
        // Восстановление агента, который удален или завершился с ошибкой
		$our_agent =CAgent::GetList(
		    array(),
            array( 'NAME' => 'CMarschroute::sync();' )
        );

		$our_agent = $our_agent->Fetch();

		CAgent::Delete( $our_agent['ID'] );
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

} // $_POST


Option::set( $module_id, 'delivery_statuses_error', '1' );

$delivery_statuses = json_decode( Option::get( $module_id, 'delivery_statuses' ), true);

// Поиск дублей статусов МАРШРУТ
$line_ds = array();
foreach ($delivery_statuses as $ds) {
    foreach ($ds as $one_ds){
        if (in_array($one_ds, $line_ds)) {
		    echo CAdminMessage::ShowMessage('Необходимо указать только один [Статус МАРШРУТ] в соответствии со [Статус]');
            Option::set( $module_id, 'delivery_statuses_error', '0' );///***
			break 2;
        }
        array_push($line_ds, $one_ds);
    }
}


$api_key = Option::get($module_id, 'api_key');
$status_for_send =  Option::get($module_id, 'status_for_send');
$pay_systems = json_decode(Option::get($module_id, 'pay_systems'));
$nds = Option::get($module_id, 'nds');
$limit = Option::get($module_id, 'limit', '');
$base_url = Option::get($module_id, 'base_url', '')

?>


<form method="POST" action="<?php echo $url_action ?>">
<table>
    <tr>
        <td><?php echo GetMessage("NDS")?></td>
        <td>
            <select name="nds">
                <option value="">---</option>
                <?php foreach (CMarschroute::getNds() as $item_nds): ?>
                    <option value="<?php echo $item_nds ?>" <?php echo ($item_nds == $nds) ? 'selected':'' ?>>
                        <?php echo $item_nds ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td><?php echo GetMessage("PRIVATE_KEY")?></td>
        <td>
            <input type="text" size="30" value="<?php echo $api_key?>" name="api_key"/>
        </td>
    </tr>

    <tr>
        <td><?php echo GetMessage("BASE_URL")?></td>
        <td>
            <input type="text" size="30" value="<?php echo $base_url?>" name="base_url"/>
        </td>
    </tr>

    <tr>
        <td>
            <?php echo GetMessage("STATUS_ORDER")?>
        </td>
        <td>
            <select name="status_for_send">
                <option value="">
                    ---
                </option>
                <?php foreach ($arOrderStatus as $key => $val):?>
                        <option value="<?php echo $key?>" <?php echo ($key == $status_for_send)? 'selected':'' ?>>
                            <?php echo $key.' -- '. $val?>
                        </option>
                <?php endforeach;?>
            </select>
        </td>
    </tr>
    <tr>
        <td>
            <?php echo GetMessage("PAYMENT_BY_CASH"); ?>
        </td>
        <td>
            <select name="pay_systems[]" multiple>
                <?php foreach ($arPaySystems as $key => $val): ?>
                    <option value="<?php echo $val['ID'] ?>" <?php echo (in_array($val['ID'], $pay_systems)) ? 'selected': '' ?> >
                        <?php echo $val['NAME'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Ограничение количества передаваемых заказов</td>
        <td>
            <input type="text" value="<?php echo $limit?>" name="limit"/>
        </td>
    </tr>

    <tr><td colspan="2" style="text-align: center; padding: 20px; font-weight: bold">Сопоставление статусов доставки</td>  </tr>
    <tr><td>Статус</td><td>Статус МАРШРУТ</td> </tr>
<?php foreach ( $arDeliveryStatus as $status_ID => $description): ?>
    <tr>
        <td>
            <?php echo $description; ?>
        </td>
        <td>
            <select name="delivery_status_<?php echo $status_ID ?>[]" multiple size="3">
                <?php foreach ($nSmStatus as $n): ?>
                    <option value="<?php echo $n ?>" <?php echo (in_array( $n, $delivery_statuses[$status_ID]))? 'selected' : ''?>>
                        <?php echo GetMessage('SM_STATUS_'.$n)?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
<?php endforeach; ?>

</table>
    <input type="submit" name="Update" <?if ($MOD_RIGHT<"W") echo "disabled" ?> value="<?echo GetMessage("MAIN_SAVE")?>" id="update">
    <input type="hidden" name="Update" value="Y">
    <input type="hidden" name="Restore" value="N" id="hidden_restore">
    <input type="submit" value="Восстановление агента" id="restore"/>
</form>

<script type="text/javascript">
    BX.ready(function () {
        BX.bind(BX('restore'), 'click', function () {
            BX('hidden_restore').value = 'Y';
        });

        BX.bind(BX('update'), 'click', function () {
            BX('hidden_restore').value = 'N';
        });
    });

</script>