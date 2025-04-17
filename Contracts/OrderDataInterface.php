<?php

namespace eshoplogistic\WCEshopLogistic\Contracts;

if ( ! defined('ABSPATH') ) {
    exit;
}

interface OrderDataInterface
{
    /**
     * @return string
     */
    public function getHash();

    /**
     * @return OfferInterface
     */
    public function getItems();
}