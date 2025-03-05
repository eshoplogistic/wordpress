<?php

namespace eshoplogistic\WCEshopLogistic\Classes\Shipping;

use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;
use eshoplogistic\WCEshopLogistic\Modules\Shipping;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\Services\CalculationService;
use eshoplogistic\WCEshopLogistic\Models\CheckoutOrderData;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Helpers\ConflictPluginsHelper;

class Base extends \WC_Shipping_Method
{
	protected $city_code;

	protected $type;

	/**
	 * Constructor for your shipping class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $instance_id = 0 )
	{
		parent::__construct( $instance_id );

		$this->supports          = array(
			'shipping-zones',
			'instance-settings',
			'instance-settings-modal',
		);
	}

	/**
	 * Always return shipping method is available
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	public function is_available( $package )
	{
		$is_available = true;
		return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
	}

	/**
	 * Init your settings
	 *
	 * @access public
	 * @return void
	 */
	function init()
	{
		// Load the settings API
		$this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
		$this->init_settings(); // This is part of the settings API. Loads settings you previously init.

		// Save settings in admin if you have any defined
		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Define settings field for this shipping
	 *
	 * @return void
	 */
	function init_form_fields() {
		$shippingHelper = new ShippingHelper();
		$slug = $shippingHelper->getSlugMethod($this->id);
		$options = $this->getOptionMethod($slug);
		$typeMethod = $shippingHelper->getTypeMethod($this->id);

		$typeMethodTitle = ($typeMethod === 'terminal') ? 'Доставка до пункта выдачи' : 'Доставка курьером';
		$defaultTitle = isset( $options['name'] ) ? $options['name'] . ': ' . $typeMethodTitle : '';

		$formFields = array(
			'title' => array(
				'title' => __('Название', WC_ESL_DOMAIN),
				'type' => 'text',
				'description' => __('Вы можете изменить название метода доставки, которое будет отображаться пользователям', WC_ESL_DOMAIN),
				'default' => $defaultTitle
			),
		);

		if($slug == 'custom'){
			$option = $this->getOptionMethod($slug);
			$optionsRepository = new OptionsRepository();
			$services = $optionsRepository->getOption('wc_esl_shipping_account_services');
			$optionCustom = array();
			foreach($services as $key => $service){
				$exCustom = explode('-', $key);
				if($exCustom[0] == 'custom' && $service[$typeMethod]){
					$optionCustom[$key] = $service['name'];
				}
			}
			$formFields['custom'] = array(
				'title' => __('Кастомная доставка', WC_ESL_DOMAIN),
				'type' => 'select',
				'options' => $optionCustom,
				'description' => __('Выберите тип кастомной доставки, которую вы создали в кабинете eShopLogistic', WC_ESL_DOMAIN),
			);
		}

		$this->instance_form_fields = $formFields;
	}

	/**
	 * calculate_shipping function.
	 *
	 * @access public
	 * @param mixed $package
	 * @return void
	 */
	public function calculate_shipping( $package = array() )
	{
		if(is_checkout()){
			$optionsRepository = new OptionsRepository();
			$frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');
			if($frameEnable){
				$rate = $this->calculate_shipping_frame($package);
			}else{
				$rate = $this->calculate_shipping_basic($package);
			}

			if($rate)
				$this->add_rate( $rate );
		}

	}

	public function calculate_shipping_basic($package): array {
		$cost = 0;
		$service = $this->getSlug();
		$sessionService = new SessionService();
		$optionsRepository = new OptionsRepository();
		$shippingHelper = new ShippingHelper();

		$accountServices = $optionsRepository->getOption('wc_esl_shipping_account_services');

		$paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');
		$payment = isset($paymentMethods[WC()->session->chosen_payment_method]) ? $paymentMethods[WC()->session->chosen_payment_method] : '';

		$postRequest = [];
		$postData = isset($_POST['post_data']) ? wc_clean($_POST['post_data']) : '';
		parse_str($postData, $postRequest);

		$mode = 'billing';
		if(isset($postRequest['ship_to_different_address'])) $mode = 'shipping';

		if(isset($_POST['calc_shipping'])) $mode = 'billing';

		$modeState = $sessionService->get($mode) ? $sessionService->get($mode) : [];
		$avalaibleServices = isset($modeState['services']) ? $modeState['services'] : [];
		if($mode === 'shipping'){
			$adress = isset($postRequest['shipping_address_1']) ? $postRequest['shipping_address_1'] : '';
		}else{
			$adress = isset($postRequest['billing_address_1']) ? $postRequest['billing_address_1'] : '';
		}
		$cityName = isset($modeState['city']) ? $modeState['city'] : '';
		$cityFias = isset($modeState['fias']) ? $modeState['fias'] : '';

		$apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
		$cityFrom = isset($accountServices[$service]['city_code']) ? $accountServices[$service]['city_code'] : '';
		$cityTo = isset($avalaibleServices[$service]) ? $avalaibleServices[$service] : $cityFias;
		$shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];

		$deliveryMethods = $sessionService->get($mode) ? $sessionService->get($mode) : [];
		$shippingMethodsRequest = isset($postRequest['shipping_method0']) ? $postRequest['shipping_method0'] : '';

		$adressRequired = $shippingHelper->getAdressRequired($service, $shippingMethodsRequest);

		if(!$cityTo){
			$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
			$wpCity = isset($package['destination']['city'])?$package['destination']['city']:'';
			$searchDefault = $eshopLogisticApi->search($wpCity);
			$searchDefault = $searchDefault->data();

			if(isset($searchDefault[0]['fias'])){
				$cityTo = $searchDefault[0]['fias'];
				$cityName = $searchDefault[0]['name'];
			}else{
				$ip = $shippingHelper->get_the_user_ip();
				$ipCity = $eshopLogisticApi->geo($ip);
				if(isset($ipCity[0])){
					$cityToTmp = $ipCity[0];
					$cityTo = isset($cityToTmp['services'][$service]) ? $cityToTmp['services'][$service] : $cityToTmp['fias'];
					$cityName = $ipCity[0]['name'];
				}
			}
		}

		$logger = new \WC_Logger();

		try {
			if(!$apiKey) throw new \Exception(__("API ключ не установлен", WC_ESL_DOMAIN));
			//if(!$payment) throw new \Exception(__("Метод оплаты не установлен", WC_ESL_DOMAIN));
			//if(!$cityFrom) throw new \Exception(__("Город отправки не установлен", WC_ESL_DOMAIN));
			if(!$cityTo) throw new \Exception(__("Город доставки не установлен", WC_ESL_DOMAIN));

			$data = new CheckoutOrderData($package['contents']);

			if($service == 'custom'){
				$settingsService = $this->instance_settings;
				if(isset($settingsService['custom']) && $settingsService['custom']){
					$service = $settingsService['custom'];
					$this->id = WC_ESL_PREFIX . $settingsService['custom']. '_' . $this->type;
				}
			}

			$cacheKey = str_replace(
				' ',
				'_',
				WC_ESL_PREFIX . $data->getHash() . '_' . $apiKey . '_' . $cityTo . '_' . $cityFrom . '_' . $payment . '_' . $service
			);

			$response = get_transient($cacheKey);

			if(false === $response || (isset($adressRequired['adress_required']) && $adressRequired['adress_required'])) {
				$calculationService = new CalculationService();

				$response = $calculationService->calculate(
					$service,
					$data,
					$cityFrom,
					$cityTo,
					$payment,
					$adress,
					$cityName
				);

				set_transient($cacheKey, $response, HOUR_IN_SECONDS);
			}

			if(
				isset($response[$this->getType()]) &&
				!empty($response[$this->getType()])
			) {
				$shippingMethods[$this->id] = $response[$this->getType()];
				$shippingMethods[$this->id]['debug'] = ( $response['debug'] ?? [] );
				$cost = isset($response[$this->getType()]['price']) ? $response[$this->getType()]['price'] : 0;
				if(is_array($cost))
					$cost = isset($response[$this->getType()]['price']['value']) ? $response[$this->getType()]['price']['value'] : $cost;

				//Костыль для оброботки конфликтов с другими плагинами(WOOCS)
				$conflict = new ConflictPluginsHelper();
				$cost = $conflict->init($cost);

				switch($this->getType()) {
					case 'terminal' && isset($response['terminals']):
						$shippingMethods[$this->id]['terminals'] = $response['terminals'];
						break;

					case 'door':
						break;

					default:
						break;
				}

				if(isset($response['comments']) && is_string($response['comments'])) {
					$shippingMethods[$this->id]['comments'] = ($shippingMethods[$this->id]['comments'])?$shippingMethods[$this->id]['comments'].' '.$response['comments']:$response['comments'];
				}
			} else {
				unset($shippingMethods[$this->id]);
			}
		} catch(\Exception $e) {
			unset($shippingMethods[$this->id]);

			$logger->debug($e->getMessage());
		}

		$sessionService->set('shipping_methods', $shippingMethods);

		$labelTitle =  str_replace(':',' -',$this->title);
		$labelDescription = $this->method_description;
		$pluginEnableShippingPrice = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_price_shipping');
		if(!$pluginEnableShippingPrice){
			$currency_code = get_woocommerce_currency();
			$currency_symbol = get_woocommerce_currency_symbol( $currency_code );
			$labelTitle = str_replace(':',' -',$this->title).' - '.$cost. ' '.$currency_symbol;
			$cost = 0;
		}
		if(isset($adressRequired['adress_required']) && $adressRequired['adress_required'] && !$adressRequired['current']){
			$cost = 0;
			$labelTitle = str_replace(':',' -',$this->title).' - '.$labelDescription;
		}

		if($cost === 0)
			$labelTitle = $labelTitle.': Бесплатно';

		if(isset($shippingMethods[$this->id]['time']['value']))
			$labelTitle = $labelTitle.'. Срок доставки - '.$shippingMethods[$this->id]['time']['value'].' '.$shippingMethods[$this->id]['time']['unit'];

		$rate = array(
			'id' => $this->id,
			'label' => $labelTitle,
			'cost' => $cost,
			'package' => $package
		);

		return $rate;
	}

