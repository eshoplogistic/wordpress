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

	    if ($frameEnable) {
	        return $this->applyFrameRatesForBlocks($rates, $package);
	    }

	    return $this->filterRatesForLegacy($rates, $package);
    }

    /**
     * Apply frame-selected shipping label and cost to rates for Blocks checkout.
     * 
     * CONTEXT: WooCommerce Blocks - used when frame_enable is true.
     * - Reads esl_shipping_frame from session (contains user widget selection)
     * - Synchronizes frame label/cost to wc_esl_frame_mixed rate object
     * - Forces rate recalculation with updated label for Blocks UI
     * 
     * @param array $rates Current shipping rates
     * @param array $package Cart package data
     * @return array Modified rates with frame selection applied
     */
    private function applyFrameRatesForBlocks($rates, $package)
    {
        $sessionService = new SessionService();
        $shippingFrame = $sessionService->get('esl_shipping_frame');

        if (is_string($shippingFrame)) {
            $shippingFrame = maybe_unserialize($shippingFrame);
        }

        if (!is_array($shippingFrame) || empty($shippingFrame['name'])) {
            return $rates;
        }

        $optionsRepository = new OptionsRepository();
        $nameDelivery = [
            'terminal' => 'пункт выдачи заказа',
            'door' => 'курьер',
        ];

        $labelTitle = (string) $shippingFrame['name'];
        if (!empty($shippingFrame['mode']) && isset($nameDelivery[$shippingFrame['mode']])) {
            $labelTitle .= ' - ' . $nameDelivery[$shippingFrame['mode']];
        }

        if (!empty($shippingFrame['time'])) {
            $labelTitle .= '. Срок доставки - ' . $shippingFrame['time'];
        }

        $cost = 0;
        if (isset($shippingFrame['price']) && is_array($shippingFrame['price']) && isset($shippingFrame['price']['value'])) {
            $cost = (float) $shippingFrame['price']['value'];
        }

        $pluginEnableShippingPrice = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_price_shipping');
        if ($pluginEnableShippingPrice) {
            if ($cost === 0.0) {
                $labelTitle .= ': Бесплатно';
            }
        } else {
            $currencyCode = get_woocommerce_currency();
            $currencySymbol = get_woocommerce_currency_symbol($currencyCode);
            $labelTitle = str_replace(':', ' -', $labelTitle) . ' - ' . $cost . ' ' . $currencySymbol;
        }

        foreach ($rates as $rateKey => $rate) {
            if (strpos((string) $rateKey, WC_ESL_PREFIX . 'frame_mixed') === false) {
                continue;
            }

            if (is_object($rate)) {
                if (method_exists($rate, 'set_label')) {
                    $rate->set_label($labelTitle);
                }

                if (method_exists($rate, 'set_cost')) {
                    $rate->set_cost($pluginEnableShippingPrice ? $cost : 0);
                }

                $rates[$rateKey] = $rate;
            }
        }

        return $rates;
    }

    /**
     * Filter shipping rates for legacy checkout flow.
     * 
     * CONTEXT: Legacy (non-Blocks) checkout - used when frame_enable is false.
     * - Keeps only rates that were calculated by this plugin via shipping_methods session
     * - Also keeps non-eShopLogistic rates (where prefix count < 2) for compatibility
     * - Legacy checkout handles rate calculations via direct session state
     * 
     * @param array $rates Current shipping rates
     * @param array $package Cart package data
     * @return array Filtered rates with only relevant methods
     */
    private function filterRatesForLegacy($rates, $package)
    {
        $sessionService = new SessionService();
        $shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];

        $newRates = [];

        foreach ($rates as $key => $rate) {
            if (
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

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
		echo View::render('checkout/add-fields', [
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'wc_esl_eslBillingCityFields' => $eslBillingCityFields,
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'wc_esl_eslShippingCityFields' => $eslShippingCityFields,
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'wc_esl_offAddressCheck' => $offAddressCheck
		]);

	    if(isset($paymentCalcTmp['paymentCalc']) && $paymentCalcTmp['paymentCalc'] == 'true')
		    $paymentCalc = $paymentCalcTmp['paymentCalc'];

        if(isset($chosenShippingMethods[0])) {
            $typeMethod = $shippingHelper->getTypeMethod($chosenShippingMethods[0]);

            if($typeMethod === 'terminal') {
                $stateShippingMethods = $sessionService->get('shipping_methods');

                $terminals = isset($stateShippingMethods[$chosenShippingMethods[0]]['terminals']) ? $stateShippingMethods[$chosenShippingMethods[0]]['terminals'] : null;

                if(!is_null($terminals)) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
					echo View::render('checkout/terminals-input', [
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_terminals' => json_encode($terminals),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_key_ya' => $apiKeyYa
					]);
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

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
				echo View::render('checkout/frame-input', [
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_widgetKey' => $apiWidgetKey,
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_widgetOffersEsl' => $widgetOffersEsl,
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_paymentMethods' => $paymentMethods,
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_widgetCityEsl' => $widgetCityEsl,
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_paymentCalc' => $paymentCalc
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

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook name retained for backwards compatibility.
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
		) echo ': ' . esc_html(wc_price(0));

        //if(isset($stateShippingMethods[$item->method_id]['time'])) {
            //echo View::render('checkout/time', ['time' => $stateShippingMethods[$item->method_id]['time']]);
        //}

		if(isset($accountInitServices[$shippingHelper->getSlugMethod($item->method_id)]['comment'])) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
			echo View::render(
				'checkout/general-comment',
				[
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_comment' => $accountInitServices[$shippingHelper->getSlugMethod($item->method_id)]['comment']
				]
			);
		}

		if(isset($stateShippingMethods[$item->method_id]['comment'])) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
			echo View::render(
				'checkout/comment',
				[
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					'wc_esl_comment' => $stateShippingMethods[$item->method_id]['comment']
				]
			);
		}
    }
}