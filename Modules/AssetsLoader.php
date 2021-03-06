<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Classes\Plugin;

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
    }

    public function loadFrontendAssets()
    {
        if(!$this->plugin->isEnable()) return;

        if(is_checkout() || is_cart()) {
            wp_enqueue_style(
                'wc_esl_modal_css',
                WC_ESL_PLUGIN_URL . 'assets/plugins/bootstrap/css/modal.min.css',
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

        if(is_checkout()) {
            wp_enqueue_script(
                'wc_esl_modal_transition_js',
                WC_ESL_PLUGIN_URL . 'assets/plugins/bootstrap/js/transition.js',
                [ 'jquery' ],
                WC_ESL_VERSION,
                true
            );

            wp_enqueue_script(
                'wc_esl_modal_js',
                WC_ESL_PLUGIN_URL . 'assets/plugins/bootstrap/js/modal.min.js',
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
                'wc_esl_font_awesome_css',
                WC_ESL_PLUGIN_URL . 'assets/css/all.min.css',
                [],
                '5.15.3'
            );

            wp_enqueue_style(
                'wc_esl_admin_css',
                WC_ESL_PLUGIN_URL . 'assets/css/admin.css',
                [],
                filemtime( WC_ESL_PLUGIN_DIR . 'assets/css/admin.css' )
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