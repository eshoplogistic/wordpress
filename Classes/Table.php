<?php

namespace eshoplogistic\WCEshopLogistic\Classes;

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

	function extra_tablenav( $which ) {
		if ( $which == "top" ) {
			echo '<input id="buttonModalUnloadAdd" type="button" class="button button-primary" value="Добавить место">';
		}
		if ( $which == "bottom" ) {

		}
	}

	function get_columns() {
		return $columns = array(
			'product_id'     => __( 'ID' ),
			'name'   => __( 'Имя' ),
			'quantity'  => __( 'Кол-во' ),
			'total'  => __( 'Цена' ),
			'weight' => __( 'Вес' ),
			'width'  => __( 'Ширина' ),
			'length' => __( 'Длина' ),
			'height' => __( 'Высота' ),
		);
	}

	public function get_sortable_columns() {
		return $sortable = array(
			'col_link_id'      => 'link_id',
			'col_link_name'    => 'link_name',
			'col_link_count' => 'link_price'
		);
	}

	function prepare_items($items = array()) {
		global $wpdb, $_wp_column_headers;
		$screen = get_current_screen();

		$query = "SELECT * FROM $wpdb->links";
		$orderby = ! empty( $_GET["orderby"] ) ? $_GET["orderby"] : 'ASC';
		$order   = ! empty( $_GET["order"] ) ? $_GET["order"] : '';
		if ( ! empty( $orderby ) & ! empty( $order ) ) {
			$query .= ' ORDER BY ' . $orderby . ' ' . $order;
		}

		$totalitems = $wpdb->query( $query );
		$perpage = 5;
		$paged = ! empty( $_GET["paged"] ) ? $_GET["paged"] : '';
		if ( empty( $paged ) || ! is_numeric( $paged ) || $paged <= 0 ) {
			$paged = 1;
		}
		$totalpages = ceil( $totalitems / $perpage );
		if ( ! empty( $paged ) && ! empty( $perpage ) ) {
			$offset = ( $paged - 1 ) * $perpage;
			$query  .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
		}
		$this->set_pagination_args( array(
			"total_items" => $totalitems,
			"total_pages" => $totalpages,
			"per_page"    => $perpage,
		) );

		$columns                           = $this->get_columns();
		$_wp_column_headers[ $screen->id ] = $columns;

		$records = array();
		if($items){
			foreach ($items as $key=>$item){

				$records[$key] = $item->get_data();
				if( isset($records[$key]['variation_id']) && $records[$key]['variation_id'] != 0){
					$idProduct = $records[$key]['variation_id'];
				}else{
					$idProduct = $records[$key]['product_id'];
				}
				$getProductDetail = wc_get_product( $idProduct );
				$attrProduct = $getProductDetail->get_data();
				$records[$key]['weight'] = (isset($attrProduct['weight']))?round(floatval($attrProduct['weight']),2):0;
				$records[$key]['width'] = (isset($attrProduct['width']))?round(floatval($attrProduct['width']),2):0;
				$records[$key]['length'] = (isset($attrProduct['length']))?round(floatval($attrProduct['length']),2):0;
				$records[$key]['height'] = (isset($attrProduct['height']))?round(floatval($attrProduct['height']),2):0;
			}
		}

		$records[] = $wpdb->get_results( $query );
		$this->items = $records;
	}

	function display_rows() {

		$records = $this->items;

		list( $columns, $hidden ) = $this->get_column_info();

		if ( ! empty( $records ) ) {
			$i = 0;
			foreach ( $records as $key=>$rec ) {
				if(!$rec)
					continue;

				echo '<tr id="record_' . $rec['id'] . '">';
				foreach ( $columns as $column_name => $column_display_name ) {

					$class = "class='column-$column_name' name='$column_name'";
					$style = "";
					if ( in_array( $column_name, $hidden ) ) {
						$style = ' style="display:none;"';
					}
					$attributes = $class . $style;
					$editlink = '/wp-admin/link.php?action=edit&link_id=' . (int) $rec['id'];

					echo '<td ' . $attributes . '><input type="text" data-count="'.$i.'" name="products['.$i.']['.$column_name.']" value="'.stripslashes( $rec[$column_name] ).'"/></td>';
				}

				echo '</tr>';
				$i++;
			}
		}
	}

}