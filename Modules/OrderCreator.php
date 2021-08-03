<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
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
		$sessionService->drop('shipping_methods');

		if(!$shippingMethodId) return;

		if(!$this->methodsIsEshopTerminal($shippingMethodId)) return;

		$terminal = $sessionService->get('terminal_location');

		if(!$terminal) return;

		$order->set_shipping_address_1(__("Пункт выдачи: ", WC_ESL_DOMAIN) . $terminal);
	}

	public function saveOrderShipping($item)
	{
		if(
			!is_a($item, '\WC_Order_Item_Shipping') ||
			!$this->methodsIsEshopTerminal($item->get_method_id())
		) return;

		try {
			$sessionService = new SessionService();
			$terminal = $sessionService->get('terminal_location');

			if(!$terminal) return;

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

        if($typeServiceShipping !== 'terminal') return false;

        return true;
    }
}