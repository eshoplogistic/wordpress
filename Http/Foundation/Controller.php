<?php

namespace eshoplogistic\WCEshopLogistic\Http\Foundation;

use eshoplogistic\WCEshopLogistic\Contracts\ResponseInterface;

if ( ! defined('ABSPATH')) {
    exit;
}

abstract class Controller
{
    /**
     * @param array $data
     *
     * @return ResponseInterface
     */
    public function json(array $data)
    {
        return new JsonResponse($data);
    }
}