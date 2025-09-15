<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Classes\Shipping\ExportFileds;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Classes\View;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;


if (!defined('ABSPATH')) {
    exit;
}

class Unloading implements ModuleInterface
{

    private $deliveryEsl = false;
    private $shippingMethods = [];

    public $defaultFields = array(
        'key' => '', //Ключ доступа
        'action' => '', //Значение: create
        'cms' => '',
        'service' => '',
        'order' => array(
            'id' => '', //Идентификатор заказа на сайте.
            'comment' => '',
        ),
        'places' => array(
            'article' => '',
            'name' => '',
            'count' => '',
            'price' => '',
            'weight' => '', //Вес, в кг.
            'dimensions' => '', //Габариты. Формат: строка вида «Д*Ш*В», в сантиметрах. Например: 15*25*10
            'vat_rate' => '' //Значение ставки НДС Возможные варианты:0, 10, 20, -1 (без НДС)
        ),
        'receiver' => array( //Данные получателя
            'name' => '',
            'phone' => '',
            'email' => ''
        ),
        'sender' => array(
            'name' => '',
            'phone' => '',
            'company' => '',
            'email' => '',
        ),
        'delivery' => array(
            'type' => '',
            'location_from' => array( //Адрес отправителя (при заборе груза от отправителя)
                'pick_up' => '',
                //Забор груза от отправителя
                'terminal' => '',
                //Идентификатор пункта приёма груза Обязательно, если delivery.location_from.pick_up === false
                'address' => array( //Адрес забора груза Обязательно, если delivery.location_from.pick_up === true
                    'region' => '', //Регион. Например: Московская область
                    'city' => '', //Населённый пункт
                    'street' => '', //Улица
                    'house' => '', //Номер строения
                    'room' => '' //Квартира / офис / помещение
                ),
            ),
            'payment' => '',
            'cost' => '', //Стоимость доставки, рубли.
            'location_to' => array(
                'terminal' => '',
                'address' => array(
                    'region' => '',
                    'city' => '',
                    'street' => '',
                    'house' => '',
                    'room' => '',
                ),
            ),
        ),
    );

    public function init()
    {

        $optionsRepository = new OptionsRepository();
        $moduleVersion = $optionsRepository->getOption('wc_esl_shipping_plugin_enable_api_v2');
        if ($moduleVersion) {
            add_action('admin_head', [$this, 'esl_form_in_admin_bar']);
            add_action('add_meta_boxes', [$this, 'esl_button_start_meta_boxes']);
            add_action('add_meta_boxes', [$this, 'esl_button_start_meta_boxes_HPOS']);
        }
    }

    public function esl_button_start_meta_boxes_HPOS()
    {
        $shippingHelper = new ShippingHelper();
        if (!$shippingHelper->HPOS_is_enabled()) {
            return false;
        }

        global $post;

        $pageType = $shippingHelper->admin_post_type();
        $postId = false;
        if (isset($post->ID)) {
            $postId = $post->ID;
        }
        if (isset($_GET['id'])) {
            $postId = absint($_GET['id']);
        }
        if (!$postId) {
            return false;
        }

        if ($pageType == 'shop_order') {
            $order = wc_get_order($postId);
            $orderShippings = $order->get_shipping_methods();
            $orderShipping = array();

            foreach ($orderShippings as $key => $item) {
                $orderShipping = array(
                    'id' => $item->get_method_id(),
                    'name' => $item->get_method_title(),
                );
            }
            $checkDelivery = stripos($orderShipping['id'], WC_ESL_PREFIX);
            if ($checkDelivery === false) {
                return false;
            }

            $checkName = $this->getMethodByName($orderShipping['name']);
            if (!$checkName['name']) {
                return false;
            }

            $screen = wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled()
                ? wc_get_page_screen_id('shop-order')
                : 'shop_order';

            add_meta_box(
                'woocommerce-order-esl-unloading',
                __('Параметры выгрузки', 'eshoplogisticru'),
                [$this, 'order_meta_box_start_button'],
                $screen,
                'side',
                'high'
            );
        }
    }

