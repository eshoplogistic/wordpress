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

	/**
	 * Пишет запрос/ответ ESL API в стандартный логгер WooCommerce (источник "wc-esl-shipping",
	 * WooCommerce > Статус > Журналы), а не в текстовый файл внутри папки плагина — файл был
	 * доступен по прямой публичной ссылке без авторизации и мог раскрывать API-ключ и ПДн
	 * покупателей (адрес, телефон, email) кому угодно, кто знает URL.
	 *
	 * Формат: короткая сводка первой строкой (action/service/order_id — чтобы можно было
	 * понять "что за запрос и откуда" не разворачивая JSON), затем отдельно request и response
	 * в pretty-print JSON, а не одной нечитаемой строкой.
	 *
	 * @param mixed  $log  Ответ API (обычно массив, декодированный из JSON).
	 * @param mixed  $type Данные запроса (если переданы, добавляются в лог вместе с URL запроса).
	 */
	public function eslWriteLog($log, $type = '') {
		if(isset($type['target']))
			return false;

		if( ! function_exists('wc_get_logger') )
			return false;

		$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
		$isRequestArray = is_array($type) || is_object($type);
		$requestArr = $isRequestArray ? (array) $type : array();

		$orderId = $requestArr['order_id'] ?? $requestArr['order']['id'] ?? '';
		$summary = sprintf(
			'ESL API%s%s%s | %s',
			isset($requestArr['action']) ? ' action=' . $requestArr['action'] : '',
			isset($requestArr['service']) ? ' service=' . $requestArr['service'] : '',
			$orderId !== '' ? ' order_id=' . $orderId : '',
			$this->apiUrl
		);

		$lines = array($summary);

		if ($isRequestArray && $requestArr) {
			$requestJson = wp_json_encode($requestArr, $jsonFlags);
			$lines[] = '--- Запрос ---';
			$lines[] = false !== $requestJson ? $requestJson : 'Failed to encode request payload';
		}

		if (is_array($log) || is_object($log)) {
			$responseJson = wp_json_encode((array) $log, $jsonFlags);
			$lines[] = '--- Ответ ---';
			$lines[] = false !== $responseJson ? $responseJson : 'Failed to encode response payload';
		} else {
			$lines[] = '--- Ответ ---';
			$lines[] = (string) $log;
		}

		wc_get_logger()->info(implode("\n", $lines), array('source' => 'wc-esl-shipping'));
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

			if($this->eslLog == '1'){
				$this->eslWriteLog( $response, $data );
			}

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

	/**
	 * Справочник организационно-правовых форм для служб, которым он требуется (например, Деловые линии).
	 *
	 * @return ApiResponseInterface
	 */
	public function apiServiceOpf()
	{
		$this->generateApiUrl('service/opf');

		return $this->sendLoadRequest(array());
	}

	/**
	 * Поиск терминалов/ПВЗ службы доставки (используется для подсказки кода терминала отправителя).
	 *
	 * @param string $service
	 * @param string $settlement
	 * @param string $region
	 * @param string $address
	 * @param bool   $onlyBranches
	 *
	 * @return ApiResponseInterface
	 */
	public function apiServiceTerminals($service, $settlement = '', $region = '', $address = '', $onlyBranches = false)
	{
		$this->generateApiUrl('service/terminals');
		$data['service'] = $service;
		if ($settlement) $data['settlement'] = $settlement;
		if ($region) $data['region'] = $region;
		if ($address) $data['address'] = $address;
		if ($onlyBranches) $data['only_branches'] = 1;

		return $this->sendLoadRequest($data);
	}
}