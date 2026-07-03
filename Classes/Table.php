<?php

namespace eshoplogistic\WCEshopLogistic\Classes;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use WP_List_Table;

defined( 'ABSPATH' ) || exit;

class Table extends WP_List_Table {

	function __construct() {
		parent::__construct( array(
			'singular' => 'esl_list_text_link',
			'plural'   => 'esl_list_links',
			'ajax'     => false
		) );
	}

	function get_columns() {
		return array(
			'product_id'     => __( 'ID', 'eshoplogisticru' ),
			'name'   => __( 'Имя', 'eshoplogisticru' ),
			'quantity'  => __( 'Кол-во', 'eshoplogisticru' ),
			'price'  => __( 'Цена', 'eshoplogisticru' ),
			'weight' => __( 'Вес', 'eshoplogisticru' ),
			'width'  => __( 'Ширина', 'eshoplogisticru' ),
			'length' => __( 'Длина', 'eshoplogisticru' ),
			'height' => __( 'Высота', 'eshoplogisticru' ),
			'delete' => __( 'Удалить', 'eshoplogisticru' ),
		);
	}

	public function get_sortable_columns() {
		return $sortable = array(
			'col_link_id'      => 'link_id',
			'col_link_name'    => 'link_name',
			'col_link_count' => 'link_price'
		);
	}

