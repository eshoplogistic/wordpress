<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Classes\Plugin;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Services\SessionService;
use eshoplogistic\WCEshopLogistic\Models\CheckoutOrderData;
use eshoplogistic\WCEshopLogistic\Models\OfferData;
use eshoplogistic\WCEshopLogistic\Helpers\ShippingHelper;

if ( ! defined('ABSPATH') ) {
    exit;
}

class GutenbergBlock implements ModuleInterface
{
    private $optionsRepository;

    public function __construct()
    {
        $this->optionsRepository = new OptionsRepository();
    }

    public function init()
    {
        // Регистрация кастомных блоков через block.json
        add_action('init', [$this, 'registerBlocks']);
        
        // Подключение ресурсов блоков (frontend и стили для editor + frontend)
        add_action('enqueue_block_assets', [$this, 'enqueueBlockAssets']);
        
        // Подключение стилей и скриптов только для редактора
        add_action('enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets']);
        
        // Локализация frontend-скриптов
        add_action('wp_enqueue_scripts', [$this, 'localizeBlockScripts']);

        // Автоподключение калькулятора к "голому" блочному чекауту (woocommerce/checkout
        // без явно вставленного eshoplogisticru/checkout-shipping) — доинъектируем блок
        // сразу после order-summary, туда же, куда он попадает при ручной вставке.
        add_filter('render_block_woocommerce/checkout-order-summary-block', [$this, 'injectCheckoutShippingBlock'], 10, 2);
    }

    /**
     * Доинъектирует калькулятор ESL в блочный checkout, если он не вставлен вручную.
     *
     * @param string $blockContent Отрендеренный HTML блока order-summary
     * @param array  $block Данные блока
     * @return string
     */
    public function injectCheckoutShippingBlock($blockContent, $block)
    {
        if (! is_checkout() || is_wc_endpoint_url('order-received')) {
            return $blockContent;
        }

        // Явно вставленный блок уже сам всё отрендерит — не дублируем.
        if (has_block('eshoplogisticru/checkout-shipping')) {
            return $blockContent;
        }

        if (!$this->isAccountUsable()) {
            return $blockContent;
        }

        return $blockContent . $this->renderCheckoutShippingBlock([]);
    }

    /**
     * Аккаунт пригоден для расчёта доставки (ключ настроен, не заблокирован, последняя
     * синхронизация состояния аккаунта не завершилась ошибкой — см. Classes\Plugin::isEnable()).
     * Этот блок — Gutenberg-блок из tier-1 (always-on), в отличие от Modules\Shipping/Checkout
     * он не гейтится автоматически при инициализации модулей, поэтому проверяем явно здесь,
     * иначе виджет выбора ПВЗ продолжит работать в блочном чекауте, даже когда доставка отключена
     * (см. случай "закончился баланс аккаунта").
     *
     * @return bool
     */
    private function isAccountUsable()
    {
        $plugin = new Plugin();
        return $plugin->isEnable();
    }

    /**
     * Регистрирует Gutenberg-блоки для eShopLogistic
     */
    public function registerBlocks()
    {
        // Сначала предварительно регистрируем стили, чтобы блоки могли на них ссылаться
        wp_register_style(
            'wc-esl-block-frontend-styles',
            WC_ESL_PLUGIN_URL . 'assets/css/block-styles.css',
            [],
            WC_ESL_VERSION
        );

        // Регистрируем блоки с явными render callback-ами
        register_block_type(WC_ESL_PLUGIN_DIR . 'blocks/checkout-shipping/block.json', [
            'render_callback' => function($attributes, $content, $block) {
                return $this->renderCheckoutShippingBlock($attributes);
            }
        ]);

        register_block_type(WC_ESL_PLUGIN_DIR . 'blocks/product-calculator/block.json', [
            'render_callback' => function($attributes, $content, $block) {
                return $this->renderProductCalculatorBlock($attributes);
            }
        ]);

        register_block_type(WC_ESL_PLUGIN_DIR . 'blocks/cart-shipping/block.json', [
            'render_callback' => function($attributes, $content, $block) {
                return $this->renderCartShippingBlock($attributes);
            }
        ]);

        register_block_type(WC_ESL_PLUGIN_DIR . 'blocks/checkout-form/block.json', [
            'render_callback' => function($attributes, $content, $block) {
                return $this->renderCheckoutFormBlock($attributes);
            }
        ]);
    }

    /**
     * Рендерит блок доставки на checkout
     */
    public function renderCheckoutShippingBlock($attributes)
    {
        // Получаем необходимые опции
        $widgetKey = $this->optionsRepository->getOption('wc_esl_shipping_widget_key');
        $apiKeyWCart = $this->optionsRepository->getOption('wc_esl_shipping_api_key_wcart');

        // Если один из ключей не задан — используем другой как fallback
        if (empty($widgetKey) && !empty($apiKeyWCart)) {
            $widgetKey = $apiKeyWCart;
        } elseif (!empty($widgetKey) && empty($apiKeyWCart)) {
            $apiKeyWCart = $widgetKey;
        }

        // Получаем настройку корзинного виджета
        $frameEnable = $this->optionsRepository->getOption('wc_esl_shipping_frame_enable');
        $isFrameEnabled = !empty($frameEnable) && in_array($frameEnable, ['yes', 'on', '1', 1, true], true);

        // Если настройка неполная, показываем заглушку
        if (empty($widgetKey) && empty($apiKeyWCart)) {
            return '<div class="wc-esl-checkout-shipping-block" style="padding: 20px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; margin: 20px 0;">' .
                   '<p style="color: #666;">' . esc_html__('eShopLogistic Shipping Calculator - Configuration required', 'eshoplogisticru') . '</p>' .
                   '</div>';
        }
        
        // Полный виджет показываем только на реальной странице checkout
        if (is_checkout() && !is_wc_endpoint_url('order-received')) {
            if (!$this->isAccountUsable()) {
                return '';
            }

            // Получаем данные сессии
            $sessionService = new SessionService();
            $shippingEsl = $sessionService->get('esl_shipping_frame');

            // Blocks checkout работает только с shipping-адресом.
            $widgetCityEsl = $sessionService->get('shipping') ?: [];

            // Мягкая миграция старого состояния: если в shipping пусто,
            // но в billing есть город, переносим его в shipping для блока.
            if (empty($widgetCityEsl)) {
                $legacyBillingCity = $sessionService->get('billing') ?: [];
                if (!empty($legacyBillingCity)) {
                    $widgetCityEsl = $legacyBillingCity;
                    $sessionService->set('shipping', $legacyBillingCity);
                }
            }

            // Для последующих block-рендеров фиксируем mode как shipping.
            $sessionService->set('mode_shipping', 'shipping');
            $terminalLocation = $sessionService->get('terminal_location') ?: '';
            
            // Получаем данные корзины
            $items = WC()->cart->get_cart_contents();
            $data = new CheckoutOrderData($items);
            $widgetOffersEsl = [];
            
            if ($data->getItems()) {
                /** @var OfferData $item */
                foreach ($data->getItems() as $item) {
                    $widgetOffersEsl[] = [
                        'article' => $item->getArticle(),
                        'name' => $item->getName(),
                        'count' => $item->getQuantity(),
                        'price' => $item->getPrice(),
                        'weight' => $item->getWeight(),
                        'dimensions' => $item->getDimensions(),
                    ];
                }
            }
            
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy hook name retained for backward compatibility.
            $widgetOffersEsl = apply_filters('esl_offers_filter', $widgetOffersEsl);
            
            // Получаем способы оплаты
            $paymentMethods = $this->optionsRepository->getOption('wc_esl_shipping_payment_methods') ?: [];
            
            // Получаем текст кнопки из настроек
            $addOption = $this->optionsRepository->getOption('wc_esl_shipping_add_form');
            $pvzName = isset($addOption['pvzName']) && $addOption['pvzName'] 
                ? $addOption['pvzName'] 
                : __('Выбрать способ доставки и пункт самовывоза', 'eshoplogisticru');

            $count = 0;
            $countText = __('службы', 'eshoplogisticru');

            $eslLoader = false;
            if (isset($addOption['eslLoader'])) {
                $eslLoader = wp_get_attachment_image_url($addOption['eslLoader'], 'full');
            }

            $citySelectModal = isset($addOption['citySelectModal']) && $addOption['citySelectModal'] === 'true';

            // Запускаем буферизацию вывода
            ob_start();
            ?>
            <div class="wc-esl-checkout-shipping-block" data-block-type="checkout-shipping">
                <div class="preloader">
                    <?php if ($eslLoader): ?>
                        <div class="preloader__img">
                            <img src="<?php echo esc_attr($eslLoader); ?>" width="150" height="150">
                        </div>
                    <?php else: ?>
                        <div class="preloader__row">
                            <div class="preloader__item"></div>
                            <div class="preloader__item"></div>
                        </div>
                    <?php endif; ?>
                </div>
                <div id="tips-city-container" style="display: none;">
                    <i class="ico">☓</i>
                    <?php echo esc_html__('Укажите город для расчета доставки', 'eshoplogisticru'); ?>
                </div>

                <div id="wc-esl-terminals-wrap-button-shipping" class="wc-esl-terminals__container wc-esl-terminals__frame">
                    <div class="esl_desct_delivery" style="display: none;">
                        <p>Всего доступно <span class="count"><?php echo esc_html($count); ?></span>
                        <span class="countText"><?php echo esc_html($countText); ?></span> доставки.
                            <br><span class="addText">Выбран самый дешевый вариант.</span></p>
                    </div>
                    <button
                            class="wc-esl-terminals__button wc-esl-frame__button"
                            type="button"
                            data-mode="shipping"
                    >
                        <?php echo esc_html($pvzName); ?>
                    </button>
                    <p class="form-row form-row-wide validate-required wc_esl_shipping_terminal_field" id="wc_esl_shipping_terminal_field"<?php echo $terminalLocation ? '' : ' style="display:none;"'; ?>>
                        <label for="wc_esl_shipping_terminal"><?php echo esc_html__('Пункт выдачи', 'eshoplogisticru'); ?> <abbr class="required" title="<?php echo esc_attr__('обязательное поле', 'eshoplogisticru'); ?>">*</abbr></label>
                        <span class="woocommerce-input-wrapper">
                            <input type="text" class="input-text" name="wc_esl_shipping_terminal" id="wc_esl_shipping_terminal" value="<?php echo esc_attr($terminalLocation); ?>" readonly>
                        </span>
                    </p>
                </div>
                
                <?php if ($isFrameEnabled): ?>
                <div id="modal-esl-frame" class="modal-esl-frame">
                    <div class="modal_content">
                        <div class="title">
                            <span class="close_modal_window">×</span>
                        </div>
                        <div id="eShopLogisticWidgetCart" 
                             data-key="<?php echo esc_attr($apiKeyWCart); ?>" 
                             data-lazy-load="false" 
                             data-controller="/?rest_route=/wc-esl/v2/widget-data/" 
                             data-v-app></div>
                        <div id="boxEshoplogistic" class="boxEshoplogistic" style="display:none;">
                            <div id='eShopLogisticWidgetKey' data-key='<?php echo esc_attr($widgetKey); ?>'></div>
                            <input id='widgetOffersEsl' value='<?php echo esc_attr(json_encode($widgetOffersEsl)); ?>' type='hidden'>
                            <input id='widgetCityEsl' value='<?php echo esc_attr(json_encode($widgetCityEsl)); ?>' type='hidden'>
                            <input id='widgetPaymentEsl' value='<?php echo esc_attr(json_encode($paymentMethods)); ?>' type='hidden'>
                            <input id='wc_esl_billing_terminal' value='<?php echo esc_attr($terminalLocation); ?>' type='hidden'>
                        </div>
                        <div class="footer">
                            <input id="buttonModalDoor" type="button" value="<?php echo esc_attr__('Выбрать', 'eshoplogisticru'); ?>">
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <?php
                // Корзинный виджет выключен — рендерим данные терминалов для яндекс-карты
                $shippingHelper = new ShippingHelper();
                $chosenMethods  = WC()->session->get('chosen_shipping_methods') ?: [];
                $chosenMethod   = isset($chosenMethods[0]) ? $chosenMethods[0] : '';
                $typeMethod     = $chosenMethod ? $shippingHelper->getTypeMethod($chosenMethod) : null;
                $terminals      = [];
                if ($typeMethod === 'terminal') {
                    $stateShippingMethods = $sessionService->get('shipping_methods') ?: [];
                    $terminals = isset($stateShippingMethods[$chosenMethod]['terminals'])
                        ? $stateShippingMethods[$chosenMethod]['terminals']
                        : [];
                }
                $apiKeyYa = $this->optionsRepository->getOption('wc_esl_shipping_api_key_ya') ?: '';
                ?>
                <input type="hidden" name="wc-esl-terminals" id="wcEslTerminals" value="<?php echo esc_attr(wp_json_encode($terminals)); ?>" />
                <input type="hidden" name="wc-esl-api-key-ya" id="wcEslKeyYa" value="<?php echo esc_attr($apiKeyYa); ?>" />
                <?php endif; ?>

                <?php if ($citySelectModal): ?>
                <div id="modal-esl-city" class="modal-esl-frame">
                    <div class="modal_content">
                        <div class="title">
                            <span class="close_modal_window">×</span>
                            <p><strong><?php echo esc_html__('Выберите свой населённый пункт', 'eshoplogisticru'); ?></strong><br><?php echo esc_html__('Начните ввод названия населённого пункта для поиска', 'eshoplogisticru'); ?></p>
                        </div>
                        <input id="esl_modal-search" value="" placeholder="<?php echo esc_attr__('Населенный пункт', 'eshoplogisticru'); ?>" data-mode="shipping">
                        <div id="esl_result-search"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php
            return ob_get_clean();
        }
        
             // Для предпросмотра в редакторе админки показываем заглушку
        return $this->renderBlockPlaceholder('wc-esl-checkout-shipping-block', __('Shipping Calculator (Checkout)', 'eshoplogisticru'), __('Displays on checkout page', 'eshoplogisticru'));
    }

    /**
             * Рендерит блок калькулятора на странице товара
     */
    public function renderProductCalculatorBlock($attributes)
    {
        if (is_product()) {
            global $post;
            $product = wc_get_product($post->ID);

            if (!$product) {
                return '';
            }

            $widgetKey = !empty($attributes['widgetKey'])
                ? sanitize_text_field($attributes['widgetKey'])
                : $this->optionsRepository->getOption('wc_esl_shipping_widget_key');

            if (!$widgetKey) {
                return '';
            }

            // Как и checkout-shipping (см. isAccountUsable() выше), этот блок из tier-1
            // (always-on) не гейтится автоматически при инициализации модулей, поэтому
            // проверяем явно здесь — иначе кнопка/инлайн-виджет продолжают показываться
            // в карточке товара, даже когда ключ невалиден или аккаунт заблокирован/не синхронизирован.
            if (!$this->isAccountUsable()) {
                return '';
            }

            $displayMode = $attributes['displayMode'] ?? 'button';
            $productData = $product->get_data();

            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            } else {
                $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
            }
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Legacy filter name retained for backward compatibility.
            $ip = apply_filters('wc_esl_get_ip', $ip);

            $shippingHelper = new ShippingHelper();
            $offers = [[
                'article'    => $productData['id'],
                'name'       => $productData['name'],
                'count'      => 1,
                'price'      => $productData['price'],
                'weight'     => $productData['weight'],
                'dimensions' => $shippingHelper->dimensionsOption($productData['length']) . '*' .
                                $shippingHelper->dimensionsOption($productData['width']) . '*' .
                                $shippingHelper->dimensionsOption($productData['height']),
            ]];
            $jsonOffers = htmlspecialchars(json_encode($offers));

            $html = '<div class="wc-esl-product-calculator-block" ' .
                    'data-widget-key="' . esc_attr($widgetKey) . '" ' .
                    'data-display-mode="' . esc_attr($displayMode) . '" ' .
                    'data-product-id="' . esc_attr($product->get_id()) . '">';

            if ($displayMode === 'button') {
                // Кнопка + модальный виджет — тот же контракт, что и у шорткода [esl_widget_button].
                $widgetBut = $this->optionsRepository->getOption('wc_esl_shipping_widget_but');
                $buttonLabel = $widgetBut ?: __('Заказать с доставкой', 'eshoplogisticru');

                $html .= '<button type="button" data-esl-widget class="wc-esl-calculator-trigger button button-primary" data-title="' . esc_attr($buttonLabel) . '">' .
                        esc_html($buttonLabel) .
                        '</button>';
                $html .= '<div id="eShopLogisticWidgetModal" ' .
                        'data-lazy-load="true" ' .
                        'data-ip="' . esc_attr($ip) . '" ' .
                        'data-key="' . esc_attr($widgetKey) . '" ' .
                        'data-offers="' . $jsonOffers . '"></div>';

                wp_enqueue_script('wc_esl_app_v2_js', 'https://api.esplc.ru/widgets/modal/app.js', [], WC_ESL_VERSION, true);
            } else {
                // Инлайн-виджет — тот же контракт, что и у вкладки товара esl_product_widget_tab_content.
                $html .= '<div id="eShopLogisticWidgetBlock" ' .
                        'data-lazy-load="true" ' .
                        'data-ip="' . esc_attr($ip) . '" ' .
                        'data-key="' . esc_attr($widgetKey) . '" ' .
                        'data-offers="' . $jsonOffers . '"></div>';

                wp_enqueue_script('wc_esl_app_tab_v2_js', 'https://api.esplc.ru/widgets/block/app.js', [], WC_ESL_VERSION, true);
                wp_enqueue_script('wc_esl_app_tab_js', WC_ESL_PLUGIN_URL . 'assets/js/app_tab.js', [], WC_ESL_VERSION, true);
            }

            $html .= '</div>';

            wp_enqueue_style('wc_esl_style_frame_css', WC_ESL_PLUGIN_URL . 'assets/css/style-frame.css', [], WC_ESL_VERSION);

            return $html;
        }

        return $this->renderBlockPlaceholder('wc-esl-product-calculator-block', __('Product Shipping Calculator', 'eshoplogisticru'), __('Displays on product pages', 'eshoplogisticru'));
    }

    /**
        * Рендерит блок доставки в корзине
     */
    public function renderCartShippingBlock($attributes)
    {
        if (is_cart()) {
            return '<div class="wc-esl-cart-shipping-block" data-block-type="cart-shipping"></div>';
        }

        return $this->renderBlockPlaceholder('wc-esl-cart-shipping-block', __('Cart Shipping (Frame)', 'eshoplogisticru'), __('Displays on cart page', 'eshoplogisticru'));
    }

    /**
        * Рендерит блок формы checkout (для совместимости с блочным checkout)
     */
    public function renderCheckoutFormBlock($attributes)
    {
        if (is_checkout() && !is_wc_endpoint_url('order-received')) {
            return '<div class="wc-esl-checkout-form-block" ' .
                   'data-form-type="' . esc_attr($attributes['formType'] ?? 'full') . '"></div>';
        }

        return $this->renderBlockPlaceholder('wc-esl-checkout-form-block', __('Checkout Form (Legacy)', 'eshoplogisticru'), __('Displays on checkout page', 'eshoplogisticru'));
    }

    /**
     * Единая заглушка для блоков, показываемая в редакторе и вне их целевого контекста
     * (согласовано с фолбэком renderCheckoutShippingBlock).
     */
    private function renderBlockPlaceholder($className, $title, $description)
    {
        return '<div class="' . esc_attr($className) . '" style="padding: 20px; background: #f9f9f9; border: 2px dashed #ccc; border-radius: 4px; margin: 20px 0; text-align: center;">' .
               '<p style="margin: 0; color: #666;">📦 ' . esc_html($title) . '</p>' .
               '<p style="margin: 5px 0 0 0; font-size: 12px; color: #999;">' . esc_html($description) . '</p>' .
               '</div>';
    }

    /**
        * Локализует скрипты блоков на frontend
     */
    public function localizeBlockScripts()
    {
        $addForm = $this->optionsRepository->getOption('wc_esl_shipping_add_form');
        $paymentCalcEnabled = isset($addForm['paymentCalc']) && $addForm['paymentCalc'] === 'true';

        $widgetKey   = $this->optionsRepository->getOption('wc_esl_shipping_widget_key');
        $apiKeyWCart = $this->optionsRepository->getOption('wc_esl_shipping_api_key_wcart');
        if (empty($widgetKey) && !empty($apiKeyWCart)) {
            $widgetKey = $apiKeyWCart;
        }

        wp_localize_script('wc_esl_block_frontend_js', 'wcEslBlockFrontend', [
            'widgetKey' => $widgetKey,
            'pluginUrl' => WC_ESL_PLUGIN_URL,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'isCheckout' => is_checkout(),
            'isCart' => is_cart(),
            'isProduct' => is_product(),
            'nonce' => wp_create_nonce('wc_esl_block_nonce'),
            'paymentCalc' => $paymentCalcEnabled,
        ]);
    }

    /**
     * Подключает ресурсы, специфичные для редактора блоков
     */
    public function enqueueBlockEditorAssets()
    {
        // Подключаем JS редактора с зависимостями WordPress
        wp_enqueue_script(
            'wc-esl-block-editor-js',
            WC_ESL_PLUGIN_URL . 'assets/js/block-editor.js',
            ['wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-block-editor', 'wp-i18n'],
            WC_ESL_VERSION,
            true
        );

        wp_set_script_translations(
            'wc-esl-block-editor-js',
            'eshoplogisticru',
            WC_ESL_PLUGIN_DIR . 'languages'
        );

        // Локализуем скрипт доступными опциями
        wp_localize_script('wc-esl-block-editor-js', 'wcEslBlockEditor', [
            'widgetKey' => $this->optionsRepository->getOption('wc_esl_shipping_widget_key'),
            'pluginUrl' => WC_ESL_PLUGIN_URL,
            'pluginDir' => WC_ESL_PLUGIN_DIR,
            'i18n' => [
                'checkoutShipping' => __('Shipping Calculator (Checkout)', 'eshoplogisticru'),
                'productCalculator' => __('Product Shipping Calculator', 'eshoplogisticru'),
                'cartShipping' => __('Cart Shipping (Frame)', 'eshoplogisticru'),
                'checkoutForm' => __('Checkout Form (Legacy)', 'eshoplogisticru'),
                'displayMode' => __('Display Mode', 'eshoplogisticru'),
                'button' => __('Button', 'eshoplogisticru'),
                'inline' => __('Inline Widget', 'eshoplogisticru'),
                'widgetKey' => __('Widget Key', 'eshoplogisticru'),
                'widgetKeyHelp' => __('Leave empty to use default from settings', 'eshoplogisticru'),
                'alignment' => __('Alignment', 'eshoplogisticru'),
                'left' => __('Left', 'eshoplogisticru'),
                'center' => __('Center', 'eshoplogisticru'),
                'right' => __('Right', 'eshoplogisticru'),
                'settings' => __('Settings', 'eshoplogisticru'),
                'formType' => __('Form Type', 'eshoplogisticru'),
                'full' => __('Full', 'eshoplogisticru'),
                'compact' => __('Compact', 'eshoplogisticru'),
            ],
        ]);
    }

    /**
     * Подключает ресурсы блоков (frontend и editor)
     */
    public function enqueueBlockAssets()
    {
        // Подключаем только frontend-стили блоков
        wp_enqueue_style(
            'wc-esl-block-frontend-styles',
            WC_ESL_PLUGIN_URL . 'assets/css/block-styles.css',
            [],
            WC_ESL_VERSION
        );
    }
}
