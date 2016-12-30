<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
    "NAME" => "Виджет расчёта доставки",
    "DESCRIPTION" => "",
    "SORT" => 10,
    "PATH" => array(
        "ID" => "e-store",
        "CHILD" => array(
            "ID" => "marschroute_widget",
            "NAME" => "Marschroute widget"
        )
    ),
);