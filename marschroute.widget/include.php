<?php
IncludeModuleLangFile(__FILE__);
//AddEventHandler('sale', 'OnSaleComponentOrderOneStepDelivery', array('\mrsData', 'd'));
\Bitrix\Main\EventManager::getInstance()->addEventHandler('sale', 'OnSaleComponentOrderOneStepProcess', array('\mrsData', 'd'));

class mrsData
{
    public static function d($arResult, $arUserResult, $arParams)
    {
        //var_dump($arResult);
    }
}