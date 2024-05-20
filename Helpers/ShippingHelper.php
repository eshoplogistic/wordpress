<?php

namespace eshoplogistic\WCEshopLogistic\Helpers;

use Automattic\WooCommerce\Utilities\OrderUtil;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

class ShippingHelper
{
	public $adressRequired = array(
		'dostavista',
	);

    public function isEslMethod($methodId)
    {
        return (strpos($methodId, WC_ESL_PREFIX) !== false);
    }

    public function getTypeMethod($methodId)
    {
        if(!$this->isEslMethod($methodId)) return null;

        $idWithoutPrefix = explode(WC_ESL_PREFIX, $methodId)[1];
        
        return explode('_', $idWithoutPrefix)[1];
    }

    public function getSlugMethod($methodId)
    {
        if(!$this->isEslMethod($methodId)) return null;

        $idWithoutPrefix = explode(WC_ESL_PREFIX, $methodId)[1];
        
        return explode('_', $idWithoutPrefix)[0];
    }

	public function getAdressRequired($delivery, $shipping_method)
	{
		if(!$shipping_method) return null;
		if(!isset(explode(WC_ESL_PREFIX, $shipping_method)[1])) return null;

		$result = array(
			'current' => false,
			'adress_required' => false,
		);
		$idWithoutPrefix = explode(WC_ESL_PREFIX, $shipping_method)[1];
		$idWithoutPrefix =  explode('_', $idWithoutPrefix)[0];
		$nameCurrectDelivery = $shipping_method;
		if($idWithoutPrefix)
			$nameCurrectDelivery = $idWithoutPrefix;

		if(in_array($nameCurrectDelivery, $this->adressRequired)){
			$result['current'] = true;
		}

		if(in_array($delivery, $this->adressRequired)){
			$result['adress_required'] = true;
		}
		return $result;
	}

	public function get_the_user_ip() {
		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			//check ip from share internet
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			//to check ip is pass from proxy
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return apply_filters( 'edd_get_ip', $ip );
	}

	public function dimensionsOption($dimension)
	{
		$dimension = (float)$dimension;
		$optionsRepository = new OptionsRepository();
		$dimensionMeasurement = $optionsRepository->getOption('wc_esl_shipping_dimension_measurement');
		if(!$dimension)
			return 0;

		switch ($dimensionMeasurement) {
			case 'cm':
				return $dimension;
				break;

			case 'm':
				return $dimension * 100;
				break;

			case 'mm':
				return $dimension / 10;
				break;

			case 'in':
				return $dimension / 0.39370;
				break;

			case 'yd':
				return $dimension / 0.010936;
				break;

			default:
				return $dimension;
				break;
		}
	}

	public function weightOption($weight)
	{
		$weight = (float)$weight;

		switch (get_option('woocommerce_weight_unit')) {
			case 'kg':
				return $weight;
				break;

			case 'g':
				return $weight / 1000;
				break;

			case 'lbs':
				return $weight / 2.2046;
				break;

			case 'oz':
				return $weight / 35.274;
				break;

			default:
				return $weight;
				break;
		}
	}

	public function admin_post_type () {
		global $post, $parent_file, $typenow, $current_screen, $pagenow;

		$post_type = NULL;

		if($post && (property_exists($post, 'post_type') || method_exists($post, 'post_type')))
			$post_type = $post->post_type;

		if(empty($post_type) && !empty($current_screen) && (property_exists($current_screen, 'post_type') || method_exists($current_screen, 'post_type')) && !empty($current_screen->post_type))
			$post_type = $current_screen->post_type;

		if(empty($post_type) && !empty($typenow))
			$post_type = $typenow;

		if(empty($post_type) && isset($_REQUEST['post']) && !empty($_REQUEST['post']) && function_exists('get_post_type') && $get_post_type = get_post_type((int)$_REQUEST['post']))
			$post_type = $get_post_type;

		if(empty($post_type) && isset($_REQUEST['post_type']) && !empty($_REQUEST['post_type']))
			$post_type = sanitize_key($_REQUEST['post_type']);

		if(empty($post_type) && 'edit.php' == $pagenow)
			$post_type = 'post';

		if(empty($post_type) && 'admin.php' == $pagenow)
			$post_type = 'admin';

		return $post_type;
	}

	public function HPOS_is_enabled(){
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled()) {
			return true;
		}
		return false;
	}
}