    public function esl_button_start_meta_boxes()
    {
        global $post;

        $shippingHelper = new ShippingHelper();
        $pageType = $shippingHelper->admin_post_type();
        $postId = false;
        if (isset($post->ID)) {
            $postId = $post->ID;
        }
        if (isset($_GET['id'])) {
            $postId = absint($_GET['id']);
        }
        if (!$postId) {
            return false;
        }

        if ($pageType == 'shop_order') {

            $order = wc_get_order($postId);
            $orderShippings = $order->get_shipping_methods();
            $orderShipping = array();

            foreach ($orderShippings as $key => $item) {
                $orderShipping = array(
                    'id' => $item->get_method_id(),
                    'name' => $item->get_method_title(),
                );
            }
            $checkDelivery = stripos($orderShipping['id'], WC_ESL_PREFIX);
            if ($checkDelivery === false) {
                return false;
            }

            $checkName = $this->getMethodByName($orderShipping['name']);
            if (!$checkName['name']) {
                return false;
            }

            add_meta_box(
                'woocommerce-order-esl-unloading',
                __('Параметры выгрузки', 'eshoplogisticru'),
                [$this, 'order_meta_box_start_button'],
                'shop_order',
                'side',
                'default'
            );
        }
    }

    public function esl_form_in_admin_bar()
    {
        global $post, $pagenow;

        $shippingHelper = new ShippingHelper();
        $pageType = $shippingHelper->admin_post_type();
        $postId = false;
        if (isset($post->ID)) {
            $postId = $post->ID;
        }
        if (isset($_GET['id'])) {
            $postId = absint($_GET['id']);
        }
        if (!$postId) {
            return false;
        }

        if (($pageType == 'shop_order' && $pagenow == 'post.php') || ($pageType == 'shop_order' && $pagenow == 'admin.php')) {
            $order = wc_get_order($postId);
            $orderShippings = $order->get_shipping_methods();
            $orderShipping = array();

            foreach ($orderShippings as $key => $item) {
                $orderShipping = array(
                    'id' => $item->get_method_id(),
                    'name' => $item->get_method_title(),
                );
            }
            $checkDelivery = stripos($orderShipping['id'], WC_ESL_PREFIX);
            if ($checkDelivery === false) {
                return false;
            }

            $checkName = $this->getMethodByName($orderShipping['name']);
            if (!$checkName['name']) {
                return false;
            }

            $order = wc_get_order($postId);
            if ($order !== false) {
                $orderData = $order->get_data();
                $orderItems = $order->get_items();
                $orderShippings = $order->get_shipping_methods();
                $address = $order->get_address();
                $addressShipping = $order->get_shipping_address_1();
                $orderShipping = array();
                $optionsRepository = new OptionsRepository();
                $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
                $exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

                foreach ($orderShippings as $key => $item) {
                    $shippingMethod = wc_get_order_item_meta($item->get_id(), 'esl_shipping_methods', $single = true);
                    if ($shippingMethod) {
                        $this->shippingMethods = json_decode($shippingMethod, true);
                    }

                    $orderShipping = array(
                        'id' => $item->get_method_id(),
                        'name' => $item->get_name(),
                        'title' => $item->get_method_title(),
                        'total' => $item->get_total(),
                        'tax' => $item->get_total_tax(),
                    );

                }

                $checkDelivery = stripos($orderShipping['id'], WC_ESL_PREFIX);
                if ($checkDelivery === false) {
                    return false;
                }


                if ($orderShipping['id'] === 'wc_esl_frame_mixed') {
                    $typeMethod = $this->getMethodByName($orderShipping['name']);
                } else {
                    $shippingHelper = new ShippingHelper();
                    $options = $this->getOptionMethod($shippingHelper->getSlugMethod($orderShipping['id']));
                    $nameCurrectDelivery = $options['name'];
                    $typeMethodTitle = $shippingHelper->getTypeMethod($orderShipping['id']);
                    $idWithoutPrefix = explode(WC_ESL_PREFIX, $orderShipping['id'])[1];
                    $idWithoutPrefix = explode('_', $idWithoutPrefix)[0];
                    if ($idWithoutPrefix) {
                        $nameCurrectDelivery = $idWithoutPrefix;
                    }

                    $typeMethod = array(
                        'name' => $nameCurrectDelivery,
                        'type' => $typeMethodTitle
                    );
                }

                $cutAddressShipping = array(
                    'terminal' => '',
                    'terminal_address' => ''
                );
                if ($typeMethod['type'] === 'door') {
                    $cutAddressShipping = $this->getPartAddressNameDoor($addressShipping);
                }

                if ($typeMethod['type'] === 'terminal') {
                    $cutAddressShipping = $this->getPartAddressNameTerminal($addressShipping);
                }

                $additional = array(
                    'key' => $apiKey,
                    'service' => mb_strtolower($typeMethod['name']),
                    'detail' => true
                );

                $methodDelivery = new ExportFileds();
                $fieldDelivery = $methodDelivery->exportFields(mb_strtolower($typeMethod['name']), $this->shippingMethods, $order);

                $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
                $additionalFields = $eshopLogisticApi->apiExportAdditional($additional);
                if ($additionalFields->hasErrors()) {
                    $additionalFields = [];
                } else {
                    $additionalFields = $additionalFields->data();
                }
                $orderShippingId = reset($orderData['shipping_lines']);
                $orderShippingId = $orderShippingId->get_id();
                $infoApi = $eshopLogisticApi->infoAccount();
                if ($infoApi->hasErrors()) {
                    $infoApi = [];
                } else {
                    $infoApi = $infoApi->data();
                }

                $optionsRepository = new OptionsRepository();
                $addFieldSaved = $optionsRepository->getOption('wc_esl_shipping_add_field_form');

                $street = get_post_meta($order->get_id(), 'esl_billing_field_street', true);
                $building = get_post_meta($order->get_id(), 'esl_billing_field_building', true);
                $room = get_post_meta($order->get_id(), 'esl_billing_field_room', true);

                if (!$street)
                    $street = get_post_meta($order->get_id(), 'esl_shipping_field_street', true);

                if (!$building)
                    $building = get_post_meta($order->get_id(), 'esl_shipping_field_building', true);

                if (!$room)
                    $room = get_post_meta($order->get_id(), 'esl_shipping_field_room', true);

                echo View::render('unloading-form', [
                    'orderData' => $orderData,
                    'orderItems' => $orderItems,
                    'orderShipping' => $orderShipping,
                    'address' => $address,
                    'addressShipping' => $cutAddressShipping,
                    'typeMethod' => $typeMethod,
                    'additionalFields' => $additionalFields,
                    'exportFormSettings' => $exportFormSettings,
                    'shippingMethods' => $this->shippingMethods,
                    'fieldDelivery' => $fieldDelivery,
                    'orderShippingId' => $orderShippingId,
                    'infoApi' => $infoApi,
                    'addFieldSaved' => $addFieldSaved,
                    'street' => $street,
                    'building' => $building,
                    'room' => $room
                ]);
            }
        }

    }

