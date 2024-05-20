<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Cart implements ModuleInterface
{
    public function init()
    {
        add_action('woocommerce_before_cart', [$this, 'testBeforeCart']);
        add_action('woocommerce_cart_totals_before_shipping', [$this, 'clearShippingCache']);
        // add_filter('woocommerce_shipping_show_shipping_calculator', [$this, 'disableShippingCalculator'], 10, 3);
    }

    public function clearShippingCache()
    {
        $packages = WC()->cart->get_shipping_packages();
        
        foreach ( $packages as $key => $value ) {
            $shipping_session = "shipping_for_package_$key";
    
            unset( WC()->session->$shipping_session );
        }
    }

    public function disableShippingCalculator($first, $i, $package)
    {
        return false;
    }

    public function testBeforeCart()
    {
    }
}