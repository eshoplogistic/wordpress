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

    public function getAvailablePaymentGateways(): array
    {
        $gateways = WC()->payment_gateways->get_available_payment_gateways();
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