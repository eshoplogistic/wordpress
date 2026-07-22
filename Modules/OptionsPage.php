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
	 * @var UnloadingOrder $unloading
	 */
	private $unloading;

	public function __construct()
	{
		$this->option = new OptionsRepository();
		$this->paymentGateways = new PaymentGatewaysRepository();
		$this->unloading = new UnloadingOrder();
	}

	public function init()
	{
		add_action( 'admin_menu', [$this, 'registerOptionsPage'], 99 );
		add_action( 'admin_notices', [$this, 'renderPaymentMethodsNotice'] );
	}

	public function renderPaymentMethodsNotice()
	{
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only page check, no state change.
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( $page !== 'wc_esl_options' || ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$pluginEnable   = $this->option->getOption( 'wc_esl_shipping_plugin_enable' );
		$apiKey         = $this->option->getOption( 'wc_esl_shipping_api_key' );
		$paymentMethods = $this->option->getOption( 'wc_esl_shipping_payment_methods' );

		if ( $pluginEnable !== '1' || empty( $apiKey ) || ! empty( $paymentMethods ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html__( 'eShopLogistic: не сопоставлены методы оплаты с методами оплаты сервиса (вкладка «Оплата и виджет») — это повлияет на расчёт стоимости доставки.', 'eshoplogistic' )
		);
	}

	public function registerOptionsPage()
	{
		add_menu_page(
			__( 'Настройки WC eShopLogistic', 'eshoplogistic' ),
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
		$options = $this->options();
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
		echo View::render('settings', $options);
	}

	private function options(): array
	{
		$plugin_enable = $this->option->getOption('wc_esl_shipping_plugin_enable');
		$plugin_enable_price_shipping = $this->option->getOption('wc_esl_shipping_plugin_enable_price_shipping');
		$plugin_enable_log = $this->option->getOption('wc_esl_shipping_plugin_enable_log');
		$api_key = $this->option->getOption('wc_esl_shipping_api_key');
		$api_key_wcart = $this->option->getOption('wc_esl_shipping_api_key_wcart');
		$api_key_ya = $this->option->getOption('wc_esl_shipping_api_key_ya');
		$widget_but = $this->option->getOption('wc_esl_shipping_widget_but');
		$dimension_measurement = $this->option->getOption('wc_esl_shipping_dimension_measurement');
		$frame_enable = $this->option->getOption('wc_esl_shipping_frame_enable');
		
		return array(
			'wc_esl_plugin_enable'     => $plugin_enable,
			'wc_esl_plugin_enable_price_shipping' => $plugin_enable_price_shipping,
			'wc_esl_plugin_enable_log' => $plugin_enable_log,
			'wc_esl_api_key'           => $api_key,
			'wc_esl_api_key_wcart'           => $api_key_wcart,
			'wc_esl_api_key_ya'           => $api_key_ya,
			'wc_esl_secret_code'       => $this->option->getOption('wc_esl_shipping_widget_secret_code'),
			'wc_esl_widget_key'        => $this->option->getOption('wc_esl_shipping_widget_key'),
			'wc_esl_widget_but'        => $widget_but,
			'wc_esl_paymentGateways'   => $this->paymentGateways->getAvailablePaymentGateways(),
			'wc_esl_paymentMethods'    => $this->option->getOption('wc_esl_shipping_payment_methods'),
			'wc_esl_dimension_measurement'     => $dimension_measurement,
			'wc_esl_add_form'     => $this->option->getOption('wc_esl_shipping_add_form'),
			'wc_esl_export_form'     => $this->option->getOption('wc_esl_shipping_export_form'),
			'wc_esl_frame_enable'     => $frame_enable,
			'wc_esl_status_form'     => $this->option->getOption('wc_esl_shipping_plugin_status_form'),
			'wc_esl_status_wp'     => $this->unloading->getStatusWp(),
			'wc_esl_add_field_form'     => $this->option->getOption('wc_esl_shipping_add_field_form'),
		);
	}
}