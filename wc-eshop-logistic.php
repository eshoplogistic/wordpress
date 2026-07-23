<?php

/**
 * The plugin bootstrap file
 *
 *
 * @link              https://wp.eshoplogistic.ru/
 * @since             2.2.23
 * @package           WC_Eshop_Logistic
 *
 * @wordpress-plugin
 * Plugin Name:       eShopLogistic Shipping Calculator
 * Plugin URI:        https://wp.eshoplogistic.ru/
 * Description:       Integration with eShopLogistic service for shipping calculation with multiple carriers: CDEK, DPD, Boxberry, IML, Post Russia, Delovye Linii, PEC, Dostavista, GTD, Baikal Service and others. Calculates delivery cost and time in cart and product card.
 * Version:           2.2.23
 * Author:            eShopLogistic
 * Author URI:        https://eshoplogistic.ru/p747575
 * License:           GPLv2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       eshoplogisticru
 * Domain Path:       /languages
 */

// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
	add_action( 'admin_notices', function () {
		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html__( 'eShopLogistic Shipping Calculator requires WooCommerce to be installed and active.', 'eshoplogisticru' )
		);
	} );

	return;
}

define( 'WC_ESL_PLUGIN_NAME', plugin_basename(__FILE__) );

define( 'WC_ESL_PLUGIN_URL', plugin_dir_url(__FILE__) );

define( 'WC_ESL_PLUGIN_ENTRY', __FILE__ );

define( 'WC_ESL_PLUGIN_DIR', plugin_dir_path(__FILE__) );

define( 'WC_ESL_VERSION', '2.2.23' );

define( 'WC_ESL_DOMAIN', 'eshoplogisticru' );

define( 'WC_ESL_PREFIX', 'wc_esl_' );

define( 'WC_ESL_MIGRATOR_HISTORY_KEY', 'wc_esl_migrations_history' );

include_once 'autoload.php';
include_once 'globals.php';

\eshoplogistic\WCEshopLogistic\Classes\WCEshopLogistic::instance()->init();