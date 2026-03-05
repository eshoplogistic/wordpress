<?php

if ( ! defined( 'ABSPATH' ) ) exit;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;

if ( ! function_exists( 'wc_esl_shipping_get_option' ) ) {

	function wc_esl_shipping_get_option( $key ) {
		$options = new \eshoplogistic\WCEshopLogistic\DB\OptionsRepository();

		return $options->getOption( $key );
	}

}

if ( ! function_exists( 'shortcode_widget_button_handler' ) ) {

	function shortcode_widget_button_handler( $atts, $content = null, $code = "" ) {
		$optionsRepository = new OptionsRepository();
		$widgetKey         = $optionsRepository->getOption( 'wc_esl_shipping_widget_key' );
		$widgetBut         = $optionsRepository->getOption( 'wc_esl_shipping_widget_but' );
		$widgetKey = isset($atts['key']) ? sanitize_text_field($atts['key']) : $widgetKey;

		if ( ! $widgetKey ) {
			return '';
		}

		global $post;

		if ( ! isset( $post ) && ! $post ) {
			return '';
		}
		$wc_product = wc_get_product( $post->ID );
		if ( ! $wc_product ) {
			return '';
		}

		$wc_product = $wc_product->get_data();

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
		} else {
			$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		}

		$shippingHelper = new ShippingHelper();
		$length = $shippingHelper->dimensionsOption($wc_product['length']);
		$width = $shippingHelper->dimensionsOption($wc_product['width']);
		$height = $shippingHelper->dimensionsOption($wc_product['height']);
		$item[]   = array(
			'article' => $wc_product['id'],
			'name'    => $wc_product['name'],
			'count'   => 1,
			'price'   => $wc_product['price'],
			'weight'  => $wc_product['weight'],
			'dimensions' => $length.'*'.$width.'*'.$height
		);
		$jsonItem = esc_attr( wp_json_encode( $item ) );

		$block_content = '<button data-esl-widget data-title="' . esc_attr('Быстрый заказ с доставкой') . '">' . esc_html('Быстрый заказ с доставкой') . '</button>';
		$block_content .= '<div id="eShopLogisticWidgetModal"
					data-lazy-load="true"
					data-debug="1"
					data-ip="' . esc_attr(apply_filters( 'wc_esl_get_ip', $ip )) . '"
					data-key="' . esc_attr($widgetKey) . '"
					data-offers="' . esc_attr($jsonItem) . '">
					</div>';

		wp_enqueue_script(
			'wc_esl_app_v2_js',
			'https://api.esplc.ru/widgets/modal/app.js',
			[],
			WC_ESL_VERSION,
			true
		);

		wp_enqueue_style(
			'wc_esl_style_frame_css',
			WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css',
			[],
			WC_ESL_VERSION
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are escaped within $block_content construction
		echo $block_content;
	}

}

if ( ! function_exists( 'shortcode_widget_button_tab_handler' ) ) {


	function shortcode_widget_button_tab_handler($atts) {
		if(isset($atts['key']))
			$GLOBALS['wc_esl_widget_tab_key'] = sanitize_text_field(wp_unslash($atts['key']));

		add_filter( 'woocommerce_product_tabs', 'esl_product_widget_tab', 25 );
	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy function name retained for backwards compatibility.
	function esl_product_widget_tab( $tabs ) {

		$optionsRepository = new OptionsRepository();
		$widgetBut         = $optionsRepository->getOption( 'wc_esl_shipping_widget_but' );

		$tabs['esl_product_widget_tab'] = array(
			'title'    => $widgetBut,
			'priority' => 25,
			'callback' => 'esl_product_widget_tab_content',
		);

		return $tabs;

	}

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- Legacy function name retained for backwards compatibility.
	function esl_product_widget_tab_content() {
		$optionsRepository = new OptionsRepository();
		$widgetKey         = $optionsRepository->getOption( 'wc_esl_shipping_widget_key' );
		$widgetKey = isset($GLOBALS['wc_esl_widget_tab_key']) ? sanitize_text_field($GLOBALS['wc_esl_widget_tab_key']) : $widgetKey;

		if ( ! $widgetKey ) {
			return '';
		}

		global $post;

		if ( ! isset( $post ) && ! $post ) {
			return '';
		}
		$wc_product = wc_get_product( $post->ID );
		if ( ! $wc_product ) {
			return '';
		}

		$wc_product = $wc_product->get_data();

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
		} else {
			$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		}

		$shippingHelper = new ShippingHelper();
			$length = $shippingHelper->dimensionsOption($wc_product['length']);
			$width = $shippingHelper->dimensionsOption($wc_product['width']);
			$height = $shippingHelper->dimensionsOption($wc_product['height']);
			$item[]   = array(
				'article' => $wc_product['id'],
				'name'    => $wc_product['name'],
				'count'   => 1,
				'price'   => $wc_product['price'],
				'weight'  => $wc_product['weight'],
				'dimensions' => $length.'*'.$width.'*'.$height
			);
			$jsonItem = htmlspecialchars( json_encode( $item ) );


			$block_content = '<div id="eShopLogisticWidgetBlock"
							    data-lazy-load="true"
					    data-ip="' . esc_attr(apply_filters( 'wc_esl_get_ip', $ip )) . '"
							</div>';

			$block_content .= '<button type="button" class="hidden" id="wtpbtn" data-widget-load="">Заказать с доставкой</button>';

			wp_enqueue_script(
				'wc_esl_app_tab_v2_js',
				'https://api.esplc.ru/widgets/block/app.js',
				[],
				WC_ESL_VERSION,
				true
			);
			wp_enqueue_script(
				'wc_esl_app_tab_js',
				WC_ESL_PLUGIN_URL . 'assets/js/app_tab.js',
				[],
				WC_ESL_VERSION,
				true
			);

	wp_enqueue_style(
		'wc_esl_style_frame_css',
		WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css',
		[],
		WC_ESL_VERSION
	);
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are escaped within $block_content construction
		echo $block_content;
	}


}

