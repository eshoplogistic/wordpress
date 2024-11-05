<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Http\Controllers\OptionsController;
use eshoplogistic\WCEshopLogistic\Http\Controllers\SessionController;
use eshoplogistic\WCEshopLogistic\Http\Request;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined('ABSPATH') ) {
	exit;
}

class Ajax implements ModuleInterface
{
	private $errorString = '';

	public function init()
	{
		if (wp_doing_ajax()) {
			$this->initRoutes();
			$this->initAdminRoutes();
		}
	}

	public function initRoutes()
	{
		add_action('wp_ajax_nopriv_wc_esl_search_cities', [$this, 'searchCities']);
		add_action('wp_ajax_wc_esl_search_cities', [$this, 'searchCities']);

		add_action('wp_ajax_nopriv_wc_esl_update_shipping_address', [$this, 'updateShippingAddress']);
		add_action('wp_ajax_wc_esl_update_shipping_address', [$this, 'updateShippingAddress']);

		add_action('wp_ajax_nopriv_wc_esl_set_terminal_address', [$this, 'setTerminalAddress']);
		add_action('wp_ajax_wc_esl_set_terminal_address', [$this, 'setTerminalAddress']);

		add_action('wp_ajax_nopriv_wc_esl_reset_shipping_address', [$this, 'resetShippingAddress']);
		add_action('wp_ajax_wc_esl_reset_shipping_address', [$this, 'resetShippingAddress']);


		add_action('wp_ajax_nopriv_wc_esl_set_terminal_filter', [$this, 'setTerminalFilter']);
		add_action('wp_ajax_wc_esl_set_terminal_filter', [$this, 'setTerminalFilter']);

		add_action('wp_ajax_nopriv_wc_esl_update_shipping', [$this, 'updateShipping']);
		add_action('wp_ajax_wc_esl_update_shipping', [$this, 'updateShipping']);
	}

	public function initAdminRoutes()
	{
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin', [$this, 'changeEnablePlugin']);
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin_price_shipping', [$this, 'changeEnablePluginPriceShipping']);
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin_log', [$this, 'changeEnablePluginLog']);
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin_api_v2', [$this, 'changeEnablePluginApiV2']);
		add_action('wp_ajax_wc_esl_shipping_save_api_key', [$this, 'saveApiKey']);
		add_action('wp_ajax_wc_esl_shipping_save_api_key_wcart', [$this, 'saveApiKeyWCart']);
		add_action('wp_ajax_wc_esl_shipping_save_api_key_ya', [$this, 'saveApiKeyYa']);
		add_action('wp_ajax_wc_esl_shipping_save_widget_secret_code', [$this, 'saveWidgetSecretCode']);
		add_action('wp_ajax_wc_esl_shipping_save_widget_key', [$this, 'saveWidgetKey']);
		add_action('wp_ajax_wc_esl_shipping_save_widget_but', [$this, 'saveWidgetBut']);
		add_action('wp_ajax_wc_esl_update_cache', [$this, 'updateCache']);
		add_action('wp_ajax_wc_esl_save_payment_method', [$this, 'savePaymentMethod']);
		add_action('wp_ajax_wc_esl_shipping_change_dimension_measurement', [$this, 'changeDimensionMeasurement']);
		add_action('wp_ajax_wc_esl_shipping_save_add_form', [$this, 'saveAddForm']);
		add_action('wp_ajax_wc_esl_shipping_save_export_form', [$this, 'saveExportForm']);
		add_action('wp_ajax_wc_esl_shipping_change_enable_frame', [$this, 'changeEnableFrame']);
		add_action('wp_ajax_wc_esl_shipping_unloading_enable', [$this, 'unloadingEnable']);
		add_action('wp_ajax_wc_esl_shipping_unloading_info', [$this, 'unloadingInfo']);
		add_action('wp_ajax_wc_esl_shipping_save_status_form', [$this, 'unloadingStatus']);
		add_action('wp_ajax_wc_esl_shipping_unloading_status_update', [$this, 'unloadingStatusUpdate']);
		add_action('wp_ajax_wc_esl_shipping_unloading_delete', [$this, 'unloadingDelete']);

	}

	public function changeEnablePlugin()
	{
		$status = isset($_POST['status']) ? wc_clean($_POST['status']) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'plugin_enable' => $status === 'true' ? 1 : 0
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}

