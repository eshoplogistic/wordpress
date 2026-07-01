<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Classes\Shipping\ExportFileds;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Http\Controllers\OptionsController;
use eshoplogistic\WCEshopLogistic\Http\Controllers\SessionController;
use eshoplogistic\WCEshopLogistic\Http\Request;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if (! defined('ABSPATH')) {
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

		add_action('wp_ajax_nopriv_wc_esl_get_shipping_data', [$this, 'getShippingData']);
		add_action('wp_ajax_wc_esl_get_shipping_data', [$this, 'getShippingData']);

		add_action('wp_ajax_nopriv_wc_esl_get_terminals', [$this, 'getTerminals']);
		add_action('wp_ajax_wc_esl_get_terminals', [$this, 'getTerminals']);
	}

	public function initAdminRoutes()
	{
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin', [$this, 'changeEnablePlugin']);
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin_price_shipping', [$this, 'changeEnablePluginPriceShipping']);
		add_action('wp_ajax_wc_esl_shipping_change_enable_plugin_log', [$this, 'changeEnablePluginLog']);
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
		add_action('wp_ajax_wc_esl_shipping_get_add_field', [$this, 'getAddField']);
		add_action('wp_ajax_wc_esl_shipping_save_add_field', [$this, 'saveAddField']);
	}

	public function changeEnablePlugin()
	{		
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;

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
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;

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
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;

		$options = [];

		$options['data']['wc_esl_shipping'] = array(
			'plugin_enable_log' => $status === 'true' ? 1 : 0
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		$response->send();
	}

	public function saveApiKey()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$api_key = !empty($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveApiKey($api_key);

		$response->send();
	}

	public function saveApiKeyWCart()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$api_key = !empty($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveApiKeyWCart($api_key);

		$response->send();
	}


	public function saveApiKeyYa()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$api_key_ya = !empty($_POST['api_key_ya']) ? sanitize_text_field(wp_unslash($_POST['api_key_ya'])) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveApiKeyYa($api_key_ya);

		$response->send();
	}

	public function saveWidgetSecretCode()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$secretCode = !empty($_POST['secret_code']) ? sanitize_text_field(wp_unslash($_POST['secret_code'])) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveWidgetSecretCode($secretCode);

		$response->send();
	}

	public function saveWidgetKey()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$widgetKey = !empty($_POST['widget_key']) ? sanitize_text_field(wp_unslash($_POST['widget_key'])) : '';

		$optionsController = new OptionsController();
		$response = $optionsController->saveWidgetKey($widgetKey);

		$response->send();
	}

	public function saveWidgetBut()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$widgetBut = !empty($_POST['widget_but']) ? sanitize_text_field(wp_unslash($_POST['widget_but'])) : 'Рассчитать доставку';

		$optionsController = new OptionsController();
		$response = $optionsController->saveWidgetBut($widgetBut);

		$response->send();
	}

	public function saveAddForm()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_array method
		$addFrom = !empty($_POST['add_form']) ? $this->sanitize_array(wp_unslash($_POST['add_form'])) : [];
		$addFrom = stripslashes(html_entity_decode($addFrom));
		$addFrom = json_decode($addFrom, true);
		$result = array();

		foreach ($addFrom as $value) {
			if (isset($result[$value['name']])) {
				if (is_array($result[$value['name']])) {
					$result[$value['name']][] = $value['value'];
				} else {
					$result[$value['name']] = array($result[$value['name']], $value['value']);
				}
			} elseif (isset($value['name'])) {
				$result[$value['name']] = $value['value'];
			}
		}

		$optionsController = new OptionsController();
		$response = $optionsController->saveAddForm($result);

		$response->send();
	}

	public function saveExportForm()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_array method
		$exportFrom = !empty($_POST['export_form']) ? $this->sanitize_array(wp_unslash($_POST['export_form'])) : [];
		$exportFrom = stripslashes(html_entity_decode($exportFrom));
		$exportFrom = json_decode($exportFrom, true);
		$result = array();

		foreach ($exportFrom as $value) {
			if (isset($value['name']))
				$result[$value['name']] = $value['value'];
		}

		$optionsController = new OptionsController();
		$response = $optionsController->saveExportForm($result);

		$response->send();
	}

	public function saveAddField()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_array method
		$addField = !empty($_POST['result']) ? $this->sanitize_array(wp_unslash($_POST['result'])) : [];
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_array method
		$type = !empty($_POST['type']) ? $this->sanitize_array(wp_unslash($_POST['type'])) : [];
		$addField = stripslashes(html_entity_decode($addField));
		$addField = json_decode($addField, true);

		$optionsRepository = new OptionsRepository();
		$result = $optionsRepository->getOption('wc_esl_shipping_add_field_form');

		$result[$type] = [];
		foreach ($addField as $value) {
			if (isset($value['name']))
				$result[$type][$value['name']] = $value['value'];
		}

		$optionsController = new OptionsController();
		$response = $optionsController->saveAddFieldForm($result);

		$response->send();
	}


	public function searchCities()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$target = isset($_POST['target']) ? sanitize_text_field(wp_unslash($_POST['target'])) : '';
		$target = trim($target);
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$currentCountry = isset($_POST['currentCountry']) ? sanitize_text_field(wp_unslash($_POST['currentCountry'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$typeFilter = isset($_POST['typeFilter']) ? sanitize_text_field(wp_unslash($_POST['typeFilter'])) : 'false';

		$targetLength = function_exists('mb_strlen') ? mb_strlen($target) : strlen($target);
		if ($targetLength < 2) {
			wp_send_json([
				'success' => true,
				'data' => []
			]);
		}

		$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
		$result = $eshopLogisticApi->search($target, $currentCountry);

		if ($result->hasErrors()) wp_send_json(['success' => false]);

		$result = $result->data();
		if ($typeFilter != 'false') {
			$resultTmp = array();
			foreach ($result as $key => $value) {
				if (!isset($value[$typeFilter]))
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
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$fias = isset($_POST['fias']) ? sanitize_text_field(wp_unslash($_POST['fias'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$city = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$adress = isset($_POST['adress']) ? sanitize_text_field(wp_unslash($_POST['adress'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$region = isset($_POST['region']) ? sanitize_text_field(wp_unslash($_POST['region'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$postcode = isset($_POST['postcode']) ? sanitize_text_field(wp_unslash($_POST['postcode'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_array
		$services = isset($_POST['services']) ? $this->sanitize_array(wp_unslash($_POST['services'])) : [];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'billing';

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
				// Сбрасываем кэш терминалов по методам доставки, чтобы при следующем
				// обращении к getTerminals был выполнен пересчёт для нового города.
				$sessionService = new SessionService();
				$sessionService->set('shipping_methods', []);
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
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		global $wpdb;

		$like = '%transient_' . WC_ESL_PREFIX . '%';
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is variable from wpdb object
		$query = $wpdb->prepare("SELECT `option_name` AS `name` FROM " . $wpdb->options . " WHERE `option_name` LIKE %s ORDER BY `option_name`", $like);
		$cache_key = 'wc_esl_transients_list';
		$transients = wp_cache_get($cache_key, 'eshoplogisticru');
		if ($transients === false) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Intentional options table query for transient cleanup, query is prepared above.
			$transients = $wpdb->get_results($query);
			wp_cache_set($cache_key, $transients, 'eshoplogisticru', 60); // кэш на 60 секунд
		}

		if ($transients) {
			foreach ($transients as $transient) {
				delete_transient(explode('_transient_', $transient->name)[1]);
			}
		}

		$optionsRepository = new OptionsRepository();
		$apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

		if ($apiKey) {
			$optionsController = new OptionsController();
			$response = $optionsController->saveApiKey($apiKey);
		}

		wp_send_json([
			'success' => true,
			'data' => $transients,
			'msg' => __("Кэш успешно очищен", 'eshoplogisticru')
		]);
	}

	public function savePaymentMethod()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$formData = isset($_POST['formData']) ? wp_unslash($_POST['formData']) : null;

		if (is_null($formData)) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сохранения методов оплаты", 'eshoplogisticru')
			]);
		}

		$params = array();
		parse_str($formData, $params);

		if (!isset($params['esl_pay_type']) || !is_array($params['esl_pay_type'])) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сохранения методов оплаты", 'eshoplogisticru')
			]);
		}

		$payTypes = [];

		array_walk_recursive($params['esl_pay_type'], function (&$val) {
			$val = sanitize_text_field($val);
		});

		foreach ($params['esl_pay_type'] as $key => $value) {
			$payTypes[sanitize_key($key)] = $value;
		}

		if (empty($payTypes)) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сохранения методов оплаты", 'eshoplogisticru')
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
			'msg' => __("Методы оплаты успешно сохранены", 'eshoplogisticru')
		]);
	}

	public function setTerminalAddress()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$terminal = isset($_POST['terminal']) ? sanitize_text_field(wp_unslash($_POST['terminal'])) : '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$terminal_code = isset($_POST['terminal_code']) ? sanitize_text_field(wp_unslash($_POST['terminal_code'])) : '';

		if (!$terminal) wp_send_json(['success' => false, 'msg' => __("Некорректный адрес пункта выдачи", 'eshoplogisticru')]);

		$sessionService = new SessionService();
		$sessionService->set('terminal_location', $terminal . '. Код пункта: ' . $terminal_code);

		wp_send_json([
			'success' => true,
			'data' => $terminal . '. Код пункта: ' . $terminal_code,
			'msg' => __("Aдрес пункта выдачи успешно сохранён", 'eshoplogisticru')
		]);
	}

	public function setTerminalFilter()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint, sanitized via sanitize_text_field
		$filters = isset($_POST['filters']) ? sanitize_text_field(wp_unslash($_POST['filters'])) : '';
		$filters =  json_decode(stripslashes($filters), true);
		$terminals = array();

		$shippingHelper = new ShippingHelper();
		$chosenShippingMethods = WC()->session->get('chosen_shipping_methods');
		$sessionService = new SessionService();

		if (isset($chosenShippingMethods[0])) {
			$typeMethod           = $shippingHelper->getTypeMethod($chosenShippingMethods[0]);
			$stateShippingMethods = $sessionService->get('shipping_methods');
			$terminals            = isset($stateShippingMethods[$chosenShippingMethods[0]]['terminals']) ? $stateShippingMethods[$chosenShippingMethods[0]]['terminals'] : null;

			if (!is_null($terminals)) {
				$terminals = $this->terminalFilterInit($filters, $terminals);
			}
		}

		wp_send_json([
			'success' => true,
			'data' => $terminals,
			'msg' => __("Адрес скорректирован", 'eshoplogisticru')
		]);
	}

	public function terminalFilterInit($filters, $terminals)
	{
		$result = $terminals;
		foreach ($filters as $key => $value) {
			$value = trim(mb_strtolower($value));
			if ($key == 'search-filter-esl' && $value) {
				foreach ($result as $k => $v) {
					$lastPos = 0;
					$positions = array();
					$check = false;
					while (($lastPos = strpos(mb_strtolower($v['address']), $value, $lastPos)) !== false) {
						$positions[] = $lastPos;
						$lastPos = $lastPos + strlen($value);
						$check = true;
					}
					if (!$check)
						unset($result[$k]);
				}
			}
			if ($key == 'metro-filter-esl' && $value) {
				foreach ($result as $k => $v) {
					$lastPos = 0;
					$positions = array();
					$check = false;
					while (($lastPos = strpos(mb_strtolower($v['note']), $value, $lastPos)) !== false) {
						$positions[] = $lastPos;
						$lastPos = $lastPos + strlen($value);
						$check = true;
					}
					if (!$check)
						unset($result[$k]);
				}
			}
			if ($key == 'automat-filter-esl' && $value && $filters['pvz-filter-esl'] === false) {
				foreach ($result as $k => $v) {
					if (!$v['is_postamat'])
						unset($result[$k]);
				}
			}
			if ($key == 'pvz-filter-esl' && $value && $filters['automat-filter-esl'] === false) {
				foreach ($result as $k => $v) {
					if ($v['is_postamat'])
						unset($result[$k]);
				}
			}
		}

		return array_values($result);
	}

	public function resetShippingAddress()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Public AJAX endpoint for session reset functionality
		try {
			$sessionService = new SessionService();
			$sessionService->dropAll();

			wp_send_json([
				'success' => true,
				'data' => $sessionService->getAll(),
				'msg' => __("Сессия успешно сброшена", 'eshoplogisticru')
			]);
		} catch (\Exception $e) {
			wp_send_json([
				'success' => false,
				'msg' => __("Ошибка сброса кэша", 'eshoplogisticru')
			]);
		}
	}

	public function changeDimensionMeasurement()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;

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
		if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping')) {
			wp_send_json_error('Security check failed');
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data sanitized after json_decode via sanitize_array()
		$rawData = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
		$rawData = is_string($rawData) ? $rawData : '';

		$data = $rawData !== '' ? json_decode($rawData, true) : [];
		if (!is_array($data)) {
			$data = [];
		}

		$data = $this->sanitize_array($data);
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input sanitized via wc_clean()
		$data['city'] = isset($_POST['city']) ? wc_clean(wp_unslash($_POST['city'])) : '';
		$sessionService = new SessionService();
		$sessionService->set('esl_shipping_frame', $data);
		$frameMode = isset($data['mode']) ? strtolower(trim((string) $data['mode'])) : '';
		$isDoorMode = in_array($frameMode, ['door', 'todoor', 'courier'], true);
		if ($isDoorMode || (!isset($data['address']) || !$data['address']) && !in_array($frameMode, ['terminal', 'pickup', 'pvz', 'point', 'mixed'], true)) {
			$sessionService->drop('terminal_location');
		}
	}

	public function getTerminals()
	{
		$shippingHelper = new ShippingHelper();
		$sessionService = new SessionService();

		$chosenMethods = WC()->session ? WC()->session->get('chosen_shipping_methods') : [];
		$chosenMethods = $chosenMethods ?: [];
		$chosenMethod  = isset($chosenMethods[0]) ? $chosenMethods[0] : '';

		if (!$chosenMethod) {
			wp_send_json_success(['terminals' => []]);
			return;
		}

		$typeMethod = $shippingHelper->getTypeMethod($chosenMethod);
		if ($typeMethod !== 'terminal') {
			wp_send_json_success(['terminals' => []]);
			return;
		}

		$shippingMethods = $sessionService->get('shipping_methods') ?: [];
		$terminals = isset($shippingMethods[$chosenMethod]['terminals'])
			? $shippingMethods[$chosenMethod]['terminals']
			: [];

		// Если сессия пуста (WC Blocks Store API использует отдельную сессию),
		// перезапускаем calculate_shipping_basic() напрямую в контексте браузерного
		// AJAX-запроса. Ответ ESL API уже закеширован в transient — вызов будет быстрым.
		if (empty($terminals) && WC()->cart) {
			$packages = WC()->cart->get_shipping_packages();
			if (!empty($packages)) {
				$package = reset($packages);
				$methodInstances = WC()->shipping() ? WC()->shipping()->load_shipping_methods($package) : [];
				foreach ($methodInstances as $method) {
					if ($method->id === $chosenMethod && method_exists($method, 'calculate_shipping_basic')) {
						$method->calculate_shipping_basic($package);
						break;
					}
				}
				$shippingMethods = $sessionService->get('shipping_methods') ?: [];
				$terminals = isset($shippingMethods[$chosenMethod]['terminals'])
					? $shippingMethods[$chosenMethod]['terminals']
					: [];
			}
		}

		wp_send_json_success(['terminals' => $terminals]);
	}

	public function getShippingData()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Public AJAX endpoint
		$mode = isset($_POST['mode']) ? sanitize_text_field(wp_unslash($_POST['mode'])) : 'billing';

		$request = new Request(['mode' => $mode]);

		$sessionController = new SessionController();
		$response = $sessionController->getShippingData($request);

		$response->send();
	}

	public function changeEnableFrame()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : null;

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
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_array method
		$data = isset($_POST['data']) ? $this->sanitize_array(wp_unslash($_POST['data'])) : null;

		$unloading = new Unloading();
		$resultParams = $unloading->params_delivery_init($data);

		if ($resultParams->hasErrors()) {
			$error = $resultParams->jsonSerialize();

			$logger = wc_get_logger();
			$context = array('source' => 'esl-error-load-unloading');
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r -- Debug logging for error tracking
			$logger->info(print_r($error, true),  $context);

			if (isset($error['data']['errors'])) {
				$this->iteratorError($error['data']['errors']);
				$error = $this->errorString;
			}
			if (!$error)
				$error = 'Ошибка при выгрузке заказа';

			wp_send_json([
				'success' => 'error',
				'msg' => $error
			]);
		} else {
			wp_send_json([
				'success' => true,
				'msg' => __("Заказ создан", 'eshoplogisticru')
			]);
		}
	}

	public function unloadingDelete()
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified via wp_verify_nonce in the conditional
		if (
			!isset($_POST['order_id']) ||
			!isset($_POST['order_type']) ||
			!isset($_POST['esl_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['esl_nonce'])), 'esl_unloading_action') ||
			!current_user_can('manage_woocommerce')
		) {
			wp_send_json_error('Недостаточно прав или неверный nonce');
		}

		$order_id = absint(wp_unslash($_POST['order_id']));
		$order_type = sanitize_text_field(wp_unslash($_POST['order_type']));
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$unloading = new Unloading();
		$result = $unloading->infoOrder($order_id, $order_type, 'delete');

		wp_send_json([
			'success' => true,
			'data' => $result,
			'msg' => esc_html__("Удаление заказа для выгрузки", 'eshoplogisticru')
		]);
	}

	public function unloadingInfo()
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified via wp_verify_nonce in the conditional
		if (
			!isset($_POST['order_id']) ||
			!isset($_POST['order_type']) ||
			!isset($_POST['esl_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['esl_nonce'])), 'esl_unloading_action') ||
			!current_user_can('manage_woocommerce')
		) {
			wp_send_json_error('Недостаточно прав или неверный nonce');
		}

		$order_id = absint(wp_unslash($_POST['order_id']));
		$order_type = sanitize_text_field(wp_unslash($_POST['order_type']));
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$unloading = new Unloading();
		$result = $unloading->infoOrder($order_id, $order_type);
		$html = '';

		$order = wc_get_order($order_id);
		$orderShippings = $order ? $order->get_shipping_methods() : [];
		$shippingMethod = '';
		foreach ($orderShippings as $key => $item) {
			$shippingMethod = wc_get_order_item_meta($item->get_id(), 'esl_shipping_methods', $single = true);
		}

		if (isset($result['data']['messages'])) {
			$html = '<div class="esl-status_infoTitle">' . esc_html($result['data']['messages']) . '</div>';
		}
		if (isset($result['state']['number'])) {
			$html .= '<div class="esl-status_infoTitle">Номер заказа: <input type="text" value="' . esc_attr($result['state']['number']) . '" id="copyText1" disabled><button id="copyBut1" class="button button-primary" onclick="copyToClipboard(copyText1, this)">Скопировать номер</button></div>';
		}
		if (isset($shippingMethod) && $shippingMethod) {
			$shippingMethods = json_decode($shippingMethod, true);
			if (isset($shippingMethods['answer']['order']['id'])) {
				$html .= '<div class="esl-status_infoTitle">Идентификатор заказа в системе "' . esc_html($order_type) . '": ' . esc_html($shippingMethods['answer']['order']['id']) . '</div>';
			}
		}
		if (isset($result['order']['orderId'])) {
			$html .= '<div class="esl-status_infoTitle">Идентификатор заказа: ' . esc_html($result['order']['orderId']) . '</div>';
		}
		if (isset($result['state'])) {
			$html .= '<div class="esl-status_info">Текущий статус: ' . esc_html($result['state']['status']['description']) . '</div>';
		}
		if (isset($result['state']['service_status']['description'])) {
			$html .= '<br><div class="esl-status_info">Описание: ' . esc_html($result['state']['service_status']['description']) . '</div>';
		}

		$print = $unloading->returnPrint();
		if ($print)
			$html .= $print;

		if (!$html)
			$html = '<div class="esl-status_infoTitle">Ошибка при загрузке данных.</div>';

		wp_send_json([
			'success' => true,
			'data' => $html,
			'msg' => ''
		]);
	}

	public function unloadingStatus()
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified via wp_verify_nonce in the conditional
		if (
			!isset($_POST['export_form']) ||
			!isset($_POST['nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ||
			!current_user_can('manage_woocommerce')
		) {
			wp_send_json_error('Недостаточно прав или неверный nonce');
		}

		$export_form_raw = sanitize_text_field(wp_unslash($_POST['export_form']));
		// phpcs:enable WordPress.Security.NonceVerification.Missing
		$data = json_decode(stripslashes($export_form_raw), true);
		if (is_array($data)) {
			$data = $this->sanitize_array($data);
		}

		$options = [];
		$options['data']['wc_esl_shipping'] = array(
			'plugin_status_form' => $data
		);

		$request = new Request($options);

		$optionsController = new OptionsController();
		$response = $optionsController->save($request);

		wp_send_json([
			'success' => true,
			'msg' => esc_html__("Заказ создан", 'eshoplogisticru')
		]);
	}

	public function unloadingStatusUpdate()
	{
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce is verified via wp_verify_nonce in the conditional
		if (
			!isset($_POST['order_id']) ||
			!isset($_POST['order_type']) ||
			!isset($_POST['esl_nonce']) ||
			!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['esl_nonce'])), 'esl_unloading_action') ||
			!current_user_can('manage_woocommerce')
		) {
			wp_send_json_error('Недостаточно прав или неверный nonce');
		}

		$order_id = absint(wp_unslash($_POST['order_id']));
		$order_type = sanitize_text_field(wp_unslash($_POST['order_type']));
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$unloading = new Unloading();
		$status = $unloading->infoOrder($order_id, $order_type);
		if (isset($status['success']) && $status['success'] === false) {
			$result = isset($status['data']['messages']) ? esc_html($status['data']['messages']) : 'Ошибка при получении данных';
		} else {
			$result = $unloading->updateStatusById($status, $order_id);
		}

		wp_send_json([
			'success' => true,
			'data' => $result,
			'msg' => ""
		]);
	}

	public function iteratorError($arr)
	{

		foreach ($arr as $key => $val) {

			if (is_array($val)) {
				$this->iteratorError($val);
			} else {
				$this->errorString .= $this->errorString . '<span>' . $val . '</span><br>';
			}
		}
	}

	public function getAddField()
	{
		// Nonce verification
		if( !isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'wc-esl-shipping') ) {
			wp_send_json_error('Security check failed');
			return;
		}

		// Permission check
		if( !current_user_can('manage_woocommerce') ) {
			wp_send_json_error('Insufficient permissions');
			return;
		}

		$type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : null;

		$optionsRepository = new OptionsRepository();
		$apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

		$additional = array(
			'key'     => $apiKey,
			'service' => mb_strtolower($type),
			'detail'  => true
		);

		$eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
		$additionalFields = $eshopLogisticApi->apiExportAdditional($additional);
		$addFieldSaved = $optionsRepository->getOption('wc_esl_shipping_add_field_form');
		$methodDelivery = new ExportFileds();
		$fieldDelivery  = $methodDelivery->exportFields(mb_strtolower($type));

		$html = '<form action="/" method="post" id="eslAddFieldForm" data-type="' . esc_attr($type) . '">';
		if (is_object($additionalFields) && method_exists($additionalFields, 'hasErrors') && $additionalFields->hasErrors()) {
			$html .= '<p>Ошибка при получении дополнительных услуг</p>';
		} else {
			if (is_object($additionalFields) && method_exists($additionalFields, 'data')) {
				$additionalFields = $additionalFields->data();
			}
			// Если $additionalFields уже массив, ничего не делаем
			if (is_array($additionalFields)) {
				$additionalFieldsRu = array(
					'packages'  => 'Упаковка',
					'cargo'     => 'Груз',
					'recipient' => 'Получатель',
					'other'     => 'Другие услуги',
				);
				$type = mb_strtolower($type);
				$html .= '<div class="esl-box_add">';
				foreach ($additionalFields as $key => $value) {
					$title = ($additionalFieldsRu[$key]) ?? $key;
					$html .= '<p>' . esc_html($title) . '</p>';
					if (is_array($value)) {
						foreach ($value as $k => $v) {
							if (!isset($v['name']))
								continue;
							$valueSaved = '0';
							if (isset($addFieldSaved[$type][$k]) && $addFieldSaved[$type][$k] != '0') {
								$valueSaved = $addFieldSaved[$type][$k];
							}
							$html .= '<div class="form-field_add">';
							$html .= '<label class="label" for="' . esc_attr($k) . '">' . esc_html($v['name']) . '</label>';
							if ($v['type'] === 'integer') {
								$html .= '<input class="form-value_add" type="number" name="' . esc_attr($k) . '" value="' . esc_attr($valueSaved) . '" max="' . esc_attr($v['max_value']) . '">';
							} else {
								$check = '';
								if ($valueSaved != '0')
									$check = 'checked="checked"';
								$html .= '<input class="form-value_add" name="' . esc_attr($k) . '" type="checkbox" ' . $check . '>';
							}
							$html .= '</div>';
						}
					} // если $value не массив, ничего не делаем
				}
				$html .= '</div>';
			} else {
				$html .= '<p>Дополнительные услуги отсутствуют.</p>';
			}
		}

		if ($fieldDelivery) {
			$html .= ' <h4>Дополнительные настройки выгрузки ТК.</h4>';
			// Внешний цикл по массиву полей
			foreach ($fieldDelivery as $nameArr => $arr) {
				// Внутренний цикл по каждому полю
				foreach ($arr as $key => $value) {
					// Разбиваем ключ на части
					list($name, $typeField, $nameRu) = explode('||', $key);
					$nameRu = $nameRu ?? $name;
					$styleForm = '';

					// Устанавливаем специальный класс для чекбоксов
					if ($typeField === 'checkbox') {
						$styleForm = 'checkbox-area';
					}

					// Выводим контейнер поля формы
					$html .= '
                                <div class="form-field_add ' . esc_attr($styleForm) . '">
                                <label class="label" for="' . esc_attr($name) . '">' . esc_html($nameRu) . '</label>
                                ';


					$nameValue = $nameArr . '[' . $name . ']';
					$nameFiledSaved = $nameArr . '[' . $name . ']';
					// Генерируем соответствующее поле ввода
					switch ($typeField) {
						case 'text':
							$valueSaved = '';
							if (isset($addFieldSaved[$type][$nameFiledSaved])) {
								$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
							}
						$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="text" value="' . esc_attr($valueSaved) . '">';
						break;

					case 'checkbox':
						$valueSaved = '';
						if (isset($addFieldSaved[$type][$nameFiledSaved]) && $addFieldSaved[$type][$nameFiledSaved] == 'on') {
							$valueSaved = 'checked';
						}
						$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="checkbox" ' . esc_attr($valueSaved) . '>';
						break;

					case 'date':
						$valueSaved = '';
						if (isset($addFieldSaved[$type][$nameFiledSaved])) {
							$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
						}
						$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="date" value="' . esc_attr($valueSaved) . '">';
						break;

					case 'number':
						$valueSaved = '';
						if (isset($addFieldSaved[$type][$nameFiledSaved])) {
							$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
						}
						$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="number" value="' . esc_attr($valueSaved) . '">';
						break;

					case 'time':
						$valueSaved = '';
						if (isset($addFieldSaved[$type][$nameFiledSaved])) {
							$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
						}
						$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="time" value="' . esc_attr($valueSaved) . '">';
						break;

					case 'select':
						$html .= '<select class="form-value" name="' . esc_attr($nameValue) . '">';
						// Цикл по опциям селекта
						if (is_array($value)) {
							foreach ($value as $k => $v) {
								if (is_array($v) && isset($v['text'])) {
									$valueSaved = '';
									if (isset($addFieldSaved[$type][$nameFiledSaved]) && $k == $addFieldSaved[$type][$nameFiledSaved]) {
										$valueSaved = 'selected';
									}
									$html .= '<option value="' . esc_attr($k) . '" ' . esc_attr($valueSaved) . '>' . esc_html($v['text']) . '</option>';
								} else {
									$valueSaved = '';
									if (isset($addFieldSaved[$type][$nameFiledSaved]) && $k == $addFieldSaved[$type][$nameFiledSaved]) {
										$valueSaved = 'selected';
									}
									$html .= '<option value="' . esc_attr($k) . '" ' . esc_attr($valueSaved) . '>' . esc_html($v) . '</option>';
								}
							}
						}
						$html .= '</select>';
						break;
				}

				$html .= '</div>';
			}
		}
	}

	$sttExForOneDelivery  = $methodDelivery->settingsExportForOneDelivery(mb_strtolower($type));

	if ($sttExForOneDelivery) {
		foreach ($sttExForOneDelivery as $nameArr => $arr) {
			foreach ($arr as $key => $value) {
					list($name, $typeField, $nameRu, $valueDefault) = explode('||', $key);
					$nameRu = $nameRu ?? $name;
					$styleForm = '';

					if ($typeField == 'hr') {
						$html .= '<h3>' . $nameRu . '</h3>';
						continue;
					}


					$html .= '
                                <div class="form-field_add ' . $styleForm . '">
                                <label class="label" for="' . $name . '">' . $nameRu . '</label>';

					$nameValue = $nameArr . '[' . $name . ']';
					$nameFiledSaved = $nameArr . '[' . $name . ']';

					switch ($typeField) {
						case 'text':
							$valueSaved = $valueDefault ?? '';
							if (isset($addFieldSaved[$type][$nameFiledSaved])) {
								$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
							}
							$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="text" value="' . esc_attr($valueSaved) . '">';
							break;

						case 'checkbox':
							$valueSaved = '';
							if (isset($addFieldSaved[$type][$nameFiledSaved]) && $addFieldSaved[$type][$nameFiledSaved] == 'on') {
								$valueSaved = 'checked';
							}
							$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="checkbox" ' . esc_attr($valueSaved) . '>';
							break;

						case 'date':
							$valueSaved = '';
							if (isset($addFieldSaved[$type][$nameFiledSaved])) {
								$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
							}
							$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="date" value="' . esc_attr($valueSaved) . '">';
							break;

						case 'number':
							$valueSaved = '';
							if (isset($addFieldSaved[$type][$nameFiledSaved])) {
								$valueSaved = $addFieldSaved[$type][$nameFiledSaved];
							}
							$html .= '<input class="form-value" name="' . esc_attr($nameValue) . '" type="number" value="' . esc_attr($valueSaved) . '">';
							break;

						case 'select':
							$html .= '<select class="form-value" name="' . esc_attr($nameValue) . '">';
							foreach ($value as $k => $v) {
								if (is_array($v) && isset($v['text'])) {
									$valueSaved = '';
									if (isset($addFieldSaved[$type][$nameFiledSaved]) && $k == $addFieldSaved[$type][$nameFiledSaved]) {
										$valueSaved = 'selected';
									}
									$html .= '<option value="' . esc_attr($k) . '" ' . esc_attr($valueSaved) . '>' . esc_html($v['text']) . '</option>';
								} else {
									$valueSaved = '';
									if (isset($addFieldSaved[$type][$nameFiledSaved]) && $k == $addFieldSaved[$type][$nameFiledSaved]) {
										$valueSaved = 'selected';
									}
									$html .= '<option value="' . esc_attr($k) . '" ' . esc_attr($valueSaved) . '>' . esc_html($v) . '</option>';
								}
							}
							$html .= '</select>';
							break;
					}

					$html .= '</div>';
				}
			}
		}

		$checkSelf = '';
		$checkTK = '';
		if (isset($addFieldSaved[$type]['pick_up']) && $addFieldSaved[$type]['pick_up'] == 0) {
			$checkSelf = 'selected';
		}
		if (isset($addFieldSaved[$type]['pick_up']) && $addFieldSaved[$type]['pick_up'] == 1) {
			$checkTK = 'selected';
		}
		$html .= '
            <h4>Дополнительные настройки ТК.</h4>
            <div class="form-field_add">
                <label class="label">Способ доставки до терминала ТК</label>
                 <select name="pick_up" class="form-value">
                    <option value="0" ' . esc_attr($checkSelf) . '>Сами привезём на терминал транспортной компании</option>
                    <option value="1" ' . esc_attr($checkTK) . '>Груз заберёт транспортная компания</option>
                 </select>
            </div>
        ';

		$html .= '</form>';

		wp_send_json([
			'success' => true,
			'data' => $html,
			'msg' => ""
		]);
	}

	private function sanitize_array($array)
	{
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$array[$key] = $this->sanitize_array($value);
			} else {
				// Если ожидается строка, очищаем, иначе оставляем как есть
				$array[$key] = is_string($value) ? sanitize_text_field($value) : $value;
			}
		}
		return $array;
	}
}
