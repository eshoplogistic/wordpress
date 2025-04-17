<?php

namespace eshoplogistic\WCEshopLogistic\Http\Response;

use eshoplogistic\WCEshopLogistic\Contracts\ApiResponseInterface;

if ( ! defined('ABSPATH') ) {
    exit;
}

class CollectionResponse implements ApiResponseInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct( $data )
    {
        $this->data = $data;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return false;
    }

    /**
     * @return array
     */
    public function data(): array
    {
        return $this->data;
    }
}