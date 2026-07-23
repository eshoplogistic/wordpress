<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;

if ( ! defined('ABSPATH') ) {
    exit;
}

class CheckoutValidator implements ModuleInterface
{
    public function init()
    {
        add_action('woocommerce_checkout_process', [$this, 'validateFields']);
        add_filter('woocommerce_checkout_fields', [$this, 'removeDefaultFieldsFromValidation'], 99);

        // WooCommerce Blocks / Store API: аналог validateFields() для блочного чекаута.
        // Приоритет 5, чтобы отработать раньше OrderCreator::processBlocksOrder (10) —
        // при выброшенном исключении оставшиеся колбэки этого хука не выполняются,
        // заказ не создаётся лишний раз без выбранного ПВЗ.
        add_action('woocommerce_store_api_checkout_order_processed', [$this, 'validateBlocksOrder'], 5, 1);

        add_filter('default_checkout_billing_address_1', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_billing_address_2', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_billing_city', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_billing_state', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_billing_postcode', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_billing_city', [$this, 'clearCheckoutField'], 10, 2);

        add_filter('default_checkout_shipping_address_1', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_shipping_address_2', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_shipping_city', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_shipping_state', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_shipping_postcode', [$this, 'clearCheckoutField'], 10, 2);
        add_filter('default_checkout_shipping_city', [$this, 'clearCheckoutField'], 10, 2);
    }

    public function validateFields()
    {
        $this->chosenShippingMethodsIsEshopSelected();

        if(!$this->chosenShippingMethodsIsEshopTerminal()) return;

        $type = $this->getTypeToValidate();

        $this->validateTerminalField($type);
    }

    /**
     * @param \WC_Order $order
     */
    public function validateBlocksOrder($order)
    {
        $optionsRepository = new OptionsRepository();
        $checkDelivery = $optionsRepository->getOption('wc_esl_shipping_add_form');
        if (isset($checkDelivery['checkDelivery']) && $checkDelivery['checkDelivery'] === 'true') return;

        $shippingMethodId = null;
        foreach ($order->get_items('shipping') as $item) {
            $shippingMethodId = $item->get_method_id();
        }

        if (!$shippingMethodId) return;

        $orderCreator = new OrderCreator();
        if (!$orderCreator->methodsIsEshopTerminal($shippingMethodId)) return;

        $sessionService = new SessionService();
        $terminal = $orderCreator->getTerminalLocation($sessionService);

        if ('' === $terminal) {
            throw new RouteException(
                'esl_terminal_required',
                __('Пункт выдачи доставки является обязательным полем.', 'eshoplogisticru'), // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- static translated string, no user input.
                400
            );
        }
    }

    private function validateTerminalField($mode) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checkout flow handles nonce verification.
        $shippingMethodPosted = isset($_POST['shipping_method'][0]) ? sanitize_text_field(wp_unslash($_POST['shipping_method'][0])) : '';
        $check = $shippingMethodPosted ?: false;

        $terminalFieldKey = 'wc_esl_' . $mode . '_terminal';
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checkout flow handles nonce verification.
        $terminalPosted = isset($_POST[$terminalFieldKey]) ? sanitize_text_field(wp_unslash($_POST[$terminalFieldKey])) : '';

        if('' === $terminalPosted) {
            $message = "<strong>Пункт выдачи доставки</strong> является обязательным полем.";

            $this->addErrorNotice($message);
        }
    }

    /**
     * @param array $fields
     * @return array
     */
    public function removeDefaultFieldsFromValidation($fields)
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checkout flow handles nonce verification.
        if ( ! wp_doing_ajax() || empty($_POST)) {
            return $fields;
        }

        if(!$this->chosenShippingMethodsIsEshopTerminal()) return $fields;

        $type = $this->getTypeToValidate();
        unset( $fields[$type][$type . '_address_1'] );
        unset( $fields[$type][$type . '_address_2'] );

        return $fields;
    }

    /**
     * @param mixed $value
     * @param string $input
     * 
     * @return mixed
     */
    public function clearCheckoutField($value, $input)
    {
        $sessionService = new SessionService();

		if($input == 'billing_city' && !$value){
			$shippingHelper = new ShippingHelper();
			$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
			$ip = $shippingHelper->get_the_user_ip();
			$ipCity = $eshopLogisticApi->geo($ip);
			if(isset($ipCity[0]))
				$value = $ipCity[0]['name'];
		}
        if($sessionService->get('shipping_methods')) return $value;

        return ($value)?:'';
    }

	private function chosenShippingMethodsIsEshopSelected()
	{
		$optionsRepository = new OptionsRepository();
		$checkDelivery = $optionsRepository->getOption('wc_esl_shipping_add_form');
		if(isset($checkDelivery['checkDelivery']) && $checkDelivery['checkDelivery'] == 'true')
			return false;

		$chosenShippingMethods = isset(WC()->session->get('chosen_shipping_methods')[0]) ? WC()->session->get('chosen_shipping_methods')[0] : '';

		if(!$chosenShippingMethods) return false;

		$explodedAtPrefix = explode(WC_ESL_PREFIX, $chosenShippingMethods);

		if(empty($explodedAtPrefix)) return false;

		$typeServiceShipping = explode('_', $explodedAtPrefix[1]);

		if(!isset($typeServiceShipping[1])) return false;

		$typeServiceShipping = $typeServiceShipping[1];

		if($typeServiceShipping === 'mixed'){
			$sessionService = new SessionService();
			$shippingFrame = $sessionService->get('esl_shipping_frame') ? $sessionService->get('esl_shipping_frame') : 0;
			if(!isset($shippingFrame['name'])){
				$message = "<strong>Выбор доставки</strong> является обязательным условием.";
				$this->addErrorNotice($message);
			}
		}

		return true;
	}

    private function chosenShippingMethodsIsEshopTerminal()
    {
        $chosenShippingMethods = isset(WC()->session->get('chosen_shipping_methods')[0]) ? WC()->session->get('chosen_shipping_methods')[0] : '';

        if(!$chosenShippingMethods) return false;

        $explodedAtPrefix = explode(WC_ESL_PREFIX, $chosenShippingMethods);

        if(empty($explodedAtPrefix)) return false;

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
	    if($serviceShipping === 'postrf') return false;

	    return true;
    }

    /**
     * @return string
     */
    private function getTypeToValidate()
    {
	    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce checkout flow handles nonce verification.
	    $shipToDifferent = isset($_POST['ship_to_different_address']) ? absint(wp_unslash($_POST['ship_to_different_address'])) : 0;
	    if (1 === $shipToDifferent) {
            return 'shipping';
        }

        return 'billing';
    }

    private function addErrorNotice($msg)
    {
        wc_add_notice($msg, 'error');
    }
}