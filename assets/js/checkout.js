(function( $ ) {
	'use strict';

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

	function searchCity( target, renderFunc ) {
		$.ajax({
            method: 'POST',
            url: wc_esl_shipping_global.ajaxUrl,
            async: true,
            data: {
                action : 'wc_esl_search_cities',
                target
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

	function renderCitiesItem( { fias, name, region, postal_code, services } ) {
		let label = name;
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

	function changeVisibleElements(
		differentShippingAddress = false,
		isTerminal = true,
		billingCountry = '',
		shippingCountry = ''
	) {
		let billingTerminals 	= $( '#wc-esl-terminals-wrap-billing' );
		let shippingTerminals 	= $( '#wc-esl-terminals-wrap-shipping' );
		let billingAddress1 	= $( '#billing_address_1_field' );
		let billingAddress2 	= $( '#billing_address_2_field' );
		let shippingAddress1 	= $( '#shipping_address_1_field' );
		let shippingAddress2 	= $( '#shipping_address_2_field' );
		let inputListTerminals 	= $( '#wcEslTerminals' );

		if( isTerminal ) {

			if( differentShippingAddress && ( shippingCountry === 'RU' ) ) {
				billingTerminals.hide();
				shippingTerminals.show();

				billingAddress1.show();
				billingAddress2.show();
				shippingAddress1.hide();
				shippingAddress2.hide();
			} else if(
				differentShippingAddress && ( shippingCountry !== 'RU' )
			) {
				billingTerminals.hide();
				shippingTerminals.hide();

				billingAddress1.show();
				billingAddress2.show();
				shippingAddress1.show();
				shippingAddress2.show();
			} else if(
				!differentShippingAddress && ( billingCountry === 'RU' )
			) {
				billingTerminals.show();
				shippingTerminals.hide();

				billingAddress1.hide();
				billingAddress2.hide();
				shippingAddress1.hide();
				shippingAddress2.hide();
			} else {
				billingTerminals.hide();
				shippingTerminals.hide();

				billingAddress1.show();
				billingAddress2.show();
				shippingAddress1.show();
				shippingAddress2.show();
			}

			if(inputListTerminals.length === 0){
				billingTerminals.hide();
			}
			
		} else {
			billingTerminals.hide();
			shippingTerminals.hide();

			billingAddress1.show();
			billingAddress2.show();
			shippingAddress1.show();
			shippingAddress2.show();
		}
	}

	$( document ).ready( function( e ) {
		let differentShippingAddress 	= $( '#ship-to-different-address-checkbox' ).is( ':checked' );
		let currentShippingMethod 		= $( 'input[name="shipping_method[0]"]:checked' ).val();
		let shippingMethodIsEshop 		= shippingMethodIsEshopFunc( currentShippingMethod );
		let typeShippingMethod 			= shippingMethodTypeFunc( currentShippingMethod );
		let currentBillingCountry 		= $( '#billing_country' ).val();
		let currentShippingCountry 		= $( '#shipping_country' ).val();

		changeVisibleElements(
			differentShippingAddress,
			typeShippingMethod === 'terminal',
			currentBillingCountry,
			currentShippingCountry
		);


		if($('#billing_city').length === 1){
			$('#billing_city').prop("autocomplete", "nope");
			inputFocusCity('billing_city');
		}else if($('#billing_state').length === 1){
			$('#billing_state').prop("autocomplete", "nope");
			inputFocusCity('billing_state');
		}

		function inputFocusCity($name = 'billing_city'){
			$( 'body' ).on( 'keyup focus', '#'+$name, function( e ) {
				let value = $( this ).val();
				let mode = 'billing';
				let $this = $( this );

				if( value.length > 2 ) {

					if( currentBillingCountry === 'RU' ) {
						searchCity( value, function( items ) {
							$this.parents( '.form-row' ).append(
								renderCitiesList( items, mode )
							);
						} );
					}
				}
			});
		}

		$( 'body' ).on( 'keyup focus', '#shipping_city', function( e ) {
			let value = $( this ).val();
			let mode = 'shipping';
			let $this = $( this );

			if( value.length > 2 ) {

				if( currentBillingCountry === 'RU' ) {
					searchCity( value, function( items ) {
						$this.parents( '.form-row' ).append(
							renderCitiesList( items, mode )
						);
					} );
				}
			}
		});

		$( 'body' ).on( 'change', 'input[name="payment_method"]', function( e ) {
			$( 'body' ).trigger( 'update_checkout' );
		});

		$( 'body' ).on( 'updated_checkout', function() {
			
			changeVisibleElements(
				differentShippingAddress,
				typeShippingMethod === 'terminal',
				currentBillingCountry,
				currentShippingCountry
			);

		});

		$( 'body' ).on( 'click', '.wc-esl-search-city__item', function( e ) {

			let mode = $( this ).parents( '.wc-esl-search-city__list' ).data( 'mode' );

			let fias = $( this ).data( 'fias' );
			let region = $( this ).data( 'region' );
			let postcode = $( this ).data( 'postcode' );
			let services = $( this ).data( 'services' );
			let city = $( this ).data( 'city' );

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
                    mode
                },
                dataType: 'json',
                success: function( response ) {

                	console.log( response );

					if( response.success ) {

						$( `#${mode}_city` ).val( city );
						$( `#${mode}_state` ).val( region );
						$( `#${mode}_postcode` ).val( postcode );

						$( `#wc_esl_${mode}_terminal` ).val( '' );
						$(`.wc-esl-terminals__button[data-mode="${mode}"]`).text("Выбрать пункт выдачи");

						preload( `.woocommerce-${mode}-fields`, false );
					}

					$( 'body' ).trigger( 'update_checkout' );
                }
            });
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

		$( 'body' ).on( 'change', 'input[name="shipping_method[0]"]', function( e ) {
			$('#wc_esl_billing_terminal').val('');
			currentShippingMethod 	= $( 'input[name="shipping_method[0]"]:checked' ).val();
			shippingMethodIsEshop 	= shippingMethodIsEshopFunc( currentShippingMethod );
			typeShippingMethod 		= shippingMethodTypeFunc( currentShippingMethod );
		});

		$( 'body' ).on( 'change', '#billing_country', function( e ) {
			currentBillingCountry = $( this ).val();
		});

		$( 'body' ).on( 'change', '#shipping_country', function( e ) {
			currentShippingCountry = $( this ).val();
		});

	});

})( jQuery );