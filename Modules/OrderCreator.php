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

		// WooCommerce Blocks / Store API: срабатывает после полного создания заказа и сохранения позиций.
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'processBlocksOrder'], 10, 1);
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

		$terminal = $this->getTerminalLocation($sessionService);

		if(!$terminal) return;

		$order->set_shipping_address_1(__("Пункт выдачи: ", 'eshoplogisticru') . $terminal);
	}

	public function saveOrderShipping($item)
	{
		if(
			!is_a($item, '\WC_Order_Item_Shipping')
		) return;

		try {
			$sessionService = new SessionService();
			$terminal = $this->getTerminalLocation($sessionService);

			$shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];
			$shippingMethodId = $item->get_method_id();

			if( isset( $shippingMethods[$shippingMethodId] ) ) {
				unset($shippingMethods[$shippingMethodId]['terminals']);
				$jsonArr = json_encode( $shippingMethods[$shippingMethodId], JSON_UNESCAPED_UNICODE);
				$item->update_meta_data('esl_shipping_methods', $jsonArr);
				if($terminal){
					if(isset($shippingMethods[$shippingMethodId]['data']['terminal']['time'])){
						$timeVal = $shippingMethods[$shippingMethodId]['data']['terminal']['time']['value'] ?? '';
						$timeUnit = $shippingMethods[$shippingMethodId]['data']['terminal']['time']['unit'] ?? '';
						$timeText = $shippingMethods[$shippingMethodId]['data']['terminal']['time']['text'] ?? '';
						$time = $timeVal.' '.$timeUnit.' - '.$timeText;
						$item->update_meta_data(__("Срок доставки", 'eshoplogisticru'), $time);
					}
					if(isset($shippingMethods[$shippingMethodId]['time'])){
						$timeVal = $shippingMethods[$shippingMethodId]['time']['value'] ?? '';
						$timeUnit = $shippingMethods[$shippingMethodId]['time']['unit'] ?? '';
						$timeText = $shippingMethods[$shippingMethodId]['time']['text'] ?? '';
						$time = $timeVal.' '.$timeUnit.' - '.$timeText;
						$item->update_meta_data(__("Срок доставки", 'eshoplogisticru'), $time);
					}
				}else{
					if(isset($shippingMethods[$shippingMethodId]['data']['door']['time'])){
						$timeVal = $shippingMethods[$shippingMethodId]['data']['door']['time']['value'] ?? '';
						$timeUnit = $shippingMethods[$shippingMethodId]['data']['door']['time']['unit'] ?? '';
						$timeText = $shippingMethods[$shippingMethodId]['data']['door']['time']['text'] ?? '';
						$time = $timeVal.' '.$timeUnit.' - '.$timeText;
						$item->update_meta_data(__("Срок доставки", 'eshoplogisticru'), $time);
					}
					if(isset($shippingMethods[$shippingMethodId]['time'])){
						$timeVal = $shippingMethods[$shippingMethodId]['time']['value'] ?? '';
						$timeUnit = $shippingMethods[$shippingMethodId]['time']['unit'] ?? '';
						$timeText = $shippingMethods[$shippingMethodId]['time']['text'] ?? '';
						$time = $timeVal.' '.$timeUnit.' - '.$timeText;
						$item->update_meta_data(__("Срок доставки", 'eshoplogisticru'), $time);
					}
				}
			}
			$sessionService->drop('shipping_methods');

			if(!$terminal || !$this->methodsIsEshopTerminal($item->get_method_id())) return;

			$item->update_meta_data(__("Пункт выдачи", 'eshoplogisticru'), $terminal);
		} catch(\Exception $e) {
			return;
		}
	}

	/**
	 * Обрабатывает создание заказа через WooCommerce Blocks / Store API.
	 * Эквивалентно createOrder + saveOrderShipping, но вызывается после сохранения всех позиций.
	 */
	public function processBlocksOrder( $order ) {
		$sessionService   = new SessionService();
		$terminal         = $this->getTerminalLocation( $sessionService );
		$shippingMethods  = $sessionService->get( 'shipping_methods' ) ?: [];

		$shippingMethodId = null;
		foreach ( $order->get_items( 'shipping' ) as $item ) {
			$shippingMethodId = $item->get_method_id();

			// Мета-данные для позиции доставки (Срок доставки, Пункт выдачи).
			if ( isset( $shippingMethods[ $shippingMethodId ] ) ) {
				$methodData = $shippingMethods[ $shippingMethodId ];
				unset( $methodData['terminals'] );
				$item->update_meta_data( 'esl_shipping_methods', json_encode( $methodData, JSON_UNESCAPED_UNICODE ) );

				$timeKey = $terminal ? 'terminal' : 'door';
				$timeData = $methodData['data'][ $timeKey ]['time'] ?? $methodData['time'] ?? null;
				if ( $timeData ) {
					$timeVal  = $timeData['value'] ?? '';
					$timeUnit = $timeData['unit']  ?? '';
					$timeText = $timeData['text']  ?? '';
					$item->update_meta_data( __( 'Срок доставки', 'eshoplogisticru' ), "{$timeVal} {$timeUnit} - {$timeText}" );
				}
			}

			if ( $terminal && $this->methodsIsEshopTerminal( $shippingMethodId ) ) {
				$item->update_meta_data( __( 'Пункт выдачи', 'eshoplogisticru' ), $terminal );
			}

			$item->save();
		}

		$sessionService->drop( 'shipping_methods' );

		// Адрес доставки — как в createOrder.
		if ( $terminal && $shippingMethodId && $this->methodsIsEshopTerminal( $shippingMethodId ) ) {
			$order->set_shipping_address_1( __( 'Пункт выдачи: ', 'eshoplogisticru' ) . $terminal );
			$order->save();
		}
	}

	public function getTerminalLocation(SessionService $sessionService)
	{
		$terminal = $sessionService->get('terminal_location');
		if (is_string($terminal) && '' !== trim($terminal)) {
			return $terminal;
		}

		$shippingFrame = $sessionService->get('esl_shipping_frame');
		if (is_string($shippingFrame)) {
			$shippingFrame = maybe_unserialize($shippingFrame);
		}

		if (!is_array($shippingFrame)) {
			return '';
		}

		$terminalAddress = isset($shippingFrame['terminalAddress']) ? trim((string) $shippingFrame['terminalAddress']) : '';
		$terminalCode = isset($shippingFrame['terminalCode']) ? trim((string) $shippingFrame['terminalCode']) : '';

		if ('' === $terminalAddress && isset($shippingFrame['terminal']) && is_array($shippingFrame['terminal'])) {
			$terminalAddress = isset($shippingFrame['terminal']['address']) ? trim((string) $shippingFrame['terminal']['address']) : '';
			$terminalCode = isset($shippingFrame['terminal']['code']) ? trim((string) $shippingFrame['terminal']['code']) : $terminalCode;
		}

		if ('' === $terminalAddress && isset($shippingFrame['pvz']) && is_array($shippingFrame['pvz'])) {
			$terminalAddress = isset($shippingFrame['pvz']['address']) ? trim((string) $shippingFrame['pvz']['address']) : '';
			$terminalCode = isset($shippingFrame['pvz']['code']) ? trim((string) $shippingFrame['pvz']['code']) : $terminalCode;
		}

		if ('' !== $terminalAddress && '' !== $terminalCode) {
			return $terminalAddress . '. Код пункта: ' . $terminalCode;
		}

		if ('' !== $terminalAddress) {
			return $terminalAddress;
		}

		$mode = isset($shippingFrame['mode']) ? strtolower(trim((string) $shippingFrame['mode'])) : '';
		$isTerminalMode = in_array($mode, ['terminal', 'pickup', 'pvz', 'point'], true);
		if (!$isTerminalMode) {
			return '';
		}

		$frameAddress = isset($shippingFrame['address']) ? trim((string) $shippingFrame['address']) : '';
		if ('' === $frameAddress) {
			return '';
		}

		$addressParts = preg_split('/\s+/', $frameAddress, 2);
		if (is_array($addressParts) && 2 === count($addressParts) && '' !== trim($addressParts[0]) && '' !== trim($addressParts[1])) {
			return trim($addressParts[1]) . '. Код пункта: ' . trim($addressParts[0]);
		}

		return $frameAddress;
	}

	public function methodsIsEshopTerminal($methodId)
    {
        $explodedAtPrefix = explode(WC_ESL_PREFIX, $methodId);

		if (!isset($explodedAtPrefix[1]) || '' === $explodedAtPrefix[1]) return false;

        $typeServiceShipping = explode('_', $explodedAtPrefix[1]);

        if(!isset($typeServiceShipping[1])) return false;

        $serviceShipping = $typeServiceShipping[0];
        $typeServiceShipping = $typeServiceShipping[1];

		if($typeServiceShipping === 'mixed'){
			$sessionService = new SessionService();
			$shippingFrame = $sessionService->get('esl_shipping_frame') ? $sessionService->get('esl_shipping_frame') : 0;
			if(isset($shippingFrame['mode']) && $shippingFrame['mode'] == 'terminal'){
				return true;
			}
			return false;
		}
        if($typeServiceShipping !== 'terminal') return false;

        return true;
    }
}