    protected function getOptionMethod($slug)
    {
        if (empty($slug)) {
            return null;
        }

        $optionsRepository = new OptionsRepository();
        $services = $optionsRepository->getOption('wc_esl_shipping_account_services');

        if (!isset($services[$slug])) {
            return null;
        }

        return $services[$slug];
    }

    public function order_meta_box_start_button()
    {
        echo View::render('unloading-button', [
            'shippingMethods' => $this->shippingMethods,
        ]);
    }

    public function params_delivery_init($data)
    {
        $data = json_decode(stripslashes($data), true);
        $defaultParamsCreate = $this->defaultFieldApiCreate($data);

        $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
        $result = $eshopLogisticApi->apiExportCreate($defaultParamsCreate);

        if (!$result->hasErrors()) {
            $shippingMethod = wc_get_order_item_meta($data['order_shipping_id'], 'esl_shipping_methods', $single = true);
            if ($shippingMethod) {
                $shippingMethods = json_decode($shippingMethod, true);
            }else{
                $shippingMethods = [];
            }

            sleep(3);
            $optionsRepository = new OptionsRepository();
            $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
            $shippingMethods['answer'] = $result->data();

            $dataGet = array(
                'key' => $apiKey,
                'action' => 'get',
                'order_id' => $shippingMethods['answer']['order']['id'],
                'service' => $data['delivery_id'],
            );
            $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
            $resultGet = $eshopLogisticApi->apiExportCreateSdek($dataGet);

            if(!$resultGet->hasErrors()){
                $resultTracking = $resultGet->data();
                if(isset($resultTracking['state']['tracking'])){
                    $shippingMethods['tracking'] = $resultTracking['state']['tracking'];
                    wc_update_order_item_meta($data['order_shipping_id'], 'Трек-код', $resultTracking['state']['tracking']);
                }

                $jsonArr = json_encode($shippingMethods, JSON_UNESCAPED_UNICODE);
                wc_update_order_item_meta($data['order_shipping_id'], 'esl_shipping_methods', $jsonArr);

                if ($data['delivery_id'] == 'sdek' && isset($shippingMethods['answer']['order']['id'])) {
                    return $resultGet;
                }
            }
        }

        return $result;
    }

