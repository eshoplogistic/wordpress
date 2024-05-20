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
		update_option('woocommerce_currency', 'RUB');
	}
}