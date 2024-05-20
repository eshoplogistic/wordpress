window.keyDelivery = 'terminal'
window.widgetInit = false
let cityMain = false

function isHidden(el) {
    var style = window.getComputedStyle(el);
    return (style.display === 'none')
}

function isNumeric(value) {
    return /^-{0,1}\d+$/.test(value);
}

(function ($) {

    function shippingFieldName( $this = false ){
        var shipping_methods = {};

        if($this && $( $this ).val())
            return $( $this ).val();

        $( 'select.shipping_method, :input[name^=shipping_method][type=radio]:checked, :input[name^=shipping_method][type=hidden]' ).each( function() {
            shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
        } );

        if(shipping_methods[0])
            return shipping_methods[0];

        $( ':input[name^=shipping_method]' ).each( function() {
            shipping_methods[ $( this ).data( 'index' ) ] = $( this ).val();
        } );

        if(shipping_methods[0])
            return shipping_methods[0];
    }

    function shippingMethodIsEshopFunc( method_name ) {

        if( !method_name ) return false;

        return method_name.indexOf( 'wc_esl_' ) !== -1;
    }

    function shippingMethodTypeFunc( method_name ) {

        let type = null;

        if( method_name ) {

            if( method_name.indexOf( 'wc_esl_' ) !== -1 ) {

                if( method_name.indexOf( '_door' ) !== -1 ) type = 'door';
                else if( method_name.indexOf( '_terminal' ) !== -1 ) type = 'terminal';
                else if( method_name.indexOf( '_mixed' ) !== -1 ){
                    type = window.keyDelivery;
                }
                else type = null;

            }

        }

        return type;
    }

    function preload( selector, status = true ) {
        let element = $( selector );

        if( status ) {
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

    function searchCity( target, renderFunc, currentCountry ) {
        $.ajax({
            method: 'POST',
            url: wc_esl_shipping_global.ajaxUrl,
            async: true,
            data: {
                action : 'wc_esl_search_cities',
                target,
                currentCountry
            },
            dataType: 'json',
            success: function( response ) {

                if( response.success ) {
                    renderFunc( response.data );
                }
            }
        });
    }

    function renderCitiesItem( { fias, name, region, postal_code, services, type } ) {
        let label = '';
        label += ( type.length > 0 ) ? `${type} ` : '';
        label += name;
        label += ( region.length > 0 ) ? ` - ${region}` : '';

        let html = `<li
			class="wc-esl-search-city__item"
			data-fias="${fias}"
			data-city="${name}"
			data-region="${region}"
			data-postcode="${postal_code}"
			data-services='${JSON.stringify( services )}'
		>${label}</li>`;

        $( '#tips-city-container' ).show();

        return html;
    }

    function renderCitiesList( items, mode = 'billing' ) {
        if( items.length < 1 ) return '';

        let html = `<ul
			class="wc-esl-search-city__list"
			id="result_wc_esl_search_city_${mode}"
			data-mode="${mode}"
		>`;

        items.forEach( item => {
            html += renderCitiesItem( item );
        });

        html += '</ul>';

        return html;
    }

    function changeVisibleElements(
        differentShippingAddress = false,
        isTerminal = true,
        billingCountry = '',
        shippingCountry = ''
    ) {
        let billingTerminals 		= $( '#wc-esl-terminals-wrap-billing' );
        let shippingTerminals 		= $( '#wc-esl-terminals-wrap-shipping' );
        let billingButton 		    = $( '#wc-esl-terminals-wrap-button-billing' );
        let shippingButton 		    = $( '#wc-esl-terminals-wrap-button-shipping' );
        let billingAddress1 		= $( '#billing_address_1_field' );
        let billingAddress2 		= $( '#billing_address_2_field' );
        let shippingAddress1 		= $( '#shipping_address_1_field' );
        let shippingAddress2 		= $( '#shipping_address_2_field' );


        if( isTerminal === 'terminal' && cityMain) {
            if( differentShippingAddress && ( shippingCountry ) ) {
                billingTerminals.hide().removeClass('show');
                shippingTerminals.show().addClass('show');
                billingButton.hide();
                shippingButton.show();

                billingAddress1.hide();
                billingAddress2.hide();
                shippingAddress1.hide();
                shippingAddress2.hide();
            }else if(
                !differentShippingAddress && ( billingCountry )
            ) {
                billingTerminals.show().addClass('show');
                shippingTerminals.hide().removeClass('show');;
                billingButton.show();
                shippingButton.hide();

                billingAddress1.hide();
                billingAddress2.hide();
                shippingAddress1.hide();
                shippingAddress2.hide();
            } else {

                billingTerminals.hide().removeClass('show');
                shippingTerminals.hide().removeClass('show');
                billingButton.hide();
                shippingButton.hide();

                billingAddress1.show();
                billingAddress2.show();
                shippingAddress1.show();
                shippingAddress2.show();
            }


        }else if(
            isTerminal === 'door' && cityMain
        ){
            billingTerminals.hide().removeClass('show');
            shippingTerminals.hide().removeClass('show');
            billingButton.show();
            shippingButton.hide();

            billingAddress1.show();
            billingAddress2.show();
            shippingAddress1.show();
            shippingAddress2.show();

            let response = [];
            response.deliveryAddress = [];
            response.deliveryAddress.address = 'Курьер до адреса';
            response.deliveryAddress.code = [];
            //esl.setTerminal(response)
        }else {
            billingTerminals.hide().removeClass('show');
            shippingTerminals.hide().removeClass('show');
            billingButton.hide();
            shippingButton.hide();

            billingAddress1.show();
            billingAddress2.show();
            shippingAddress1.show();
            shippingAddress2.show();
        }
    }

    $( document ).ready( function( e ) {
        let differentShippingAddress 	= $( '#ship-to-different-address-checkbox' ).is( ':checked' );
        let currentShippingMethod 		= shippingFieldName();
        let shippingMethodIsEshop 		= shippingMethodIsEshopFunc( currentShippingMethod );
        let typeShippingMethod 			= shippingMethodTypeFunc( currentShippingMethod );
        let currentBillingCountry 		= $( '#billing_country' ).val();
        let currentShippingCountry 		= $( '#shipping_country' ).val();
        let addCityAdressBilling 		= $( '#billing_address_1' ).val();
        let addCityAdressShipping		= $( '#shipping_address_1' ).val();
        let checkAddAdress 				= false;
        let searchCityVar;

        changeVisibleElements(
            differentShippingAddress,
            typeShippingMethod,
            currentBillingCountry,
            currentShippingCountry
        );


        if($('#billing_city').length === 1){
            $('#billing_city').prop("autocomplete", "nope");
            inputFocusCity('billing_city');
        }else if($('#billing_state').length === 1){
            $('#billing_state').prop("autocomplete", "nope");
            inputFocusCity('billing_state');
        }else if($('#billing_address_1').length === 1){
            inputFocusAdress('billing_address_1');
        }else if($('#shipping_address_1').length === 1){
            inputFocusAdress('shipping_address_1');
        }


        function inputFocusAdress($name = 'billing_address_1'){
            let billingButton 		    = $( '#wc-esl-terminals-wrap-button-billing' );
            let shippingButton 		    = $( '#wc-esl-terminals-wrap-button-shipping' );
            billingButton.hide()
            shippingButton.hide()

            $( 'body' ).on( 'blur', '#'+$name, function( e ) {

                currentShippingMethod = shippingFieldName();
                if(currentShippingMethod === 'wc_esl_dostavista_door'){
                    checkAddAdress = true;
                }else{
                    checkAddAdress = false;
                }

                if(searchCityVar){
                    if($name === 'billing_address_1')
                        addCityAdressBilling = $( this ).val();

                    if($name === 'shipping_address_1')
                        addCityAdressShipping = $( this ).val();

                    if( $( this ).val().length > 1  && checkAddAdress) {

                        if( currentBillingCountry ) {
                            sendRequestCity(searchCityVar);
                        }
                    }
                }

                if(!searchCityVar && checkAddAdress){
                    $('#billing_city').val('');
                    $('#shipping_city').val('');
                }

            });
        }


        function inputFocusCity($name = 'billing_city'){

            $( 'body' ).on( 'keyup focus', '#'+$name, function( e ) {
                let value = $( this ).val();
                let mode = 'billing';
                let $this = $( this );

                if( value.length > 2 ) {

                    if( currentBillingCountry ) {
                        searchCity( value, function( items ) {
                            $('#result_wc_esl_search_city_billing').remove();
                            if($this.parents( '.cfw-input-wrap-row' ).length === 1){
                                $this.parents( '.cfw-input-wrap-row' ).append(
                                    renderCitiesList( items, mode )
                                );
                            }else{
                                $this.parents( '.form-row' ).append(
                                    renderCitiesList( items, mode )
                                );
                            }

                        }, currentBillingCountry );
                    }
                }
            });
        }

        function sendRequestCity($this){
            let mode = $( $this ).parents( '.wc-esl-search-city__list' ).data( 'mode' );

            let fias = $( $this ).data( 'fias' );
            let region = $( $this ).data( 'region' );
            let postcode = $( $this ).data( 'postcode' );
            let services = $( $this ).data( 'services' );
            let city = $( $this ).data( 'city' );
            let adress  = '';
            if(mode === 'billing')
                adress = addCityAdressBilling;
            if(mode === 'shipping')
                adress = addCityAdressShipping;

            preload( `.woocommerce-${mode}-fields` );

            $( '.wc-esl-search-city__list' ).hide();
            $( '#tips-city-container' ).hide();

            $.ajax({
                method: 'POST',
                url: wc_esl_shipping_global.ajaxUrl,
                async: true,
                data: {
                    action : 'wc_esl_update_shipping_address',
                    fias,
                    region,
                    postcode,
                    services,
                    city,
                    mode,
                    adress
                },
                dataType: 'json',
                success: function( response ) {

                    if( response.success ) {
                        $( `#${mode}_city` ).val( city );
                        $( `#${mode}_state` ).val( region );
                        $( `#${mode}_postcode` ).val( postcode );

                        $( `#wc_esl_${mode}_terminal` ).val( '' );
                        $(`.wc-esl-terminals__button[data-mode="${mode}"]`).text("Выбрать способ доставки и пункт самовывоза");

                        preload( `.woocommerce-${mode}-fields`, false );
                        $( 'body' ).trigger( 'update_esl_city' );
                    }

                    $( 'body' ).trigger( 'update_checkout' );
                }
            });
        }

        $( 'body' ).on( 'keyup focus', '#shipping_city', function( e ) {
            let value = $( this ).val();
            let mode = 'shipping';
            let $this = $( this );

            if( value.length > 2 ) {

                if( currentBillingCountry ) {
                    searchCity( value, function( items ) {
                        $('#result_wc_esl_search_city_shipping').remove();
                        if($this.parents( '.cfw-input-wrap-row' ).length === 1){
                            $this.parents('.cfw-input-wrap-row' ).append(
                                renderCitiesList( items, mode )
                            );
                        }else{
                            $this.parents( '.form-row' ).append(
                                renderCitiesList( items, mode )
                            );
                        }
                    }, currentBillingCountry );
                }
            }
        });

        if(document.getElementById('paymentCalc')) {
            console.log('start')
            $('body').on('change', 'input[name="payment_method"]', function (e) {
                $('body').trigger('update_checkout');
            });
        }

        $( 'body' ).on( 'updated_checkout', function() {
            currentShippingMethod = shippingFieldName();
            typeShippingMethod = shippingMethodTypeFunc( currentShippingMethod )


            changeVisibleElements(
                differentShippingAddress,
                typeShippingMethod,
                currentBillingCountry,
                currentShippingCountry
            );

        });


        $( 'body' ).on( 'click', '.wc-esl-search-city__item', function( e ) {
            searchCityVar = this;
            sendRequestCity(this);
        });

        $( 'body' ).on( 'change', '#ship-to-different-address-checkbox', function( e ) {
            differentShippingAddress = $( this ).is( ':checked' );

            changeVisibleElements(
                differentShippingAddress,
                typeShippingMethod,
                currentBillingCountry,
                currentShippingCountry
            );
        });

        $( 'body' ).on(
            'change',
            'select.shipping_method, :input[name^=shipping_method]',
            function ( e ){
                $('#wc_esl_billing_terminal').val('');
                $('#wc_esl_shipping_terminal').val('');
                currentShippingMethod 	= shippingFieldName( this );
                shippingMethodIsEshop 	= shippingMethodIsEshopFunc( currentShippingMethod );
                typeShippingMethod 		= shippingMethodTypeFunc( currentShippingMethod );
                esl.sleep(2000).then(() => {
                    esl.run('city');
                });
            }
        );

        $( 'body' ).on( 'change', '#billing_country', function( e ) {
            currentBillingCountry = $( this ).val();
        });

        $( 'body' ).on( 'change', '#shipping_country', function( e ) {
            currentShippingCountry = $( this ).val();
        });

    });

    let esl = {
        items: {
            widget_id: 'eShopLogisticStatic',
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
        widget_city: {name: null, type: null, fias: null, services: {}},
        widget_payment: {key: ''},
        esldata_value: {},
        request: function (action) {
            return new Promise(function (resolve, reject) {
                document.body.classList.add('load')
                document.body.classList.remove("loaded_hiding")
                setTimeout(function() {
                    sendRequestShipping(action)
                }, 1000)
            })
        },
        sleep: function(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        },
        check: function () {
            let check = true

            const current_payment = document.querySelector('input[name=payment_method]:checked')

            if (!current_payment) {
                check = false
            } else {
                this.current.payment_id = current_payment.value
            }
            if(!document.getElementById(this.items.esldata_payments_id))
                check = false

            if(document.getElementById('billing_city').value || document.getElementById('shipping_city').value){
                cityMain = true
                document.getElementById('tips-city-container').style.display = 'none';
            }else{
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
            this.widget_city.type = to.region
            this.widget_city.name = to.city
            this.widget_city.fias = to.fias
            this.widget_city.services = to.services
            this.widget_payment.key = (this.current.payment_id) ? this.current.payment_id : 'card'
            this.widget_payment.active = true

            let current_payment = this.current.payment_id
            if (current_payment) {
                for (const [key, value] of Object.entries(payments)) {
                    if (key.indexOf(current_payment) != -1) {
                        this.widget_payment.key = value
                    }
                }
            }

            if(isNumeric(this.widget_payment.key))
                this.widget_payment.key = 'card'

        },
        run: async function (reload = '') {
            if (!this.check()) {
                console.log('ESL: Ошибка проверки элементов')
                document.body.classList.add('loaded_hiding')
                document.body.classList.remove("load")
                changeVisibleElements()
                return false
            }
            const widget = document.getElementById(this.items.widget_id)
            this.prepare()


            let detail = {
                city: this.widget_city,
                payment: this.widget_payment,
                offers: this.widget_offers
            }

            if (reload.length !== 0 && window.widgetInit) {
                switch (reload) {
                    case 'offers':
                        let offers = await this.request('cart=1')
                        detail = {
                            offers: JSON.stringify(offers)
                        }
                        break
                    case 'payment':
                        detail = {
                            payment: this.widget_payment
                        }
                        break
                    case 'city':
                        detail = {
                            city: this.widget_city
                        }

                }
                widget.dispatchEvent(new CustomEvent('eShopLogistic:reload', {detail}))
            } else {
                widget.dispatchEvent(new CustomEvent('eShopLogistic:load', {detail}))
                window.widgetInit = true
            }

        },
        confirm: async function (response) {
            let esldata = {
                price: 0,
                time: '',
                name: response.name,
                key: response.keyShipper,
                mode: response.keyDelivery,
                address: '',
                comment: '',
                deliveryMethods: '',
                selectPvz: ''
            }

            if(document.getElementById('terminalEsl') && document.getElementById('terminalEsl').value){
                esldata.selectPvz = document.getElementById('terminalEsl').value
            }

            if (response.comment) {
                esldata.comment = response.comment
            }

            if (response.deliveryMethods) {
                esldata.deliveryMethods = response.deliveryMethods
            }

            if (response.keyDelivery === 'postrf') {
                esldata.price = response.terminal.price
                esldata.time = response.terminal.time
                if (response.terminal.comment) {
                    esldata.comment += '<br>' + response.terminal.comment
                }
            } else {
                esldata.price = response[response.keyDelivery].price
                esldata.time = response[response.keyDelivery].time
                if (response[response.keyDelivery].comment) {
                    esldata.comment += '<br>' + response[response.keyDelivery].comment
                }
            }

            if (response.deliveryAddress) {
                esldata.address = response.deliveryAddress.code + ' ' + response.deliveryAddress.address
            } else {
                if (response.currentAddress) {
                    esldata.address = response.currentAddress
                }
            }

            await this.request(JSON.stringify(esldata))

        },
        setTerminal: function (response) {
            _self = this

            let address = response.deliveryAddress

            const request = new XMLHttpRequest()
            request.open('POST', wc_esl_shipping_global.ajaxUrl, true)
            request.responseType = 'json'
            request.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
            request.setRequestHeader("Content-type", "application/x-www-form-urlencoded")
            request.send(`action=wc_esl_set_terminal_address&terminal=${address.address}&terminal_code=${address.code}`)

            request.addEventListener("readystatechange", () => {

                if (request.readyState === 4 && request.status === 200) {
                    jQuery('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val(address.address);
                    jQuery(".wc-esl-terminals__button").text("Выбрать способ доставки и пункт самовывоза");
                    modalEsl.style.display = "none";
                }
            })
        },
        error: function (response) {
            console.log('Ошибка виджета, включен дефолтный режим доставки', response)
            document.body.classList.add('loaded_hiding')
            document.body.classList.remove("load")
        },
    }

    function eslRun() {
        esl.run()
    }

    window.addEventListener('load', function (event) {
        eslRun()
    });


    document.addEventListener('eShopLogistic:ready', () => {
        eShopLogistic.onSelectedPVZ = function (response) {
            console.log('onSelectedPVZ', response)
            esl.setTerminal(response)
        }
        eShopLogistic.onError = function (response) {
            console.log('onError', response)
            esl.error(response)
            document.dispatchEvent(new CustomEvent('esl2onError', {detail: response}))
        }
        eShopLogistic.onSelectedService = function (response) {
            console.log('onSelectedService', response)
            jQuery('#wc_esl_billing_terminal, #wc_esl_shipping_terminal').val('');
            window.keyDelivery = response.keyDelivery
            let differentShippingAddress 	= jQuery( '#ship-to-different-address-checkbox' ).is( ':checked' );
            let currentBillingCountry 		= jQuery( '#billing_country' ).val();
            let currentShippingCountry 		= jQuery( '#shipping_country' ).val();
            if(window.keyDelivery === 'door'){
                jQuery( '#buttonModalDoor' ).show();
            }else{
                jQuery( '#buttonModalDoor' ).hide();
            }

            changeVisibleElements(
                differentShippingAddress,
                window.keyDelivery,
                currentBillingCountry,
                currentShippingCountry
            );

            let count = 0
            let countText = 'служб'
            jQuery.each(response['deliveryMethods'], function(key, value) {
                if(response['keyDelivery'] === value['keyShipper']){
                    count = value['services'].length
                }
            });

            if(count === 1)
                countText = 'служба';
            if(count > 1 && count < 5)
                countText = 'службы'

            jQuery('.esl_desct_delivery .count').html(count)
            jQuery('.esl_desct_delivery .countText').html(countText)

            esl.confirm(response)
        }
        eShopLogistic.onSelectedCity = function (response) {
            console.log('onSelectedCity', response)
        }
    })


    function sendRequestShipping(action){

        jQuery.ajax({
            method: 'POST',
            url: wc_esl_shipping_global.ajaxUrl,
            async: true,
            data: {
                action : 'wc_esl_update_shipping',
                data : action,
                city: esl.widget_city.name
            },
            dataType: 'json',
            success: function( response ) {
                jQuery( 'body' ).trigger( 'update_checkout' )
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

    function duplicateBoxClear(){
        let box = document.querySelectorAll('.'+esl.items.esl_box);
        let last = box[box.length- 1];

        for(let i=0; i<box.length; i++){
            if( box[i] !== last ){
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

    jQuery( 'body' ).on( 'update_esl_city', function() {

        jQuery( 'body' ).on( 'updated_checkout', function() {
            document.body.classList.add('load')
            document.body.classList.remove("loaded_hiding")
            esl.run('city')
            jQuery('body').off('updated_checkout');
        });
    });

    if(document.getElementById('paymentCalc')) {
        jQuery('body').on('change', 'input[name="payment_method"]', function (e) {

            jQuery('body').on('updated_checkout', function () {
                esl.run('payment')
                jQuery('body').off('updated_checkout');
            });

        });
    }


    let css = ['https://api.eshoplogistic.ru/widget/cart/v1/css/app.css'],
        js = ['https://api.eshoplogistic.ru/widget/cart/v1/js/chunk-vendors.js', 'https://api.eshoplogistic.ru/widget/cart/v1/js/app.js'];

    for (const path of css) {
        let links = document.getElementsByTagName('link');
        for (let i = links.length; i--;) {
            if (links[i].href === path){
                links[i].parentNode.removeChild(links[i])
            }
        }

        let style = document.createElement('link');
        style.rel = "stylesheet"
        style.href = path
        document.body.appendChild(style)
    }
    for (const path of js) {
        let scripts = document.getElementsByTagName('script');
        for (let i = scripts.length; i--;) {
            if (scripts[i].src === path){
                scripts[i].parentNode.removeChild(scripts[i])
            }
        }
        let script = document.createElement('script');
        script.src = path
        document.body.appendChild(script)
    }


}((jQuery)));