	function prepare_items($items = array(), $typeMethod = array()) {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		$allowed_orderby = array('product_id', 'name', 'quantity', 'price', 'weight', 'width', 'length', 'height');
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sorting params in admin table UI.
		$orderbyRaw = isset($_GET['orderby']) ? sanitize_key(wp_unslash($_GET['orderby'])) : '';
		$orderby = in_array($orderbyRaw, $allowed_orderby, true) ? $orderbyRaw : 'product_id';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only sorting params in admin table UI.
		$orderRaw = isset($_GET['order']) ? strtolower(sanitize_key(wp_unslash($_GET['order']))) : 'asc';
		$order = in_array($orderRaw, array('asc', 'desc'), true) ? strtoupper($orderRaw) : 'ASC';
		$perpage = 5;
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination param in admin table UI.
		$pagedRaw = isset($_GET['paged']) ? absint(wp_unslash($_GET['paged'])) : 1;
		$paged = $pagedRaw > 0 ? $pagedRaw : 1;
		$offset = ($paged - 1) * $perpage;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- ORDER BY identifiers are allow-listed above.
		$query = "SELECT * FROM $wpdb->links ORDER BY $orderby $order LIMIT %d, %d";

		$cache_key = 'wc_esl_table_totalitems_' . md5($query . $offset . $perpage);
		$totalitems = wp_cache_get($cache_key, 'eshoplogisticru');
		if ($totalitems === false) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core links table count in admin list context, uses core $wpdb table name.
			$totalitems = (int) $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->links");
			wp_cache_set($cache_key, $totalitems, 'eshoplogisticru', 60); // кэш на 60 секунд
		}
		$totalpages = ceil($totalitems / $perpage);
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query is built from static column/table names and class constants, not user input.
		$query = $wpdb->prepare($query, $offset, $perpage);
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page"    => $perpage,
		) );

		$columns                           = $this->get_columns();
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WordPress core global, must use this exact name.
		$_wp_column_headers[ $screen->id ] = $columns;

		$records = array();
		if($items){
            $optionsRepository  = new OptionsRepository();
            $addFieldSaved = $optionsRepository->getOption('wc_esl_shipping_add_field_form');

            if(isset($addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[merge_in_one]']) && $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[merge_in_one]']){
                $mergeRecords = array();
                $i = 0;
                $mergeRecordsKey = '';
                foreach ($items as $key=>$item){

                    $records[$key] = $item->get_data();

                    if($i == 0){
                        $mergeRecords[$key] = $records[$key];
                        $mergeRecordsKey = $key;
                    }

                    if( isset($records[$key]['variation_id']) && $records[$key]['variation_id'] != 0){
                        $idProduct = $records[$key]['variation_id'];
                    }else{
                        $idProduct = $records[$key]['product_id'];
                    }

                    $getProductDetail = wc_get_product( $idProduct );
                    if(!$getProductDetail)
                        continue;

                    $sku = $getProductDetail->get_sku();
                    if($sku){
                        $records[$key]['product_id'] = $sku;
                    }
                    $price = $getProductDetail->get_price();
                    $weight = $getProductDetail->get_weight();
                    $width = $getProductDetail->get_width();
                    $length = $getProductDetail->get_length();
                    $height = $getProductDetail->get_height();
                    $quantity = $records[$key]['quantity'] ?? 1;

                    $pricePre = $mergeRecords[$mergeRecordsKey]['price'] ?? 0;
                    $weightPre = $mergeRecords[$mergeRecordsKey]['weight'] ?? 0;
                    $widthPre = $mergeRecords[$mergeRecordsKey]['width'] ?? 0;
                    $lengthPre = $mergeRecords[$mergeRecordsKey]['length'] ?? 0;
                    $heightPre = $mergeRecords[$mergeRecordsKey]['height']?? 0;
                    $quantitytPre = $mergeRecords[$mergeRecordsKey]['quantityPre']?? 0;

                    $weight = (isset($weight))?round(floatval($weight),2):0;
                    $width = (isset($width))?round(floatval($width),2):0;
                    $length = (isset($length))?round(floatval($length),2):0;
                    $height = (isset($height))?round(floatval($height),2):0;

                    $mergeRecords[$mergeRecordsKey]['quantity'] = $quantity + $quantitytPre;
                    $mergeRecords[$mergeRecordsKey]['quantityPre'] = $quantity;
                    $mergeRecords[$mergeRecordsKey]['price'] = ($price * $quantity) + $pricePre;
                    $mergeRecords[$mergeRecordsKey]['weight'] = ($weight * $quantity) + $weightPre;
                    $mergeRecords[$mergeRecordsKey]['name'] = $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_name]'] ?? 'Товар';

                    if(isset($addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_width]']) && $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_width]']){
                        $mergeRecords[$mergeRecordsKey]['width'] = $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_width]'];
                    }else{
                        $mergeRecords[$mergeRecordsKey]['width'] = ($width > $widthPre)?$width:$widthPre;
                    }

                    if(isset($addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_length]']) && $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_length]']){
                        $mergeRecords[$mergeRecordsKey]['length'] = $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_length]'];
                    }else{
                        $mergeRecords[$mergeRecordsKey]['length'] = ($length > $lengthPre)?$length:$lengthPre;
                    }

                    if(isset($addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_height]']) && $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_height]']){
                        $mergeRecords[$mergeRecordsKey]['height'] = $addFieldSaved[$typeMethod['name']]['export_stt_one_delivery[default_stt_one_delivery_height]'];
                    }else{
                        $mergeRecords[$mergeRecordsKey]['height'] = ($height > $heightPre)?$height:$heightPre;
                    }

                    $i++;
                }

                $records = $mergeRecords;
            }else{
                foreach ($items as $key=>$item){

                    $records[$key] = $item->get_data();
                    if( isset($records[$key]['variation_id']) && $records[$key]['variation_id'] != 0){
                        $idProduct = $records[$key]['variation_id'];
                    }else{
                        $idProduct = $records[$key]['product_id'];
                    }

                    $getProductDetail = wc_get_product( $idProduct );
                    if(!$getProductDetail)
                        continue;

                    $sku = $getProductDetail->get_sku();
                    if($sku){
                        $records[$key]['product_id'] = $sku;
                    }
                    $price = $getProductDetail->get_price();
                    $weight = $getProductDetail->get_weight();
                    $width = $getProductDetail->get_width();
                    $length = $getProductDetail->get_length();
                    $height = $getProductDetail->get_height();
                    $records[$key]['price'] = $price;
                    $records[$key]['weight'] = (isset($weight))?round(floatval($weight),2):0;
                    $records[$key]['width'] = (isset($width))?round(floatval($width),2):0;
                    $records[$key]['length'] = (isset($length))?round(floatval($length),2):0;
                    $records[$key]['height'] = (isset($height))?round(floatval($height),2):0;
                }
            }


		}

		//$records[] = $wpdb->get_results( $query );
		$this->items = $records;
	}

}