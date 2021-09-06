<?php

namespace eshoplogistic\WCEshopLogistic\Http;

if ( ! defined('ABSPATH') ) {
    exit;
}

use eshoplogistic\WCEshopLogistic\Contracts\HttpClient;
use eshoplogistic\WCEshopLogistic\Exceptions\ApiServiceException;

class WpHttpClient implements HttpClient
{
    public function get( $url, $body = null, $headers = [] )
    {
        $response = wp_remote_get( $url, [
            'headers' => $headers,
            'timeout' => 30,
            'body' => $body
        ]);

        if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
            return wp_remote_retrieve_body( $response );
        }

        return null;
    }

    /**
     * @param string $url
     * @param null $body
     * @param array $headers
     *
     * @return mixed
     *
     * @throws ApiServiceException
     */
    public function post( $url, $body = null, $headers = [] )
    {
        $response = wp_remote_post( $url, [
            'headers' => $headers,
            'timeout' => apply_filters( 'wc_esl_http_post_timeout', 10 ),
            'body' => $body
        ]);

        if ( is_wp_error( $response ) ) {
            throw new ApiServiceException( $response->get_error_message() );
        }

        return $response['body'];
    }
}