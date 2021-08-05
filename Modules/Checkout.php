<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Checkout implements ModuleInterface
{
    public function init()
    {
        add_action('woocommerce_after_checkout_billing_form', [$this, 'injectBillingFields']);
        add_action('woocommerce_after_checkout_shipping_form', [$this, 'injectShippingFields']);
        add_action('woocommerce_checkout_update_order_review', [$this, 'clearShippingCache']);
        add_action('woocommerce_after_shipping_rate', [$this, 'infoShippingMethodItem']);
    }

    public function clearShippingCache()
    {
        $packages = WC()->cart->get_shipping_packages();
        
        foreach ( $packages as $key => $value ) {
            $shipping_session = "shipping_for_package_$key";
    
            unset( WC()->session->$shipping_session );
        }
    }

    public function injectBillingFields()
    {
        $this->injectFields( 'billing' );
    }

    public function injectShippingFields()
    {
        $this->injectFields( 'shipping' );
    }

    private function injectFields($type)
    {
        $this->renderCheckoutFields($type);
    }

    private function renderCheckoutFields($type)
    {
        ?>

        <div id="wc-esl-terminals-wrap-<?php echo $type ?>" class="wc-esl-terminals__container">

            <?php
                $sessionService = new SessionService();

                woocommerce_form_field(
                    "wc_esl_{$type}_terminal",
                    array(
                        'label' => __('Пункт выдачи', WC_ESL_DOMAIN),
                        'required' => true,
                        'custom_attributes' => array(
                            'readonly' => true
                        )
                    ),
                    $sessionService->get('terminal_location') ? $sessionService->get('terminal_location') : ''
                );
            ?>

            <button
            	class="wc-esl-terminals__button"
            	type="button"
            	data-mode="<?php echo $type ?>"
            >
                <?php echo $sessionService->get('terminal_location') ? __('Выбрать другой пункт выдачи', WC_ESL_DOMAIN) : __('Выбрать пункт выдачи', WC_ESL_DOMAIN) ?>
            </button>
        </div>

        <?php
    }

    public function infoShippingMethodItem($item)
    {
        $sessionService = new SessionService();
        $optionsRepository = new OptionsRepository();

        $shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];
        $paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');

        if(!isset($shippingMethods[$item->id])) return;
    }
}