if ( ! function_exists( 'shortcode_widget_static_handler' ) ) {

	function shortcode_widget_static_handler( $atts, $content = null, $code = "" ) {
		$optionsRepository = new OptionsRepository();
		$widgetKey         = $optionsRepository->getOption( 'wc_esl_shipping_widget_key' );
		$widgetKey = isset($atts['key']) ? sanitize_text_field($atts['key']) : $widgetKey;

		if ( ! $widgetKey ) {
			return '';
		}

		global $post;

		if ( ! isset( $post ) && ! $post ) {
			return '';
		}
		$wc_product = wc_get_product( $post->ID );
		if ( ! $wc_product ) {
			return '';
		}

		$wc_product = $wc_product->get_data();

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
		} else {
			$ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
		}

		$shippingHelper = new ShippingHelper();
		$length = $shippingHelper->dimensionsOption($wc_product['length']);
		$width = $shippingHelper->dimensionsOption($wc_product['width']);
		$height = $shippingHelper->dimensionsOption($wc_product['height']);
		$item[]   = array(
			'article' => $wc_product['id'],
			'name'    => $wc_product['name'],
			'count'   => 1,
			'price'   => $wc_product['price'],
			'weight'  => $wc_product['weight'],
			'dimensions' => $length.'*'.$width.'*'.$height
		);
		$jsonItem = htmlspecialchars( json_encode( $item ) );


		$block_content = '<div id="eShopLogisticWidgetBlock"
						    data-lazy-load="true"
						    data-ip="' . apply_filters( 'wc_esl_get_ip', $ip ) . '"
						    data-key="'.$widgetKey.'"
						    data-offers="'.$jsonItem.'">
						</div>';

		$block_content .= '<button type="button" class="hidden" id="wtpbtn" data-widget-load="">Заказать с доставкой</button>';

		wp_enqueue_script(
			'wc_esl_app_tab_v2_js',
			'https://api.esplc.ru/widgets/block/app.js',
			[],
			WC_ESL_VERSION,
			true
		);
		wp_enqueue_script(
			'wc_esl_app_tab_js',
			WC_ESL_PLUGIN_URL . 'assets/js/app_tab.js',
			[],
			WC_ESL_VERSION,
			true
		);

		wp_enqueue_style(
			'wc_esl_style_frame_css',
			WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css',
			[],
			WC_ESL_VERSION
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are escaped within $block_content construction
		echo $block_content;
	}

}

if ( ! function_exists( 'shortcode_widget_email_time_delivery' ) ) {
	function shortcode_widget_email_time_delivery( $atts, $content = null, $code = "" ) {
		if(!isset($atts['id']))
			return false;

		$order = wc_get_order( $atts['id'] );
		if($order){
			$orderShippings = $order->get_shipping_methods();
			foreach ($orderShippings as $key=>$item){
				$shippingMethod = wc_get_order_item_meta( $item->get_id() , 'esl_shipping_methods', $single = true );
			}
			$shippingMethods = json_decode($shippingMethod, true);
			if(isset($shippingMethods['time'])){
				echo esc_html($shippingMethods['time']['value']).' '.esc_html($shippingMethods['time']['unit']);
			}
		}
	}
}

if ( ! function_exists( 'shortcode_widget_email_status_delivery' ) ) {
    function shortcode_widget_email_status_delivery( $atts, $content = null, $code = "" ) {
        if(!isset($atts['id']))
            return false;

        $order = wc_get_order( $atts['id'] );
        if($order){
            $orderShippings = $order->get_shipping_methods();
            foreach ($orderShippings as $key=>$item){
                $shippingMethod = wc_get_order_item_meta( $item->get_id() , 'esl_shipping_methods', $single = true );
            }
            $shippingMethods = json_decode($shippingMethod, true);
            if(isset($shippingMethods['tracking']['status']['name'])){
                echo esc_html($shippingMethods['tracking']['status']['name']);
            }
        }
    }
}