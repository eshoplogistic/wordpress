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

class EshopLogisticApiV2
{
	/**
	 * @var string
	 */
	private $apiBaseUrl = 'https://api.esplc.ru/';

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

	private $initAccount;

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
	public function infoAccount()
	{
		$this->generateApiUrl('client/state');
		$result = $this->sendLoadRequest(array());
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
	public function search($target = '', $currentCountry = '')
	{
		$this->generateApiUrl('locality/search');
		$data['target'] = $target;
		if($currentCountry)
			$data['country'] = $currentCountry;

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
			if ( $response['http_status_message'] === 'OK' ) {
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

	public function eslWriteLog($log, $type = '') {
		if(isset($type['target']))
			return false;

		$d = date("j-M-Y H:i:s e");
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
				if($type){
					$urlRequest = $this->apiUrl;
					$tmp['sendRequest'] = $type;
					$tmp['sendRequest']['url'] = $urlRequest;
					array_unshift($log, $tmp);
				}
				error_log($header.$d.$header.print_r($log, true), 3, $path);
			} else {
				error_log($header.$d.$header.$log,3, $path);
			}
		}
	}
}