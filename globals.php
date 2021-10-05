<?php
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! function_exists('wc_esl_shipping_get_option')) {

    function wc_esl_shipping_get_option($key)
    {
        $options = new \eshoplogistic\WCEshopLogistic\DB\OptionsRepository();
        return $options->getOption($key);
    }

}

if ( ! function_exists('shortcode_widget_button_handler')) {

    function shortcode_widget_button_handler($atts, $content = null, $code = "")
    {
        $optionsRepository = new OptionsRepository();
        $widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');

        if(!$widgetKey)
            return '';

        global $post;
        $wc_product = wc_get_product( $post->ID );
        if(!$wc_product)
            return '';

        $wc_product = $wc_product->get_data();

        $block_content = sprintf(
            '<button type="button" 
            data-widget-button="" 
            data-article="%1$s" 
            data-name="%2$s" 
            data-price="%3$s" 
            data-unit="" 
            data-weight="%4$s">
            В корзину
            </button>',
            $wc_product['id'],
            $wc_product['name'],
            $wc_product['price'],
            $wc_product['weight']
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

        echo $block_content;
    }

}