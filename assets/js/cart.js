(function( $ ) {
	'use strict';

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

	$( document ).ready( function( e ) {
		let currentBillingCountry = $( '#calc_shipping_country' ).val();

		$( 'body' ).on( 'keyup focus', '#calc_shipping_city', function( e ) {
			let value = $( this ).val();
			let mode = 'billing';
			let $this = $( this );

			if( value.length > 2 ) {

				if( currentBillingCountry ) {
					searchCity( value, function( items ) {
						$this.parents( '.form-row' ).append(
							renderCitiesList( items, mode )
						);
					} );
				}
			}
		});

		$( 'body' ).on( 'click', '.wc-esl-search-city__item', function( e ) {

			let mode = $( this ).parents( '.wc-esl-search-city__list' ).data( 'mode' );

			let fias = $( this ).data( 'fias' );
			let region = $( this ).data( 'region' );
			let postcode = $( this ).data( 'postcode' );
			let services = $( this ).data( 'services' );
			let city = $( this ).data( 'city' );

			preload( `.shipping-calculator-form` );

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

						$( `#calc_shipping_city` ).val( city );
						$( `#calc_shipping_state` ).val( region );
						$( `#calc_shipping_postcode` ).val( postcode );

						preload( `.shipping-calculator-form`, false );
					}
				}
			});
		});

		$( 'body' ).on( 'change', '#calc_shipping_country', function( e ) {
			currentBillingCountry = $( this ).val();
		});

	});
})( jQuery );