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
	private $apiBaseUrl = array(
		'v1'=>'https://api.eshoplogistic.ru/api/',
		'v2'=>'https://api.esplc.ru/'
	);

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
	 * @var string
	 */
	private $eslLog;

	/**
	 * @var array
	 */
	private $initAccount;

	/**
	 * @var string
	 */
	private $partnerKey = '264a7a4e8112746.78051365';

	/**
	 * @param HttpClient $client
	 */
	public function __construct( $client )
	{
		$optionsRepository = new OptionsRepository();

		$this->client = $client;
		$this->apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
		$this->eslLog = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_log');
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
	public function infoAccount($apiKey = '')
	{
		if($apiKey !== $this->apiKey)
			$this->setApiKey($apiKey);

		$this->generateApiUrl('client/state');
		$result = $this->sendLoadRequest(array());
		if($result->hasErrors())
			return $result;

		$resultAccount = $result->data();

		$this->initAccount = (isset($resultAccount['services']))?$resultAccount['services']:'';

		return $result;
	}

	/**
	 * @return ApiResponseInterface
	 */
	public function initAccount()
	{
		return new CollectionResponse( $this->initAccount );
	}

	/**
	 * @param string $target
	 *
	 * @return ApiResponseInterface
	 */
	public function search($target = '', $currentCountry = '', $region = '')
	{
		$this->generateApiUrl('locality/search');
		$data['target'] = $target;
		if($currentCountry)
			$data['country'] = $currentCountry;
            if($region)
                $data['region'] = $region;

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
		$this->generateApiUrl( 'delivery/calculation' );
		$data['service'] = $delivery;
		unset($data['from']);

		return $this->sendLoadRequest( $data );
	}

	/**
	 * @return ApiResponseInterface
	 */
	public function allServices()
	{
		return new CollectionResponse( $this->initAccount );
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
			if($this->eslLog == '1'){
				$this->eslWriteLog( $response, $data );
			}
			if ( isset($response['success']) && $response['success'] || ($response['http_status'] == 200) ) {
				if(isset($response['debug']))
					$response['data']['debug'] = $response['debug'];

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

		$data['partner_key'] = $this->partnerKey;

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
		$this->apiUrl = $this->apiBaseUrl['v2'] . $path;
	}

	public function getApiUrl(){
		return $this->apiBaseUrl['v2'];
	}

	public function eslWriteLog($log, $type = '') {
		if(isset($type['target']))
			return false;

		$d = gmdate("j-M-Y H:i:s") . ' UTC';
		$header = ' ####################### ';
		$plugin = WP_PLUGIN_DIR . '/eshoplogisticru';
		if(is_dir( $plugin )){
			$path = $plugin.'/esl.log';
			if (file_exists($path)) {
				$size = filesize($path);
				$sizeMb = round($size / 1024 / 1024, 2);
				if($sizeMb > 10){
					file_put_contents($path, '');
				}
			}

			if (is_array($log) || is_object($log)) {
				if (is_object($log)) {
					$log = (array) $log;
				}
				if($type){
					$urlRequest = $this->apiUrl;
					$tmp['sendRequest'] = $type;
					$tmp['sendRequest']['url'] = $urlRequest;
					array_unshift($log, $tmp);
				}
				$encodedLog = wp_json_encode($log, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
				if (false === $encodedLog) {
					$encodedLog = 'Failed to encode log payload';
				}
				file_put_contents($path, $header . $d . $header . $encodedLog . PHP_EOL, FILE_APPEND);
			} else {
				file_put_contents($path, $header . $d . $header . (string) $log . PHP_EOL, FILE_APPEND);
			}
		}
	}

	public function geo($ip = '')
	{
		//v2 has no geo method
		return null;
	}

	/**
	 * @param array $data
	 *
	 * @return ApiResponseInterface
	 */
	public function apiExportCreate($data = array())
	{
		$this->generateApiUrl('delivery/order');

		return $this->sendLoadRequest($data);
	}

	/**
	 * @param array $data
	 *
	 * @return ApiResponseInterface
	 */
	public function apiExportCreateSdek($data = array())
	{
		$this->generateApiUrl('delivery/order');

		try {
			$response = $this->sendRequest( $data );

			if ( $response['http_status'] == 200 && isset($response['data']['state']['number'])) {
				return new CollectionResponse( $response['data'] );
			}

			if(isset($response['data']['state']['errors']))
				$response['errors'] = $response['data']['state']['errors'];

			return new ErrorResponse( $response );

		} catch ( ApiServiceException $e ) {

			return new ExceptionResponse( $e );
		}

	}

	/**
	 * @param array $data
	 *
	 * @return ApiResponseInterface
	 */
	public function apiExportAdditional($data = array())
	{
		$this->generateApiUrl('service/additional');

		return $this->sendLoadRequest($data);
	}


	/**
	 * @param string $service
	 *
	 * @return ApiResponseInterface
	 */
	public function apiServiceTariffs($service = '')
	{
		$this->generateApiUrl('service/tariffs');
		$data['service'] = $service;

		return $this->sendLoadRequest($data);
	}

	/**
	 * @param string $service
	 *
	 * @return ApiResponseInterface
	 */
	public function apiServiceCounterparties($service = '')
	{
		$this->generateApiUrl('service/counterparties');
		$data['service'] = $service;

		return $this->sendLoadRequest($data);
	}
}