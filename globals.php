<?php

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
		$widgetKey = isset($atts['key']) ? wc_clean($atts['key']) : $widgetKey;

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
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$moduleVersion = $optionsRepository->getOption( 'wc_esl_shipping_plugin_enable_api_v2' );

		if ( $moduleVersion ) {
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

			$block_content = '<button data-esl-widget data-title="Быстрый заказ с доставкой">Быстрый заказ с доставкой</button>';
			$block_content .= '<div id="eShopLogisticWidgetModal"
                        data-lazy-load="true"
                        data-debug="1"
                        data-ip="' . apply_filters( 'edd_get_ip', $ip ) . '"
                        data-key="' . $widgetKey . '"
                        data-offers="' . $jsonItem . '">
						</div>';

			wp_enqueue_script(
				'wc_esl_app_v2_js',
				'https://api.esplc.ru/widgets/modal/app.js',
				[],
				WC_ESL_VERSION,
				true
			);
		} else {
			$block_content = sprintf(
				'<button type="button" 
            data-widget-button="" 
            data-article="%1$s" 
            data-name="%2$s" 
            data-price="%3$s" 
            data-unit="" 
            data-weight="%4$s"
            data-ip="%5$s">
            %6$s
            </button>',
				$wc_product['id'],
				$wc_product['name'],
				$wc_product['price'],
				$wc_product['weight'],
				apply_filters( 'edd_get_ip', $ip ),
				$widgetBut
			);
			$block_content .= sprintf(
				'<div id="eShopLogisticApp" data-key="%1$s"></div>',
				$widgetKey
			);

			wp_enqueue_script(
				'wc_esl_app_js',
				WC_ESL_PLUGIN_URL . 'assets/js/app.js',
				[],
				WC_ESL_VERSION,
				true
			);
		}

		wp_enqueue_style(
			'wc_esl_style_frame_css',
			WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css',
			[],
			WC_ESL_VERSION
		);

		echo $block_content;
	}

}

if ( ! function_exists( 'shortcode_widget_button_tab_handler' ) ) {


	function shortcode_widget_button_tab_handler($atts) {
		if(isset($atts['key']))
			$_POST['esl_key'] = $atts['key'];

		add_filter( 'woocommerce_product_tabs', 'esl_product_widget_tab', 25 );
	}

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

	function esl_product_widget_tab_content() {
		$optionsRepository = new OptionsRepository();
		$widgetKey         = $optionsRepository->getOption( 'wc_esl_shipping_widget_key' );
		$widgetKey = isset($_POST['esl_key']) ? wc_clean($_POST['esl_key']) : $widgetKey;

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
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$moduleVersion = $optionsRepository->getOption( 'wc_esl_shipping_plugin_enable_api_v2' );

		if ( $moduleVersion ) {
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
							    data-ip="' . apply_filters( 'edd_get_ip', $ip ) . '"
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
		} else {
			$block_content = sprintf(
				'<div
			id="eShopLogisticStatic"
			class="eShopLogisticStatic__block"
			data-no-form="0"
			data-v-app
            data-key="%1$s"
            data-article="%2$s" 
            data-name="%3$s" 
            data-price="%4$s" 
            data-unit="" 
            data-weight="%5$s"
            data-ip="%6$s">
            </div>',
				$widgetKey,
				$wc_product['id'],
				$wc_product['name'],
				$wc_product['price'],
				$wc_product['weight'],
				apply_filters( 'edd_get_ip', $ip ),
			);

			$block_content .= '<button type="button" class="hidden" id="wtpbtn" data-widget-load="">Заказать с доставкой</button>';

			wp_enqueue_script(
				'wc_esl_app_tab_js',
				WC_ESL_PLUGIN_URL . 'assets/js/app_tab.js',
				[],
				WC_ESL_VERSION,
				true
			);
		}

		wp_enqueue_style(
			'wc_esl_style_frame_css',
			WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css',
			[],
			WC_ESL_VERSION
		);

		echo $block_content;
	}


}

if ( ! function_exists( 'shortcode_widget_static_handler' ) ) {

	function shortcode_widget_static_handler( $atts, $content = null, $code = "" ) {
		$optionsRepository = new OptionsRepository();
		$widgetKey         = $optionsRepository->getOption( 'wc_esl_shipping_widget_key' );
		$widgetKey = isset($atts['key']) ? wc_clean($atts['key']) : $widgetKey;

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
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		$moduleVersion = $optionsRepository->getOption( 'wc_esl_shipping_plugin_enable_api_v2' );


		if ( $moduleVersion ) {
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
							    data-ip="' . apply_filters( 'edd_get_ip', $ip ) . '"
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
		}

		wp_enqueue_style(
			'wc_esl_style_frame_css',
			WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css',
			[],
			WC_ESL_VERSION
		);

		echo $block_content;
	}

}