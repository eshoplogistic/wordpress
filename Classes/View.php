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
		extract( $data );
		include $fileName;
		$output = ob_get_clean();

		return $output;
	}
}