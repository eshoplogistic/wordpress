<?php

namespace eshoplogistic\WCEshopLogistic\Http;

if ( ! defined('ABSPATH')) {
    exit;
}

class Request
{
    protected $data = [];

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return isset($this->data[$key])
            ? $this->data[$key]
            : $default;
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->data;
    }
}