if(document.getElementById('wc_esl_billing_terminal')){
    if(document.getElementById('wc_esl_billing_terminal').value){
        window.keyDelivery = 'terminal'
    }else if(document.getElementById('wc_esl_shipping_terminal').value){
        window.keyDelivery = 'terminal'
    }else{
        window.keyDelivery = 'door'
    }
}else{
    window.keyDelivery = 'door'
}


window.widgetInit = false
let cityMain = false
let errorCity = 0
let hashSelectService = []

function isHidden(el) {
    var style = window.getComputedStyle(el);
    return (style.display === 'none')
}

function isNumeric(value) {
    return /^-{0,1}\d+$/.test(value);
}

(function ($) {

    function shippingFieldName($this = false) {
        var shipping_methods = {};

        if ($this && $($this).val())
            return $($this).val();

        $('select.shipping_method, :input[name^=shipping_method][type=radio]:checked, :input[name^=shipping_method][type=hidden]').each(function () {
            shipping_methods[$(this).data('index')] = $(this).val();
        });

        if (shipping_methods[0])
            return shipping_methods[0];

        $(':input[name^=shipping_method]').each(function () {
            shipping_methods[$(this).data('index')] = $(this).val();
        });

        if (shipping_methods[0])
            return shipping_methods[0];
    }

    function shippingMethodIsEshopFunc(method_name) {

        if (!method_name) return false;

        return method_name.indexOf('wc_esl_') !== -1;
    }

    function shippingMethodTypeFunc(method_name) {

        let type = null;

        if (method_name) {

            if (method_name.indexOf('wc_esl_') !== -1) {

                if (method_name.indexOf('_door') !== -1) type = 'door';
                else if (method_name.indexOf('_terminal') !== -1) type = 'terminal';
                else if (method_name.indexOf('_mixed') !== -1) {
                    type = window.keyDelivery;
                } else type = null;

            }

        }

        return type;
    }

    function preload(selector, status = true) {
        let element = $(selector);

        if (status) {
            element.block({
                message: null,
                overlayCSS: {
                    background: '#fff',
                    opacity: 0.6
                }
            });
        } else {
            element.unblock();
        }
    }

    function searchCity(target, renderFunc, currentCountry, typeFilter = false) {
        $.ajax({
            method: 'POST',
            url: wc_esl_shipping_global.ajaxUrl,
            async: true,
            data: {
                action: 'wc_esl_search_cities',
                target,
                currentCountry,
                typeFilter
            },
            dataType: 'json',
            success: function (response) {

                if (response.success) {
                    renderFunc(response.data);
                }
            }
        });
    }

    function renderCitiesItem({fias, name, region, postal_code, services, type}) {
        let label = '';
        label += (type.length > 0) ? `${type} ` : '';
        label += name;
        label += (region.length > 0) ? ` - ${region}` : '';

        let html = `<li
			class="wc-esl-search-city__item"
			data-fias="${fias}"
			data-city="${name}"
			data-region="${region}"
			data-postcode="${postal_code}"
			data-services='${JSON.stringify(services)}'
		>${label}</li>`;

        $('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val('');

        return html;
    }

    function renderCitiesList(items, mode = 'billing') {
        if (items.length < 1) return '';

        let html = `<ul
			class="wc-esl-search-city__list not-selected"
			id="result_wc_esl_search_city_${mode}"
			data-mode="${mode}"
		>`;

        items.forEach(item => {
            html += renderCitiesItem(item);
        });

        html += '</ul>';

        return html;
    }

    function renderCitiesModal(items, mode = 'billing') {
        if (items.length < 1) return '';

        let html = `<ul
			class="wc-esl-search-city-modal__list"
			id="result_wc_esl_search_city_${mode}"
			data-mode="${mode}"
		>`;

        for (const [key, value] of Object.entries(items)) {
            html += '<div class="wc-esl-search-region-modal__list"><p class="title-region">'+key+'</p>';
            value.forEach(item => {
                html += renderCitiesModalItem(item);
            });
            html += '</div>';
        }

        html += '</ul>';

        return html;
    }

    function renderCitiesModalItem({fias, name, region, postal_code, services, type}) {
        let label = '';
        label += (type.length > 0) ? `${type} ` : '';
        label += name;
        label += (region.length > 0) ? ` - ${region}` : '';

        let html = `<li
			class="wc-esl-search-city-modal__item"
			data-fias="${fias}"
			data-city="${name}"
			data-region="${region}"
			data-postcode="${postal_code}"
			data-services='${JSON.stringify(services)}'
		>${label}</li>`;

        return html;
    }

    function changeVisibleElements(
        differentShippingAddress = false,
        isTerminal = true,
        billingCountry = '',
        shippingCountry = ''
    ) {
        let billingTerminals = $('#wc-esl-terminals-wrap-billing');
        let shippingTerminals = $('#wc-esl-terminals-wrap-shipping');
        let billingButton = $('#wc-esl-terminals-wrap-button-billing');
        let shippingButton = $('#wc-esl-terminals-wrap-button-shipping');
        let billingAddress1 = $('#billing_address_1_field');
        let billingAddress2 = $('#billing_address_2_field');
        let shippingAddress1 = $('#shipping_address_1_field');
        let shippingAddress2 = $('#shipping_address_2_field');
        let offAddressCheck = $('#offAddressCheck');

        let billingFieldStreet = $('#esl_billing_field_street_field');
        let billingFieldBuilding = $('#esl_billing_field_building_field');
        let billingFieldRoom = $('#esl_billing_field_room_field');

        let shippingFieldStreet = $('#esl_shipping_field_street_field');
        let shippingFieldBuilding = $('#esl_shipping_field_building_field');
        let shippingFieldRoom = $('#esl_shipping_field_room_field');

        if (isTerminal === 'terminal' && cityMain) {
            if (differentShippingAddress && (shippingCountry)) {
                if(offAddressCheck.length === 0){
                    billingAddress1.hide();
                    billingAddress2.hide();
                    shippingAddress1.hide();
                    shippingAddress2.hide();

                    billingFieldStreet.hide();
                    billingFieldBuilding.hide();
                    billingFieldRoom.hide();
                    shippingFieldStreet.hide();
                    shippingFieldBuilding.hide();
                    shippingFieldRoom.hide();
                }

                billingButton.hide();
                shippingButton.show();
                billingTerminals.hide().removeClass('show');
                shippingTerminals.show().addClass('show');
            } else if (
                !differentShippingAddress && (billingCountry)
            ) {
                if(offAddressCheck.length === 0){
                    billingAddress1.hide();
                    billingAddress2.hide();
                    shippingAddress1.hide();
                    shippingAddress2.hide();

                    billingFieldStreet.hide();
                    billingFieldBuilding.hide();
                    billingFieldRoom.hide();
                    shippingFieldStreet.hide();
                    shippingFieldBuilding.hide();
                    shippingFieldRoom.hide();
                }

                billingButton.show();
                shippingButton.hide();
                billingTerminals.show().addClass('show');
                shippingTerminals.hide().removeClass('show');
            } else {
                if(offAddressCheck.length === 0){
                    billingAddress1.show();
                    billingAddress2.show();
                    shippingAddress1.show();
                    shippingAddress2.show();

                    billingFieldStreet.show();
                    billingFieldBuilding.show();
                    billingFieldRoom.show();
                    shippingFieldStreet.show();
                    shippingFieldBuilding.show();
                    shippingFieldRoom.show();
                }

                billingButton.hide();
                shippingButton.hide();
                billingTerminals.hide().removeClass('show');
                shippingTerminals.hide().removeClass('show');
            }

        } else if (
            isTerminal === 'door' && cityMain
        ) {
            if(offAddressCheck.length === 0){
                billingAddress1.hide();
                billingAddress2.hide();
                shippingAddress1.hide();
                shippingAddress2.hide();

                billingFieldStreet.show();
                billingFieldBuilding.show();
                billingFieldRoom.show();
                shippingFieldStreet.show();
                shippingFieldBuilding.show();
                shippingFieldRoom.show();
            }

            billingButton.show();
            shippingButton.hide();
            billingTerminals.hide().removeClass('show');
            shippingTerminals.hide().removeClass('show');

            let response = [];
            response.deliveryAddress = [];
            response.deliveryAddress.address = 'Курьер до адреса';
            response.deliveryAddress.code = [];
            //esl.setTerminal(response)
        } else {
            if(offAddressCheck.length === 0){
                billingAddress1.show();
                billingAddress2.show();
                shippingAddress1.show();
                shippingAddress2.show();

                billingFieldStreet.hide();
                billingFieldBuilding.hide();
                billingFieldRoom.hide();
                shippingFieldStreet.hide();
                shippingFieldBuilding.hide();
                shippingFieldRoom.hide();
            }

            billingButton.hide();
            shippingButton.hide();
            billingTerminals.hide().removeClass('show');
            shippingTerminals.hide().removeClass('show');
        }
    }

    $(document).ready(function (e) {
        let differentShippingAddress = $('#ship-to-different-address-checkbox').is(':checked');
        let currentShippingMethod = shippingFieldName();
        let shippingMethodIsEshop = shippingMethodIsEshopFunc(currentShippingMethod);
        let typeShippingMethod = shippingMethodTypeFunc(currentShippingMethod);
        let currentBillingCountry = ($('#billing_country').val())?$('#billing_country').val():'RU';
        let currentShippingCountry = ($('#shipping_country').val())?$('#shipping_country').val():'RU';
        let addCityAdressBilling = $('#billing_address_1').val();
        let addCityAdressShipping = $('#shipping_address_1').val();
        let checkAddAdress = false;
        let searchCityVar;
        let modalSelectCity = $('#modal-esl-city').length > 0;
        let billingCityFields = $('#eslBillingCityFields').val();
        let shippingCityFields = $('#eslShippingCityFields').val();

        changeVisibleElements(
            differentShippingAddress,
            typeShippingMethod,
            currentBillingCountry,
            currentShippingCountry
        );


        if ($('#'+billingCityFields).length === 1) {
            $('#'+billingCityFields).prop("autocomplete", "nope");
            inputFocusCity(billingCityFields);
        }
        if ($('#'+shippingCityFields).length === 1 && modalSelectCity) {
            $('#'+shippingCityFields).prop("autocomplete", "nope");
            inputFocusCity(shippingCityFields);
        }
        if(modalSelectCity){
            inputStartCityModal();
        }

        function inputFocusAdress($name = 'billing_address_1') {
            let billingButton = $('#wc-esl-terminals-wrap-button-billing');
            let shippingButton = $('#wc-esl-terminals-wrap-button-shipping');
            billingButton.hide()
            shippingButton.hide()

            $('body').on('blur', '#' + $name, function (e) {

                currentShippingMethod = shippingFieldName();
                if (currentShippingMethod === 'wc_esl_dostavista_door') {
                    checkAddAdress = true;
                } else {
                    checkAddAdress = false;
                }

                if (searchCityVar) {
                    if ($name === 'billing_address_1')
                        addCityAdressBilling = $(this).val();

                    if ($name === 'shipping_address_1')
                        addCityAdressShipping = $(this).val();

                    if ($(this).val().length > 1 && checkAddAdress) {

                        if (currentBillingCountry) {
                            sendRequestCity(searchCityVar);
                        }
                    }
                }

                if (!searchCityVar && checkAddAdress) {
                    $('#'+billingCityFields).val('');
                    $('#'+shippingCityFields).val('');
                }

            });
        }

        function inputStartCityModal(){
            let inputSearch = '';
            $('body').on('keyup changed', '#esl_modal-search', function (e) {
                let value = $(this).val();

                let $this = $(this);
                let modeInput = $this.attr('data-mode');
                inputSearch = $(this);

                if (value.length > 1) {
                    if (currentBillingCountry) {
                        searchCity(value, function (items) {
                            if(Object.getOwnPropertyNames(items).length > 1) {
                                $this.closest('.modal-esl-frame').find('#esl_result-search').html(
                                    renderCitiesModal(items, modeInput)
                                );
                            }else{
                                $this.closest('.modal-esl-frame').find('#esl_result-search').html(
                                    '<button id="esl_modal_button-search">Выбрать данный населённый пункт</button>'
                                );
                            }

                        }, currentBillingCountry, 'region');
                    }
                }else{
                    $this.closest('.modal-esl-frame').find('#esl_result-search').html('');
                }
            });

            $('body').on('click', '.wc-esl-search-city-modal__item', function (e) {
                searchCityVar = this;
                sendRequestCity(this);
                let city = $(this).data('city');
                inputSearch.val(city)
                document.getElementById("modal-esl-city").style.display = "none"
            });

            $('body').on('click', '#esl_modal_button-search', function (e) {
                e.preventDefault();
                let modalSearch = $('#esl_modal-search');
                let value = modalSearch.val();
                let modeInput = modalSearch.attr('data-mode');
                $( `#${modeInput}_city` ).val( value );

                document.getElementById("modal-esl-city").style.display = "none"
                window.keyDelivery = 'door'
                changeVisibleElements(
                    differentShippingAddress,
                    false,
                    currentBillingCountry,
                    currentShippingCountry
                );
                esl.request('');
            });
        }

        function inputFocusCity($name = 'billing_city') {

            if(modalSelectCity){
                let mode = 'billing';
                if ($name === 'shipping_city')
                    mode = 'shipping';

                $('#' + $name).after("<button type='button' value='OK' class='esl_city_button' id='esl_city_"+$name+"' data-mode='"+mode+"'>" +
                    "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"16\" height=\"16\" fill=\"currentColor\" class=\"bi bi-house-fill\" viewBox=\"0 0 16 16\">\n" +
                    "<path d=\"M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z\"/>\n" +
                    "<path d=\"m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Z\"/>\n" +
                    "</svg>" +
                    "</button>");
                let modalEslCity = document.getElementById("modal-esl-city")
                let span = modalEslCity.getElementsByClassName("close_modal_window")[0]

                $('#esl_city_' + $name).on('click',function(){
                    modalEslCity.style.display = "block"
                    mode = $(this).data('mode');
                    $('#esl_modal-search').attr('data-mode', mode);
                    $('#esl_modal-search').val('');
                    $('#esl_modal-search').next('#esl_result-search').html('');
                    span.onclick = function () {
                        modalEslCity.style.display = "none"
                    }

                    window.onclick = function (event) {
                        if (event.target === modalEslCity) {
                            modalEslCity.style.display = "none"
                        }
                    }
                })
            }else{
                $('body').on('keyup changed', '#' + $name, function (e) {
                    let value = $(this).val();
                    let mode = 'billing';
                    let $this = $(this);


                    if (value.length > 2) {

                        let searchCityBox = $('#result_wc_esl_search_city_billing');
                        if(!searchCityBox.hasClass('not-selected') && searchCityBox){
                            searchCityBox.addClass('not-selected')
                            $('#tips-city-container').show();
                            $(`.wc-esl-terminals__button`).prop('disabled', true);
                            $('.wc-esl-terminals__container').hide()
                            window.keyDelivery = 'door'
                            currentShippingMethod = shippingFieldName();
                            typeShippingMethod = shippingMethodTypeFunc(currentShippingMethod)
                            changeVisibleElements(
                                differentShippingAddress,
                                typeShippingMethod,
                                currentBillingCountry,
                                currentShippingCountry
                            );
                            esl.request('');
                        }

                        if (currentBillingCountry) {
                            searchCity(value, function (items) {
                                $('#result_wc_esl_search_city_billing').remove();
                                if ($this.parents('.cfw-input-wrap-row').length === 1) {
                                    $this.parents('.cfw-input-wrap-row').append(
                                        renderCitiesList(items, mode)
                                    );
                                } else {
                                    $this.parents('.form-row').append(
                                        renderCitiesList(items, mode)
                                    );
                                }

                            }, currentBillingCountry);
                        }
                    }
                });
            }

        }

        function sendRequestCity($this) {
            let mode = '';
            if($($this).parents('.wc-esl-search-city-modal__list').length > 0){
                mode = $($this).parents('.wc-esl-search-city-modal__list').data('mode');
            }else{
                mode = $($this).parents('.wc-esl-search-city__list').data('mode');
            }

            let fias = $($this).data('fias');
            let region = $($this).data('region');
            let postcode = $($this).data('postcode');
            let services = $($this).data('services');
            let city = $($this).data('city');
            let adress = '';
            if (mode === 'billing')
                adress = addCityAdressBilling;
            if (mode === 'shipping')
                adress = addCityAdressShipping;

            preload(`.woocommerce-${mode}-fields`);

            $($this).parents('.wc-esl-search-city__list').removeClass('not-selected');
            $(`.wc-esl-terminals__button`).prop('disabled', false);
            $('.wc-esl-search-city__list').hide();
            $('#tips-city-container').hide();

            $.ajax({
                method: 'POST',
                url: wc_esl_shipping_global.ajaxUrl,
                async: true,
                data: {
                    action: 'wc_esl_update_shipping_address',
                    fias,
                    region,
                    postcode,
                    services,
                    city,
                    mode,
                    adress
                },
                dataType: 'json',
                success: function (response) {

                    if (response.success) {
                        $(`#${mode}_city`).val(city);
                        $(`#${mode}_state`).val(region);
                        $(`#${mode}_postcode`).val(postcode);

                        $(`#wc_esl_${mode}_terminal`).val('');
                        //$(`.wc-esl-terminals__button[data-mode="${mode}"]`).text("Выбрать способ доставки и пункт самовывоза");

                        preload(`.woocommerce-${mode}-fields`, false);
                        $('body').trigger('update_esl_city');
                    }

                    $('body').trigger('update_checkout');
                }
            });
        }

        $('body').on('keyup changed', '#'+shippingCityFields, function (e) {
            let value = $(this).val();
            let mode = 'shipping';
            let $this = $(this);

            if (value.length > 2) {

                let searchCityBox = $('#result_wc_esl_search_city_shipping');
                if(!searchCityBox.hasClass('not-selected') && searchCityBox){
                    searchCityBox.addClass('not-selected')
                    $('#tips-city-container').show();
                    $(`.wc-esl-terminals__button`).prop('disabled', true);
                    esl.request('');
                }

                if (currentShippingCountry) {
                    searchCity(value, function (items) {
                        $('#result_wc_esl_search_city_shipping').remove();
                        if ($this.parents('.cfw-input-wrap-row').length === 1) {
                            $this.parents('.cfw-input-wrap-row').append(
                                renderCitiesList(items, mode)
                            );
                        } else {
                            $this.parents('.form-row').append(
                                renderCitiesList(items, mode)
                            );
                        }
                    }, currentShippingCountry);
                }
            }
        });

        $('body').on('change', 'input[name="payment_method"]', function (e) {
            $('body').trigger('update_checkout');
        });

        $('body').on('updated_checkout', function () {
            currentShippingMethod = shippingFieldName();
            typeShippingMethod = shippingMethodTypeFunc(currentShippingMethod)

            changeVisibleElements(
                differentShippingAddress,
                typeShippingMethod,
                currentBillingCountry,
                currentShippingCountry
            );

        });


        $('body').on('click', '.wc-esl-search-city__item', function (e) {
            searchCityVar = this;
            sendRequestCity(this);
        });

        $('body').on('change', '#ship-to-different-address-checkbox', function (e) {
            differentShippingAddress = $(this).is(':checked');

            changeVisibleElements(
                differentShippingAddress,
                typeShippingMethod,
                currentBillingCountry,
                currentShippingCountry
            );
        });

        $('body').on(
            'change',
            'select.shipping_method, :input[name^=shipping_method]',
            function (e) {
                $('#wc_esl_billing_terminal').val('');
                $('#wc_esl_shipping_terminal').val('');
                currentShippingMethod = shippingFieldName(this);
                shippingMethodIsEshop = shippingMethodIsEshopFunc(currentShippingMethod);
                typeShippingMethod = shippingMethodTypeFunc(currentShippingMethod);

                changeVisibleElements(
                    differentShippingAddress,
                    typeShippingMethod,
                    currentBillingCountry,
                    currentShippingCountry
                );

                esl.sleep(2000).then(() => {
                    esl.run('city');
                });
            }
        );

        $('body').on('change', '#billing_country', function (e) {
            currentBillingCountry = $(this).val();
        });

        $('body').on('change', '#shipping_country', function (e) {
            currentShippingCountry = $(this).val();
        });

    });

    let esl = {
        items: {
            widget_id: 'eShopLogisticWidgetCart',
            esldata_field_id: 'widgetCityEsl',
            esldata_offers_id: 'widgetOffersEsl',
            esldata_payments_id: 'widgetPaymentEsl',
            esldata_to_id: 'widgetCityEsl',
            esl_button_id: 'container_widget_esl_button',
            esl_box: 'boxEshoplogistic',
        },
        type_delivery_default: {
            terminal: 'pickup',
            postrf: 'post',
            door: 'todoor',
        },
        current: {payment_id: null, delivery_id: null},
        widget_offers: '',
        widget_city: {name: null, fias: null, services: {}},
        widget_payment: '',
        esldata_value: {},
        request: function (action) {
            return new Promise(function (resolve, reject) {
                document.body.classList.add('load')
                document.body.classList.remove("loaded_hiding")
                setTimeout(function () {
                    sendRequestShipping(action)
                }, 1000)
            })
        },
        sleep: function (ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
        check: function () {
            let check = true

            const current_payment = document.querySelector('input[name=payment_method]:checked')

            if (!current_payment) {
                check = false
                console.log('Ошибка поиска payment_method')
            } else {
                this.current.payment_id = current_payment.value
            }
            if (!document.getElementById(this.items.esldata_payments_id)){
                check = false
                console.log('Ошибка поиска widgetPaymentEsl')
            }


            let billingCityFields = document.getElementById('eslBillingCityFields').value
            let shippingCityFields = document.getElementById('eslShippingCityFields').value
            let billing_city = document.getElementById(billingCityFields)
            let shipping_city = document.getElementById(shippingCityFields)

            if(billing_city !== null){
                if(billing_city.value){
                    cityMain = true
                    document.getElementById('tips-city-container').style.display = 'none';
                }
            }

            if(shipping_city !== null){
                if(shipping_city.value){
                    cityMain = true
                    document.getElementById('tips-city-container').style.display = 'none';
                }
            }

            if (cityMain === false){
                check = false
                document.getElementById('tips-city-container').style.display = 'block';
            }

            return check
        },
        prepare: function () {
            duplicateBoxClear()

            const payments = JSON.parse(document.getElementById(this.items.esldata_payments_id).value)
            const terminal = document.getElementById('terminalEsl')
            const to = JSON.parse(document.getElementById(this.items.esldata_to_id).value)

            this.widget_offers = document.getElementById(this.items.esldata_offers_id).value
            this.widget_city.name = to.city
            this.widget_city.fias = to.fias
            this.widget_city.services = to.services
            this.widget_payment = (this.current.payment_id) ? this.current.payment_id : 'card'

            let current_payment = this.current.payment_id
            if (current_payment) {
                for (const [key, value] of Object.entries(payments)) {
                    if (key.indexOf(current_payment) != -1) {
                        this.widget_payment = value
                    }
                }
            }

            if (isNumeric(this.widget_payment))
                this.widget_payment = 'card'

        },
        run: async function (reload = '') {
            let currentShippingMethod = shippingFieldName();
            let shippingMethodIsEshop = shippingMethodIsEshopFunc(currentShippingMethod);
            if (!this.check() || !shippingMethodIsEshop) {
                console.log('ESL: Ошибка проверки элементов')
                document.body.classList.add('loaded_hiding')
                document.body.classList.remove("load")
                changeVisibleElements()
                return false
            }
            const widget = document.getElementById(this.items.widget_id)
            this.prepare()

            let settlement = this.widget_city

            let params = {
                offers: this.widget_offers,
                payment: this.widget_payment
            }

            if (reload.length !== 0 && window.widgetInit) {
                switch (reload) {
                    case 'offers':
                        let offers = await this.request('cart=1')
                        params = {
                            offers: JSON.stringify(offers)
                        }
                        break
                    case 'payment':
                        params = {
                            offers: this.widget_offers,
                            payment: this.widget_payment
                        }
                        break
                    case 'city':
                        settlement = this.widget_city

                }
                console.log('reload')
                document.body.classList.add('load')
                document.body.classList.remove("loaded_hiding")
                widget.dispatchEvent(new CustomEvent('eShopLogisticWidgetCart:updateParamsRequest', {
                    detail: {
                        settlement: settlement,
                        requestParams: params
                    }
                }))
            } else {
                console.log('load')
                widget.addEventListener('eShopLogisticWidgetCart:onLoadApp', (event) => {
                    document.body.classList.add('load')
                    document.body.classList.remove("loaded_hiding")
                    widget.dispatchEvent(new CustomEvent('eShopLogisticWidgetCart:updateParamsRequest', {
                        detail: {
                            settlement: settlement,
                            requestParams: params
                        }
                    }))
                })
                window.widgetInit = true
            }

        },
        confirm: async function (response) {
            let esldata = {
                price: 0,
                time: '',
                name: response.service.name,
                key: response.service.code,
                mode: response.typeDelivery,
                address: '',
                comment: '',
                deliveryMethods: '',
                selectPvz: ''
            }

            if (document.getElementById('terminalEsl') && document.getElementById('terminalEsl').value) {
                esldata.selectPvz = document.getElementById('terminalEsl').value
            }

            if (response.service.comment) {
                esldata.comment = response.service.comment
            }

            if (response.deliveryMethods) {
                esldata.deliveryMethods = response.deliveryMethods
            }

            let time = response.service.responseData[response.typeDelivery].time

            esldata.price = response.service.responseData[response.typeDelivery].price
            esldata.time = time.value + ' ' + time.unit
            if (response.service.responseData[response.typeDelivery].comment) {
                esldata.comment += '<br>' + response.service.responseData[response.typeDelivery].comment
            }

            if (typeof response.terminal == 'object') {
                esldata.address = response.terminal.code + ' ' + response.terminal.address
            }

            await this.request(JSON.stringify(esldata))

        },
        setTerminal: function (response, hide = true) {
            _self = this

            let address = response
            const request = new XMLHttpRequest()
            request.open('POST', wc_esl_shipping_global.ajaxUrl, true)
            request.responseType = 'json'
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
            request.send(`action=wc_esl_set_terminal_address&terminal=${address.address}&terminal_code=${address.code}`)

            request.addEventListener("readystatechange", () => {

                if (request.readyState === 4 && request.status === 200) {
                    jQuery('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val(address.address);
                    //jQuery(".wc-esl-terminals__button").text("Выбрать способ доставки и пункт самовывоза");
                    if(hide)
                        modalEsl.style.display = "none";
                }
            })
        },
        error: function (response) {
            console.log('Ошибка виджета, включен дефолтный режим доставки', response)
        },
    }

    function eslRun() {
        esl.run()
    }

    window.addEventListener('load', function (event) {
        eslRun()
    });

    document.addEventListener('DOMContentLoaded', () => {
        const root = document.getElementById('eShopLogisticWidgetCart');

        root.addEventListener('eShopLogisticWidgetCart:onLoadApp', (event) => {
            const widget = document.getElementById(esl.items.widget_id)

            let settlement = esl.items.widget_city

            let params = {
                offers: esl.items.widget_offers,
                payment: esl.items.widget_payment
            }


            widget.dispatchEvent(new CustomEvent('eShopLogisticWidgetCart:updateParamsRequest', {
                detail: {
                    settlement: settlement,
                    requestParams: params
                }
            }))
            window.widgetInit = true
        });

        root.addEventListener('eShopLogisticWidgetCart:onBalloonOpen', (event) => {
            let data = event.detail
            console.log('Событие onBalloonOpen', data)
            let hash = objectHash.sha1(data);
            hashSelectService = hash

            if (typeof data.terminal == 'object') {
                esl.setTerminal(data.terminal, false)
            } else {
                jQuery('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val('');
                window.keyDelivery = data.typeDelivery
                let differentShippingAddress = jQuery('#ship-to-different-address-checkbox').is(':checked');
                let currentBillingCountry = (jQuery('#billing_country').val())?jQuery('#billing_country').val():'RU';
                let currentShippingCountry = (jQuery('#shipping_country').val())?jQuery('#shipping_country').val():'RU';
                if (window.keyDelivery === 'door') {
                    jQuery('#buttonModalDoor').show();
                } else {
                    jQuery('#buttonModalDoor').hide();
                }

                changeVisibleElements(
                    differentShippingAddress,
                    window.keyDelivery,
                    currentBillingCountry,
                    currentShippingCountry
                );

            }
        })

        root.addEventListener('eShopLogisticWidgetCart:onSelectedService', (event) => {
            let data = event.detail
            console.log('Событие onSelectedService', data)
            let hash = objectHash.sha1(event.detail);
            console.log(hash)
            console.log(hashSelectService)

            if (typeof data.terminal == 'object') {
                esl.setTerminal(data.terminal)
            } else {
                jQuery('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val('');
                window.keyDelivery = data.typeDelivery
                let differentShippingAddress = jQuery('#ship-to-different-address-checkbox').is(':checked');
                let currentBillingCountry = (jQuery('#billing_country').val())?jQuery('#billing_country').val():'RU';
                let currentShippingCountry = (jQuery('#shipping_country').val())?jQuery('#shipping_country').val():'RU';
                if (window.keyDelivery === 'door') {
                    jQuery('#buttonModalDoor').show();
                } else {
                    jQuery('#buttonModalDoor').hide();
                }

                changeVisibleElements(
                    differentShippingAddress,
                    window.keyDelivery,
                    currentBillingCountry,
                    currentShippingCountry
                );
            }

            if(hash !== hashSelectService){
            }
            esl.confirm(data)

        })

        root.addEventListener('eShopLogisticWidgetCart:onAllServicesLoaded', (event) => {
            console.log('Событие onAllServicesLoaded', event.detail)
            let count = 0
            let countText = 'служб'
            count = event.detail.length

            if (count === 1)
                countText = 'служба';
            if (count > 1 && count < 5)
                countText = 'службы'

            let nameDelivery = ''
            for (let i = 0; i < count; i++) {
                if(i !== 0)
                    nameDelivery += ', '

                nameDelivery += event.detail[i].name
            }

            jQuery('.esl_desct_delivery .count').html(count)
            jQuery('.esl_desct_delivery .countText').html(countText)
            jQuery('.esl_desct_delivery .addText').html(nameDelivery)
            jQuery('.esl_desct_delivery').show()

            document.body.classList.add('loaded_hiding')
            document.body.classList.remove("load")

            let differentShippingAddress = jQuery('#ship-to-different-address-checkbox').is(':checked');
            let currentBillingCountry = (jQuery('#billing_country').val())?jQuery('#billing_country').val():'RU';
            let currentShippingCountry = (jQuery('#shipping_country').val())?jQuery('#shipping_country').val():'RU';
            if (window.keyDelivery === 'door') {
                jQuery('#buttonModalDoor').show();
            } else {
                jQuery('#buttonModalDoor').hide();
            }

            changeVisibleElements(
                differentShippingAddress,
                window.keyDelivery,
                currentBillingCountry,
                currentShippingCountry
            );
        })

        root.addEventListener('eShopLogisticWidgetCart:onSelectTypeDelivery', (event) => {
            console.log('Событие onSelectTypeDelivery', event.detail)
        })

        root.addEventListener('eShopLogisticWidgetCart:onInvalidSettlementCode', () => {
            console.log('Неверный код населенного пункта')

            errorCity = errorCity + 1
            if(errorCity < 2){
                esl.sleep(2000).then(() => {
                    esl.run('city')
                });
            }

            errorGetMessage()
        })

        root.addEventListener('eShopLogisticWidgetCart:onInvalidName', () => {
            console.log('Неверный name города')

            errorGetMessage('Неверное название города')
        })

        root.addEventListener('eShopLogisticWidgetCart:onInvalidServices', () => {
            console.log('Неверный массив служб')
            errorGetMessage('Невозможна доставка по указанному адресу')
        })

        root.addEventListener('eShopLogisticWidgetCart:onInvalidPayment', () => {
            console.log('Не передана оплата')

            errorGetMessage('Не найден способ оплаты')
        })

        root.addEventListener('eShopLogisticWidgetCart:onInvalidOffers', () => {
            console.log('Не передан offers')

            errorGetMessage('Не найден заказ')
        })

        root.addEventListener('eShopLogisticWidgetCart:onNotAvailableServices', (event) => {
            console.log('Событие onNotAvailableServices', event)

            jQuery('.esl_desct_delivery .addText').html('Нет доступных вариантов доставки.')
            jQuery('.esl_desct_delivery .count').html('0')

            document.body.classList.add('loaded_hiding')
            document.body.classList.remove("load")

            let differentShippingAddress = jQuery('#ship-to-different-address-checkbox').is(':checked');
            let currentBillingCountry = (jQuery('#billing_country').val())?jQuery('#billing_country').val():'RU';
            let currentShippingCountry = (jQuery('#shipping_country').val())?jQuery('#shipping_country').val():'RU';
            if (window.keyDelivery === 'door') {
                jQuery('#buttonModalDoor').show();
            } else {
                jQuery('#buttonModalDoor').hide();
            }

            changeVisibleElements(
                differentShippingAddress,
                window.keyDelivery,
                currentBillingCountry,
                currentShippingCountry
            );
        })
    })

    function errorGetMessage(error){

        document.body.classList.add('loaded_hiding')
        document.body.classList.remove("load")

        jQuery('.wc-esl-terminals__container').hide()
        $('#wc-esl-terminals-wrap-billing, #wc-esl-terminals-wrap-shipping').hide().removeClass('show');
        if(error)
            $('#tips-city-container').html(error).show();
    }


    function sendRequestShipping(action) {
        jQuery.ajax({
            method: 'POST',
            url: wc_esl_shipping_global.ajaxUrl,
            async: true,
            data: {
                action: 'wc_esl_update_shipping',
                data: action,
                city: esl.widget_city.name
            },
            dataType: 'json',
            success: function (response) {
                jQuery('body').trigger('update_checkout')
                document.body.classList.add('loaded_hiding')
                document.body.classList.remove("load")
            }
        });
    }


    let modalEsl = document.getElementById("modal-esl-frame")
    let span = document.getElementsByClassName("close_modal_window")[0]
    let modalDoorButton = document.getElementById("buttonModalDoor")


    span.onclick = function () {
        modalEsl.style.display = "none"
    }

    modalDoorButton.onclick = function () {
        modalEsl.style.display = "none"
    }

    window.onclick = function (event) {
        if (event.target == modalEsl) {
            modalEsl.style.display = "none"
        }
    }

    let bindEvents = {
        clickOnTerminals: function (event) {
            modalEsl.style.display = "block"
        },
        onCloseModal: function () {
            console.log('closeModal')
        },
    }

    function duplicateBoxClear() {
        let box = document.querySelectorAll('.' + esl.items.esl_box);
        let last = box[box.length - 1];

        for (let i = 0; i < box.length; i++) {
            if (box[i] !== last) {
                box[i].parentNode.removeChild(box[i]);
            }
        }
    }


    let els_terminals_buttons = document.getElementsByClassName('wc-esl-terminals__button')

    if (els_terminals_buttons) {

        for (let i = 0; i < els_terminals_buttons.length; i++) {
            els_terminals_buttons[i].addEventListener('click', bindEvents.clickOnTerminals, false);
        }

    }

    jQuery('body').on('update_esl_city', function () {
        jQuery('body').on('updated_checkout', function () {
            document.body.classList.add('load')
            document.body.classList.remove("loaded_hiding")
            esl.run('city')
            jQuery('body').off('updated_checkout');
        });
    });

    if(document.getElementById('paymentCalc')){

        jQuery('body').on('change', 'input[name="payment_method"]', function (e) {

            jQuery('body').on('updated_checkout', function () {
                esl.run('payment')
                jQuery('body').off('updated_checkout');
                document.getElementById('wc_esl_billing_terminal').value = '';
                document.getElementById('wc_esl_shipping_terminal').value = '';
                jQuery('.esl_desct_delivery').append('<p>Информация о доставке была обновлена, пожалуйста, выберите подходящий вариант доставки.</p>')
            });

        });
    }


}((jQuery)));
