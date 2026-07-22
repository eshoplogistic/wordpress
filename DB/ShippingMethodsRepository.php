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

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is a class constant, not user input
		$query = "SELECT * FROM {$this->table}";

		$cache_key = 'wc_esl_shipping_methods_all';
		$results = wp_cache_get($cache_key, 'eshoplogistic');
		if ($results === false) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Repository-level read query with object cache, table name is constant
			$results = $wpdb->get_results( $query );
			wp_cache_set($cache_key, $results, 'eshoplogistic', 60); // кэш на 60 секунд
		}
		return $results;
	}

	public function getById($id)
	{
		global $wpdb;
		$id = absint($id);
		$cache_key = 'wc_esl_shipping_method_' . $id;
		$result = wp_cache_get($cache_key, 'eshoplogistic');
		if (false !== $result) {
			return $result;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is a class constant, not user input
		$query = $wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared -- Repository-level read query with object cache.
		$result = $wpdb->get_row($query);
		wp_cache_set($cache_key, $result, 'eshoplogistic', 60);
		return $result;
	}
}