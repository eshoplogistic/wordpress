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

		if(is_checkout() || is_cart()) {
			wp_enqueue_style(
				'wc_esl_modal_css',
				WC_ESL_PLUGIN_URL . 'assets/css/modal.css',
				[],
				WC_ESL_VERSION
			);

			wp_enqueue_style(
				'wc_esl_style_css',
				WC_ESL_PLUGIN_URL . 'assets/css/style.css',
				[],
				WC_ESL_VERSION
			);
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

		if(is_checkout() && $frameEnable && empty( is_wc_endpoint_url('order-received'))) {
			wp_enqueue_script(
				'wc_esl_modal_js',
				WC_ESL_PLUGIN_URL . 'assets/js/modal.js',
				[ 'jquery' ],
				WC_ESL_VERSION,
				true
			);
			$moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');

			if(isset($moduleVersion) && $moduleVersion == '1'){
				wp_enqueue_script(
					'wc_esl_app_frame_js_v2',
					'https://api.esplc.ru/widgets/cart/app.js',
					[],
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
			}else{
				wp_enqueue_script(
					'wc_esl_checkout_frame_js',
					WC_ESL_PLUGIN_URL . 'assets/js/checkout_frame.js',
					[ 'jquery' ],
					WC_ESL_VERSION,
					true
				);
				$this->injectGlobals('wc_esl_checkout_frame_js');
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

	public function loadAdminAssets($hook_suffix)
	{
		if( $hook_suffix === 'toplevel_page_wc_esl_options' )
		{
			wp_enqueue_style(
				'wc_esl_bootstrap_css',
				WC_ESL_PLUGIN_URL . 'assets/css/bootstrap.min.css',
				[],
				'4.6.0'
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
				'4.6.0',
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
		$moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');
		$shippingHelper = new ShippingHelper();
		$pageType = $shippingHelper->admin_post_type();

		if( $pageType === 'shop_order' && $moduleVersion){
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
			'ajaxUrl'  => admin_url('admin-ajax.php'),
			'homeUrl'  => home_url(),
			'nonce'    => wp_create_nonce('wc-esl-shipping')
		];

		wp_localize_script($scriptId, 'wc_esl_shipping_global', $data);
	}
}