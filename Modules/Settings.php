<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Settings implements ModuleInterface
{
	public function init()
	{
		add_action('init', [$this, 'setWoocommerceCurrency']);
	}

	   public function setWoocommerceCurrency()
	   {
		   if (!current_user_can('manage_options')) {
			   return;
		   }
		   update_option('woocommerce_currency', 'RUB');
	   }
}