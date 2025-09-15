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

		$cache_key = 'wc_esl_shipping_methods_all';
		$results = wp_cache_get($cache_key, 'eshoplogisticru');
		if ($results === false) {
			$results = $wpdb->get_results( $query );
			wp_cache_set($cache_key, $results, 'eshoplogisticru', 60); // кэш на 60 секунд
		}
		return $results;
	}

	public function getById($id)
	{
		global $wpdb;
		$query = $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", absint($id));
		$result = $wpdb->get_row($query);
		return $result;
	}
}