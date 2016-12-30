if ( !BX.Marschroute ) {
    BX.Marschroute = {};
}

if(typeof BX.Marschroute.widget == 'undefined'){
    BX.Marschroute.widget = function (params) {
        this.params = params || {};
        var self = this;
        var objSearch = null;
        var formData = BX.Sale.OrderAjaxComponent.getAllFormData();

        var openWidgetButtonListener = function (e) {
            e.preventDefault();
            self.onDeliveryChange(true);
        };

        this.hideMarschrouteFields = function () {
            var it = BX.Sale.OrderAjaxComponent.propertyCollection.getIterator();
            while ( prop = it() ) {
                if ( 5 == prop.getGroupId() ) {
                    // Ищем родительскую ноду
                    var parentNode = BX.findParent(prop.getParentNode(), function (node) {
                        var data = node.dataset;
                        return (data.propertyIdRow == prop.getId());
                    });
                    // Если нашли поле - прячем
                    if ( parentNode ) {
                        BX.hide(parentNode);
                    }
                }
            }
        };

        this.onDeliveryChange = function (force) {
            // Не Маршрут, ничего делать не надо
            if ( self.params.DELIVERY_ID != formData.DELIVERY_ID && !force ) {
                return;
            }

            // Спрячем поля Маршрута
            // Делаем каждый раз, потому что при смене типа доставки поля удаляются и добавляются
            self.hideMarschrouteFields();

            // Покажем окно выбора доставки
            var routewidgetWindow = document.getElementById('routewidget_window');
            BX.show(routewidgetWindow);

            // Собираем информацию о заказе
            var total = BX.Sale.OrderAjaxComponent.result.TOTAL;
            var widgetOptions = {
                weight: (total.ORDER_WEIGHT) ? parseInt(total.ORDER_WEIGHT) : 1000,
                sum: (total.ORDER_PRICE) ? parseFloat(total.ORDER_PRICE) : 1,
                size: [100, 100, 100]
            };

            // Показываем диалог выбора доставки
            marschrouteWidget.open(widgetOptions);
        };

        this.updateDeliveryData = function (delivery) {
            console.log(delivery);
            // Заполним скрытые поля
            var fillers = {
                'MARSCHROUTE_PLACE_ID': delivery.place_id,
                'MARSCHROUTE_DELIVERY_COST': delivery.delivery_cost,
                'ADDRESS': delivery.address ? delivery.address : '',
                'MARSCHROUTE_DELIVERY_KLDR': delivery.city_id,
                'COMMENT': delivery.comment_user ? delivery.comment_user : ''
            };

            var it = BX.Sale.OrderAjaxComponent.propertyCollection.getIterator();
            while ( prop = it() ) {
                var settings = prop.getSettings();
                if ( 5 == prop.getGroupId() ) {
                    // Устанавливаем свойства для Маршрута
                    if ( fillers[settings.CODE] ) {
                        prop.setValue(fillers[settings.CODE]);
                    }
                } else {
                    if ( fillers[settings.CODE]  ) { // && !prop.getValue()
                        prop.setValue(fillers[settings.CODE]);
                    }
                }
            }
            /*
            var orderDescription = document.getElementById('orderDescription');
            console.log(orderDescription);
            console.log(orderDescription.value);
            console.log(fillers);
            if ( orderDescription && orderDescription.value == '' ) {
                console.log('set comment');
                orderDescription.value = fillers['COMMENT'];
            }
            */
            // Обновим данные заказа
            BX.Sale.OrderAjaxComponent.sendRequest();
        }

        // Будем отслеживать событие успешной отправки AJAX запроса
        // По нему узнаем, что какие-то данные заказа поменялись
        BX.addCustomEvent('ONAJAXSUCCESS', function (e) {
            // Это был не заказ
            if ( e.error || !e.order ) {
                return;
            }
            self.hideMarschrouteFields();

            var newFormData = BX.Sale.OrderAjaxComponent.getAllFormData();
            // Если доставка стала Маршрут и есть PLACE_ID, то установим специальное местоположение
            if ( newFormData.DELIVERY_ID == self.params.DELIVERY_ID ) {
                if ( self.params.DEFAULT_LOCATION && objSearch && objSearch.getValue() !== self.params.DEFAULT_LOCATION ) {
                    objSearch.setValueByLocationCode(self.params.DEFAULT_LOCATION, true);
                }
            }

            // Если доставку не меняли, то не наше дело
            if ( newFormData.DELIVERY_ID != formData.DELIVERY_ID ) {
                formData = newFormData;
                self.onDeliveryChange();
            }
        });

        // Поймаем стандартный BX event инициализации selector'а поиска
        // Потому что в window.BX.locationSelectors его может и не быть
        BX.addCustomEvent('BX-UI-SLS-INIT', function (search) {
            if ( search instanceof BX.Sale.component.location.selector.search ) {
                objSearch = search;
            }
        });

        // Инициализация виджета
        window.marschrouteWidget = window.marschrouteWidget || new Widget({
            public_key: self.params.PUBLIC_KEY,
            target_id: 'routewidget',

            // Обработка <Подтвердить выбор доставки>
            onSubmit: function (delivery, widget) {
                // скрыть окно
                var routewidgetWindow = document.getElementById('routewidget_window');
                BX.hide(routewidgetWindow);
                self.updateDeliveryData(delivery);
            }
        });

        // Обработчик кастомной кнопки закрытия виджета
        window.addEventListener('load', function () {
            // Закрыть диалог виджета
            document.getElementById('routewidget_window_close').addEventListener('click', function () {
                BX.hide(document.getElementById('routewidget_window'));
            });

            // Повторное открытие виджета
            // Битрикс обращается с подгружаемым DOM like-a-boss, поэтому такое сложное отслеживание клика.
            BX.Sale.OrderAjaxComponent.orderBlockNode.addEventListener('click', function ( e ) {
                if ( 'routewidget_window_open' == e.target.id ) {
                    openWidgetButtonListener(e);
                }
            });

        });

        // Инициализация при первой загрузке формы (если Маршрут выбран доставкой по-умолчанию)
        if ( self.params.DELIVERY_ID == formData.DELIVERY_ID ) {
            this.onDeliveryChange(true);
        }
    }
}