<?php

namespace eshoplogistic\WCEshopLogistic\Models;

if ( ! defined('ABSPATH') ) {
    exit;
}

class OrderData
{
	/**
	 * @var array $data
	 */
	private $data;

	/**
	 * @param array $data
	 */
	public function __construct(array $data)
	{
		$default = [
			'offers' => [],
			'address' => [
				'billing' => [
					'first_name' => '',
					'email' => '',
					'phone' => '',
					'address_1' => '',
					'address_2' => '',
					'city' => '',
					'state' => '',
					'postcode' => '',
					'country' => '',
				],
				'shipping' => [
					'address_1' => '',
					'address_2' => '',
					'city' => '',
					'state' => '',
					'postcode' => '',
					'country' => '',
				]
			],
			'shipping_method' => [
				'id' => 0,
				'title' => '',
				'cost' => 0
			],
			'payment_method' => [
				'id' => 0,
				'title' => ''
			]
		];

		$this->data = wp_parse_args($data, $default);
	}

	/**
	 * @return string
	 */
	public function save()
	{
		$order = new \WC_Order();
		$shippingItem = new \WC_Order_Item_Shipping();

		foreach($this->data['offers'] as $offer) {
			$product = wc_get_product($offer['id']);

			if($product) {
				$order->add_product($product, $offer['quantity']);
			}
		}

		$order->set_address([
			'first_name' => $this->data['address']['billing']['first_name'],
			'email'      => $this->data['address']['billing']['email'],
			'phone'      => $this->data['address']['billing']['phone'],
			'address_1'  => $this->data['address']['billing']['address_1'],
			'address_2'  => $this->data['address']['billing']['address_2'],
			'city'       => $this->data['address']['billing']['city'],
			'state'      => $this->data['address']['billing']['state'],
			'postcode'   => $this->data['address']['billing']['postcode'],
			'country'    => $this->data['address']['billing']['country']
		], 'billing');

		$order->set_address([
			'address_1'  => $this->data['address']['shipping']['address_1'],
			'address_2'  => $this->data['address']['shipping']['address_2'],
			'city'       => $this->data['address']['shipping']['city'],
			'state'      => $this->data['address']['shipping']['state'],
			'postcode'   => $this->data['address']['shipping']['postcode'],
			'country'    => $this->data['address']['shipping']['country']
		], 'shipping');

		$order->set_payment_method($this->data['payment_method']['id']);
		$order->set_payment_method_title($this->data['payment_method']['title']);

		$shippingItem->set_method_title($this->data['shipping_method']['title']);
		$shippingItem->set_method_id($this->data['shipping_method']['id']);
		$shippingItem->set_total($this->data['shipping_method']['cost']);

		$order->add_item($shippingItem);

		$order->calculate_totals();
		$order->update_status('on-hold');

		return $order->get_order_number();
	}
}