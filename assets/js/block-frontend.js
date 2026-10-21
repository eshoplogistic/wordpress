/**
 * eShopLogistic Gutenberg Blocks Frontend-скрипт
 * Обрабатывает инициализацию и функциональность пользовательских блоков
 * Поддерживает как новые блоки Gutenberg, так и legacy checkout на шорткодах
 */

(function() {
    'use strict';

    // Объект конфигурации для front-end
    const wcEslConfig = window.wcEslBlockFrontend || {};

    // Инициализация всех блоков после готовности DOM
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализируем блоки, если они существуют
        if (document.querySelector('.wc-esl-checkout-shipping-block')) {
            initializeCheckoutShippingBlock();
        }
        if (document.querySelector('.wc-esl-product-calculator-block')) {
            initializeProductCalculatorBlock();
        }
        if (document.querySelector('.wc-esl-cart-shipping-block')) {
            initializeCartShippingBlock();
        }
        if (document.querySelector('.wc-esl-checkout-form-block')) {
            initializeCheckoutFormBlock();
        }
        
        // Инициализируем legacy checkout, если блочный checkout отсутствует
        if (!document.querySelector('.wc-esl-checkout-form-block') && 
            document.body.classList.contains('woocommerce-checkout')) {
            initializeLegacyCheckout();
        }
    });

    /**
     * Инициализирует блок доставки на checkout
     */
    function initializeCheckoutShippingBlock() {
        const blocks = document.querySelectorAll('.wc-esl-checkout-shipping-block');
        
        blocks.forEach(function(block) {
            if (block.classList.contains('initialized')) return;
            block.classList.add('initialized');

            // Загружаем скрипт checkout frame-блока, если он еще не загружен
            if (!window.wcEslCheckoutBlock) {
                loadExternalScript(wcEslConfig.pluginUrl + 'assets/js/checkout_frame_block.js?ver=' + encodeURIComponent(wcEslConfig.checkoutFrameBlockVer || ''), function() {
                    if (window.wcEslCheckoutBlock) {
                        const widgetContainer = block.querySelector('#eShopLogisticWidgetCart');
                        if (widgetContainer && window.wcEslCheckoutBlock.initWidget) {
                            window.wcEslCheckoutBlock.initWidget(widgetContainer);
                        }
                        if (window.wcEslCheckoutBlock.handleShippingMethodChange) {
                            window.wcEslCheckoutBlock.handleShippingMethodChange();
                        }
                    }
                });
            } else {
                const widgetContainer = block.querySelector('#eShopLogisticWidgetCart');
                if (widgetContainer && window.wcEslCheckoutBlock.initWidget) {
                    window.wcEslCheckoutBlock.initWidget(widgetContainer);
                }
                if (window.wcEslCheckoutBlock.handleShippingMethodChange) {
                    window.wcEslCheckoutBlock.handleShippingMethodChange();
                }
            }
            
            if (window.jQuery) {
                window.jQuery(document).on('updated_checkout', function() {
                    if (window.wcEslCheckoutBlock && window.wcEslCheckoutBlock.handleShippingMethodChange) {
                        window.wcEslCheckoutBlock.handleShippingMethodChange();
                    }
                });
                
                window.jQuery(document).on('checkout_error', function() {
                });
            }
        });
    }

    function initializeProductCalculatorBlock() {}
    function initializeCartShippingBlock() {}
    function initializeCheckoutFormBlock() {}
    function loadCheckoutFrame(container) {}
    function loadCartFrame(container) {}
    function loadProductWidget(container, widgetKey, productId) {}
    function loadLegacyCheckoutForm(container, formType) {}
    function initializeLegacyCheckout() {}
    function loadExternalScript(url, callback) {
        if (document.querySelector('script[src="' + url + '"]')) {
            if (callback) callback();
            return;
        }

        const script = document.createElement('script');
        script.src = url;
        script.async = true;
        script.type = 'text/javascript';
        
        if (callback) {
            script.onload = callback;
            script.onerror = function() {
                console.warn('Failed to load external script: ' + url);
            };
        }

        document.head.appendChild(script);
    }

    window.openProductCalculator = function(productId, widgetKey) {};
    window.updateCartFrame = function() {};
    window.updateCheckoutFrame = function() {};

})();