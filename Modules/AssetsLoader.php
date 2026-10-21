<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Classes\Plugin;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;

if ( ! defined('ABSPATH') ) {
	exit;
}

class AssetsLoader implements ModuleInterface
{
	/**
	 * @var Plugin $plugin
	 */
	protected $plugin;

	public function init()
	{
		$this->plugin = new Plugin();

		add_action( 'wp_enqueue_scripts', [ $this, 'loadFrontendAssets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'loadAdminAssets' ] );
		// Используем wp_enqueue_scripts для инъекции конфига вместе со скриптами
		add_action( 'wp_enqueue_scripts', [ $this, 'injectConfigScript' ], 999 );

		add_action( 'before_woocommerce_init', function() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WC_ESL_PLUGIN_DIR.'/wc-eshop-logistic.php', true );
			}
		} );
	}

	public function loadFrontendAssets()
	{
		if(!$this->plugin->isEnable()) return;

		$optionsRepository = new OptionsRepository();
		$frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');
		$widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');
		$usingBlocksCheckout = false;

		if (is_checkout() && !is_wc_endpoint_url('order-received')) {
			$usingBlocksCheckout = has_block('woocommerce/checkout') ||
							   has_block('eshoplogisticru/checkout-shipping') ||
							   has_block('eshoplogisticru/checkout-form');
		}

		// Загружаем фронтенд-скрипты блоков для всех страниц
		wp_enqueue_script(
			'wc_esl_block_frontend_js',
			WC_ESL_PLUGIN_URL . 'assets/js/block-frontend.js',
			[ 'jquery' ],
			WC_ESL_VERSION,
			true
		);

		wp_enqueue_style(
			'wc_esl_block_styles_css',
			WC_ESL_PLUGIN_URL . 'assets/css/block-styles.css',
			[],
			WC_ESL_VERSION
		);

		if(is_checkout() || is_cart()) {
			wp_enqueue_style(
				'wc_esl_modal_css',
				WC_ESL_PLUGIN_URL . 'assets/css/modal.css',
				[],
				WC_ESL_VERSION
			);

			// Стили legacy-чекаута не должны перекрывать UI блочного чекаута.
			if (is_cart() || (is_checkout() && !$usingBlocksCheckout)) {
				wp_enqueue_style(
					'wc_esl_style_css',
					WC_ESL_PLUGIN_URL . 'assets/css/style.css',
					[],
					WC_ESL_VERSION
				);
			}
		}

		if(is_checkout()) {
			wp_enqueue_script(
				'wc_esl_http_client_js',
				WC_ESL_PLUGIN_URL . 'assets/js/http-client.js',
				[],
				WC_ESL_VERSION,
				true
			);
		}

		if(is_checkout() && !$frameEnable) {
			wp_enqueue_script(
				'wc_esl_modal_js',
				WC_ESL_PLUGIN_URL . 'assets/js/modal.js',
				[ 'jquery' ],
				WC_ESL_VERSION,
				true
			);

			wp_enqueue_script(
				'wc_esl_yandex_map_js',
				WC_ESL_PLUGIN_URL . 'assets/js/yandex-map.js',
				[ 'jquery' ],
				WC_ESL_VERSION,
				true
			);

			wp_enqueue_script(
				'wc_esl_checkout_js',
				WC_ESL_PLUGIN_URL . 'assets/js/checkout.js',
				[ 'jquery' ],
				WC_ESL_VERSION,
				true
			);

			$this->injectGlobals('wc_esl_checkout_js');
		}

		if(is_checkout() && empty( is_wc_endpoint_url('order-received'))) {
			// Используются ли блоки Gutenberg для доставки — включает и явную вставку
			// ESL-блока, и голый woocommerce/checkout (калькулятор теперь автоматически
			// доинъектируется в order-summary через GutenbergBlock::injectCheckoutShippingBlock).
			$usingBlocks = $usingBlocksCheckout;

			// SDK cart виджета нужен только когда frame включён. Для блочного чекаута
			// это не нужно: checkout_frame_block.js сам грузит и отслеживает SDK
			// динамически (свой <script data-esl-widget-sdk="cart">), не видит этот
			// подключённый через wp_enqueue_script тег (у него нет такого маркера) и
			// поэтому раньше загружал app.js второй раз с нуля — второй, "спешный"
			// экземпляр не всегда успевал получить полный список служб доставки.
			if ($frameEnable && !$usingBlocks) {
				wp_enqueue_script(
					'wc_esl_app_frame_js_v2',
					'https://api.esplc.ru/widgets/cart/app.js',
					[],
					WC_ESL_VERSION,
					true
				);
			}
			
			// Загружаем checkout_frame_v2.js только если НЕ используются блоки и frame включён
			if ($frameEnable && !$usingBlocks) {
				wp_enqueue_script(
					'wc_esl_modal_js',
					WC_ESL_PLUGIN_URL . 'assets/js/modal.js',
					[ 'jquery' ],
					WC_ESL_VERSION,
					true
				);
				wp_enqueue_script(
					'wc_esl_object_hash',
					WC_ESL_PLUGIN_URL . 'assets/js/object_hash.js',
					[],
					WC_ESL_VERSION,
					true
				);
				wp_enqueue_script(
					'wc_esl_checkout_frame_js_v2',
					WC_ESL_PLUGIN_URL . 'assets/js/checkout_frame_v2.js',
					[ 'jquery' ],
					WC_ESL_VERSION,
					true
				);
				$this->injectGlobals('wc_esl_object_hash');
				$this->injectGlobals('wc_esl_checkout_frame_js_v2');
			}

		}

		if(is_cart()) {
			wp_enqueue_script(
				'wc_esl_cart_js',
				WC_ESL_PLUGIN_URL . 'assets/js/cart.js',
				[ 'jquery' ],
				WC_ESL_VERSION,
				true
			);

			$this->injectGlobals('wc_esl_cart_js');
		}

		$this->injectGlobals('wc_esl_checkout_js');
	}

	/**
	 * Встраивает конфигурационный скрипт inline перед фронтенд-скриптом
	 */
	public function injectConfigScript() {
		if(!$this->plugin->isEnable()) return;

		$optionsRepository = new OptionsRepository();
		$frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');
		$widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');
		$apiKeyWCart = $optionsRepository->getOption('wc_esl_shipping_api_key_wcart');

		// Взаимный fallback: если один из ключей пуст — используем другой
		if (empty($widgetKey) && !empty($apiKeyWCart)) {
			$widgetKey = $apiKeyWCart;
		} elseif (!empty($widgetKey) && empty($apiKeyWCart)) {
			$apiKeyWCart = $widgetKey;
		}

		$addForm = $optionsRepository->getOption('wc_esl_shipping_add_form');
		$paymentCalcEnabled = isset($addForm['paymentCalc']) && $addForm['paymentCalc'] === 'true';

		$billingCityField = !empty($addForm['billingCity']) ? $addForm['billingCity'] : 'billing_city';
		$shippingCityField = !empty($addForm['shippingCity']) ? $addForm['shippingCity'] : 'shipping_city';
		$offAddressCheckEnabled = isset($addForm['offAddressCheck']) && $addForm['offAddressCheck'] === 'true';
		$citySelectModalEnabled = isset($addForm['citySelectModal']) && $addForm['citySelectModal'] === 'true';

		$eslLoaderUrl = '';
		if (!empty($addForm['eslLoader'])) {
			$eslLoaderUrl = wp_get_attachment_image_url($addForm['eslLoader'], 'full') ?: '';
		}

		// Проверяем наличие опции
		$isFrameEnabled = false;
		if ( !empty($frameEnable) ) {
			$isFrameEnabled = in_array($frameEnable, ['yes', 'on', '1', 1, true], true);
		}

		// checkout_frame_block.js грузится динамически через loadExternalScript()
		// в block-frontend.js, в обход wp_enqueue_script — поэтому не получает
		// автоматический ?ver= от WordPress и кэшируется браузером по голому URL.
		// WC_ESL_VERSION годами не бампается при правке этого файла, так что берём
		// mtime — любое изменение файла само сбрасывает кэш.
		$checkoutFrameBlockPath = WC_ESL_PLUGIN_DIR . 'assets/js/checkout_frame_block.js';
		$checkoutFrameBlockVer = file_exists($checkoutFrameBlockPath) ? filemtime($checkoutFrameBlockPath) : WC_ESL_VERSION;

		// Строим конфиг объект как JavaScript
		$config_script = 'window.wcEslBlockFrontend = {' . "\n";
		$config_script .= '    "checkoutFrameBlockVer": ' . json_encode($checkoutFrameBlockVer) . ',' . "\n";
		$config_script .= '    "widgetKey": ' . json_encode($widgetKey) . ',' . "\n";
		$config_script .= '    "apiKeyWCart": ' . json_encode($apiKeyWCart) . ',' . "\n";
		$config_script .= '    "pluginUrl": ' . json_encode(WC_ESL_PLUGIN_URL) . ',' . "\n";
		$config_script .= '    "ajaxUrl": ' . json_encode(admin_url('admin-ajax.php')) . ',' . "\n";
		$config_script .= '    "isCheckout": ' . json_encode(is_checkout() ? "1" : "") . ',' . "\n";
		$config_script .= '    "isCart": ' . json_encode(is_cart() ? "1" : "") . ',' . "\n";
		$config_script .= '    "isProduct": ' . json_encode(is_product() ? "1" : "") . ',' . "\n";
		$config_script .= '    "nonce": ' . json_encode(wp_create_nonce('wc_esl_block_nonce')) . ',' . "\n";
		$config_script .= '    "shippingNonce": ' . json_encode(wp_create_nonce('wc-esl-shipping')) . ',' . "\n";
		$config_script .= '    "checkoutFrameEnabled": ' . json_encode($isFrameEnabled) . ',' . "\n";
		$config_script .= '    "paymentCalc": ' . json_encode($paymentCalcEnabled) . ',' . "\n";
		$config_script .= '    "billingCityField": ' . json_encode($billingCityField) . ',' . "\n";
		$config_script .= '    "shippingCityField": ' . json_encode($shippingCityField) . ',' . "\n";
		$config_script .= '    "offAddressCheck": ' . json_encode($offAddressCheckEnabled) . ',' . "\n";
		$config_script .= '    "citySelectModal": ' . json_encode($citySelectModalEnabled) . ',' . "\n";
		$config_script .= '    "eslLoaderUrl": ' . json_encode($eslLoaderUrl) . ',' . "\n";
		$config_script .= '    "debugFrameEnable": ' . json_encode($frameEnable) . "\n";
		$config_script .= '};' . "\n";
		$config_script .= 'console.log("✓ wcEslBlockFrontend injected:", window.wcEslBlockFrontend);' . "\n";
		$config_script .= 'console.log("  - checkoutFrameEnabled:", window.wcEslBlockFrontend.checkoutFrameEnabled);' . "\n";
		$config_script .= 'console.log("  - widgetKey:", window.wcEslBlockFrontend.widgetKey);' . "\n";

		// Добавляем inline скрипт ПЕРЕД загрузкой block-frontend.js
		wp_add_inline_script('wc_esl_block_frontend_js', $config_script, 'before');
	}

	public function loadAdminAssets($hook_suffix)
	{
		if( $hook_suffix === 'toplevel_page_wc_esl_options' )
		{
			wp_enqueue_style(
				'wc_esl_bootstrap_css',
				WC_ESL_PLUGIN_URL . 'assets/css/bootstrap.min.css',
				[],
				'4.6.2'
			);


			wp_enqueue_style(
				'wc_esl_admin_css',
				WC_ESL_PLUGIN_URL . 'assets/css/admin.css',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/css/admin.css' )
			);

			wp_enqueue_style(
				'wc_esl_modal_css',
				WC_ESL_PLUGIN_URL . 'assets/css/modal.css',
				[],
				WC_ESL_VERSION
			);

			wp_enqueue_script(
				'wc_esl_bootstrap_js',
				WC_ESL_PLUGIN_URL . 'assets/js/bootstrap.min.js',
				[],
				'4.6.2',
				true
			);

			wp_enqueue_script(
				'wc_esl_push_js',
				WC_ESL_PLUGIN_URL . 'assets/js/push.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/push.js' ),
				true
			);

			wp_enqueue_script(
				'wc_esl_http_client_js',
				WC_ESL_PLUGIN_URL . 'assets/js/http-client.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/http-client.js' ),
				true
			);

			wp_enqueue_script(
				'wc_esl_preloader_js',
				WC_ESL_PLUGIN_URL . 'assets/js/preloader.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/preloader.js' ),
				true
			);

			wp_enqueue_script(
				'wc_esl_settings_js',
				WC_ESL_PLUGIN_URL . 'assets/js/settings.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/settings.js' ),
				true
			);

			wp_enqueue_script(
				'wc_esl_sortable_js',
				WC_ESL_PLUGIN_URL . 'assets/js/html5sortable.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/html5sortable.js' ),
				false
			);

			wp_enqueue_script(
				'wc_esl_modal_js',
				WC_ESL_PLUGIN_URL . 'assets/js/modal.js',
				[ 'jquery' ],
				WC_ESL_VERSION,
				true
			);
		}

		$optionsRepository = new OptionsRepository();
		$shippingHelper = new ShippingHelper();
		$pageType = $shippingHelper->admin_post_type();

		if( $pageType === 'shop_order' ){
			wp_enqueue_style(
				'wc_esl_unloading_css',
				WC_ESL_PLUGIN_URL . 'assets/css/unloading.css',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/css/unloading.css' )
			);
			wp_enqueue_script(
				'wc_esl_http_client_js',
				WC_ESL_PLUGIN_URL . 'assets/js/http-client.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/http-client.js' ),
				true
			);
			wp_enqueue_script(
				'wc_esl_preloader_js',
				WC_ESL_PLUGIN_URL . 'assets/js/preloader.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/preloader.js' ),
				true
			);
			wp_enqueue_script(
				'wc_esl_push_js',
				WC_ESL_PLUGIN_URL . 'assets/js/push.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/push.js' ),
				true
			);
			wp_enqueue_script(
				'wc_esl_settings_unloading_js',
				WC_ESL_PLUGIN_URL . 'assets/js/settings_unloading.js',
				[],
				filemtime( WC_ESL_PLUGIN_DIR . 'assets/js/settings_unloading.js' ),
				true
			);
		}

		$this->injectGlobals('wc_esl_http_client_js');
	}

	private function injectGlobals( $scriptId )
	{
		$data = [
			'ajaxUrl'         => admin_url('admin-ajax.php'),
			'homeUrl'         => home_url(),
			'nonce'           => wp_create_nonce('wc-esl-shipping'),
			'eslNonce'        => wp_create_nonce('esl_unloading_action')
		];

		wp_localize_script($scriptId, 'wc_esl_shipping_global', $data);
	}
}