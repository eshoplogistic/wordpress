<?php

namespace eshoplogistic\WCEshopLogistic\Contracts;

if ( ! defined('ABSPATH') ) {
    exit;
}

interface ModuleInterface
{
    /**
     * Boot function
     *
     * @return void
     */
    public function init();
}