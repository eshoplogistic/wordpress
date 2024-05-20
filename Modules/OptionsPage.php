<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Classes\View;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository;

if ( ! defined('ABSPATH') ) {
	exit;
}

class OptionsPage implements ModuleInterface
{
	/**
	 * @var OptionsRepository $option
	 */
	private $option;

	/**
	 * @var PaymentGatewaysRepository $paymentGateways
	 */
	private $paymentGateways;

	/**
	 * @var Unloading $unloading
	 */
	private $unloading;

	public function __construct()
	{
		$this->option = new OptionsRepository();
		$this->paymentGateways = new PaymentGatewaysRepository();
		$this->unloading = new Unloading();
	}

	public function init()
	{
		add_action( 'admin_menu', [$this, 'registerOptionsPage'], 99 );
	}

	public function registerOptionsPage()
	{
		add_menu_page(
			__( 'Настройки WC eShopLogistic', WC_ESL_DOMAIN ),
			'WC eShopLogistic',
			'manage_options',
			'wc_esl_options',
			[$this, 'html'],
			'dashicons-car', // WC_ESL_PLUGIN_URL . 'assets/images/menu-icon.png'
			'57.25'
		);
	}

	public function html()
	{
		echo View::render('settings', $this->options());
	}

	private function options(): array
	{
		return array(
			'plugin_enable'     => $this->option->getOption('wc_esl_shipping_plugin_enable'),
			'plugin_enable_price_shipping' => $this->option->getOption('wc_esl_shipping_plugin_enable_price_shipping'),
			'plugin_enable_log' => $this->option->getOption('wc_esl_shipping_plugin_enable_log'),
			'plugin_enable_api_v2' => $this->option->getOption('wc_esl_shipping_plugin_enable_api_v2'),
			'api_key'           => $this->option->getOption('wc_esl_shipping_api_key'),
			'api_key_wcart'           => $this->option->getOption('wc_esl_shipping_api_key_wcart'),
			'api_key_ya'           => $this->option->getOption('wc_esl_shipping_api_key_ya'),
			'secret_code'       => $this->option->getOption('wc_esl_shipping_widget_secret_code'),
			'widget_key'        => $this->option->getOption('wc_esl_shipping_widget_key'),
			'widget_but'        => $this->option->getOption('wc_esl_shipping_widget_but'),
			'paymentGateways'   => $this->paymentGateways->getAvailablePaymentGateways(),
			'paymentMethods'    => $this->option->getOption('wc_esl_shipping_payment_methods'),
			'dimension_measurement'     => $this->option->getOption('wc_esl_shipping_dimension_measurement'),
			'add_form'     => $this->option->getOption('wc_esl_shipping_add_form'),
			'export_form'     => $this->option->getOption('wc_esl_shipping_export_form'),
			'frame_enable'     => $this->option->getOption('wc_esl_shipping_frame_enable'),
			'status_form'     => $this->option->getOption('wc_esl_shipping_plugin_status_form'),
			'status_wp'     => $this->unloading->getStatusWp(),
		);
	}
}