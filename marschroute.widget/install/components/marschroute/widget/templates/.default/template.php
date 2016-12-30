<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
?>
<script type="text/javascript" src="https://marschroute.ru/widgets/delivery/js/widget.js"></script>
<div id="routewidget_window" class="routewidget_window" style="
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 999999;
    display: none;
">
    <div style="
        width: 1100px;
        margin: auto ">
        <div id="routewidget_window_close" class="routewidget_window_close">&times;</div>
        <div id="routewidget" style="
                     width: 1000px;
                     height: 600px;
                     min-width: 750px;
                     min-height: 602px;
                     margin-top: 30px;
                     "></div>
    </div>
</div>
<script type="text/javascript">
    if (!window.BX && top.BX) {
        window.BX = top.BX;
    }
    if ( BX.Sale.OrderAjaxComponent.result ) {
        new BX.Marschroute.widget(<?=CUtil::PhpToJSObject($arResult['WIDGET_INIT'])?>);
    }
</script>