(function( $ ) {
	'use strict';

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

	function searchCity( target, renderFunc, currentCountry, typeFilter = false ) {
		$.ajax({
			method: 'POST',
			url: wc_esl_shipping_global.ajaxUrl,
			async: true,
			data: {
				action : 'wc_esl_search_cities',
				target,
				currentCountry,
				typeFilter
			},
			dataType: 'json',
			success: function( response ) {

				console.log(response);

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
		let billingTerminals 		= $( '#wc-esl-terminals-wrap-billing' );
		let shippingTerminals 		= $( '#wc-esl-terminals-wrap-shipping' );
		let billingAddress1 		= $( '#billing_address_1_field' );
		let billingAddress2 		= $( '#billing_address_2_field' );
		let shippingAddress1 		= $( '#shipping_address_1_field' );
		let shippingAddress2 		= $( '#shipping_address_2_field' );
		let inputListTerminals 		= $( '#wcEslTerminals' );
		let currentShippingMethod	= shippingFieldName();
		let offAddressCheck = $('#offAddressCheck');

		if( isTerminal ) {
			if( differentShippingAddress && ( shippingCountry ) ) {
				if(offAddressCheck.length === 0){
					billingAddress1.show();
					billingAddress2.show();
					shippingAddress1.hide();
					shippingAddress2.hide();
				}

				billingTerminals.hide().removeClass('show');
				shippingTerminals.show().addClass('show');
			}else if(
				!differentShippingAddress && ( billingCountry )
			) {
				if(offAddressCheck.length === 0){
					if(currentShippingMethod !== 'wc_esl_postrf_terminal'){
						billingAddress1.hide();
						billingAddress2.hide();
						shippingAddress1.hide();
						shippingAddress2.hide();
					}else{
						billingAddress1.show();
						billingAddress2.show();
						shippingAddress1.show();
						shippingAddress2.show();
					}
				}
				billingTerminals.show().addClass('show');
				shippingTerminals.hide().removeClass('show');
			} else {
				if(offAddressCheck.length === 0){
					billingAddress1.show();
					billingAddress2.show();
					shippingAddress1.show();
					shippingAddress2.show();
				}

				billingTerminals.hide().removeClass('show');
				shippingTerminals.hide().removeClass('show');
			}

			if(inputListTerminals.length === 0){
				billingTerminals.hide().removeClass('show');
				shippingTerminals.hide().removeClass('show');
			}

		} else {
			if(offAddressCheck.length === 0){
				billingAddress1.show();
				billingAddress2.show();
				shippingAddress1.show();
				shippingAddress2.show();
			}

			billingTerminals.hide().removeClass('show');
			shippingTerminals.hide().removeClass('show');
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
		let modalSelectCity = $('#modal-esl-city').length > 0;
		let billingCityFields = $('#eslBillingCityFields').val();
		let shippingCityFields = $('#eslShippingCityFields').val();

		changeVisibleElements(
			differentShippingAddress,
			typeShippingMethod === 'terminal',
			currentBillingCountry,
			currentShippingCountry
		);


		if($('#'+billingCityFields).length === 1){
			$('#'+billingCityFields).prop("autocomplete", "nope");
			inputFocusCity(billingCityFields);
		}

		if($('#billing_address_1').length === 1){
			inputFocusAdress('billing_address_1');
		}
		if($('#shipping_address_1').length === 1){
			inputFocusAdress('shipping_address_1');
		}

		if ($('#'+shippingCityFields).length === 1 && modalSelectCity) {
			$('#'+shippingCityFields).prop("autocomplete", "nope");
			inputFocusCity(shippingCityFields);
		}
		if(modalSelectCity){
			inputStartCityModal();
		}

		function inputFocusAdress($name = 'billing_address_1'){
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
								$this.next('#esl_result-search').html(
									renderCitiesModal(items, modeInput)
								);
							}else{
								$this.next('#esl_result-search').html('<button id="esl_modal_button-search">Выбрать данный населённый пункт</button>');
							}
						}, currentBillingCountry, 'region');
					}
				}else{
					$this.next('#esl_result-search').html('');
				}
			});

			$('body').on('click', '.wc-esl-search-city-modal__item', function (e) {
				searchCityVar = this;
				sendRequestCity(this);
				document.getElementById("modal-esl-city").style.display = "none"
			});

			$('body').on('click', '#esl_modal_button-search', function (e) {
				e.preventDefault();
				let modalSearch = $('#esl_modal-search');
				let value = modalSearch.val();
				let modeInput = modalSearch.attr('data-mode');
				$( `#${modeInput}_city` ).val( value );
				$.ajax({
					method: 'POST',
					url: wc_esl_shipping_global.ajaxUrl,
					async: true,
					data: {
						action : 'wc_esl_update_shipping_address',
					},
					dataType: 'json',
					success: function( response ) {

						console.log( response );

						$( 'body' ).trigger( 'update_checkout' );
						document.getElementById("modal-esl-city").style.display = "none"
					}
				});
			});
		}


		function inputFocusCity($name = 'billing_city'){
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
				$( 'body' ).on( 'change', '#'+$name, function( e ) {
					$('#tips-city-container').show();
				})

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
		}

		function sendRequestCity($this){
			let mode = '';
			if($($this).parents('.wc-esl-search-city-modal__list').length > 0){
				mode = $($this).parents('.wc-esl-search-city-modal__list').data('mode');
			}else{
				mode = $($this).parents('.wc-esl-search-city__list').data('mode');
			}

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

					console.log( response );

					if( response.success ) {

						$( `#${mode}_city` ).val( city );
						$( `#${mode}_state` ).val( region );
						$( `#${mode}_postcode` ).val( postcode );

						$( `#wc_esl_${mode}_terminal` ).val( '' );
						//$(`.wc-esl-terminals__button[data-mode="${mode}"]`).text("Выбрать пункт выдачи");

						preload( `.woocommerce-${mode}-fields`, false );
					}

					$( 'body' ).trigger( 'update_checkout' );
				}
			});
		}

		$( 'body' ).on( 'keyup focus', '#'+shippingCityFields, function( e ) {
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

		$( 'body' ).on( 'change', 'input[name="payment_method"]', function( e ) {
			$( 'body' ).trigger( 'update_checkout' );
		});

		$( 'body' ).on( 'updated_checkout', function() {
			if(!typeShippingMethod){
				currentShippingMethod = shippingFieldName();
				typeShippingMethod = shippingMethodTypeFunc( currentShippingMethod )
			}

			changeVisibleElements(
				differentShippingAddress,
				typeShippingMethod === 'terminal',
				currentBillingCountry,
				currentShippingCountry
			);

		});



		$( 'body' ).on( 'click', '.wc-esl-search-city__item', function( e ) {
			searchCityVar = this;
			$('#tips-city-container').hide();
			sendRequestCity(this);
		});

		$( 'body' ).on( 'change', '#ship-to-different-address-checkbox', function( e ) {
			differentShippingAddress = $( this ).is( ':checked' );

			changeVisibleElements(
				differentShippingAddress,
				typeShippingMethod === 'terminal',
				currentBillingCountry,
				currentShippingCountry
			);
		});

		$( 'body' ).on(
			'change',
			'select.shipping_method, :input[name^=shipping_method]',
			function ( e ){
				$('#wc_esl_billing_terminal').val('');
				currentShippingMethod 	= shippingFieldName( this );
				shippingMethodIsEshop 	= shippingMethodIsEshopFunc( currentShippingMethod );
				typeShippingMethod 		= shippingMethodTypeFunc( currentShippingMethod );
			}
		);

		$( 'body' ).on( 'change', '#billing_country', function( e ) {
			currentBillingCountry = $( this ).val();
		});

		$( 'body' ).on( 'change', '#shipping_country', function( e ) {
			currentShippingCountry = $( this ).val();
		});

	});

})( jQuery );