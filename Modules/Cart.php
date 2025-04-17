<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Cart implements ModuleInterface
{
    public function init()
    {
        add_action('woocommerce_update_cart_action_cart_updated', [$this, 'onActionCartUpdated']);
        add_action('woocommerce_cart_totals_before_shipping', [$this, 'clearShippingCache']);
        add_action('woocommerce_add_to_cart', [$this, 'addProductCart']);
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

    public function onActionCartUpdated()
    {
	    $sessionService = new SessionService();
	    $sessionService->set('esl_shipping_frame', '');
    }

	public function addProductCart()
	{
		$sessionService = new SessionService();
		$sessionService->set('esl_shipping_frame', '');
	}
}