<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Payment implements ModuleInterface
{
    /**
     * @var OptionsRepository $options
     */
    protected $options;

    /**
     * @var ShippingHelper $shippingHelper
     */
    protected $shippingHelper;

    public function __construct()
    {
        $this->options = new OptionsRepository();
        $this->shippingHelper = new ShippingHelper();
    }

	public function init()
    {
        add_filter('woocommerce_available_payment_gateways', [$this, 'filterPaymentGateways'], 9999, 1);
    }

    public function filterPaymentGateways($gateways)
    {
        if(!isset(WC()->session)) return $gateways;

        $chosenShippingMethods = WC()->session->get('chosen_shipping_methods');

        if(!isset($chosenShippingMethods[0])) return $gateways;

        if(!$this->shippingHelper->isEslMethod($chosenShippingMethods[0])) return $gateways;

        $wcEslPaymentMethods = $this->options->getOption('wc_esl_shipping_payment_methods');
        $accountInitServices = $this->options->getOption('wc_esl_shipping_account_init_services');
        $slugCurrentShippingMethod = $this->shippingHelper->getSlugMethod($chosenShippingMethods[0]);

        $paymentsForShippingMethod = isset($accountInitServices[$slugCurrentShippingMethod]['payments']) ? $accountInitServices[$slugCurrentShippingMethod]['payments'] : [];

        if(empty($paymentsForShippingMethod)) return $gateways;

        $newGateways = [];

        foreach($gateways as $key => $gateway) {
            if(!isset($wcEslPaymentMethods[$key])) continue;

            foreach($paymentsForShippingMethod as $payment) {
                if($wcEslPaymentMethods[$key] === $payment['key']) {
                    $newGateways[$key] = $gateway;
	                $newGateways[$key]->description = ($newGateways[$key]->description)?$newGateways[$key]->description.' '.$payment['comment']:$payment['comment'];
	                break;
                }
            }
        }

        return $newGateways;
    }
}