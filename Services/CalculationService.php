<?php

namespace eshoplogistic\WCEshopLogistic\Services;

use eshoplogistic\WCEshopLogistic\Contracts\OrderDataInterface;
use eshoplogistic\WCEshopLogistic\Contracts\OfferInterface;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Helpers\EslLogger;
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

	    // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook name retained for backwards compatibility.
	    $offers = apply_filters( 'esl_offers_filter', $offers );

	    if($service === 'dostavista'){
			$cityTo = $cityName.' '.$adress;
		}

        // Точка доработки: позволяет фильтру в проекте скорректировать адрес доставки и
        // состав заказа перед реальным запросом к API (например, поправить кол-во/вес
        // позиций, если это не покрывается настройками плагина).
        $originalCityTo = $cityTo;
        $originalOffers = $offers;

        $wcEslBeforeCalculate = apply_filters( 'wc_esl_before_calculate', [
            'to' => $cityTo,
            'offers' => $offers,
        ], $service, $data, $cityFrom, $payment );

        $cityTo = $wcEslBeforeCalculate['to'] ?? $cityTo;
        $offers = $wcEslBeforeCalculate['offers'] ?? $offers;

        if ( $cityTo !== $originalCityTo || $offers !== $originalOffers ) {
            EslLogger::debug( '[ESL calculate] wc_esl_before_calculate changed request data', [
                'service' => $service,
                'before' => [ 'to' => $originalCityTo, 'offers' => $originalOffers ],
                'after' => [ 'to' => $cityTo, 'offers' => $offers ],
            ] );
        }

        EslLogger::debug( '[ESL calculate] delivery calculation request', [
            'service' => $service,
            'from' => $cityFrom,
            'to' => $cityTo,
            'payment' => $payment,
            'offers' => $offers,
        ] );

        $response = $this->api->calculateDelivery($service, [
            'from' => $cityFrom,
            'to' => $cityTo,
            'payment' => $payment,
            'offers' => json_encode($offers),
            'debug' => 1
        ]);

        if($response->hasErrors()) throw new \Exception(esc_html(__("Ошибка при расчёте стоимости доставки", 'eshoplogisticru')));

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