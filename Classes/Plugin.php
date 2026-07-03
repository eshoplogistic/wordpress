<?php

namespace eshoplogistic\WCEshopLogistic\Classes;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class Plugin
{
	/**
	 * @return bool
	 */
	public function isEnable()
	{
		$optionsRepository = new OptionsRepository();

		$pluginEnable    = $optionsRepository->getOption('wc_esl_shipping_plugin_enable');
		$apiKey          = $optionsRepository->getOption('wc_esl_shipping_api_key');
		$paymentMethods  = $optionsRepository->getOption('wc_esl_shipping_payment_methods');
		$accountBlocked  = $optionsRepository->getOption('wc_esl_shipping_account_blocked');
		$eslLog          = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_log');

		$logger = $eslLog && function_exists('wc_get_logger') ? wc_get_logger() : null;

		if ( $logger ) {
			// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_var_export -- Debug logging gated behind admin-configurable log flag, not left over debug code.
			$logger->debug(
				'[ESL isEnable] plugin_enable=' . var_export( $pluginEnable, true )
				. ', api_key=' . ( empty( $apiKey ) ? 'EMPTY' : 'SET' )
				. ', payment_methods=' . var_export( $paymentMethods, true )
				. ', account_blocked=' . var_export( $accountBlocked, true ),
				[ 'source' => 'wc-esl-shipping' ]
			);
			// phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_var_export
		}

		if ( $pluginEnable !== '1' ) {
			if ( $logger ) $logger->debug( '[ESL isEnable] BLOCKED: plugin_enable != 1', [ 'source' => 'wc-esl-shipping' ] );
			return false;
		}

		if ( empty( $apiKey ) ) {
			if ( $logger ) $logger->debug( '[ESL isEnable] BLOCKED: api_key is empty', [ 'source' => 'wc-esl-shipping' ] );
			return false;
		}

		if ( empty( $paymentMethods ) ) {
			if ( $logger ) $logger->debug( '[ESL isEnable] WARNING: payment_methods is empty (will affect cost calculation)', [ 'source' => 'wc-esl-shipping' ] );
		}

		if ( ! isset( $accountBlocked ) ) {
			if ( $logger ) $logger->debug( '[ESL isEnable] BLOCKED: account_blocked is not set', [ 'source' => 'wc-esl-shipping' ] );
			return false;
		}

		if ( $accountBlocked === '1' ) {
			if ( $logger ) $logger->debug( '[ESL isEnable] BLOCKED: account_blocked = 1', [ 'source' => 'wc-esl-shipping' ] );
			return false;
		}

		if ( $logger ) $logger->debug( '[ESL isEnable] OK, plugin is enabled', [ 'source' => 'wc-esl-shipping' ] );
		return true;
	}
}