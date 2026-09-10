<?php

namespace eshoplogistic\WCEshopLogistic\Classes;

if ( ! defined('ABSPATH') ) {
	exit;
}

class View
{
	public static function render( $view, $data = [] )
	{
		$fileName = __DIR__ . '/../views/' . $view . '.php';
		
		ob_start();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables are prefixed in the array keys passed to this function
		extract( $data );
		include $fileName;
		$output = ob_get_clean();

		return $output;
	}
}