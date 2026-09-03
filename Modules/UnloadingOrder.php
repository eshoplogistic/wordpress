<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\Classes\Shipping\ExportFileds;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Classes\View;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\AddressParser;
use eshoplogistic\WCEshopLogistic\Helpers\EslLogger;
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
        if (!current_user_can('edit_shop_orders')) {
            return false;
        }

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
                $fieldDelivery = $methodDelivery->exportFields(mb_strtolower($typeMethod['name']), $this->shippingMethods, $order, $typeMethod['type']);

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

                $district = '';

                // Структурированных полей адреса на чекауте нет - покупатель пишет улицу/дом/
                // квартиру произвольным текстом в стандартные поля WooCommerce. Разбираем эту
                // строку эвристически и подставляем только то, в чём разбор уверен; остальное
                // оставляем пустым, чтобы не записать в заявку на доставку неверные данные.
                if ($typeMethod['type'] === 'door' && (!$street || !$building || !$room)) {
                    $parsedAddress = AddressParser::parse(
                        (string) $order->get_shipping_address_1() ?: (string) $order->get_billing_address_1(),
                        (string) $order->get_shipping_address_2() ?: (string) $order->get_billing_address_2(),
                        (string) $order->get_shipping_city() ?: (string) $order->get_billing_city(),
                        (string) $order->get_shipping_state() ?: (string) $order->get_billing_state()
                    );

                    if (!$street) $street = $parsedAddress['street'];
                    if (!$building) $building = $parsedAddress['building'];
                    if (!$room) $room = $parsedAddress['room'];
                    $district = $parsedAddress['district'];
                }

                // Регион получателя обычно приходит из debug-данных расчёта стоимости
                // (esl_shipping_methods.debug.shipping_route.to.region), но для части служб/городов
                // (например, городов федерального значения) API их не возвращает, и поле уходит
                // на выгрузку пустым. У некоторых ТК (Байкал Сервис) это приводит к отказу в приёме
                // заявки ("требуется указать адрес доставки"), даже когда указан код ПВЗ. Поле
                // "state" в адресе WooCommerce у заказов, оформленных через виджет плагина,
                // заполняется тем же региональным значением (см. Http/Controllers/OrderController::save()),
                // поэтому используем его как резервный источник — тот же приём, что и для
                // street/building/room выше.
                $region = $this->shippingMethods['debug']['shipping_route']['to']['region'] ?? '';
                if (!$region) {
                    $region = (string) $order->get_shipping_state() ?: (string) $order->get_billing_state();
                }

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
                echo View::render('unloading-form', [
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_orderData' => $orderData,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_orderNumber' => $order->get_order_number(),
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
                    'wc_esl_room' => $room,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_district' => $district,
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    'wc_esl_region' => $region
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
            // Логируем именно тот payload, который реально ушёл в API (defaultParamsCreate),
            // а не сырые данные формы выгрузки ($data) — иначе по логу невозможно понять,
            // какого поля не хватило ТК для отказа (например "требуется указать адрес доставки").
            EslLogger::info('[ESL params_delivery_init] export failed', array(
                'source' => 'esl-error-load-unloading',
                'request' => $defaultParamsCreate,
                'response' => $result->jsonSerialize(),
            ));

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

        // Сразу после успешной выгрузки — попытка немедленно сменить статус заказа
        // (настройка «Статус заказа сразу после выгрузки»), не дожидаясь трек-номера.
        // Best-effort: результат не влияет на ответ оператору по самой выгрузке.
        if (!empty($data['order_id'])) {
            $this->updateStatusById($shippingMethods['answer'], $data['order_id'], true);
        }

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
        // после создания. Повторяем запрос до 2 раз (3с, затем 2с) — короче, чем
        // раньше (было 8с+2с+2с=12с), чтобы не упереться в таймаут PHP/прокси внутри
        // AJAX-запроса. Если трек так и не пришёл, это не ошибка — заявка у ТК уже
        // создана, откатывать локальное состояние нельзя (повторное нажатие
        // "Выгрузить" создаст дубль заявки у перевозчика). Помечаем как "ожидает
        // подтверждения" — дальше трек подхватит Cron/UnloadingCron.php.
        if ($deliveryId === 'pecom') {
            $this->saveShippingMethods($orderShippingId, $shippingMethods);

            $maxAttempts = 2;
            $retryDelay = 2;
            $resultGet = null;

            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                sleep($attempt === 1 ? 3 : $retryDelay);
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
        }

        $this->saveShippingMethods($orderShippingId, $shippingMethods);
    }

    /**
     * Сохраняет трек-код из ответа infoOrder('get'), если он там есть.
     * Нужно для ручного обновления статуса (кнопка "Обновить" в карточке заказа,
     * Ajax::unloadingStatusUpdate()) — этот путь раньше вызывал только
     * updateStatusById() и трек-номер нигде не сохранял, в отличие от
     * params_delivery_init()/Cron/UnloadingCron.php.
     *
     * @param int   $orderId
     * @param array $status Ответ infoOrder('get'), т.е. $resultTracking из applyTrackingResult().
     */
    public function saveTrackingFromStatus($orderId, array $status)
    {
        if (!isset($status['state']['tracking'])) {
            return;
        }

        $orderShippingId = $this->getOrderShippingItemId($orderId);
        if (!$orderShippingId) {
            return;
        }

        $shippingMethod = wc_get_order_item_meta($orderShippingId, 'esl_shipping_methods', $single = true);
        $shippingMethods = $shippingMethod ? json_decode($shippingMethod, true) : [];
        if (!is_array($shippingMethods)) {
            $shippingMethods = [];
        }

        $this->applyTrackingResult($orderShippingId, $shippingMethods, $status);
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
            'Интеграл' => 'integral',
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
                'company' => $exportFormSettings['sender-company'] ?? '',
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
        // передаваемую в ТК, суммой, которую нужно получить с покупателя. Как в moj_sklad, применяется
        // только когда заказ отмечен предоплаченным (payment_type === 'already_paid') — именно для этого
        // сценария ("товар оплачен на сайте, но доставку курьер должен взять при получении") ТК и различает
        // эту сумму отдельно от общего способа оплаты заказа. delivery-custom-cost — служебный ключ,
        // в payload попадать не должен.
        if (isset($data['delivery']) && is_array($data['delivery'])) {
            $takePayment = !empty($data['delivery']['take_payment']);

            if (
                $takePayment
                && ($data['payment_type'] ?? '') === 'already_paid'
                && isset($data['delivery']['delivery-custom-cost'])
                && $data['delivery']['delivery-custom-cost'] !== ''
            ) {
                $defaultFields['delivery']['cost'] = $data['delivery']['delivery-custom-cost'];

                // Ставка НДС для этой суммы — своя настройка (sdek: «Ваша ставка НДС»), как в moj_sklad,
                // приоритетнее общей delivery-vat_rate выше, но только если она вообще задана оператором.
                if (isset($exportFormSettings['cost-custom-delivery-' . $deliveryId]) && $exportFormSettings['cost-custom-delivery-' . $deliveryId] !== '') {
                    $defaultFields['delivery']['vat_rate'] = $exportFormSettings['cost-custom-delivery-' . $deliveryId];
                }
            }

            // У СДЭК (в отличие от postrf/fivepost/yandex, см. overriding-parameters.html)
            // delivery.take_payment — реальный флаг API, включающий "Оплата с получателя:
            // За доставку" в личном кабинете СДЭК. Раньше он вырезался вместе со служебным
            // delivery-custom-cost и никогда не долетал до ТК.
            if ($deliveryId === 'sdek') {
                $defaultFields['delivery']['take_payment'] = $takePayment;
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

        // Продавец — реквизиты "истинного продавца", если он отличается от отправителя.
        // По ТК, а не общие для магазина: если заполнить их глобально, они утекут во все
        // службы разом и СДЭК/другие ТК зарегистрируют заказ как поступивший от третьей
        // стороны, а не от отправителя (см. moj_sklad: seller-name-{deliveryId}).
        $sellerName = $exportFormSettings['seller-name-' . $deliveryId] ?? '';
        $sellerPhone = $exportFormSettings['seller-phone-' . $deliveryId] ?? '';
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

        $exportFieldsHelper = new ExportFileds();
        $exportFields = $exportFieldsHelper->sendExportFields($data['delivery_id']);
        foreach ($exportFields as $key => $value) {
            if (isset($data[$key])) {
                $defaultFields[$key] = $shippingHelper->mergeDeep($defaultFields[$key], $data[$key]);
            }
        }

        // СДЭК/DPD: "Объединение грузовых мест" + "Отправлять состав заказа для страховки" —
        // позиции заказа передаются как есть (см. Classes/Table.php::prepare_items(), там же
        // отключено буквальное слияние строк "Места" при этой комбинации настроек), а вес/габариты
        // итогового объединённого места уходят отдельно через API-поле order.combine_places
        // (нужно транспортным компаниям для оформления страховки груза).
        if (
            in_array($deliveryId, $exportFieldsHelper->carriersWithInsuranceItems(), true)
            && !empty($exportFormSettings['merge-in-one-' . $deliveryId])
            && !empty($exportFormSettings['combine-places-send-items-' . $deliveryId])
            && !empty($data['products'])
        ) {
            $combineWeight = 0;
            $combineWidth = 0;
            $combineLength = 0;
            $combineHeight = 0;

            foreach ($data['products'] as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }

                $quantity = (float) ($item['quantity'] ?? 1);
                $combineWeight += (float) ($item['weight'] ?? 0) * $quantity;
                $combineWidth = max($combineWidth, (float) ($item['width'] ?? 0));
                $combineLength = max($combineLength, (float) ($item['length'] ?? 0));
                $combineHeight = max($combineHeight, (float) ($item['height'] ?? 0));
            }

            if (!empty($exportFormSettings['default-stt-width-' . $deliveryId])) {
                $combineWidth = $exportFormSettings['default-stt-width-' . $deliveryId];
            }
            if (!empty($exportFormSettings['default-stt-length-' . $deliveryId])) {
                $combineLength = $exportFormSettings['default-stt-length-' . $deliveryId];
            }
            if (!empty($exportFormSettings['default-stt-height-' . $deliveryId])) {
                $combineHeight = $exportFormSettings['default-stt-height-' . $deliveryId];
            }

            $defaultFields['order']['combine_places'] = array(
                'apply' => true,
                'weight' => $shippingHelper->weightOption($combineWeight),
                'dimensions' => $shippingHelper->dimensionsOption($combineWidth) . '*' . $shippingHelper->dimensionsOption($combineLength) . '*' . $shippingHelper->dimensionsOption($combineHeight),
            );
        }

        if (isset($data['fulfillment'])) {
            $defaultFields['delivery']['variant'] = $data['delivery_id'];
        }

        if (WC_ESL_FAKE_EXPORT) {
            $defaultFields['fake'] = 1;
        }

        // Точка доработки: фильтр получает уже полностью собранный запрос на выгрузку
        // (places/receiver/sender/delivery и т.д.) и может поправить его перед фактической
        // отправкой ТК — например, если штатных настроек ТК не хватает.
        $originalFields = $defaultFields;
        $defaultFields = apply_filters('wc_esl_before_export', $defaultFields, $data);

        if ($defaultFields !== $originalFields) {
            // 'key' — токен доступа к API, в журнал не пишем.
            $sanitizedBefore = $originalFields;
            $sanitizedAfter = $defaultFields;
            unset($sanitizedBefore['key'], $sanitizedAfter['key']);

            EslLogger::debug('[ESL export] wc_esl_before_export changed request data', array(
                'order_id' => $data['order_id'] ?? '',
                'delivery_id' => $data['delivery_id'] ?? '',
                'before' => $sanitizedBefore,
                'after' => $sanitizedAfter,
            ));
        }

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

        $carrierOrderId = $this->getCarrierOrderId($id);
        if ($carrierOrderId === null) {
            return array(
                'success' => false,
                'data' => array(
                    'messages' => __('Заказ ещё не выгружен в кабинет транспортной компании', 'eshoplogisticru'),
                ),
            );
        }
        $id = $carrierOrderId;

        $data = array(
            'key' => $apiKey,
            'action' => $action,
            'order_id' => $id,
            'service' => $type,
        );

        if (WC_ESL_FAKE_EXPORT) {
            $data['fake'] = 1;
        }

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

    /**
     * Резолвит идентификатор заказа в системе ТК по локальному WC order id
     * (esl_shipping_methods.answer.order.id), с фолбэком на сам order id,
     * если выгрузки ещё не было. Общий кусок для infoOrder()/printOrder().
     *
     * @param int $orderId
     *
     * @return int|string
     */
    private function resolveCarrierOrderId($orderId)
    {
        return $this->getCarrierOrderId($orderId) ?? $orderId;
    }

    /**
     * Как resolveCarrierOrderId(), но без фолбэка: null означает, что заказ ещё
     * не выгружен в кабинет ТК (нет esl_shipping_methods.answer.order.id). Нужен
     * отдельно для infoOrder() — там фолбэк на локальный order_id бессмысленен:
     * такого заказа у ТК не существует, и get/tracking/delete с ним гарантированно
     * вернут ошибку (для которой у ТК нет более осмысленного текста, чем "Ошибка
     * получения данных от транспортной компании") вместо явного "не выгружен".
     *
     * @param int $orderId
     *
     * @return int|string|null
     */
    private function getCarrierOrderId($orderId)
    {
        $order = wc_get_order($orderId);
        if (!$order) {
            return null;
        }

        $orderData = $order->get_data();
        $orderShippingId = reset($orderData['shipping_lines']);
        if (!$orderShippingId) {
            return null;
        }
        $orderShippingId = $orderShippingId->get_id();

        $shippingMethod = wc_get_order_item_meta($orderShippingId, 'esl_shipping_methods', $single = true);
        if ($shippingMethod) {
            $shippingMethods = json_decode($shippingMethod, true);
            if (isset($shippingMethods['answer']['order']['id'])) {
                return $shippingMethods['answer']['order']['id'];
            }
        }

        return null;
    }

    /**
     * Получение печатной формы (этикетка/накладная/штрихкоды и т.д.) по уже
     * выгруженному заказу. Портировано из moj_sklad UnloadingPrint::initType() —
     * тот же endpoint (delivery/order, action=print), только результат — не HTML
     * виджета, а сырые success/url для рендера ссылки в Ajax::unloadingPrint().
     *
     * @param int    $orderId
     * @param string $orderType Слаг ТК (sdek, pecom, dpd ...).
     * @param string $mode      barcodes|order|label|invoice|bill|act ...
     * @param string $paper     Формат бумаги (A4, A5 ...), если применимо к ТК.
     * @param string $type      Доп. вариант печатной формы (напр. yandex: one|many — ярлыков на страницу).
     *
     * @return array{success: bool, url?: string, error?: array}
     */
    public function printOrder($orderId, $orderType, $mode, $paper = '', $type = '')
    {
        $optionsRepository = new OptionsRepository();
        $apiKey = $optionsRepository->getOption('wc_esl_shipping_api_key');

        $carrierOrderId = $this->resolveCarrierOrderId($orderId);

        $data = array(
            'key' => $apiKey,
            'action' => 'print',
            'order_id' => $carrierOrderId,
            'service' => $orderType,
        );

        if (WC_ESL_FAKE_EXPORT) {
            $data['fake'] = 1;
        }

        if ($mode) {
            $data['mode'] = $mode;
        }
        if ($paper) {
            $data['format'] = $paper;
        }
        if ($type) {
            $data['type'] = $type;
        }

        $eshopLogisticApi = new EshopLogisticApi(new WpHttpClient());
        $result = $eshopLogisticApi->apiExportCreate($data);

        if ($result->hasErrors()) {
            return array('success' => false, 'error' => $result->jsonSerialize());
        }

        $resultData = $result->data();

        return array('success' => true, 'url' => $resultData['url'] ?? '');
    }

    public function getStatusWp()
    {
        return wc_get_order_statuses();
    }

    public static function getCarrierStatusNames()
    {
        return [
            'accepted'   => 'Загружен в ЛК перевозчика',
            'need_check' => 'Загружен в ЛК перевозчика, но требуется уточнения',
            'created'    => 'Загружен в ЛК перевозчика и проверен',
            'received'   => 'Принят на склад перевозчика',
            'delivered'  => 'В доставке у перевозчика',
            'awaiting'   => 'Ожидает самовывоза из ПВЗ/постамата',
            'courier'    => 'Передан курьеру',
            'taken'      => 'Доставлен',
            'canceled'   => 'Отменен',
            'return'     => 'Возвращается отправителю',
            'returned'   => 'Возвращен отправителю',
            'n/a'        => 'Не определён',
        ];
    }

    public function updateStatusById($id, $order_id, $first = false)
    {
        $optionsRepository = new OptionsRepository();
        $order = wc_get_order($order_id);
        if (!$order) {
            return false;
        }

        $orderStatus = $order->get_status();
        $resultNameStatus = '';

        // Сразу после успешной выгрузки — если в настройках задан фиксированный статус
        // (независимо от статуса ТК), используем его вместо ожидания трек-номера/кода статуса.
        if ($first) {
            $exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
            if (!empty($exportFormSettings['after-unloading-status'])) {
                $resultNameStatus = $exportFormSettings['after-unloading-status'];
            }
        }

        if (!$resultNameStatus) {
            if (!isset($id['state']['number']) && !isset($id['state']['status']['code'])) {
                return false;
            }

            $settingsStatus = $optionsRepository->getOption('wc_esl_shipping_plugin_status_form');
            if (isset($id['state']['status']['code']) && isset($settingsStatus[$id['state']['status']['code']])) {
                $resultNameStatus = $settingsStatus[$id['state']['status']['code']][0]['name'];
            }
        }

        if ($resultNameStatus) {
            if ($orderStatus == $resultNameStatus || 'wc-' . $orderStatus == $resultNameStatus) {
                return 'Статус не изменился';
            }

            try {
                $result = $order->update_status($resultNameStatus);
            } catch (\Exception $e) {
                return 'Ошибка при обновлении статуса: ' . $e->getMessage();
            }

            if ($result) {
                return 'Статус обновлен';
            }

            return sprintf('Ошибка при обновлении: WooCommerce не смог установить статус "%s"', $resultNameStatus);
        }

        $carrierCode = $id['state']['status']['code'] ?? null;
        $carrierNumber = $id['state']['number'] ?? null;

        if ($carrierCode !== null) {
            $carrierStatusNames = self::getCarrierStatusNames();
            $carrierStatusName = $carrierStatusNames[$carrierCode] ?? $carrierCode;

            return sprintf(
                'Ошибка при обновлении: для статуса ТК "%s" не настроено сопоставление в настройках плагина',
                $carrierStatusName
            );
        }

        return sprintf(
            'Ошибка при обновлении: не найдено сопоставление статуса для трек-номера "%s"',
            $carrierNumber
        );

    }

}