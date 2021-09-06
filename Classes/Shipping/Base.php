<?php

namespace eshoplogistic\WCEshopLogistic\Classes\Shipping;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\Services\CalculationService;
use eshoplogistic\WCEshopLogistic\Models\CheckoutOrderData;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Helpers\ConflictPluginsHelper;

class Base extends \WC_Shipping_Method
{
    protected $city_code;

    protected $type;

    /**
     * Constructor for your shipping class
     *
     * @access public
     * @return void
     */
    public function __construct( $instance_id = 0 )
    {
        parent::__construct( $instance_id );

        $this->supports          = array(
            'shipping-zones',
            'instance-settings',
            'instance-settings-modal',
        );
    }

    /**
     * Always return shipping method is available
     *
     * @param array $package Shipping package.
     * @return bool
     */
    public function is_available( $package )
    {
        $is_available = true;
        return apply_filters( 'woocommerce_shipping_' . $this->id . '_is_available', $is_available, $package, $this );
    }

    /**
     * Init your settings
     *
     * @access public
     * @return void
     */
    function init()
    {
        // Load the settings API
        $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
        $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

        // Save settings in admin if you have any defined
        add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Define settings field for this shipping
     * 
     * @return void 
     */
    function init_form_fields() {
        $shippingHelper = new ShippingHelper();
        $options = $this->getOptionMethod($shippingHelper->getSlugMethod($this->id));

        $typeMethodTitle = ($shippingHelper->getTypeMethod($this->id) === 'terminal') ? 'Доставка до пункта выдачи' : 'Доставка курьером';
        $defaultTitle = isset( $options['name'] ) ? $options['name'] . ': ' . $typeMethodTitle : '';
 
        $this->instance_form_fields = array(
            'title' => array(
                'title' => __('Название', WC_ESL_DOMAIN),
                'type' => 'text',
                'description' => __('Вы можете изменить название метода доставки, которое будет отображаться пользователям', WC_ESL_DOMAIN),
                'default' => $defaultTitle
            ),
        );
 
    }

    /**
     * calculate_shipping function.
     *
     * @access public
     * @param mixed $package
     * @return void
     */
    public function calculate_shipping( $package = array() )
    {
        $cost = 0;
        $service = $this->getSlug();
        $sessionService = new SessionService();
        $optionsRepository = new OptionsRepository();

        $accountServices = $optionsRepository->getOption('wc_esl_shipping_account_services');
        
        $paymentMethods = $optionsRepository->getOption('wc_esl_shipping_payment_methods');
        $payment = isset($paymentMethods[WC()->session->chosen_payment_method]) ? $paymentMethods[WC()->session->chosen_payment_method] : null;

        $postRequest = [];
        $postData = isset($_POST['post_data']) ? wc_clean($_POST['post_data']) : '';
        parse_str($postData, $postRequest);

        $mode = 'billing';
        if(isset($postRequest['ship_to_different_address'])) $mode = 'shipping';

        if(isset($_POST['calc_shipping'])) $mode = 'billing';

        $modeState = $sessionService->get($mode) ? $sessionService->get($mode) : [];
        $avalaibleServices = isset($modeState['services']) ? $modeState['services'] : [];

        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
        $cityFrom = isset($accountServices[$service]['city_code']) ? $accountServices[$service]['city_code'] : '';
        $cityTo = isset($avalaibleServices[$service]) ? $avalaibleServices[$service] : '';
        $shippingMethods = $sessionService->get('shipping_methods') ? $sessionService->get('shipping_methods') : [];

        $deliveryMethods = $sessionService->get($mode) ? $sessionService->get($mode) : [];

        $logger = new \WC_Logger();

        try {
            if(!$apiKey) throw new \Exception(__("API ключ не установлен", WC_ESL_DOMAIN));
            if(!$payment) throw new \Exception(__("Метод оплаты не установлен", WC_ESL_DOMAIN));
            if(!$cityFrom) throw new \Exception(__("Город отправки не установлен", WC_ESL_DOMAIN));
            if(!$cityTo) throw new \Exception(__("Город доставки не установлен", WC_ESL_DOMAIN));

            $data = new CheckoutOrderData($package['contents']);

            $cacheKey = str_replace(
                ' ',
                '_',
                WC_ESL_PREFIX . $data->getHash() . '_' . $apiKey . '_' . $cityTo . '_' . $cityFrom . '_' . $payment . '_' . $service
            );

            $response = get_transient($cacheKey);

            if(false === $response) {
                $calculationService = new CalculationService();

                $response = $calculationService->calculate(
                    $service,
                    $data,
                    $cityFrom,
                    $cityTo,
                    $payment
                );

                set_transient($cacheKey, $response, HOUR_IN_SECONDS);
            }

            if(
                isset($response[$this->getType()]) &&
                !empty($response[$this->getType()])
            ) {
                $shippingMethods[$this->id] = $response[$this->getType()];
                $cost = isset($response[$this->getType()]['price']) ? $response[$this->getType()]['price'] : 0;
                //Костыль для оброботки конфликтов с другими плагинами(WOOCS)
                $conflict = new ConflictPluginsHelper();
                $cost = $conflict->init($cost);

                switch($this->getType()) {
                    case 'terminal':
                        $shippingMethods[$this->id]['terminals'] = $response['terminals'];
                        break;
                
                    case 'door':
                        break;

                    default:
                        break;
                }

                if(isset($response['comments'])) {
                    $shippingMethods[$this->id]['comments'] = $response['comments'];
                }
            } else {
            	unset($shippingMethods[$this->id]);
            }
        } catch(\Exception $e) {
        	unset($shippingMethods[$this->id]);

	        $logger->debug($e->getMessage());
        }

        $sessionService->set('shipping_methods', $shippingMethods);

        $rate = array(
            'id' => $this->id,
            'label' => $this->title,
            'cost' => $cost,
            'package' => $package
        );

        // Register the rate
        $this->add_rate( $rate );
    }

    public function getSlug()
    {
        $idWithoutPrefix = explode(WC_ESL_PREFIX, $this->id)[1];
        
        return explode('_', $idWithoutPrefix)[0];
    }

    public function getType()
    {
        $idWithoutPrefix = explode(WC_ESL_PREFIX, $this->id)[1];
        
        return explode('_', $idWithoutPrefix)[1];
    }

    protected function getOptionMethod($slug)
    {
        if(empty($slug)) return null;

        $optionsRepository = new OptionsRepository();
        $services = $optionsRepository->getOption('wc_esl_shipping_account_services');

        if(!isset($services[$slug])) return null;

        return $services[$slug];
    }
}
