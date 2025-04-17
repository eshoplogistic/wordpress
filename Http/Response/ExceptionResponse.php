<?php

namespace eshoplogistic\WCEshopLogistic\Http\Response;

use eshoplogistic\WCEshopLogistic\Contracts\ApiResponseInterface;
use eshoplogistic\WCEshopLogistic\Exceptions\ApiServiceException;

if ( ! defined('ABSPATH') ) {
    exit;
}

class ExceptionResponse implements ApiResponseInterface, \JsonSerializable
{
    /**
     * @var ApiServiceException
     */
    private $exception;

    /**
     * @param ApiServiceException $exception
     */
    public function __construct( $exception )
    {
        $this->exception = $exception;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return true;
    }

    public function jsonSerialize()
    {
        return [
            'success' => false,
            'data' => [
                'exception' => $this->exception->getMessage()
            ]
        ];
    }
}