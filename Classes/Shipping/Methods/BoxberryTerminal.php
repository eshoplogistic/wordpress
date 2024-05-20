<?php

namespace eshoplogistic\WCEshopLogistic\Classes\Shipping\Methods;

use eshoplogistic\WCEshopLogistic\Classes\Shipping\Base;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

class BoxberryTerminal extends Base
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

        $slug = 'boxberry';
        $type = 'terminal';

        $option = $this->getOptionMethod($slug);

        $title = isset( $option['name'] ) ? $option['name'] . ': Доставка до пункта выдачи' : '';

        $this->id                   = WC_ESL_PREFIX . $slug . '_' . $type;
        $this->method_title         = isset( $option['name'] ) ? $option['name'] . ': Доставка до пункта выдачи' : '';
        $this->method_description   = '';
        
        $this->init();

        $this->title                = !empty( $this->get_option('title') ) ? $this->get_option('title') : $title;
        $this->type                 = $type;
    }

}