<?php

namespace eshoplogistic\WCEshopLogistic\Classes\Shipping\Methods;

use eshoplogistic\WCEshopLogistic\Classes\Shipping\Base;

class FrameMixed extends Base
{
	/**
	 * Constructor class
	 *
	 * @access public
	 * @return void
	 */
	public function __construct( $instance_id = 0 )
	{

		parent::__construct( $instance_id );

		$slug = 'frame';
		$type = 'mixed';

		$title = 'Калькулятор доставки ESL';

		$this->id                   = WC_ESL_PREFIX . $slug . '_' . $type;
		$this->method_title         = 'Калькулятор доставки ESL';
		$this->method_description   = '';

		$this->init();

		$this->title                = $title;
		$this->type                 = $type;
	}
}