	public function calculate_shipping_frame($package){

		$nameDelivery = [
			'terminal' => 'пункт выдачи заказа',
			'door' => 'курьер'
		];
		$sessionService = new SessionService();
		$optionsRepository = new OptionsRepository();

		$postRequest = [];
		$postData = isset($_POST['post_data']) ? wc_clean($_POST['post_data']) : '';
		parse_str($postData, $postRequest);

		$mode = 'billing';
		if(isset($postRequest['ship_to_different_address'])) $mode = 'shipping';

		if(isset($_POST['calc_shipping'])) $mode = 'billing';

		$sessionService->set('mode_shipping', $mode);
		$shippingFrame = $sessionService->get('esl_shipping_frame') ? $sessionService->get('esl_shipping_frame') : 0;
		$widgetCityEsl = $sessionService->get($mode) ? $sessionService->get($mode) : '';

		$widgetPaymentSelected = $sessionService->get('esl_shipping_selected_payment') ? $sessionService->get('esl_shipping_selected_payment') : '';
		$sessionService->set('esl_shipping_selected_payment', ( $postRequest['payment_method'] ?? '' ));
		$paymentCalc = '';
		$paymentCalcTmp = $optionsRepository->getOption('wc_esl_shipping_add_form');
		if(isset($paymentCalcTmp['paymentCalc']) && $paymentCalcTmp['paymentCalc'] == 'true')
			$paymentCalc = $paymentCalcTmp['paymentCalc'];

		if($paymentCalc == 'true' && $widgetPaymentSelected && isset($postRequest['payment_method']) && $postRequest['payment_method'] && $postRequest['payment_method'] !== $widgetPaymentSelected){
			$shippingFrame = '';
			$sessionService->set('esl_shipping_frame', '');
		}

		$cost = 0;
		if(isset($this->instance_settings['title']) && $this->instance_settings['title']){
			$labelTitle = $this->instance_settings['title'];
		}else{
			$labelTitle = $this->title;
		}

		$pluginEnableShippingPrice = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_price_shipping');
		if($shippingFrame && isset($widgetCityEsl['city']) && $widgetCityEsl['city'] == $shippingFrame['city']){
			if(isset($shippingFrame['name']) && isset($shippingFrame['price'])){
				$labelTitle = $shippingFrame['name'];
				if($shippingFrame['mode'])
					$labelTitle = $labelTitle.' - '.$nameDelivery[$shippingFrame['mode']];

				if($shippingFrame['time'])
					$labelTitle = $labelTitle.'. Срок доставки - '.$shippingFrame['time'];

				if($shippingFrame['price']['value'] === 0 && $pluginEnableShippingPrice)
					$labelTitle = $labelTitle.': Бесплатно';
				else
					$cost = $shippingFrame['price']['value'];
			}
		}

		if(!$pluginEnableShippingPrice){
			$currency_code = get_woocommerce_currency();
			$currency_symbol = get_woocommerce_currency_symbol( $currency_code );

			$labelTitle = str_replace(':',' -',$labelTitle).' - '.$cost. ' '.$currency_symbol;
			$cost = 0;
		}

		$apiWidgetKey = $optionsRepository->getOption('wc_esl_shipping_api_key_wcart');
		$cacheJson = array(
			'city' => $widgetCityEsl['fias'] ?? '',
			'key' => $apiWidgetKey,
			'service' => $shippingFrame['key'] ?? ''
		);
		$cache_key = md5('widget/calculation'.json_encode($cacheJson));
		$cache_data = get_transient($cache_key);
		if($cache_data){
			$shippingMethods[$this->id]['debug'] = ( $cache_data['debug'] ?? [] );
			$shippingMethods[$this->id]['data']['terminal'] = ( $cache_data['data']['terminal'] ?? [] );
			$shippingMethods[$this->id]['data']['door'] = ( $cache_data['data']['door'] ?? [] );
			$sessionService->set('shipping_methods', $shippingMethods);
		}

		if($cost === 0){
			$package = array();
		}

		$rate = array(
			'id' => $this->id,
			'label' => $labelTitle,
			'cost' => $cost,
			'package' => $package
		);

		return $rate;
	}

	public function getSlug()
	{
		$idWithoutPrefix = explode(WC_ESL_PREFIX, $this->id)[1];

		return explode('_', $idWithoutPrefix)[0];
	}

	public function getType()
	{
		$idWithoutPrefix = explode(WC_ESL_PREFIX, $this->id)[1];

		return explode('_', $idWithoutPrefix)[1];
	}

	protected function getOptionMethod($slug)
	{
		if(empty($slug)) return null;

		$optionsRepository = new OptionsRepository();
		$services = $optionsRepository->getOption('wc_esl_shipping_account_services');

		if(!isset($services[$slug])) return null;

		return $services[$slug];
	}
}
