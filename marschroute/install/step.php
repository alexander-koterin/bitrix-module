<?php
if(!check_bitrix_sessid()) return;

IncludeModuleLangFile(__FILE__);

if ( !IsModuleInstalled("sale") ) {
    echo CAdminMessage::ShowMessage(GetMessage("SALE_MODULE_NOT_INSTALLED"));
}
elseif (!class_exists('\Bitrix\Sale\DeliveryService') && !class_exists('\Bitrix\Sale\Delivery\Services\Table')) {
    echo CAdminMessage::ShowMessage("Class 'DeliveryService' or 'Delivery\Services\Table' not found");
}
else {

    // Собираем данные
    // Список сайтов
    $arSites = array();
    $resSitesList = CSite::GetList();
    while ( $arSite = $resSitesList->Fetch() ) {
        $arSites[$arSite['LID']] = $arSite;
    }

    // Список плательщиков
    CModule::IncludeModule('sale');
    $arPersonTypes = array();
    $resPersonTypesList = CSalePersonType::GetList();
    while ( $arPersonType = $resPersonTypesList->Fetch() ) {
        $arPersonTypes[$arPersonType['ID']] = $arPersonType;
    }

    // Разобьём плательщиков по сайтам
    $arPersonsByLids = array();
    foreach ( $arPersonTypes as $arPersonType ) {
        foreach ( $arPersonType['LIDS'] as $LID ) {
            $arPersonsByLids[$LID][] = $arPersonType['ID'];
        }
    }
?>
    <form action="<?= $APPLICATION->GetCurPage()?>" name="marschroute_install">
	<?=bitrix_sessid_post()?>
    <input type="hidden" name="lang" value="<?= LANG ?>">
    <input type="hidden" name="id" value="marschroute">
    <input type="hidden" name="install" value="Y">
    <input type="hidden" name="step" value="2">
    <table class="filter-form" cellpadding="3" cellspacing="0" border="0" width="0%">
        <tr>
            <td colspan="2">
                <label for="INSTALL_ORDER_PROPERTIES_PERSONS"><?=GetMessage("INSTALL_ORDER_PROPERTIES_PERSONS")?></label>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <select name="INSTALL_ORDER_PROPERTIES_PERSONS[]" id="INSTALL_ORDER_PROPERTIES_PERSONS" multiple="multiple" size="10">
                <?php foreach ( $arPersonsByLids as $LID => $arPersonID ) : ?>
                    <optgroup label="<?=$arSites[$LID]['NAME']?>">
                        <?php foreach ($arPersonID as $personID) : ?>
                        <option value="<?=$LID?>_<?=$personID?>"><?=$arPersonTypes[$personID]['NAME']?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <input type="checkbox" name="INSTALL_MARSCHROUTE_LOCATION" id="INSTALL_MARSCHROUTE_LOCATION" value="Y" checked />
            </td>
            <td>
                <label for="INSTALL_MARSCHROUTE_LOCATION"><?=GetMessage("INSTALL_MARSCHROUTE_LOCATION")?></label>
            </td>
        </tr>

        <tr><td colspan="2"><input type="submit" name="inst" value="<?= GetMessage("MOD_INSTALL")?>"></td></tr>
    </table>
    </form>
    <?
}