<?php

namespace eshoplogistic\WCEshopLogistic\Services;

use eshoplogistic\WCEshopLogistic\Contracts\OrderDataInterface;
use eshoplogistic\WCEshopLogistic\Contracts\OfferInterface;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;

if ( ! defined('ABSPATH') ) {
    exit;
}

class CalculationService
{
    private $api;

    public function __construct()
    {
        $this->api = new EshopLogisticApi(new WpHttpClient());
    }

    /**
     * @param string $service
     * @param OrderDataInterface $data
     * @param string $cityFrom
     * @param string $cityTo
     */
    public function calculate(string $service, OrderDataInterface $data, string $cityFrom, string $cityTo, string $payment, string $adress, string $cityName)
    {
        $offers = [];

        if($data->getItems()) {
            foreach($data->getItems() as $item) {
                $offers[] = $this->prepareOffer($item);
            }
        }

	    $offers = apply_filters( 'esl_offers_filter', $offers );

	    if($service === 'dostavista'){
			$cityTo = $cityName.' '.$adress;
		}

        $logger = new \WC_Logger();
        $logger->debug(print_r([
            'service' => $service,
            'from' => $cityFrom,
            'to' => $cityTo,
            'payment' => $payment,
            'offers' => json_encode($offers)
        ], true));

        $response = $this->api->calculateDelivery($service, [
            'from' => $cityFrom,
            'to' => $cityTo,
            'payment' => $payment,
            'offers' => json_encode($offers),
            'debug' => 1
        ]);

        if($response->hasErrors()) throw new \Exception(esc_html("Ошибка при расчёте стоимости доставки", 'eshoplogisticru'));

        return apply_filters('wc_esl_response_data_api', $response->data());
    }

    /**
     * @param OfferInterface $offer
     */
    private function prepareOffer(OfferInterface $offer)
    {
        return [
            'article' => $offer->getArticle(),
            'name' => $offer->getName(),
            'count' => $offer->getQuantity(),
            'price' => $offer->getPriceProductLineTotal(),
            'weight' => $offer->getWeight(),
            'dimensions' => $offer->getDimensions(),
        ];
    }
}