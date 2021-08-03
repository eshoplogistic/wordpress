<?php

namespace eshoplogistic\WCEshopLogistic\Api;

use eshoplogistic\WCEshopLogistic\Contracts\ApiResponseInterface;
use eshoplogistic\WCEshopLogistic\Contracts\HttpClient;
use eshoplogistic\WCEshopLogistic\Exceptions\ApiServiceException;
use eshoplogistic\WCEshopLogistic\Http\Response\CollectionResponse;
use eshoplogistic\WCEshopLogistic\Http\Response\ErrorResponse;
use eshoplogistic\WCEshopLogistic\Http\Response\ExceptionResponse;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
	exit;
}

class EshopLogisticApi
{
	/**
     * @var string
     */
    private $apiBaseUrl = 'https://api.eshoplogistic.ru/api/';

    /**
     * @var string
     */
    private $apiUrl = '';

    /**
     * @var HttpClient
     */
    private $client;

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @param HttpClient $client
     */
    public function __construct( $client )
    {
        $optionsRepository = new OptionsRepository();

        $this->client = $client;
        $this->apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey) {
        if(!empty($apiKey)) $this->apiKey = $apiKey;
    }

    /**
     * @return ApiResponseInterface
     */
    public function infoAccount()
    {
        $this->generateApiUrl('site');

        return $this->sendLoadRequest(array());
    }

    /**
     * @return ApiResponseInterface
     */
    public function initAccount()
    {
        $this->generateApiUrl('init');

        return $this->sendLoadRequest(array());
    }

    /**
     * @param string $target
     *
     * @return ApiResponseInterface
     */
    public function search($target = '')
    {
        $this->generateApiUrl('search');
        $data['target'] = $target;

        return $this->sendLoadRequest($data);
    }

    /**
     * @param string $delivery
     * @param array $data
     *
     * @return ApiResponseInterface
     */
    public function calculateDelivery($delivery, $data)
    {
        $this->generateApiUrl('delivery/' . $delivery);

        return $this->sendLoadRequest($data);
    }

    /**
     * @return ApiResponseInterface
     */
    public function allServices()
    {
        $this->generateApiUrl('info');

        return $this->sendLoadRequest(array());
    }

    /**
     * @param array $data
     *
     * @return ApiResponseInterface
     */
    private function sendLoadRequest( $data )
    {
        try {
            $response = $this->sendRequest( $data );

            if ( $response['success'] ) {
                return new CollectionResponse( $response['data'] );
            }

            return new ErrorResponse( $response );
            
        } catch ( ApiServiceException $e ) {

            return new ExceptionResponse( $e );
        }
    }

    /**
     * @param array $data
     * @return mixed
     *
     * @throws ApiServiceException
     */
    private function sendRequest( $data )
    {
        $data['key'] = $this->apiKey;

        $result = $this->client->post(
            $this->apiUrl,
            $data
        );

        return json_decode( $result, true );
    }

    /**
     * @param string $path
     *
     */
    private function generateApiUrl($path = '')
    {
       $this->apiUrl = $this->apiBaseUrl . $path;
    }
}