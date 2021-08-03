<?php

if ( ! function_exists('wc_esl_shipping_get_option')) {

    function wc_esl_shipping_get_option($key)
    {
        $options = new \eshoplogistic\WCEshopLogistic\DB\OptionsRepository();
        return $options->getOption($key);
    }

}