    public function getMethodByName($name)
    {
        $result = array(
            'name' => '',
            'type' => ''
        );

        $nameList = array(
            'СберЛогистика' => 'sberlogistics',
            '5POST' => 'fivepost',
            'Boxberry' => 'boxberry',
            'Яндекс.Доставка' => 'yandex',
            'СДЭК' => 'sdek',
            'Деловые линии' => 'delline',
            'Халва' => 'halva',
            'Kit' => 'kit',
            'Почта России' => 'postrf',
            'ПЭК' => 'pecom',
            'Магнит Пост' => 'magnit',
            'Байкал Сервис' => 'baikal',
            'DPD' => 'dpd',
            'Фулфилмент-оператор «Почтальон»' => 'pochtalion',
        );

        $typeList = array(
            'пункт выдачи заказа' => 'terminal',
            'доставка до пункта выдачи' => 'terminal',
            'курьер' => 'door',
        );

        foreach ($nameList as $key => $value) {
            if (strpos(mb_strtolower($name), mb_strtolower($key)) !== false) {
                $result['name'] = $value;
            }
        }

        foreach ($typeList as $key => $value) {
            if (strpos(mb_strtolower($name), mb_strtolower($key)) !== false) {
                $result['type'] = $value;
            }
        }

        return $result;
    }

    private function defaultFieldApiCreate($data)
    {
        if (!isset($data['delivery_id']) && !$data['delivery_id']) {
            return false;
        }

        $shippingHelper = new ShippingHelper();
        $optionsRepository = new OptionsRepository();
        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

        if (!isset($apiKey) && !$apiKey) {
            return false;
        }

        $deliveryId = $data['delivery_id'];
        if (isset($data['fulfillment'])) {
            $deliveryId = 'pochtalion';
        }

        $defaultFields = array(
            'key' => $apiKey, //Ключ доступа
            'action' => 'create', //Значение: create
            'cms' => 'wordpress',
            'service' => $deliveryId,
            'order' => array(
                'id' => $data['order_id'], //Идентификатор заказа на сайте.
                'comment' => $data['comment'],
            ),
            'receiver' => array( //Данные получателя
                'name' => $data['receiver-name'],
                'phone' => $data['receiver-phone'],
                'email' => $data['receiver-email'],
            ),
            'sender' => array(
                'name' => $data['sender-name'],
                'phone' => $data['sender-phone'],
                'company' => $data['sender-company'],
                'email' => $data['sender-email'],
            ),
            'delivery' => array(
                'type' => $data['delivery_type'],
                'location_from' => array( //Адрес отправителя (при заборе груза от отправителя)
                    'pick_up' => $data['pick_up'] == '1', //Забор груза от отправителя
                ),
                'payment' => $data['payment_type'],
                'cost' => $data['esl-unload-price'], //Стоимость доставки, рубли.
                'location_to' => array(),
            ),
        );

        if ($data['pick_up'] == '1') {
            $defaultFields['delivery']['location_from']['address'] = array( //Адрес забора груза Обязательно, если delivery.location_from.pick_up === true
                'region' => $data['sender-region'], //Регион. Например: Московская область
                'city' => $data['sender-city'],
                'street' => $data['sender-street'],
                'house' => $data['sender-house'],
                'room' => $data['sender-room'],
            );
        }
        if ($data['pick_up'] == '0') {
            $defaultFields['delivery']['location_from']['terminal'] = $data['sender-terminal'];//Идентификатор пункта приёма груза Обязательно, если delivery.location_from.pick_up === false
        }

        $defaultFields['delivery']['location_to'] = array(
            'address' => array(
                'region' => $data['receiver-region'],
                'city' => $data['receiver-city'],
                'street' => $data['receiver-street'],
                'house' => $data['receiver-house'],
                'room' => $data['receiver-room'],
            ),
        );

        if ($data['delivery_type'] === 'terminal') {
            $defaultFields['delivery']['location_to']['terminal'] = $data['terminal-code'];
        }

        if (isset($data['products'])) {
            foreach ($data['products'] as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }

                $defaultFields['places'][] = array(
                    'article' => $item['product_id'],
                    'name' => $item['name'],
                    'count' => $item['quantity'],
                    'price' => $item['price'],
                    'weight' => $shippingHelper->weightOption($item['weight']),
                    //Вес, в кг.
                    'dimensions' => $shippingHelper->dimensionsOption($item['width']) . '*' . $shippingHelper->dimensionsOption($item['length']) . '*' . $shippingHelper->dimensionsOption($item['height']),
                    //Габариты. Формат: строка вида «Д*Ш*В», в сантиметрах. Например: 15*25*10
                    'vat_rate' => 0,
                    //Значение ставки НДС Возможные варианты:0, 10, 20, -1 (без НДС)
                );
            }
        }

