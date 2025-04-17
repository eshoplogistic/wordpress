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

		$pluginEnable = $optionsRepository->getOption('wc_esl_shipping_plugin_enable');
		$apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
		$paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');
		$accountBlocked = $optionsRepository->getOption('wc_esl_shipping_account_blocked');

		if($pluginEnable !== '1') return false;

		if(empty($apiKey)) return false;

		if(empty($paymentMethods)) return false;

		if(!isset($accountBlocked)) return false;

		if($accountBlocked === '1') return false;

		return true;
	}
}