<?php

namespace eshoplogistic\WCEshopLogistic\Cron;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Http\Controllers\OptionsController;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined( 'WPINC' ) ) {
	die;
}

class SettingsCron implements ModuleInterface
{
	/**
	 * @return void
	 */
	public function init()
	{
		add_action(WC_ESL_PREFIX . 'update_settings', [$this, 'updateSettings']);
	}

	public function updateSettings()
	{
		try {

			$optionsRepository = new OptionsRepository();
	        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

	        if($apiKey) {
	            $optionsController = new OptionsController();
	            $response = $optionsController->saveApiKey($apiKey);
	        }

		} catch(\Exception $e) {
			$logger = new \WC_Logger();
            $logger->debug(__("WC eShopLogistic Cron Error: ", WC_ESL_DOMAIN) . $e->getMessage());
		}
	}
}