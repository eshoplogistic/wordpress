<?php

namespace eshoplogistic\WCEshopLogistic\Http\Controllers;

use eshoplogistic\WCEshopLogistic\Contracts\ResponseInterface;
use eshoplogistic\WCEshopLogistic\Http\Foundation\Controller;
use eshoplogistic\WCEshopLogistic\Models\OrderData;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
    exit;
}

class OrderController extends Controller
{
    /**
     * @param \WP_REST_Request $request
     *
     * @return ResponseInterface
     */
    public function save(\WP_REST_Request $request): ResponseInterface
    {
        $data = [];
        $optionsRepository = new OptionsRepository();
        $secretKey = $request->get_param('secret');
        $queryMode = $request->get_param('query_mode');

        $offers = json_decode($request->get_param('offers'));
        $idShipper = json_decode($request->get_param('idShipper'));
        $selectedDelivery = json_decode($request->get_param('selectedDelivery'));
        $selectedPayment = json_decode( $request->get_param('selectedPayment'));
        $costDelivery = $request->get_param('costDelivery');
        $totalCost = $request->get_param('totalCost');
        $city = json_decode( $request->get_param('city'));
        $addressForDelivery = $request->get_param('addressForDelivery');
        $name = $request->get_param('name');
        $email = $request->get_param('email');
        $phone = $request->get_param('phone');
        $comment = $request->get_param('comment');
        $paymentMethodOptions = $optionsRepository->getOption('wc_esl_shipping_payment_methods');

        // if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || ($_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest')) return $this->json(['success' => false, 'message' => __('Проверка на HTTP_X_REQUESTED_WITH завершилась неудачно', WC_ESL_DOMAIN)]);

        if($secretKey !== $optionsRepository->getOption('wc_esl_shipping_widget_secret_code')) return $this->json(['success' => false, 'message' => __('Ключи не совпадают', WC_ESL_DOMAIN)]);

        if($queryMode !== 'widget') return $this->json(['success' => false, 'message' => __('Контекст запроса не определен как `widget`', WC_ESL_DOMAIN)]);

        if(empty($offers)) return $this->json(['success' => false, 'message' => __('Товары в заказе не найдены', WC_ESL_DOMAIN)]);

        $data['offers'] = [];

        foreach ($offers as $offer) {
            $data['offers'][] = [
                'id' => $offer->article,
                'quantity' => $offer->count
            ];
        }

        if(empty($name)) return $this->json(['success' => false, 'message' => __('Имя не определено', WC_ESL_DOMAIN)]);

        if(empty($email) || !is_email($email)) return $this->json(['success' => false, 'message' => __('Email не определён или некорректен', WC_ESL_DOMAIN)]);

        if(!\WC_Validation::is_phone($phone)) return $this->json(['success' => false, 'message' => __('Телефон некорректен', WC_ESL_DOMAIN)]);

        if(empty($city)) return $this->json(['success' => false, 'message' => __('Город доставки не установлен', WC_ESL_DOMAIN)]);

        if(empty($addressForDelivery)) return $this->json(['success' => false, 'message' => __('Адрес доставки не установлен', WC_ESL_DOMAIN)]);

        if(empty($selectedDelivery) || empty($idShipper)) return $this->json(['success' => false, 'message' => __('Метод доставки не установлен', WC_ESL_DOMAIN)]);

        if(empty($selectedPayment)) return $this->json(['success' => false, 'message' => __('Метод оплаты не установлен', WC_ESL_DOMAIN)]);

        if(!isset($costDelivery)) return $this->json(['success' => false, 'message' => __('Цена за доставку не установлена', WC_ESL_DOMAIN)]);

        if(!isset($totalCost)) return $this->json(['success' => false, 'message' => __('Сумма заказа не установлена', WC_ESL_DOMAIN)]);

        if(!isset($paymentMethodOptions)) $this->json(['success' => false, 'message' => __('Методы оплаты не настроены', WC_ESL_DOMAIN)]);

        if(!isset($selectedPayment->key)) $this->json(['success' => false, 'message' => __('Метод оплаты не установлен', WC_ESL_DOMAIN)]);

        $address = ($selectedDelivery->key === 'terminal') ? __('Пункт выдачи: ', WC_ESL_DOMAIN) . $addressForDelivery : $addressForDelivery;

        $data['address']['billing'] = [
            'first_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address_1' => $address,
            'address_2' => '',
            'city' => isset($city->name) ? $city->name : '',
            'state' => isset($city->region) ? $city->region : '',
            'postcode' => isset($city->postcode) ? $city->postcode : '',
            'country' => 'RU',
        ];

        $data['address']['shipping'] = [
            'address_1' => $address,
            'address_2' => '',
            'city' => isset($city->name) ? $city->name : '',
            'state' => isset($city->region) ? $city->region : '',
            'postcode' => isset($city->postcode) ? $city->postcode : '',
            'country' => 'RU',
        ];

        $data['shipping_method'] = [
            'id' => WC_ESL_PREFIX . $idShipper->keyShipper . '_' . $selectedDelivery->key,
            'title' => $idShipper->name . ': ' . $selectedDelivery->name,
            'cost' => $costDelivery
        ];

        foreach($paymentMethodOptions as $key => $value) {
            if($value === $selectedPayment->key) {
                $wcPaymentGateways = \WC_Payment_Gateways::instance();
                $wcPaymentGateway = isset($wcPaymentGateways->payment_gateways()[$key]) ? $wcPaymentGateways->payment_gateways()[$key] : null;

                if(!is_null($wcPaymentGateway)) {
                    $data['payment_method'] = [
                        'id' => $wcPaymentGateway->id,
                        'title' => $wcPaymentGateway->title
                    ];
                }
            }
        }

        if(!isset($data['payment_method']['id'])) $this->json(['success' => false, 'message' => __('Метод оплаты не найден', WC_ESL_DOMAIN)]);

        $orderData = new OrderData($data);
        $orderId = $orderData->save();

        if(!$orderId) $this->json(['success' => false, 'message' => __('При создании заказа произошла ошибка', WC_ESL_DOMAIN)]);

        return $this->json([
            'success'   => true,
            'msg'       => __('Заказ успешно создан', WC_ESL_DOMAIN),
            'data'      => $orderId
        ]);
    }
}