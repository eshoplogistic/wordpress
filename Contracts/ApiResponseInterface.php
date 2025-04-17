<?php

namespace eshoplogistic\WCEshopLogistic\Contracts;

if ( ! defined('ABSPATH') ) {
    exit;
}

interface ApiResponseInterface
{
    /**
     * @return bool
     */
    public function hasErrors();
}