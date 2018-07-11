<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("marschroute"))
{
    ShowError("MARSCHROUTE NOT INSTALLED");
    return;
}

if (class_exists('\Bitrix\Sale\DeliveryService' ))
{
    $resDeliveries = \Bitrix\Sale\DeliveryService::getList(array());
}
elseif (class_exists( '\Bitrix\Sale\Delivery\Services\Table' ))
{
    $resDeliveries = \Bitrix\Sale\Delivery\Services\Table::getList(array());
}
else
{
    ShowError("Class 'DeliveryService' or 'Delivery\Services\Table' not found");
    return;
}

$arResult = array();

$fUser = CSaleBasket::GetBasketUserID();//CSaleUser::getFUserCode();
// Получаем стоимость и вес заказа (корзины)
$basket = \Bitrix\Sale\Basket::loadItemsForFUser($fUser, SITE_ID);
$arResult['ORDER_PRICE'] = $basket->getPrice(); // получаем из корзины цену с учетом скидок
$arResult['ORDER_WEIGHT'] = ($basket->getWeight() > 0) ? $basket->getWeight() : 1000; // получаем вес, иначе 1000 гр

// Настройки способа доставки Marschroute
$deliveryObj = null;
$arDeliveryList = \Bitrix\Sale\Delivery\Services\Manager::getActiveList();
foreach ( $arDeliveryList as $arDelivery ) {
    if ( '\Sale\Handlers\Delivery\MarschrouteHandler' == $arDelivery['CLASS_NAME'] ) {
        $deliveryObj = \Bitrix\Sale\Delivery\Services\Manager::createObject($arDelivery);
    }
}

if ( $deliveryObj ) {
    $arConfig = $deliveryObj->getConfig();
    $arResult['PUBLIC_KEY'] = $arConfig['MAIN']['ITEMS']['PUBLIC_KEY']['VALUE'];
}

// Проверим наличие местоположения для Маршрута
$defaultLocation = 'marschroute';
$defaultLocationDB = Bitrix\Sale\Location\LocationTable::getByCode($defaultLocation);
$defaultLocation = ($defaultLocationDB->getSelectedRowsCount() == 1) ? $defaultLocation : '';


const MARSCHROUTE_HANDLER_CLASSNAME = '\Sale\Handlers\Delivery\MarschrouteHandler';


$arDeliveries = array();
while ( $arDelivery = $resDeliveries->fetch() ) {
    $arDelivery = \Bitrix\Sale\Delivery\Services\Manager::getById($arDelivery['ID']);
    if ( $arDelivery['CLASS_NAME'] == MARSCHROUTE_HANDLER_CLASSNAME ) {
        $arDeliveries[] = $arDelivery;
    }
}

$arResult['WIDGET_INIT'] = array(
    'DEFAULT_LOCATION' => $defaultLocation,
    'DELIVERY_ID' => $arDeliveries[0]['ID'],
    'PUBLIC_KEY' => $arDeliveries[0]['CONFIG']['MAIN']['PUBLIC_KEY']
);

$this->IncludeComponentTemplate();