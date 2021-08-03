<?php

namespace eshoplogistic\WCEshopLogistic\Http\Response;

use eshoplogistic\WCEshopLogistic\Contracts\ApiResponseInterface;

if ( ! defined('ABSPATH') ) {
    exit;
}

class ErrorResponse implements ApiResponseInterface, \JsonSerializable
{
    /**
     * @var array
     */
    private $errors;

    /**
     * @var array
     */
    private $messages;

    /**
     * @var string
     */
    private $status;

    /**
     * @param array $response
     */
    public function __construct( $response )
    {
        $this->errors = $response['errors'];
        $this->messages = $response['msg'];
        $this->status = $response['status'];
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
                'errors' => $this->errors,
                'messages' => $this->messages,
                'status' => $this->status
            ]
        ];
    }
}