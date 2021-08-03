<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Http\Controllers\OptionsController;
use eshoplogistic\WCEshopLogistic\Http\Controllers\SessionController;
use eshoplogistic\WCEshopLogistic\Http\Request;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Ajax implements ModuleInterface
{
    public function init()
    {
        if (wp_doing_ajax()) {
            $this->initRoutes();
            $this->initAdminRoutes();
        }
    }

    public function initRoutes()
    {
        add_action('wp_ajax_nopriv_wc_esl_search_cities', [$this, 'searchCities']);
        add_action('wp_ajax_wc_esl_search_cities', [$this, 'searchCities']);

        add_action('wp_ajax_nopriv_wc_esl_update_shipping_address', [$this, 'updateShippingAddress']);
        add_action('wp_ajax_wc_esl_update_shipping_address', [$this, 'updateShippingAddress']);

        add_action('wp_ajax_nopriv_wc_esl_set_terminal_address', [$this, 'setTerminalAddress']);
        add_action('wp_ajax_wc_esl_set_terminal_address', [$this, 'setTerminalAddress']);

        add_action('wp_ajax_nopriv_wc_esl_reset_shipping_address', [$this, 'resetShippingAddress']);
        add_action('wp_ajax_wc_esl_reset_shipping_address', [$this, 'resetShippingAddress']);
    }

    public function initAdminRoutes()
    {
        add_action('wp_ajax_wc_esl_shipping_change_enable_plugin', [$this, 'changeEnablePlugin']);
        add_action('wp_ajax_wc_esl_shipping_save_api_key', [$this, 'saveApiKey']);
        add_action('wp_ajax_wc_esl_shipping_save_widget_secret_code', [$this, 'saveWidgetSecretCode']);
        add_action('wp_ajax_wc_esl_shipping_save_widget_key', [$this, 'saveWidgetKey']);
        add_action('wp_ajax_wc_esl_update_cache', [$this, 'updateCache']);
        add_action('wp_ajax_wc_esl_save_payment_method', [$this, 'savePaymentMethod']);
    }

    public function changeEnablePlugin()
    {
        $status = isset($_POST['status']) ? $_POST['status'] : null;

        $options = [];

        $options['data']['wc_esl_shipping'] = array(
            'plugin_enable' => $status === 'true' ? 1 : 0
        );

        $request = new Request($options);

        $optionsController = new OptionsController();
        $response = $optionsController->save($request);

        $response->send();
    }

    public function saveApiKey()
    {
        $api_key = !empty($_POST['api_key']) ? $_POST['api_key'] : '';

        $optionsController = new OptionsController();
        $response = $optionsController->saveApiKey($api_key);

        $response->send();
    }

    public function saveWidgetSecretCode()
    {
        $secretCode = !empty($_POST['secret_code']) ? $_POST['secret_code'] : '';

        $optionsController = new OptionsController();
        $response = $optionsController->saveWidgetSecretCode($secretCode);

        $response->send();
    }

    public function saveWidgetKey()
    {
        $widgetKey = !empty($_POST['widget_key']) ? $_POST['widget_key'] : '';

        $optionsController = new OptionsController();
        $response = $optionsController->saveWidgetKey($widgetKey);

        $response->send();
    }

    public function searchCities()
    {
        $target = isset($_POST['target']) ? $_POST['target'] : '';

        $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
        $result = $eshopLogisticApi->search($target);

        if($result->hasErrors()) wp_send_json(['success' => false]);

        wp_send_json([
            'success' => true,
            'data' => $result->data()
        ]);
    }

    public function updateShippingAddress()
    {
        $fias = isset($_POST['fias']) ? $_POST['fias'] : '';
        $city = isset($_POST['city']) ? $_POST['city'] : '';
        $region = isset($_POST['region']) ? $_POST['region'] : '';
        $postcode = isset($_POST['postcode']) ? $_POST['postcode'] : '';
        $services = isset($_POST['services']) ? $_POST['services'] : [];
        $mode = isset($_POST['mode']) ? $_POST['mode'] : 'billing';

        $data = [
            'shipping_city' => $city,
            'shipping_fias' => $fias,
            'shipping_region' => $region,
            'shipping_services' => $services,
            'shipping_postcode' => $postcode,
        ];

        $data[$mode] = [
            'city' => $city,
            'fias' => $fias,
            'region' => $region,
            'services' => $services,
            'postcode' => $postcode,
        ];

        switch ($mode) {
            case 'billing':
                WC()->customer->set_billing_city($city);
                WC()->customer->set_billing_state($region);
                WC()->customer->set_billing_postcode($postcode);
                break;

            case 'shipping':
                WC()->customer->set_shipping_city($city);
                WC()->customer->set_shipping_state($region);
                WC()->customer->set_shipping_postcode($postcode);
                break;
            
            default:
                break;
        }

        $request = new Request([
            'data' => $data
        ]);

        $sessionController = new SessionController();
        $response = $sessionController->saveShippingAddress($request);

        $response->send();
    }

    public function updateCache()
    {
        global $wpdb;

        $like = '%transient_'. WC_ESL_PREFIX .'%';
        $query = "SELECT `option_name` AS `name` FROM $wpdb->options WHERE `option_name` LIKE '$like' ORDER BY `option_name`";
        $transients = $wpdb->get_results($query);

        if($transients) {
            foreach($transients as $transient) {
                delete_transient(explode('_transient_', $transient->name)[1]);
            }
        }

        $optionsRepository = new OptionsRepository();
        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

        if($apiKey) {
            $optionsController = new OptionsController();
            $response = $optionsController->saveApiKey($apiKey);
        }

        wp_send_json([
            'success' => true,
            'data' => $transients,
            'msg' => __("Кэш успешно очищен", WC_ESL_DOMAIN)
        ]);
    }

    public function savePaymentMethod()
    {
        $formData = isset($_POST['formData']) ? $_POST['formData'] : null;

        if(is_null($formData)) {
            wp_send_json([
                'success' => false,
                'msg' => __("Ошибка сохранения методов оплаты", WC_ESL_DOMAIN)
            ]);
        }

        $params = array();
        parse_str($formData, $params);

        if(!isset($params['esl_pay_type'])) {
            wp_send_json([
                'success' => false,
                'msg' => __("Ошибка сохранения методов оплаты", WC_ESL_DOMAIN)
            ]);
        }

        $payTypes = [];

        foreach($params['esl_pay_type'] as $key => $value) {
            $payTypes[$key] = $value;
        }

        if(empty($payTypes)) {
            wp_send_json([
                'success' => false,
                'msg' => __("Ошибка сохранения методов оплаты", WC_ESL_DOMAIN)
            ]);
        }

        $optionsRepository = new OptionsRepository();
        $optionsRepository->save([
            'wc_esl_shipping' => [
                'payment_methods' => $payTypes
            ]
        ]);

        wp_send_json([
            'success' => true,
            'data' => $payTypes,
            'msg' => __("Методы оплаты успешно сохранены", WC_ESL_DOMAIN)
        ]);
    }

    public function setTerminalAddress()
    {
        $terminal = isset($_POST['terminal']) ? $_POST['terminal'] : '';

        if(!$terminal) wp_send_json(['success' => false, 'msg' => __("Некорректный адрес пункта выдачи", WC_ESL_DOMAIN)]);

        $sessionService = new SessionService();
        $sessionService->set('terminal_location', $terminal);

        wp_send_json([
            'success' => true,
            'data' => $terminal,
            'msg' => __("Aдрес пункта выдачи успешно сохранён", WC_ESL_DOMAIN)
        ]);
    }

    public function resetShippingAddress()
    {
        try {
            $sessionService = new SessionService();
            $sessionService->dropAll();

            wp_send_json([
                'success' => true,
                'data' => $sessionService->getAll(),
                'msg' => __("Сессия успешно сброшена", WC_ESL_DOMAIN)
            ]);
        } catch(\Exception $e) {
            wp_send_json([
                'success' => false,
                'msg' => __("Ошибка сброса кэша", WC_ESL_DOMAIN)
            ]);
        }
    }
}