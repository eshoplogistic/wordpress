<?php

namespace eshoplogistic\WCEshopLogistic\DB;

if (!defined('ABSPATH')) {
	exit;
}

class PaymentGatewaysRepository
{
	const PAYTYPE_CARD = 'card';
	const PAYTYPE_CASH = 'cash';
	const PAYTYPE_CASHLESS = 'cashless';
	const PAYTYPE_PREPAY = 'prepay';
	const PAYTYPE_UPON = 'payment_upon_receipt';
	const PAYTYPE_UPON_V2 = 'upon_receipt';

	const PAYTYPE_CARD_RU = 'Карта';
	const PAYTYPE_CASH_RU = 'Наличные';
	const PAYTYPE_CASHLESS_RU = 'Безналичный расчет';
	const PAYTYPE_PREPAY_RU = 'Предоплата';
	const PAYTYPE_UPON_RU = 'Платеж после получения';

	public function getAvailablePaymentGateways(): array
	{
		$gateways = WC()->payment_gateways->payment_gateways();
		$enabledGateways = [];

		if( $gateways ) {
			foreach( $gateways as $gateway ) {

				if( $gateway->enabled == 'yes' ) {

					$enabledGateways[] = $gateway;
				}
			}
		}

		return $enabledGateways;
	}
}