	public function changeEnablePluginPriceShipping()
	{
		$status = isset($_POST['status']) ? wc_clean($_POST['status']) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'plugin_enable_price_shipping' => $status === 'true' ? 1 : 0
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}

	public function changeEnablePluginLog()
	{
		$status = isset($_POST['status']) ? wc_clean($_POST['status']) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'plugin_enable_log' => $status === 'true' ? 1 : 0
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}

	public function changeEnablePluginApiV2()
	{
		$status = isset($_POST['status']) ? wc_clean($_POST['status']) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'plugin_enable_api_v2' => $status === 'true' ? 1 : 0
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}

	public function saveApiKey()
	{
		$api_key = !empty($_POST['api_key']) ? wc_clean($_POST['api_key']) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveApiKey($api_key);

		$response->send();
	}

	public function saveApiKeyWCart()
	{
		$api_key = !empty($_POST['api_key']) ? wc_clean($_POST['api_key']) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveApiKeyWCart($api_key);

		$response->send();
	}


	public function saveApiKeyYa()
	{
		$api_key_ya = !empty($_POST['api_key_ya']) ? wc_clean($_POST['api_key_ya']) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveApiKeyYa($api_key_ya);

		$response->send();
	}

	public function saveWidgetSecretCode()
	{
		$secretCode = !empty($_POST['secret_code']) ? wc_clean($_POST['secret_code']) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveWidgetSecretCode($secretCode);

		$response->send();
	}

	public function saveWidgetKey()
	{
		$widgetKey = !empty($_POST['widget_key']) ? wc_clean($_POST['widget_key']) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveWidgetKey($widgetKey);

		$response->send();
	}

	public function saveWidgetBut()
	{
		$widgetBut = !empty($_POST['widget_but']) ? wc_clean($_POST['widget_but']) : 'Рассчитать доставку';

		$optionsController = new OptionsController();
		$response = $optionsController->saveWidgetBut($widgetBut);

		$response->send();
	}

	public function saveAddForm()
	{
		$addFrom = !empty($_POST['add_form']) ? wc_clean($_POST['add_form']) : [];
		$addFrom = stripslashes(html_entity_decode($addFrom));
		$addFrom = json_decode($addFrom, true);
		$result = array();

		foreach ($addFrom as $value){
			if(isset($result[$value['name']])){
				if(is_array($result[$value['name']])){
					$result[$value['name']][] = $value['value'];
				}else{
					$result[$value['name']] = array($result[$value['name']], $value['value']);
				}
			}elseif(isset($value['name'])){
				$result[$value['name']] = $value['value'];
			}

		}

		$optionsController = new OptionsController();
		$response = $optionsController->saveAddForm($result);

		$response->send();
	}

	public function saveExportForm()
	{
		$exportFrom = !empty($_POST['export_form']) ? wc_clean($_POST['export_form']) : [];
		$exportFrom = stripslashes(html_entity_decode($exportFrom));
		$exportFrom = json_decode($exportFrom, true);
		$result = array();

		foreach ($exportFrom as $value){
			if(isset($value['name']))
				$result[$value['name']] = $value['value'];
		}

		$optionsController = new OptionsController();
		$response = $optionsController->saveExportForm($result);

		$response->send();
	}

	public function searchCities()
	{
		$target = isset($_POST['target']) ? wc_clean($_POST['target']) : '';
		$currentCountry = isset($_POST['currentCountry']) ? wc_clean($_POST['currentCountry']) : '';
		$typeFilter = isset($_POST['typeFilter']) ? wc_clean($_POST['typeFilter']) : 'false';

		$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
		$result = $eshopLogisticApi->search($target, $currentCountry);

		if($result->hasErrors()) wp_send_json(['success' => false]);

		$result = $result->data();
		if($typeFilter != 'false'){
			$resultTmp = array();
			foreach ($result as $key=>$value){
				if(!isset($value[$typeFilter]))
					continue;

				$resultTmp[$value[$typeFilter]][] = $value;
			}
			$result = $resultTmp;
		}

		wp_send_json([
			'success' => true,
			'data' => $result
		]);
	}

