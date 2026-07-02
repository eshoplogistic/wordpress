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

class UnloadingOrder implements ModuleInterface
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
        'seller' => array(
            'name' => '',
            'phone' => '',
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
                'platform_id' => '', //Код склада отправителя (нужен отдельным ТК, например Яндекс.Доставке)
            ),
            'payment' => '',
            'vat_rate' => '', //Значение ставки НДС на доставку
            'cost' => '', //Стоимость доставки, рубли.
            'location_to' => array(
                'terminal' => '',
                'address' => array(
                    'region' => '',
                    'district' => '', //Район
                    'city' => '',
                    'street' => '',
                    'house' => '',
                    'room' => '',
                ),
            ),
        ),
        'complement' => array(), //Доп.услуги ТК, полученные через apiExportAdditional
    );

    public function init()
    {

        add_action('admin_head', [$this, 'esl_form_in_admin_bar']);
        add_action('add_meta_boxes', [$this, 'esl_button_start_meta_boxes']);
        add_action('add_meta_boxes', [$this, 'esl_button_start_meta_boxes_HPOS']);
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
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin order page parameter.
        if (isset($_GET['id'])) {
            $postId = absint(wp_unslash($_GET['id']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
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
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin order page parameter.
        if (isset($_GET['id'])) {
            $postId = absint(wp_unslash($_GET['id']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
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
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin order page parameter.
        if (isset($_GET['id'])) {
            $postId = absint(wp_unslash($_GET['id']));
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
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

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
                echo View::render('unloading-form', [
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_orderData' => $orderData,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_orderItems' => $orderItems,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_orderShipping' => $orderShipping,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_address' => $address,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_addressShipping' => $cutAddressShipping,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_typeMethod' => $typeMethod,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_additionalFields' => $additionalFields,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_exportFormSettings' => $exportFormSettings,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_shippingMethods' => $this->shippingMethods,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_fieldDelivery' => $fieldDelivery,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_orderShippingId' => $orderShippingId,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_infoApi' => $infoApi,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_addFieldSaved' => $addFieldSaved,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_street' => $street,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_building' => $building,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_room' => $room
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
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
        echo View::render('unloading-button', [
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            'wc_esl_shippingMethods' => $this->shippingMethods,
        ]);
    }

    public function params_delivery_init($data)
    {
        $data = json_decode(stripslashes($data), true);
        $defaultParamsCreate = $this->defaultFieldApiCreate($data);

        $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
        $result = $eshopLogisticApi->apiExportCreate($defaultParamsCreate);

        if ($result->hasErrors()) {
            return $result;
        }

        $orderShippingId = $data['order_shipping_id'];
        $deliveryId = $data['delivery_id'];

        $shippingMethod = wc_get_order_item_meta($orderShippingId, 'esl_shipping_methods', $single = true);
        $shippingMethods = $shippingMethod ? json_decode($shippingMethod, true) : [];
        if (!is_array($shippingMethods)) {
            $shippingMethods = [];
        }
        $shippingMethods['answer'] = $result->data();

        $orderId = $shippingMethods['answer']['order']['id'] ?? '';
        if (!$orderId) {
            $this->saveShippingMethods($orderShippingId, $shippingMethods);
            return $result;
        }

        $optionsRepository = new OptionsRepository();
        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');
        $dataGet = array(
            'key' => $apiKey,
            'action' => 'get',
            'order_id' => $orderId,
            'service' => $deliveryId,
        );

        // ПЭК подтверждает заявку асинхронно — трек-номер может быть не готов сразу
        // после создания. Повторяем запрос до 3 раз (8с, затем 2с, 2с). Если за 3
        // попытки трек так и не пришёл, это не ошибка — заявка у ТК уже создана,
        // откатывать локальное состояние нельзя (повторное нажатие "Выгрузить"
        // создаст дубль заявки у перевозчика). Помечаем как "ожидает подтверждения".
        if ($deliveryId === 'pecom') {
            $this->saveShippingMethods($orderShippingId, $shippingMethods);

            $maxAttempts = 3;
            $retryDelay = 2;
            $resultGet = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                sleep($attempt === 1 ? 8 : $retryDelay);
                $resultGet = $eshopLogisticApi->apiExportCreateSdek($dataGet);
                if (!$resultGet->hasErrors()) {
                    break;
                }
            }

            if (!$resultGet->hasErrors()) {
                unset($shippingMethods['pending_confirmation']);
                $this->applyTrackingResult($orderShippingId, $shippingMethods, $resultGet->data());
                return $resultGet;
            }

            $shippingMethods['pending_confirmation'] = true;
            $this->saveShippingMethods($orderShippingId, $shippingMethods);
            return $result;
        }

        // СДЭК: один follow-up запрос, но с полноценным откатом при ошибке — если ТК
        // вернула ошибку именно на этапе получения трек-номера, считаем выгрузку
        // неудавшейся и разрешаем оператору повторить попытку.
        if ($deliveryId === 'sdek') {
            sleep(3);
            $resultGet = $eshopLogisticApi->apiExportCreateSdek($dataGet);

            if (!$resultGet->hasErrors()) {
                $this->applyTrackingResult($orderShippingId, $shippingMethods, $resultGet->data());
                return $resultGet;
            }

            $this->clearShippingAnswer($orderShippingId);
            return $resultGet;
        }

        // Остальные ТК: необязательный best-effort follow-up без отката — трек-номер
        // может прийти позже через периодический опрос статуса (Cron/UnloadingCron.php).
        sleep(3);
        $resultGet = $eshopLogisticApi->apiExportCreateSdek($dataGet);
        if (!$resultGet->hasErrors()) {
            $this->applyTrackingResult($orderShippingId, $shippingMethods, $resultGet->data());
        } else {
            $this->saveShippingMethods($orderShippingId, $shippingMethods);
        }

        return $result;
    }

    /**
     * Резолвит order-item ID строки доставки заказа (тот же паттерн, что уже
     * используется в infoOrder()).
     *
     * @param int $orderId
     *
     * @return int
     */
    private function getOrderShippingItemId($orderId)
    {
        $order = wc_get_order($orderId);
        if (!$order) {
            return 0;
        }

        $orderData = $order->get_data();
        $orderShippingId = reset($orderData['shipping_lines']);

        return $orderShippingId ? $orderShippingId->get_id() : 0;
    }

    /**
     * @param int   $orderShippingId
     * @param array $shippingMethods
     */
    private function saveShippingMethods($orderShippingId, array $shippingMethods)
    {
        wc_update_order_item_meta($orderShippingId, 'esl_shipping_methods', json_encode($shippingMethods, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @param int   $orderShippingId
     * @param array $shippingMethods
     * @param array $resultTracking
     */
    private function applyTrackingResult($orderShippingId, array $shippingMethods, $resultTracking)
    {
        if (isset($resultTracking['state']['tracking'])) {
            $shippingMethods['tracking'] = $resultTracking['state']['tracking'];
            wc_update_order_item_meta($orderShippingId, 'Трек-код', $resultTracking['state']['tracking']);
        }

        $this->saveShippingMethods($orderShippingId, $shippingMethods);
    }

    /**
     * Сбрасывает локальные данные о созданной заявке (ответ ТК, трек-номер, флаг
     * ожидания подтверждения) — используется и как откат при ошибке follow-up
     * запроса (SDEK), и при явном удалении заявки оператором.
     *
     * @param int $orderShippingId
     */
    private function clearShippingAnswer($orderShippingId)
    {
        $shippingMethod = wc_get_order_item_meta($orderShippingId, 'esl_shipping_methods', $single = true);
        $shippingMethods = $shippingMethod ? json_decode($shippingMethod, true) : [];
        if (!is_array($shippingMethods)) {
            $shippingMethods = [];
        }

        unset($shippingMethods['answer'], $shippingMethods['tracking'], $shippingMethods['pending_confirmation']);

        $this->saveShippingMethods($orderShippingId, $shippingMethods);
        wc_delete_order_item_meta($orderShippingId, 'Трек-код');
    }

    /**
     * Публичная точка входа для очистки локального состояния заявки по ID заказа
     * WooCommerce (используется после удаления заявки в кабинете ТК).
     *
     * @param int $orderId
     */
    public function clearLocalShipment($orderId)
    {
        $orderShippingId = $this->getOrderShippingItemId($orderId);
        if (!$orderShippingId) {
            return;
        }

        $this->clearShippingAnswer($orderShippingId);
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
            'Яндекс Доставка' => 'yandex',
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
        $exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
        if (!is_array($exportFormSettings)) {
            $exportFormSettings = array();
        }

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

        // Код склада отправителя для служб, которым он требуется (например, Яндекс.Доставка). Настраивается в разрезе службы доставки.
        if (!empty($exportFormSettings['platform_id-' . $deliveryId])) {
            $defaultFields['delivery']['location_from']['platform_id'] = $exportFormSettings['platform_id-' . $deliveryId];
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

        // Район получателя — заполняется вручную оператором, если требуется службе доставки.
        if (!empty($data['receiver-district'])) {
            $defaultFields['delivery']['location_to']['address']['district'] = $data['receiver-district'];
        }

        // Код ФИАС города получателя — передаётся, только если явно указан (например, интеграцией поиска адреса).
        if (!empty($data['delivery-location_to-city_fias'])) {
            $defaultFields['delivery']['location_to']['address']['city_fias'] = $data['delivery-location_to-city_fias'];
        }

        if ($data['delivery_type'] === 'terminal') {
            $defaultFields['delivery']['location_to']['terminal'] = $data['terminal-code'];
        }

        // Ставка НДС на доставку — передаётся только если явно указана оператором, чтобы не менять поведение по умолчанию на стороне API.
        if (isset($data['delivery-vat_rate']) && $data['delivery-vat_rate'] !== '') {
            $defaultFields['delivery']['vat_rate'] = $data['delivery-vat_rate'];
        }

        // Опция «Взять оплату с получателя за доставку» (sdek/postrf/fivepost/yandex) — переопределяет стоимость доставки,
        // передаваемую в ТК, суммой, которую нужно получить с покупателя. Служебные ключи не должны попасть в итоговый payload.
        if (isset($data['delivery']) && is_array($data['delivery'])) {
            if (!empty($data['delivery']['take_payment']) && isset($data['delivery']['delivery-custom-cost']) && $data['delivery']['delivery-custom-cost'] !== '') {
                $defaultFields['delivery']['cost'] = $data['delivery']['delivery-custom-cost'];
            }
            unset($data['delivery']['take_payment'], $data['delivery']['delivery-custom-cost']);
        }

        if (isset($data['products'])) {
            $defaultPlaceVatRate = $exportFormSettings['default-vat-rate-' . $deliveryId] ?? 0;
            $declaredPriceZero = !empty($exportFormSettings['type-price-null-' . $deliveryId]);

            foreach ($data['products'] as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }

                $place = array(
                    'article' => $item['product_id'],
                    'name' => $item['name'],
                    'count' => $item['quantity'],
                    'price' => $item['price'],
                    'weight' => $shippingHelper->weightOption($item['weight']),
                    //Вес, в кг.
                    'dimensions' => $shippingHelper->dimensionsOption($item['width']) . '*' . $shippingHelper->dimensionsOption($item['length']) . '*' . $shippingHelper->dimensionsOption($item['height']),
                    //Габариты. Формат: строка вида «Д*Ш*В», в сантиметрах. Например: 15*25*10
                    'vat_rate' => $item['vat'] ?? $defaultPlaceVatRate,
                    //Значение ставки НДС Возможные варианты:0, 10, 20, -1 (без НДС)
                );

                if ($declaredPriceZero) {
                    $place['declared_price'] = 0;
                }

                $defaultFields['places'][] = $place;
            }
        }

        // Доп.услуги (чекбоксы/числовые поля из вкладки «Дополнительные услуги»), отправляются как есть в блок complement.
        if (isset($data['complement']) && is_array($data['complement'])) {
            $defaultFields['complement'] = $data['complement'];
        }

        // Продавец — общие для магазина данные (не завязаны на конкретную ТК), заполняются в настройках плагина.
        $sellerName = $exportFormSettings['seller-name'] ?? '';
        $sellerPhone = $exportFormSettings['seller-phone'] ?? '';
        if ($sellerName !== '' || $sellerPhone !== '') {
            $defaultFields['seller'] = array(
                'name' => $sellerName,
                'phone' => $sellerPhone,
            );
        }

        // Позволяет переопределить идентификатор заказа на стороне ТК, если оператор указал свой номер.
        if (!empty($data['sender-custom-order-id'])) {
            $defaultFields['order']['id'] = $data['sender-custom-order-id'];
        }

        $exportFields = new ExportFileds();
        $exportFields = $exportFields->sendExportFields($data['delivery_id']);
        foreach ($exportFields as $key => $value) {
            if (isset($data[$key])) {
                $defaultFields[$key] = $shippingHelper->mergeDeep($defaultFields[$key], $data[$key]);
            }
        }

        if (isset($data['fulfillment'])) {
            $defaultFields['delivery']['variant'] = $data['delivery_id'];
        }

        //FAKE
        $defaultFields['fake'] = 1;

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

        return $result;
    }

    public function getPartAddressNameTerminal($name)
    {
        if (!$name) {
            return array(
                'terminal' => '',
                'terminal_address' => '',
            );
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