<?php

namespace eshoplogistic\WCEshopLogistic\Http\Controllers;

use eshoplogistic\WCEshopLogistic\Contracts\ResponseInterface;
use eshoplogistic\WCEshopLogistic\Http\Foundation\Controller;
use eshoplogistic\WCEshopLogistic\Models\OrderData;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OrderController extends Controller {
	private $secretKey;
	private $queryMode;
	private $offers = array();
	private $idShipper;
	private $selectedDelivery;
	private $selectedPayment;
	private $costDelivery;
	private $totalCost;
	private $city;
	private $addressForDelivery;
	private $name;
	private $email;
	private $phone;
	private $comment;

	/**
	 * @param \WP_REST_Request $request
	 *
	 * @return ResponseInterface
	 */
	public function save( \WP_REST_Request $request ): ResponseInterface {
		$path = $_SERVER["DOCUMENT_ROOT"] . '/wp-content/esl_test.log';
		error_log(print_r($request, true), 3, $path);
		$data                 = [];
		$optionsRepository    = new OptionsRepository();
		$paymentMethodOptions = $optionsRepository->getOption( 'wc_esl_shipping_payment_methods' );
		$moduleVersion        = $optionsRepository->getOption( 'wc_esl_shipping_plugin_enable_api_v2' );
		if ( $moduleVersion ) {
			$this->listRequestParamsV2( $request );
		} else {
			$this->listRequestParamsV1( $request );
		}

		// if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || ($_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')) return $this->json(['success' => false, 'message' => __('Проверка на HTTP_X_REQUESTED_WITH завершилась неудачно', WC_ESL_DOMAIN)]);

		//if($secretKey !== $optionsRepository->getOption('wc_esl_shipping_widget_secret_code')) return $this->json(['success' => false, 'message' => __('Ключи не совпадают', WC_ESL_DOMAIN)]);

		//if($queryMode !== 'widget') return $this->json(['success' => false, 'message' => __('Контекст запроса не определен как `widget`', WC_ESL_DOMAIN)]);

		if ( empty( $this->offers ) || !is_array( $this->offers ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Товары в заказе не найдены', WC_ESL_DOMAIN )
			] );
		}

		$data['offers'] = [];

		foreach ( $this->offers as $offer ) {
			$data['offers'][] = [
				'id'       => $offer['article'],
				'quantity' => $offer['count']
			];
		}

		if ( empty( $this->name ) ) {
			return $this->json( [ 'success' => false, 'message' => __( 'Имя не определено', WC_ESL_DOMAIN ) ] );
		}

		if ( empty( $this->email ) || ! is_email( $this->email ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Email не определён или некорректен', WC_ESL_DOMAIN )
			] );
		}

		//if ( ! \WC_Validation::is_phone( $this->phone ) ) {
		//	return $this->json( [ 'success' => false, 'message' => __( 'Телефон некорректен', WC_ESL_DOMAIN ) ] );
		//}

		if ( empty( $this->city ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Город доставки не установлен', WC_ESL_DOMAIN )
			] );
		}

		if ( empty( $this->addressForDelivery ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Адрес доставки не установлен', WC_ESL_DOMAIN )
			] );
		}

		if ( empty( $this->selectedDelivery ) || empty( $this->idShipper ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Метод доставки не установлен', WC_ESL_DOMAIN )
			] );
		}

		if ( empty( $this->selectedPayment ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Метод оплаты не установлен', WC_ESL_DOMAIN )
			] );
		}

		if ( ! isset( $this->costDelivery ) ) {
			return $this->json( [
				'success' => false,
				'message' => __( 'Цена за доставку не установлена', WC_ESL_DOMAIN )
			] );
		}

		if ( ! isset( $paymentMethodOptions ) ) {
			$this->json( [ 'success' => false, 'message' => __( 'Методы оплаты не настроены', WC_ESL_DOMAIN ) ] );
		}

		if ( ! isset( $this->selectedPayment['key'] ) ) {
			$this->json( [ 'success' => false, 'message' => __( 'Метод оплаты не установлен', WC_ESL_DOMAIN ) ] );
		}

		$address = ( $this->selectedDelivery['key'] === 'terminal' ) ? __( 'Пункт выдачи: ', WC_ESL_DOMAIN ) . $this->addressForDelivery : $this->addressForDelivery;

		$data['address']['billing'] = [
			'first_name' => $this->name,
			'email'      => $this->email,
			'phone'      => $this->phone,
			'address_1'  => $address,
			'address_2'  => '',
			'city'       => $this->city['name'] ?? '',
			'state'      => $this->city['region'] ?? '',
			'postcode'   => $this->city['postcode'] ?? '',
			'country'    => 'RU',
		];

		$data['address']['shipping'] = [
			'address_1' => $address,
			'address_2' => '',
			'city'      => $this->city['name'] ?? '',
			'state'     => $this->city['region'] ?? '',
			'postcode'  => $this->city['postcode'] ?? '',
			'country'   => 'RU',
		];

		$data['shipping_method'] = [
			'id'    => WC_ESL_PREFIX . $this->idShipper['keyShipper'] . '_' . $this->selectedDelivery['key'],
			'title' => $this->idShipper['name'] . ': ' . $this->selectedDelivery['name'],
			'cost'  => $this->costDelivery
		];

		foreach ( $paymentMethodOptions as $key => $value ) {
			if ( $value === $this->selectedPayment['key'] ) {
				$wcPaymentGateways = \WC_Payment_Gateways::instance();
				$wcPaymentGateway  = isset( $wcPaymentGateways->payment_gateways()[ $key ] ) ? $wcPaymentGateways->payment_gateways()[ $key ] : null;

				if ( ! is_null( $wcPaymentGateway ) ) {
					$data['payment_method'] = [
						'id'    => $wcPaymentGateway->id,
						'title' => $wcPaymentGateway->title
					];
				}
			}
		}

		if ( ! isset( $data['payment_method']['id'] ) ) {
			$this->json( [ 'success' => false, 'message' => __( 'Метод оплаты не найден', WC_ESL_DOMAIN ) ] );
		}

		$orderData = new OrderData( $data );
		$orderId   = $orderData->save();

		if ( ! $orderId ) {
			$this->json( [
				'success' => false,
				'message' => __( 'При создании заказа произошла ошибка', WC_ESL_DOMAIN )
			] );
		}

		return $this->json( [
			'success' => true,
			'message'     => __( 'Заказ успешно создан', WC_ESL_DOMAIN ),
			'data'    => $orderId
		] );
	}

	private function listRequestParamsV1( $request ) {
		$this->secretKey          = $request->get_param( 'secret' );
		$this->queryMode          = $request->get_param( 'query_mode' );
		$this->offers             = json_decode( $request->get_param( 'offers' ), true );
		$this->idShipper          = json_decode( $request->get_param( 'idShipper' ), true );
		$this->selectedDelivery   = json_decode( $request->get_param( 'selectedDelivery' ), true );
		$this->selectedPayment    = json_decode( $request->get_param( 'selectedPayment' ), true );
		$this->costDelivery       = $request->get_param( 'costDelivery' );
		$this->totalCost          = $request->get_param( 'totalCost' );
		$this->city               = json_decode( $request->get_param( 'city' ), true );
		$this->addressForDelivery = $request->get_param( 'addressForDelivery' );
		$this->name               = $request->get_param( 'name' );
		$this->email              = $request->get_param( 'email' );
		$this->phone              = $request->get_param( 'phone' );
		$this->comment            = $request->get_param( 'comment' );
	}

	private function listRequestParamsV2( $request ) {
		$delivery = $request->get_param( 'delivery' );
		$receiver = $request->get_param( 'receiver' );

		$this->secretKey          = $request->get_param( 'secret' );
		$this->queryMode          = $request->get_param( 'query_mode' );
		$this->offers             = $request->get_param( 'offers' );
		$this->idShipper          = array(
			'keyShipper' => $delivery['code'] ?? '',
			'name'       => $delivery['name'] ?? ''
		);
		$timeValue = $delivery['data']['time']['value']  ?? '';
		$timeUnit = $delivery['data']['time']['unit'] ?? '';
		$this->selectedDelivery   = array(
			'key'     => $delivery['type']?? '',
			'time'    => $timeValue.' '.$timeUnit,
			'comment' => $delivery['data']['comment'] ?? '',
			'name'    => ''
		);
		$addressPvz = $delivery['pvz']['address'] ?? '';
		$address = $delivery['address'] ?? '';
		$this->selectedPayment    = $request->get_param( 'payment' );
		$this->costDelivery       = $delivery['data']['price']['value'] ?? '';
		$this->city               = $request->get_param( 'settlement' );
		$this->addressForDelivery = ($addressPvz)?$addressPvz:$address;
		$this->name               = $receiver['name'] ?? '';
		$this->email              = $receiver['email'] ?? '';
		$this->phone              = $receiver['phone'] ?? '';
	}

}