	public function updateShippingAddress()
	{
		$fias = isset($_POST['fias']) ? wc_clean($_POST['fias']) : '';
		$city = isset($_POST['city']) ? wc_clean($_POST['city']) : '';
		$adress = isset($_POST['adress']) ? wc_clean($_POST['adress']) : '';
		$region = isset($_POST['region']) ? wc_clean($_POST['region']) : '';
		$postcode = isset($_POST['postcode']) ? wc_clean($_POST['postcode']) : '';
		$services = isset($_POST['services']) ? wc_clean($_POST['services']) : [];
		$mode = isset($_POST['mode']) ? wc_clean($_POST['mode']) : 'billing';

		$data = [
			'shipping_city' => $city,
			'shipping_adress' => $adress,
			'shipping_fias' => $fias,
			'shipping_region' => $region,
			'shipping_services' => $services,
			'shipping_postcode' => $postcode,
		];

		$data[$mode] = [
			'city' => $city,
			'adress' => $adress,
			'fias' => $fias,
			'region' => $region,
			'services' => $services,
			'postcode' => $postcode,
		];

		switch ($mode) {
			case 'billing':
				WC()->customer->set_billing_city($city);
				WC()->customer->set_billing_state($region);
				WC()->customer->set_billing_postcode($postcode);
				break;

			case 'shipping':
				WC()->customer->set_shipping_city($city);
				WC()->customer->set_shipping_state($region);
				WC()->customer->set_shipping_postcode($postcode);
				break;

			default:
				break;
		}

		$request = new Request([
			'data' => $data
		]);
		$sessionController = new SessionController();
		$response = $sessionController->saveShippingAddress($request);

		$response->send();
	}

	public function updateCache()
	{
		global $wpdb;

		$like = '%transient_'. WC_ESL_PREFIX .'%';
		$query = "SELECT `option_name` AS `name` FROM $wpdb->options WHERE `option_name` LIKE '$like' ORDER BY `option_name`";
		$transients = $wpdb->get_results($query);

		if($transients) {
			foreach($transients as $transient) {
				delete_transient(explode('_transient_', $transient->name)[1]);
			}
		}

		$optionsRepository = new OptionsRepository();
		$apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

		if($apiKey) {
			$optionsController = new OptionsController();
			$response = $optionsController->saveApiKey($apiKey);
		}

		wp_send_json([
			'success' => true,
			'data' => $transients,
			'msg' => __("Кэш успешно очищен", WC_ESL_DOMAIN)
		]);
	}

