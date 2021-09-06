<?php

/**
 * The plugin bootstrap file
 *
 *
 * @link              https://wp.eshoplogistic.ru/
 * @since             1.0.4
 * @package           WC_Eshop_Logistic
 *
 * @wordpress-plugin
 * Plugin Name:       Калькулятор доставки для интернет-магазинов eShopLogisticRu
 * Plugin URI:        https://wp.eshoplogistic.ru/
 * Description:       Несколько служб доставки в одной интеграции: CDEK, DPD, Boxberry, IML, Почта России, Деловые Линии, ПЭК, Dostavista, GTD, Байкал Сервис и др.
 * Version:           1.0.4
 * Author:            eShopLogistic
 * Author URI:        https://eshoplogistic.ru/p747575
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wc-esl
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WC_ESL_PLUGIN_NAME', plugin_basename(__FILE__) );

define( 'WC_ESL_PLUGIN_URL', plugin_dir_url(__FILE__) );

define( 'WC_ESL_PLUGIN_ENTRY', __FILE__ );

define( 'WC_ESL_PLUGIN_DIR', plugin_dir_path(__FILE__) );

define( 'WC_ESL_VERSION', '1.0.4' );

define( 'WC_ESL_DOMAIN', 'wc-esl' );

define( 'WC_ESL_PREFIX', 'wc_esl_' );

define( 'WC_ESL_MIGRATOR_HISTORY_KEY', 'wc_esl_migrations_history' );

include_once 'autoload.php';
include_once 'globals.php';

\eshoplogistic\WCEshopLogistic\Classes\WCEshopLogistic::instance()->init();