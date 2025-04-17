<?php

namespace eshoplogistic\WCEshopLogistic\DB;

if ( ! defined('ABSPATH') ) {
    exit;
}

class ShippingMethodsRepository
{
	private $table;

	public function __construct()
	{
		$this->table = 'wc_esl_shipping_methods';
	}

	public function getAll()
	{
		global $wpdb;

		$query = "SELECT * FROM {$this->table}";

		return $wpdb->get_results( $query );
	}
}