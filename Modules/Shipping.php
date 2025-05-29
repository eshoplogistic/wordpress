<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Classes\View;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;
use eshoplogistic\WCEshopLogistic\Models\CheckoutOrderData;
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
	    $frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');

	    if($frameEnable){
		    $methods[ WC_ESL_PREFIX . 'frame_mixed' ] = 'eshoplogistic\WCEshopLogistic\Classes\Shipping\Methods\\FrameMixed';
	    }elseif(!empty($services)){
		    foreach($services as $serviceKey => $service) {
				$exCustom = explode('-', $serviceKey);
				if($exCustom[0] == 'custom'){
					$serviceKey = 'custom';
				}
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
	    $optionsRepository = new OptionsRepository();
	    $frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');

	    if($frameEnable)
			return $rates;

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

	    $optionsRepository = new OptionsRepository();
	    $apiKeyYa = $optionsRepository->getOption('wc_esl_shipping_api_key_ya');
	    $apiWidgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');

	    $paymentCalc = '';
	    $paymentCalcTmp = $optionsRepository->getOption('wc_esl_shipping_add_form');
	    $addForm = $optionsRepository->getOption('wc_esl_shipping_add_form');
	    $eslBillingCityFields = 'billing_city';
	    $eslShippingCityFields = 'shipping_city';
	    $offAddressCheck = false;
	    if(isset($addForm['billingCity']))
		    $eslBillingCityFields = $addForm['billingCity'];
		if(isset($addForm['shippingCity']))
			$eslShippingCityFields = $addForm['shippingCity'];
		if(isset($addForm['offAddressCheck']))
			$offAddressCheck = $addForm['offAddressCheck'];

	    echo View::render('checkout/add-fields', [
		    'eslBillingCityFields' => $eslBillingCityFields,
		    'eslShippingCityFields' => $eslShippingCityFields,
		    'offAddressCheck' =>  $offAddressCheck
	    ]);

	    if(isset($paymentCalcTmp['paymentCalc']) && $paymentCalcTmp['paymentCalc'] == 'true')
		    $paymentCalc = $paymentCalcTmp['paymentCalc'];

        if(isset($chosenShippingMethods[0])) {
            $typeMethod = $shippingHelper->getTypeMethod($chosenShippingMethods[0]);

            if($typeMethod === 'terminal') {
                $stateShippingMethods = $sessionService->get('shipping_methods');

                $terminals = isset($stateShippingMethods[$chosenShippingMethods[0]]['terminals']) ? $stateShippingMethods[$chosenShippingMethods[0]]['terminals'] : null;

                if(!is_null($terminals)) {
                    echo View::render('checkout/terminals-input', ['terminals' => json_encode($terminals), 'key_ya' => $apiKeyYa]);
                }
            }
	        if($typeMethod === 'mixed') {
		        $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());

		        $mode = $sessionService->get('mode_shipping');
		        //$sessionService->drop($mode);

		        if($mode == 'shipping'){
			        $city = WC()->customer->get_shipping_city();
			        $region = WC()->customer->get_shipping_state();
		        }else{
			        $city = WC()->customer->get_billing_city();
                    $region = WC()->customer->get_billing_state();
                }

		        $widgetCityEsl = $sessionService->get($mode) ? $sessionService->get($mode) : [];
		        $widgetCityEslName = isset($widgetCityEsl['name'])?mb_strtolower($widgetCityEsl['name']):'';
				if(!$widgetCityEsl || ($city && mb_strtolower($city) != $widgetCityEslName)){
					$widgetCityEsl = [[]];

					$searchDefault = '';
					if($city){
						$searchDefault = $eshopLogisticApi->search($city, '', $region);
						$searchDefault = $searchDefault->data();
					}

					if(isset($searchDefault[0])){
						$ipCity = $searchDefault;
					}else{
						$ip = $shippingHelper->get_the_user_ip();
						$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
						$ipCity = $eshopLogisticApi->geo($ip);
					}

					if(isset($ipCity[0])){
						$widgetCityEsl = $ipCity[0];
						$widgetCityEsl['city'] = $widgetCityEsl['name'];
						$widgetCityEsl['postcode'] = $widgetCityEsl['postal_code'];
						$sessionService->set($mode, $widgetCityEsl);
					}
				}


		        $widgetOffersEsl = self::infoCart();
		        $paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');

		        echo View::render('checkout/frame-input', [
					'widgetKey' => $apiWidgetKey, 'widgetOffersEsl' => $widgetOffersEsl,
					'paymentMethods' => $paymentMethods, 'widgetCityEsl' => $widgetCityEsl,
			        'paymentCalc' => $paymentCalc
		        ]);
	        }
        }
    }

	public function infoCart()
	{
		$items = WC()->cart->get_cart_contents();
		$data = new CheckoutOrderData($items);
		$offers = array();

		if($data->getItems()) {
			foreach($data->getItems() as $item) {
				$offers[] = array(
					'article' => $item->getArticle(),
					'name' => $item->getName(),
					'count' => $item->getQuantity(),
					'price' => $item->getPrice(),
					'weight' => $item->getWeight(),
					'dimensions' => $item->getDimensions(),
				);
			}
		}

		$offers = apply_filters( 'esl_offers_filter', $offers );

		return $offers;
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

        //if(isset($stateShippingMethods[$item->method_id]['time'])) {
            //echo View::render('checkout/time', ['time' => $stateShippingMethods[$item->method_id]['time']]);
        //}

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