        //if(isset($data['order']) && $data['order']){
        //foreach ($data['order'] as $key=>$value)
        //$defaultFields['order'][$key] = $value;
        //}

        $exportFields = new ExportFileds();
        $exportFields = $exportFields->sendExportFields($data['delivery_id']);
        foreach ($exportFields as $key => $value) {
            if (isset($data[$key])) {
                //$defaultFields[$key] = $defaultFields[$key] + $data[$key];
                $defaultFields[$key] = array_merge_recursive($defaultFields[$key], $data[$key]);
            }
        }

        if (isset($data['fulfillment'])) {
            $defaultFields['delivery']['variant'] = $data['delivery_id'];
        }

        //FAKE
        //$defaultFields['fake'] = 1;

        return $defaultFields;
    }

    public function getPartAddressNameDoor($name)
    {
        if (!$name) {
            return '';
        }

        $result = array(
            'region' => '',
            'city' => '',
            'street' => '',
            'house' => '',
            'room' => '',
        );

        $partExplode = explode(',', $name);

        if (isset($partExplode[0])) {
            $result['region'] = $partExplode[0];
        }
        if (isset($partExplode[1])) {
            $result['city'] = $partExplode[1];
        }
        if (isset($partExplode[2])) {
            $result['street'] = $partExplode[2];
        }
        if (isset($partExplode[3])) {
            $result['house'] = $partExplode[3];
        }
        if (isset($partExplode[4])) {
            $result['room'] = $partExplode[4];
        }
    }

    public function getPartAddressNameTerminal($name)
    {
        if (!$name) {
            return '';
        }

        $result = array(
            'terminal' => '',
        );

        $partExplode = explode(',', $name);

        foreach ($partExplode as $value) {
            if (str_contains($value, 'Код пункта')) {
                $codePart = explode('Код пункта:', $value);
                if (isset($codePart[1])) {
                    $result['terminal'] = trim($codePart[1]);
                }
                $result['terminal_address'] = trim($name);
            }
        }

        return $result;
    }

    public function returnPrint()
    {
        return View::render('unloading/print', [

        ]);
    }

    public function infoOrder($id, $type, $action = 'get', $dataAdd = [])
    {

        $optionsRepository = new OptionsRepository();
        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

        $order = wc_get_order($id);
        $orderData = $order->get_data();
        $orderShippingId = reset($orderData['shipping_lines']);
        $orderShippingId = $orderShippingId->get_id();
        $shippingMethod = wc_get_order_item_meta($orderShippingId, 'esl_shipping_methods', $single = true);
        if ($shippingMethod) {
            $shippingMethods = json_decode($shippingMethod, true);
            if (isset($shippingMethods['answer']['order']['id'])) {
                $id = $shippingMethods['answer']['order']['id'];
            }
        }

        $data = array(
            'key' => $apiKey,
            'action' => $action,
            'order_id' => $id,
            'service' => $type,
            //'fake' => 1
        );

        if($dataAdd){
            $data = array_merge($data, $dataAdd);
        }

        $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
        $result = $eshopLogisticApi->apiExportCreate($data);
        if ($result->hasErrors()) {
            return $result->jsonSerialize();
        }

        return $result->data();
    }

    public function getStatusWp()
    {
        return wc_get_order_statuses();
    }

    public function updateStatusById($id, $order_id)
    {
        if (!isset($id['state']['number']) && !isset($id['state']['status']['code'])) {
            return false;
        }

        $optionsRepository = new OptionsRepository();
        $settingsStatus = $optionsRepository->getOption('wc_esl_shipping_plugin_status_form');

        $order = wc_get_order($order_id);
        $orderStatus = $order->get_status();
        $resultNameStatus = '';

        if (isset($settingsStatus[$id['state']['status']['code']])) {
            $resultNameStatus = $settingsStatus[$id['state']['status']['code']][0]['name'];
        }


        if ($resultNameStatus) {
            if ($orderStatus == $resultNameStatus || 'wc-' . $orderStatus == $resultNameStatus) {
                return 'Статус не изменился';
            }

            $result = $order->update_status($resultNameStatus);
            if ($result) {
                return 'Статус обновлен';
            }
        }

        return 'Ошибка при обновлении';

    }

}