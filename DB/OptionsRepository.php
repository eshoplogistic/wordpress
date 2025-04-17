<?php

namespace eshoplogistic\WCEshopLogistic\DB;

if (!defined('ABSPATH')) {
	exit;
}

class OptionsRepository
{
	protected $defaults;

	public function __construct()
	{
		$this->defaults = [
			'wc_esl_shipping_api_key' => '',
			'wc_esl_shipping_api_key_wcart' => '',
			'wc_esl_shipping_api_key_ya' => '',
			'wc_esl_shipping_widget_secret_code' => '',
			'wc_esl_shipping_widget_key' => '',
			'wc_esl_shipping_widget_but' => 'Рассчитать доставку',
			'wc_esl_shipping_plugin_enable' => '1',
			'wc_esl_shipping_plugin_enable_price_shipping' => '1',
			'wc_esl_shipping_plugin_enable_log' => '0',
			'wc_esl_shipping_plugin_enable_api_v2' => '0',
			'wc_esl_shipping_account_domain' => '',
			'wc_esl_shipping_account_enable' => '0',
			'wc_esl_shipping_account_balance' => '0',
			'wc_esl_shipping_account_paid_days' => '0',
			'wc_esl_shipping_account_free_days' => '0',
			'wc_esl_shipping_account_services' => [],
			'wc_esl_shipping_account_settings' => [],
			'wc_esl_shipping_account_init_services' => [],
			'wc_esl_shipping_payment_methods' => [],
			'wc_esl_shipping_dimension_measurement' => 'cm',
			'wc_esl_shipping_frame_enable' => '0',
			'wc_esl_shipping_plugin_status_form' => [],
		];
	}

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function getOption(string $key)
	{
		return get_option(
			$key,
			isset($this->defaults[$key]) ? $this->defaults[$key] : null
		);
	}

	/**
	 * @return array
	 */
	public function getAll(): array
	{
		$options = [];

		foreach ($this->defaults as $key => $value) {
			$options[$key] = get_option($key, $value);
		}

		return $options;
	}

	public function save($data)
	{
		foreach ($data['wc_esl_shipping'] as $key => $value) {
			update_option('wc_esl_shipping_' . $key, $value);
		}

		// Flush WooCommerce Shipping Cache
		delete_option('_transient_shipping-transient-version');
	}

	public function deleteAll()
	{
		delete_option('_transient_shipping-transient-version');
	}
}