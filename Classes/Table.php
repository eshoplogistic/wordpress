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

	function extra_tablenav( $which ) {
		if ( $which == "top" ) {
			echo '<input id="buttonModalUnloadAdd" type="button" class="button button-primary" value="Добавить место">';
		}
		if ( $which == "bottom" ) {

		}
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

	function print_column_headers($with_id = true ){
		list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();
		$columns = $this->get_columns();

		$http_host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field($_SERVER['HTTP_HOST']) : '';
		$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '';
		$current_url = set_url_scheme( 'http://' . $http_host . $request_uri );
		$current_url = remove_query_arg( 'paged', $current_url );

		// When users click on a column header to sort by other columns.
		if ( isset( $_GET['orderby'] ) ) {
			$current_orderby = $_GET['orderby'];
			// In the initial view there's no orderby parameter.
		} else {
			$current_orderby = '';
		}

		// Not in the initial view and descending order.
		if ( isset( $_GET['order'] ) && 'desc' === $_GET['order'] ) {
			$current_order = 'desc';
		} else {
			// The initial view is not always 'asc', we'll take care of this below.
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
			$columns['cb']     = '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />
			<label for="cb-select-all-' . $cb_counter . '">' .
			                     '<span class="screen-reader-text">' .
			                     /* translators: Hidden accessibility text. */
			                     __( 'Select All', 'eshoplogisticru' ) .
			                     '</span>' .
			                     '</label>';
			++$cb_counter;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class          = array( 'manage-column', "column-$column_key" );
			$aria_sort_attr = '';
			$abbr_attr      = '';
			$order_text     = '';

			if ( in_array( $column_key, $hidden, true ) ) {
				$class[] = 'hidden';
			}

			if ( 'cb' === $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ), true ) ) {
				$class[] = 'num';
			}

			if ( $column_key === $primary ) {
				$class[] = 'column-primary';
			}

			if ( isset( $sortable[ $column_key ] ) ) {
				$orderby       = isset( $sortable[ $column_key ][0] ) ? $sortable[ $column_key ][0] : '';
				$desc_first    = isset( $sortable[ $column_key ][1] ) ? $sortable[ $column_key ][1] : false;
				$abbr          = isset( $sortable[ $column_key ][2] ) ? $sortable[ $column_key ][2] : '';
				$orderby_text  = isset( $sortable[ $column_key ][3] ) ? $sortable[ $column_key ][3] : '';
				$initial_order = isset( $sortable[ $column_key ][4] ) ? $sortable[ $column_key ][4] : '';

				/*
				 * We're in the initial view and there's no $_GET['orderby'] then check if the
				 * initial sorting information is set in the sortable columns and use that.
				 */
				if ( '' === $current_orderby && $initial_order ) {
					// Use the initially sorted column $orderby as current orderby.
					$current_orderby = $orderby;
					// Use the initially sorted column asc/desc order as initial order.
					$current_order = $initial_order;
				}

				/*
				 * True in the initial view when an initial orderby is set via get_sortable_columns()
				 * and true in the sorted views when the actual $_GET['orderby'] is equal to $orderby.
				 */
				if ( $current_orderby === $orderby ) {
					// The sorted column. The `aria-sort` attribute must be set only on the sorted column.
					if ( 'asc' === $current_order ) {
						$order          = 'desc';
						$aria_sort_attr = ' aria-sort="ascending"';
					} else {
						$order          = 'asc';
						$aria_sort_attr = ' aria-sort="descending"';
					}

					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					// The other sortable columns.
					$order = strtolower( $desc_first );

					if ( ! in_array( $order, array( 'desc', 'asc' ), true ) ) {
						$order = $desc_first ? 'desc' : 'asc';
					}

					$class[] = 'sortable';
					$class[] = 'desc' === $order ? 'asc' : 'desc';

					/* translators: Hidden accessibility text. */
					$asc_text = __( 'Sort ascending.', 'eshoplogisticru' );
					/* translators: Hidden accessibility text. */
					$desc_text  = __( 'Sort descending.', 'eshoplogisticru' );
					$order_text = 'asc' === $order ? $asc_text : $desc_text;
				}

				if ( '' !== $order_text ) {
					$order_text = ' <span class="screen-reader-text">' . $order_text . '</span>';
				}

				// Print an 'abbr' attribute if a value is provided via get_sortable_columns().
				$abbr_attr = $abbr ? ' abbr="' . esc_attr( $abbr ) . '"' : '';

				$column_display_name = sprintf(
					'<a href="%1$s">' .
					'<span>%2$s</span>' .
					'<span class="sorting-indicators">' .
					'<span class="sorting-indicator asc" aria-hidden="true"></span>' .
					'<span class="sorting-indicator desc" aria-hidden="true"></span>' .
					'</span>' .
					'%3$s' .
					'</a>',
					esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ),
					$column_display_name,
					$order_text
				);
			}

			$tag   = ( 'cb' === $column_key ) ? 'td' : 'th';
			$scope = ( 'th' === $tag ) ? 'scope="col"' : '';
			$id    = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . implode( ' ', $class ) . "'";
			}

			echo "<$tag $scope $id $class $aria_sort_attr $abbr_attr>$column_display_name</$tag>";
		}
	}

	function display_rows() {

		$records = $this->items;

		$columns = $this->get_columns();

		if ( ! empty( $records ) ) {
			$i = 0;
			foreach ( $records as $key=>$rec ) {
				if(!$rec)
					continue;

				echo '<tr id="record_' . esc_attr($rec['id']) . '">';
				foreach ( $columns as $column_name => $column_display_name ) {

					$class = "class='column-$column_name' name='$column_name'";
					$style = "";

					$attributes = $class . $style;
					$editlink = '/wp-admin/link.php?action=edit&link_id=' . (int) $rec['id'];

					if($column_name == 'delete'){
						if($i != 0){
							echo '<td ' . $attributes . '><div class="esl-delete_table_elem">&#65794;</div></td>';
						}
					}else{
						echo '<td ' . $attributes . '><input type="text" data-count="'.$i.'" name="products['.$i.']['.$column_name.']" value="'.stripslashes( $rec[$column_name] ).'"/></td>';
					}
				}

				echo '</tr>';
				$i++;
			}
		}
	}

}