<?php

namespace eshoplogistic\WCEshopLogistic\Classes;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\EslLogger;
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

		EslLogger::debug( '[ESL isEnable] checking plugin state', array(
			'plugin_enable'   => $pluginEnable,
			'api_key'         => empty( $apiKey ) ? 'EMPTY' : 'SET',
			'payment_methods' => $paymentMethods,
			'account_blocked' => $accountBlocked,
		) );

		if ( $pluginEnable !== '1' ) {
			EslLogger::debug( '[ESL isEnable] BLOCKED: plugin_enable != 1' );
			return false;
		}

		if ( empty( $apiKey ) ) {
			EslLogger::debug( '[ESL isEnable] BLOCKED: api_key is empty' );
			return false;
		}

		if ( empty( $paymentMethods ) ) {
			EslLogger::debug( '[ESL isEnable] WARNING: payment_methods is empty (will affect cost calculation)' );
		}

		if ( ! isset( $accountBlocked ) ) {
			EslLogger::debug( '[ESL isEnable] BLOCKED: account_blocked is not set' );
			return false;
		}

		if ( $accountBlocked === '1' ) {
			EslLogger::debug( '[ESL isEnable] BLOCKED: account_blocked = 1' );
			return false;
		}

		EslLogger::debug( '[ESL isEnable] OK, plugin is enabled' );
		return true;
	}
}