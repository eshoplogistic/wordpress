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

	public function __construct()
	{
		$this->option = new OptionsRepository();
		$this->paymentGateways = new PaymentGatewaysRepository();
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
			'api_key'           => $this->option->getOption('wc_esl_shipping_api_key'),
			'secret_code'       => $this->option->getOption('wc_esl_shipping_widget_secret_code'),
			'widget_key'        => $this->option->getOption('wc_esl_shipping_widget_key'),
			'paymentGateways'   => $this->paymentGateways->getAvailablePaymentGateways(),
			'paymentMethods'    => $this->option->getOption('wc_esl_shipping_payment_methods')
		);
	}
}