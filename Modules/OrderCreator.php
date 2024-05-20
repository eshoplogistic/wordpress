<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined('ABSPATH') ) {
    exit;
}

class OrderCreator implements ModuleInterface
{
	public function init()
	{
		if (is_admin()) {
            return;
        }

        add_action('woocommerce_checkout_create_order', [$this, 'createOrder']);
        add_action('woocommerce_before_order_item_object_save', [$this, 'saveOrderShipping']);
	}

	public function createOrder($order)
	{
		$shippingMethodId = null;

		foreach($order->get_items( 'shipping' ) as $key => $item) {
			$itemData = $item->get_data();

			$shippingMethodId = $item->get_method_id();
		}


		$sessionService = new SessionService();

		if(!$shippingMethodId) return;

		if(!$this->methodsIsEshopTerminal($shippingMethodId)) return;

		$terminal = $sessionService->get('terminal_location');

		if(!$terminal) return;

		$order->set_shipping_address_1(__("Пункт выдачи: ", WC_ESL_DOMAIN) . $terminal);
	}

	public function saveOrderShipping($item)
	{
		if(
			!is_a($item, '\WC_Order_Item_Shipping')
		) return;

		try {
			$sessionService = new SessionService();
			$terminal = $sessionService->get('terminal_location');

			$shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];
			$shippingMethodId = $item->get_method_id();

			if( isset( $shippingMethods[$shippingMethodId] ) ) {
				unset($shippingMethods[$shippingMethodId]['terminals']);
				$jsonArr = json_encode( $shippingMethods[$shippingMethodId], JSON_UNESCAPED_UNICODE);
				$item->update_meta_data('esl_shipping_methods', $jsonArr);
			}
			$sessionService->drop('shipping_methods');

			if(!$terminal || !$this->methodsIsEshopTerminal($item->get_method_id())) return;

			$item->update_meta_data(__("Пункт выдачи", WC_ESL_DOMAIN), $terminal);
		} catch(\Exception $e) {
			return;
		}
	}

	private function methodsIsEshopTerminal($methodId)
    {
        $explodedAtPrefix = explode(WC_ESL_PREFIX, $methodId);

        if(empty($explodedAtPrefix)) return false;

        $typeServiceShipping = explode('_', $explodedAtPrefix[1]);

        if(!isset($typeServiceShipping[1])) return false;

        $serviceShipping = $typeServiceShipping[0];
        $typeServiceShipping = $typeServiceShipping[1];

	    $optionsRepository = new OptionsRepository();
	    $moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');

		if($typeServiceShipping === 'mixed') return true;
        if($typeServiceShipping !== 'terminal') return false;
		if(!$moduleVersion)
	        if($serviceShipping === 'postrf') return false;

        return true;
    }
}