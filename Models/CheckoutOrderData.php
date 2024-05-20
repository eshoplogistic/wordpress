<?php

namespace eshoplogistic\WCEshopLogistic\Models;

use eshoplogistic\WCEshopLogistic\Contracts\OrderDataInterface;
use eshoplogistic\WCEshopLogistic\Contracts\OfferInterface;

if ( ! defined('ABSPATH') ) {
    exit;
}

class CheckoutOrderData implements OrderDataInterface
{
    /**
     * @var array $data
     */
    protected $data;

    /**
     * @var OfferInterface $items[]
     */
    protected $items;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;

        $this->init();
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return WC()->cart->get_cart_hash();
    }

    /**
     * @return OfferInterface[]
     */
    public function getItems()
    {
        return $this->items;
    }

    public function init()
    {
        if(empty($this->data)) return;

        foreach($this->data as $key => $item) {
            $this->items[] = new OfferData($item);
        }
    }
}