<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined('ABSPATH') ) {
    exit;
}

class CheckoutValidator implements ModuleInterface
{
    public function init()
    {
        add_action('woocommerce_checkout_process', [$this, 'validateFields']);
        add_filter('woocommerce_checkout_fields', [$this, 'removeDefaultFieldsFromValidation'], 99);

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
        if(!$this->chosenShippingMethodsIsEshopTerminal()) return;

        $type = $this->getTypeToValidate();

        $this->validateTerminalField($type);
    }

    private function validateTerminalField($mode) {
        if(empty($_POST['wc_esl_'. $mode .'_terminal'])) {
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

        if($sessionService->get('shipping_methods')) return $value;

        return '';
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

        if($typeServiceShipping !== 'terminal') return false;

        return true;
    }

    /**
     * @return string
     */
    private function getTypeToValidate()
    {
        if (isset($_POST['ship_to_different_address']) && 1 === (int)$_POST['ship_to_different_address']) {
            return 'shipping';
        }

        return 'billing';
    }

    private function addErrorNotice($msg)
    {
        wc_add_notice(__($msg, WC_ESL_DOMAIN), 'error');
    }
}