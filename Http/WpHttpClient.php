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
			if($response->get_error_message() == 'cURL error 60: SSL certificate problem: certificate has expired'){
				return $this->alternativeCurlPost($url, $body);
			}
			   throw new ApiServiceException( esc_html($response->get_error_message()) );
		}

		return $response['body'];
	}

	/**
	 * Fallback method for SSL certificate errors when wp_remote_post() fails.
	 * Retries via the WP HTTP API with certificate verification disabled,
	 * instead of the default wp_remote_post() call in post() above.
	 */
	public function alternativeCurlPost( $url, $body = null ){
		$response = wp_remote_post( $url, [
			'timeout'   => 10,
			'sslverify' => false,
			'body'      => $body,
		] );

		if ( is_wp_error( $response ) ) {
			return null;
		}

		return wp_remote_retrieve_body( $response );
	}
}