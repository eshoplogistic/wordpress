<?php

namespace eshoplogistic\WCEshopLogistic\Services;

if ( ! defined('ABSPATH') ) {
    exit;
}

class SessionService
{
    protected $prefix = WC_ESL_PREFIX;

    protected $sessionList = [];

    public function __construct()
    {
        $this->sessionList = [
            'shipping_services' => [],
            'shipping_fias' => '',
            'shipping_city' => '',
            'shipping_region' => '',
            'shipping_postcode' => '',
            'shipping_methods' => [],
            'terminal_location' => '',
            'billing' => [],
            'shipping' => [],
        ];
    }

    public function set($key, $value)
    {
        if(!isset(WC()->session)) throw new \Exception(esc_html("Сессия WooCommerce не инициализирована", 'eshoplogisticru'));

        WC()->session->set($this->prefix . $key, $value);
    }

    public function get($key)
    {
        if(!isset(WC()->session)) throw new \Exception(esc_html("Сессия WooCommerce не инициализирована", 'eshoplogisticru'));

        return WC()->session->get($this->prefix . $key);
    }

    public function drop($key)
    {
        if(!isset(WC()->session)) throw new \Exception(esc_html("Сессия WooCommerce не инициализирована", 'eshoplogisticru'));

        if(!isset($this->sessionList[$key])) throw new \Exception(esc_html("Ключ не найден в текущей сессии", 'eshoplogisticru'));

        $this->set($key, $this->sessionList[$key]);
    }

    public function save(array $data) {
        if(empty($data)) throw new \Exception(esc_html("Данные для сохранения в сессии некорректны", 'eshoplogisticru'));

        foreach($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    public function dropAll()
    {
        foreach($this->sessionList as $key => $value) {
            $this->drop($key);
        }
    }

    public function getAll()
    {
        $data = [];

        foreach($this->sessionList as $key => $value) {
            $data[$this->prefix . $key] = $this->get($key);
        }

        return $data;
    }
}