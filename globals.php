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

		// Шорткод обязан вернуть строку, а не напечатать её: WooCommerce
		// прогоняет описание товара через do_shortcode() и в других
		// контекстах (например, при формировании structured data для SEO),
		// и echo здесь приводил к тому, что разметка виджета утекала в эти
		// контексты вторым, незапрошенным экземпляром.
		return $block_content;
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

		// См. пояснение в shortcode_widget_button_handler() выше: шорткод
		// должен вернуть строку, а не напечатать её.
		return $block_content;
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

/**
 * AJAX-обработчик получения данных товара для блока калькулятора
 */
if ( ! function_exists( 'wc_esl_get_product_data_ajax' ) ) {
    function wc_esl_get_product_data_ajax() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wc-esl-shipping' ) ) {
            wp_send_json_error( array( 'message' => 'Nonce verification failed' ) );
        }

        if ( ! isset( $_POST['product_id'] ) ) {
            wp_send_json_error( array( 'message' => 'Product ID not provided' ) );
        }

        $product_id = intval( sanitize_text_field( wp_unslash( $_POST['product_id'] ) ) );
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            wp_send_json_error( array( 'message' => 'Product not found' ) );
        }

        $shippingHelper = new ShippingHelper();
        $length = $shippingHelper->dimensionsOption( $product->get_length() );
        $width = $shippingHelper->dimensionsOption( $product->get_width() );
        $height = $shippingHelper->dimensionsOption( $product->get_height() );

        wp_send_json_success( array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'weight' => $product->get_weight(),
            'dimensions' => $length . '*' . $width . '*' . $height,
            'sku' => $product->get_sku(),
        ) );
    }

    add_action( 'wp_ajax_get_product_data', 'wc_esl_get_product_data_ajax' );
    add_action( 'wp_ajax_nopriv_get_product_data', 'wc_esl_get_product_data_ajax' );
}

/**
 * Новый AJAX-обработчик данных товара с проверкой nonce (блоки Gutenberg)
 */
if ( ! function_exists( 'wc_esl_get_product_data_for_blocks' ) ) {
    function wc_esl_get_product_data_for_blocks() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wc_esl_block_nonce' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security verification failed', 'eshoplogisticru' ) ) );
        }

        if ( ! isset( $_POST['product_id'] ) ) {
            wp_send_json_error( array( 'message' => __( 'Product ID is required', 'eshoplogisticru' ) ) );
        }

        $product_id = intval( wp_unslash( $_POST['product_id'] ) );
        $product = wc_get_product( $product_id );

        if ( ! $product ) {
            wp_send_json_error( array( 'message' => __( 'Product not found', 'eshoplogisticru' ) ) );
        }

        $shippingHelper = new ShippingHelper();
        $product_data = $product->get_data();

        $length = $shippingHelper->dimensionsOption( $product_data['length'] );
        $width = $shippingHelper->dimensionsOption( $product_data['width'] );
        $height = $shippingHelper->dimensionsOption( $product_data['height'] );

        wp_send_json_success( array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'weight' => $product->get_weight(),
            'dimensions' => $length . '*' . $width . '*' . $height,
            'sku' => $product->get_sku(),
            'image' => wp_get_attachment_url( $product->get_image_id() ),
        ) );
    }

    add_action( 'wp_ajax_wc_esl_get_product_data', 'wc_esl_get_product_data_for_blocks' );
    add_action( 'wp_ajax_nopriv_wc_esl_get_product_data', 'wc_esl_get_product_data_for_blocks' );
}

/**
 * Поддержка оформления заказа на Gutenberg/блоках
 * Хук для внедрения полей формы доставки в WooCommerce Blocks Checkout
 */
if ( ! function_exists( 'wc_esl_blocks_checkout_register_hooks' ) ) {
    function wc_esl_blocks_checkout_register_hooks() {
		// Регистрируем хуки для блочного оформления заказа
        add_filter( 'woocommerce_blocks_checkout_available_block_types', function( $blocks ) {
            return $blocks;
        } );
    }

    add_action( 'init', 'wc_esl_blocks_checkout_register_hooks' );
}

/**
 * AJAX-обработчик обновления корзины при блочном оформлении заказа
 */
if ( ! function_exists( 'wc_esl_update_cart_ajax' ) ) {
    function wc_esl_update_cart_ajax() {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'wc-esl-shipping' ) ) {
            wp_send_json_error( array( 'message' => 'Nonce verification failed' ) );
        }

		// Возвращаем данные корзины для JavaScript
        wp_send_json_success( array(
            'cart_total' => WC()->cart ? WC()->cart->get_total() : 0,
            'cart_count' => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
        ) );
    }

    add_action( 'wp_ajax_wc_esl_update_cart', 'wc_esl_update_cart_ajax' );
    add_action( 'wp_ajax_nopriv_wc_esl_update_cart', 'wc_esl_update_cart_ajax' );
}

/**
 * Получение информации о доступности блоков
 * Предоставляет сведения о том, какие блоки доступны
 */
if ( ! function_exists( 'wc_esl_get_block_info' ) ) {
    function wc_esl_get_block_info() {
        $optionsRepository = new OptionsRepository();
        
        return array(
            'checkoutShippingBlock' => true,
            'productCalculatorBlock' => true,
            'cartShippingBlock' => true,
            'checkoutFormBlock' => true,
            'widgetKey' => $optionsRepository->getOption( 'wc_esl_shipping_widget_key' ),
            'frameEnabled' => (bool) $optionsRepository->getOption( 'wc_esl_shipping_frame_enable' ),
        );
    }
}
