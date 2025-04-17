<?php

namespace eshoplogistic\WCEshopLogistic\Http;

if ( ! defined('ABSPATH')) {
    exit;
}

class Response
{
    public static function make($status, $msg, $data = []): array
    {
        return [
            'status'    => $status,
            'msg'       => $msg,
            'data'      => $data
        ];
    }

    public static function makeAjax($status, $msg, $data = [])
    {
        $result = [
            'status'    => $status,
            'msg'       => $msg,
            'data'      => $data
        ];

        header('Content-Type: application/json');

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        wp_die();
    }
}