	public function savePaymentMethod()
	{
		$formData = isset($_POST['formData']) ? $_POST['formData'] : null;

		if(is_null($formData)) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сохранения методов оплаты", WC_ESL_DOMAIN)
			]);
		}

		$params = array();
		parse_str($formData, $params);

		if(!isset($params['esl_pay_type'])) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сохранения методов оплаты", WC_ESL_DOMAIN)
			]);
		}

		$payTypes = [];

		foreach($params['esl_pay_type'] as $key => $value) {
			$payTypes[$key] = $value;
		}

		if(empty($payTypes)) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сохранения методов оплаты", WC_ESL_DOMAIN)
			]);
		}

		$optionsRepository = new OptionsRepository();
		$optionsRepository->save([
			'wc_esl_shipping' => [
				'payment_methods' => $payTypes
			]
		]);

		wp_send_json([
			'success' => true,
			'data' => $payTypes,
			'msg' => __("Методы оплаты успешно сохранены", WC_ESL_DOMAIN)
		]);
	}

	public function setTerminalAddress()
	{
		$terminal = isset($_POST['terminal']) ? wc_clean($_POST['terminal']) : '';
		$terminal_code = isset($_POST['terminal_code']) ? wc_clean($_POST['terminal_code']) : '';

		if(!$terminal) wp_send_json(['success' => false, 'msg' => __("Некорректный адрес пункта выдачи", WC_ESL_DOMAIN)]);

		$sessionService = new SessionService();
		$sessionService->set('terminal_location', $terminal. '. Код пункта: '.$terminal_code);

		wp_send_json([
			'success' => true,
			'data' => $terminal. '. Код пункта: '.$terminal_code,
			'msg' => __("Aдрес пункта выдачи успешно сохранён", WC_ESL_DOMAIN)
		]);
	}

	public function setTerminalFilter()
	{
		$filters = isset($_POST['filters']) ? wc_clean($_POST['filters']) : '';
		$filters =  json_decode(stripslashes($filters), true);
		$terminals = array();

		$shippingHelper = new ShippingHelper();
		$chosenShippingMethods = WC()->session->get( 'chosen_shipping_methods' );
		$sessionService = new SessionService();

		if(isset($chosenShippingMethods[0])) {
			$typeMethod           = $shippingHelper->getTypeMethod( $chosenShippingMethods[0] );
			$stateShippingMethods = $sessionService->get( 'shipping_methods' );
			$terminals            = isset( $stateShippingMethods[ $chosenShippingMethods[0] ]['terminals'] ) ? $stateShippingMethods[ $chosenShippingMethods[0] ]['terminals'] : null;

			if(!is_null($terminals)) {
				$terminals = $this->terminalFilterInit($filters, $terminals);
			}

		}

		wp_send_json([
			'success' => true,
			'data' => $terminals,
			'msg' => __("Адрес скорректирован", WC_ESL_DOMAIN)
		]);
	}

	public function terminalFilterInit($filters, $terminals){
		$result = $terminals;
		foreach ($filters as $key=>$value){
			$value = trim(mb_strtolower($value));
			if($key == 'search-filter-esl' && $value){
				foreach ($result as $k=>$v){
					$lastPos = 0;
					$positions = array();
					$check = false;
					while (($lastPos = strpos(mb_strtolower($v['address']), $value, $lastPos))!== false) {
						$positions[] = $lastPos;
						$lastPos = $lastPos + strlen($value);
						$check = true;
					}
					if(!$check)
						unset($result[$k]);
				}
			}
			if($key == 'metro-filter-esl' && $value){
				foreach ($result as $k=>$v){
					$lastPos = 0;
					$positions = array();
					$check = false;
					while (($lastPos = strpos(mb_strtolower($v['note']), $value, $lastPos))!== false) {
						$positions[] = $lastPos;
						$lastPos = $lastPos + strlen($value);
						$check = true;
					}
					if(!$check)
						unset($result[$k]);
				}
			}
			if($key == 'automat-filter-esl' && $value && $filters['pvz-filter-esl'] === false){
				foreach ($result as $k=>$v){
					if(!$v['is_postamat'])
						unset($result[$k]);
				}
			}
			if($key == 'pvz-filter-esl' && $value && $filters['automat-filter-esl'] === false){
				foreach ($result as $k=>$v){
					if($v['is_postamat'])
						unset($result[$k]);
				}
			}
		}

		return array_values($result);
	}

	public function resetShippingAddress()
	{
		try {
			$sessionService = new SessionService();
			$sessionService->dropAll();

			wp_send_json([
				'success' => true,
				'data' => $sessionService->getAll(),
				'msg' => __("Сессия успешно сброшена", WC_ESL_DOMAIN)
			]);
		} catch(\Exception $e) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сброса кэша", WC_ESL_DOMAIN)
			]);
		}
	}

	public function changeDimensionMeasurement()
	{
		$status = isset($_POST['status']) ? wc_clean($_POST['status']) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'dimension_measurement' => $status
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}


	public function updateShipping()
	{
		$data = isset($_POST['data']) ? wc_clean($_POST['data']) : '';
		$data =  json_decode(stripslashes($data), true);
		$data['city'] = isset($_POST['city']) ? wc_clean($_POST['city']) : '';
		$sessionService = new SessionService();
		$sessionService->set('esl_shipping_frame', $data);
		if(!isset($data['address']))
			$sessionService->drop('terminal_location');

	}

	public function changeEnableFrame()
	{
		$status = isset($_POST['status']) ? wc_clean($_POST['status']) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'frame_enable' => $status === 'true' ? 1 : 0
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}

	public function unloadingEnable()
	{
		$data = isset($_POST['data']) ? wc_clean($_POST['data']) : null;

		$unloading = new Unloading();
		$resultParams = $unloading->params_delivery_init($data);

		if($resultParams->hasErrors()){
			$error = $resultParams->jsonSerialize();

			$logger = wc_get_logger();
			$context = array( 'source' => 'esl-error-load-unloading' );
			$logger->info( print_r($error, true),  $context);

			if(isset($error['data']['errors'])){
				$this->iteratorError($error['data']['errors']);
				$error = $this->errorString;
			}
			if(!$error)
				$error = 'Ошибка при выгрузке заказа';

			wp_send_json([
				'success' => 'error',
				'msg' => __($error, WC_ESL_DOMAIN)
			]);
		}else{
			wp_send_json([
				'success' => true,
				'msg' => __("Заказ создан", WC_ESL_DOMAIN)
			]);
		}

	}

	public function unloadingDelete()
	{
		if(!isset($_POST['order_id']))
			return false;
		if(!isset($_POST['order_type']))
			return false;


		$unloading = new Unloading();
		$result = $unloading->infoOrder($_POST['order_id'], $_POST['order_type'], 'delete');

		wp_send_json([
			'success' => true,
			'data' => $result,
			'msg' => __("Удаление заказа для выгрузки", WC_ESL_DOMAIN)
		]);
	}

	public function unloadingInfo()
	{
		if(!isset($_POST['order_id']))
			return false;
		if(!isset($_POST['order_type']))
			return false;

		$unloading = new Unloading();
		$result = $unloading->infoOrder($_POST['order_id'], $_POST['order_type']);
		$html = '';

		$order = wc_get_order($_POST['order_id']);
		$orderShippings = $order->get_shipping_methods();
		foreach ($orderShippings as $key=>$item){
			$shippingMethod = wc_get_order_item_meta( $item->get_id() , 'esl_shipping_methods', $single = true );
		}

		if(isset($result['data']['messages'])){
			$html = '<div class="esl-status_infoTitle">'.$result['data']['messages'].'</div>';
		}
		if(isset($result['state']['number'])){
			$html .= '<div class="esl-status_infoTitle">Номер заказа: <input type="text" value="'.$result['state']['number'].'" id="copyText1" disabled><button class="button button-primary" onclick="copyToClipboard(copyText1)">Скопировать номер</button></div>';
		}
		if(isset($shippingMethod) && $shippingMethod){
			$shippingMethods = json_decode($shippingMethod, true);
			if(isset($shippingMethods['answer']['order']['id'])){
				$html .= '<div class="esl-status_infoTitle">Идентификатор заказа в системе "'.$_POST['order_type'].'": '.$shippingMethods['answer']['order']['id'].'</div>';
			}
		}
		if(isset($result['order']['orderId'])){
			$html .= '<div class="esl-status_infoTitle">Идентификатор заказа: '.$result['order']['orderId'].'</div>';
		}
		if(isset($result['state'])){
			$html .= '<div class="esl-status_info">Текущий статус: '.$result['state']['status']['description'].'</div>';
		}
		if(isset($result['state']['service_status']['description'])){
			$html .= '<br><div class="esl-status_info">Описание: '.$result['state']['service_status']['description'].'</div>';
		}

		if(!$html)
			$html = '<div class="esl-status_infoTitle">Ошибка при загрузке данных.</div>';

		wp_send_json([
			'success' => true,
			'data' => $html,
			'msg' => __("", WC_ESL_DOMAIN)
		]);
	}

	public function unloadingStatus()
	{
		if(!isset($_POST['export_form']))
			return false;

		$data =  json_decode(stripslashes($_POST['export_form']), true);

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'plugin_status_form' => $data
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		wp_send_json([
			'success' => true,
			'msg' => __("Заказ создан", WC_ESL_DOMAIN)
		]);
	}

	public function unloadingStatusUpdate()
	{
		if(!isset($_POST['order_id']))
			return false;

		if(!isset($_POST['order_type']))
			return false;


		$unloading = new Unloading();
		$status = $unloading->infoOrder($_POST['order_id'], $_POST['order_type']);
		if(isset($status['success']) && $status['success'] === false){
			$result = $status['data']['messages'] ?? 'Ошибка при получении данных';
		}else{
			$result = $unloading->updateStatusById($status, $_POST['order_id']);
		}

		wp_send_json([
			'success' => true,
			'data' => $result,
			'msg' => __("", WC_ESL_DOMAIN)
		]);
	}

	public function iteratorError($arr){

		foreach($arr as $key => $val){

			if(is_array($val)){
				$this->iteratorError($val);
			}else{
				$this->errorString .= $this->errorString.'<span>'.$val.'</span><br>';
			}
		}
	}

}