<?php

namespace eshoplogistic\WCEshopLogistic\Contracts;

if ( ! defined('ABSPATH')) {
    exit;
}

interface ResponseInterface
{
    public function send();
}