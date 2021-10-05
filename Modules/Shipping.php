<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Classes\View;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Shipping implements ModuleInterface
{
	public function init()
    {
        add_filter( 'woocommerce_shipping_methods', [ $this, 'registerShippingMethods' ] );
        add_filter( 'woocommerce_package_rates', [ $this, 'packageRatesFilter' ], 10, 2 );
        add_action( 'woocommerce_review_order_after_shipping', [ $this, 'addTerminalsInput' ] );
        add_action( 'woocommerce_after_shipping_rate', [ $this, 'infoShippingItem' ] );
    }

    public function registerShippingMethods($methods)
    {
        $optionsRepository = new OptionsRepository();

        $services = $optionsRepository->getOption('wc_esl_shipping_account_services');

        if(!empty($services)) {
            foreach($services as $serviceKey => $service) {
                if($service['door'] == '1') {
                    $methods[ WC_ESL_PREFIX . strtolower($serviceKey) . '_door' ] = 'eshoplogistic\WCEshopLogistic\Classes\Shipping\Methods\\' . ucfirst(strtolower($serviceKey)) . 'Door';
                }

                if($service['terminal'] == '1') {
                    $methods[ WC_ESL_PREFIX . strtolower($serviceKey) . '_terminal' ] = 'eshoplogistic\WCEshopLogistic\Classes\Shipping\Methods\\' . ucfirst(strtolower($serviceKey)) . 'Terminal';
                }
            }
        }

        return $methods;
    }

    public function packageRatesFilter($rates, $package)
    {
        $sessionService = new SessionService();
        $shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];

        $newRates = [];

        foreach($rates as $key => $rate) {
            if(
                isset($shippingMethods[$key]) ||
                (count(explode(WC_ESL_PREFIX, $key)) < 2)
            ) {
                $newRates[$key] = $rate;
            }
        }

        return $newRates;
    }

    public function addTerminalsInput()
    {
        $shippingHelper = new ShippingHelper();
        $chosenShippingMethods = WC()->session->get( 'chosen_shipping_methods' );
        $sessionService = new SessionService();

        if(isset($chosenShippingMethods[0])) {
            $typeMethod = $shippingHelper->getTypeMethod($chosenShippingMethods[0]);

            if($typeMethod === 'terminal') {
                $stateShippingMethods = $sessionService->get('shipping_methods');

                $terminals = isset($stateShippingMethods[$chosenShippingMethods[0]]['terminals']) ? $stateShippingMethods[$chosenShippingMethods[0]]['terminals'] : null;

                if(!is_null($terminals)) {
                    echo View::render('checkout/terminals-input', ['terminals' => json_encode($terminals)]);
                }
            }
        }
    }

    public function infoShippingItem($item)
    {
        $shippingHelper = new ShippingHelper();
        if(!$shippingHelper->isEslMethod($item->method_id)) return;

        $sessionService = new SessionService();
        $optionsRepository = new OptionsRepository();

        $stateShippingMethods = $sessionService->get('shipping_methods');
        $accountInitServices = $optionsRepository->getOption('wc_esl_shipping_account_init_services');

        if(
            isset($stateShippingMethods[$item->method_id]['price']) &&
            $stateShippingMethods[$item->method_id]['price'] === 0
        ) echo ': ' . wc_price(0);

        if(isset($stateShippingMethods[$item->method_id]['time'])) {
            echo View::render('checkout/time', ['time' => $stateShippingMethods[$item->method_id]['time']]);
        }

        if(isset($accountInitServices[$shippingHelper->getSlugMethod($item->method_id)]['comment'])) {
            echo View::render(
                'checkout/general-comment',
                [
                    'comment' => $accountInitServices[$shippingHelper->getSlugMethod($item->method_id)]['comment']
                ]
            );
        }

        if(isset($stateShippingMethods[$item->method_id]['comment'])) {
            echo View::render(
                'checkout/comment',
                [
                    'comment' => $stateShippingMethods[$item->method_id]['comment']
                ]
            );
        }
    }
}