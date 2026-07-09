/**
 * eShopLogistic Gutenberg Blocks - скрипт редактора
 * Обрабатывает регистрацию и взаимодействие для всех блоков eShopLogistic
 */

(function() {
    'use strict';

    // Строгая проверка API WordPress
    var wp_blocks = wp?.blocks;
    var wp_element = wp?.element;
    var wp_components = wp?.components;
    var wp_i18n = wp?.i18n;
    var __ = wp_i18n && wp_i18n.__ ? wp_i18n.__ : function(text) { return text; };
    
    if (!wp_blocks || !wp_element) {
        console.error('eShopLogistic: WordPress blocks API not available. wp.blocks or wp.element is missing.');
        return;
    }

    // Предотвращение множественной инициализации
    if (window.wcEslBlockEditorInitialized) {
        console.log('eShopLogistic: Block editor already initialized, skipping...');
        return;
    }

    var el = wp_element.createElement;
    var registerBlockType = wp_blocks.registerBlockType;

    /**
        * Регистрирует один тип блока
        * @param {string} name Имя блока
        * @param {Object} config Конфигурация блока
     */
    function registerEslBlock(name, config) {
        try {
            registerBlockType(name, config);
            console.log('✓ Registered block: ' + name);
            return true;
        } catch (error) {
            console.error('Failed to register block: ' + name, error);
            return false;
        }
    }

    // Обертка для отложенной регистрации до domReady
    if (wp.domReady) {
        wp.domReady(initializeBlocks);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeBlocks);
    } else {
        initializeBlocks();
    }

    function initializeBlocks() {
        window.wcEslBlockEditorInitialized = true;
        
        console.log('eShopLogistic: Initializing block editor...');
        
        var blockCount = 0;
        var ESL_CHECKOUT_BLOCK = 'eshoplogisticru/checkout-shipping';
        var blocks = [
            {
            name: ESL_CHECKOUT_BLOCK,
                title: __('eShopLogistic Shipping Calculator (Checkout)', 'eshoplogisticru'),
                icon: 'location',
                description: __('Display shipping calculator on checkout page', 'eshoplogisticru'),
                keywords: [
                    __('shipping', 'eshoplogisticru'),
                    __('delivery', 'eshoplogisticru'),
                    __('calculator', 'eshoplogisticru'),
                    __('checkout', 'eshoplogisticru'),
                    __('eshoplogistic', 'eshoplogisticru')
                ]
            },
            {
                name: 'eshoplogisticru/product-calculator',
                title: __('eShopLogistic Product Shipping Calculator', 'eshoplogisticru'),
                icon: 'calculator',
                description: __('Display shipping calculator on product pages', 'eshoplogisticru'),
                keywords: [
                    __('shipping', 'eshoplogisticru'),
                    __('delivery', 'eshoplogisticru'),
                    __('product', 'eshoplogisticru'),
                    __('calculator', 'eshoplogisticru'),
                    __('eshoplogistic', 'eshoplogisticru')
                ]
            },
            // {
            //     name: 'eshoplogisticru/cart-shipping',
            //     title: __('eShopLogistic Cart Shipping Widget', 'eshoplogisticru'),
            //     icon: 'cart',
            //     description: __('Display shipping widget on cart page', 'eshoplogisticru'),
            //     keywords: [
            //         __('shipping', 'eshoplogisticru'),
            //         __('cart', 'eshoplogisticru'),
            //         __('delivery', 'eshoplogisticru'),
            //         __('widget', 'eshoplogisticru'),
            //         __('eshoplogistic', 'eshoplogisticru')
            //     ]
            // },
            // {
            //     name: 'eshoplogisticru/checkout-form',
            //     title: __('eShopLogistic Checkout Form', 'eshoplogisticru'),
            //     icon: 'edit-page',
            //     description: __('Display checkout form with shipping options', 'eshoplogisticru'),
            //     keywords: [
            //         __('checkout', 'eshoplogisticru'),
            //         __('form', 'eshoplogisticru'),
            //         __('shipping', 'eshoplogisticru'),
            //         __('order', 'eshoplogisticru'),
            //         __('eshoplogistic', 'eshoplogisticru')
            //     ]
            // }
        ];

        /**
         * Создает компонент редактирования-заглушку с корректной интеграцией в Gutenberg
         */
        function createEditComponent(blockData) {
            return function EditComponent(props) {
                var useBlockProps = wp.blockEditor && wp.blockEditor.useBlockProps;
                var blockProps = useBlockProps ? useBlockProps() : {};
                
                // Убеждаемся, что блок можно выбрать
                blockProps.onClick = function(e) {
                    e.stopPropagation();
                };
                
                return el(
                    'div',
                    blockProps,
                    el('div', {
                        style: {
                            padding: '20px',
                            backgroundColor: '#f0f0f0',
                            border: '2px solid #0073aa',
                            borderRadius: '4px',
                            textAlign: 'center',
                            minHeight: '80px',
                            display: 'flex',
                            flexDirection: 'column',
                            alignItems: 'center',
                            justifyContent: 'center',
                            cursor: 'default'
                        }
                    },
                    el('div', {style: {fontSize: '32px', marginBottom: '8px'}}, getBlockIcon(blockData.icon)),
                    el('div', {style: {fontWeight: '600', color: '#0073aa', marginBottom: '4px'}}, blockData.title),
                    el('div', {style: {color: '#666', fontSize: '12px'}}, blockData.description)
                    )
                );
            };
        }

        /**
         * Возвращает emoji-иконку для блока
         */
        function getBlockIcon(iconName) {
            var icons = {
                'location': '📍',
                'calculator': '🧮',
                'cart': '🛒',
                'edit-page': '📝'
            };
            return icons[iconName] || '📦';
        }

        /**
         * Регистрирует все блоки
         */
        blocks.forEach(function(blockData) {
            var config = {
                title: blockData.title,
                icon: blockData.icon,
                category: 'widgets',
                description: blockData.description,
                keywords: blockData.keywords,
                supports: {
                    customClassName: true,
                    html: false,
                    reusable: true,
                    inserter: true,
                    multiple: true,
                    remove: true,
                    lock: false,
                    align: ['left', 'center', 'right'],
                    anchor: false,
                    color: {
                        background: false,
                        text: false
                    },
                    spacing: {
                        margin: false,
                        padding: false
                    },
                    typography: {
                        fontSize: false,
                        lineHeight: false
                    },
                    border: {
                        radius: false,
                        color: false,
                        width: false,
                        style: false
                    }
                },
                attributes: {
                    alignment: {
                        type: 'string',
                        default: 'left'
                    }
                },
                // Функция редактирования - возвращает интерактивную заглушку
                edit: createEditComponent(blockData),
                // Функция сохранения - серверный рендеринг
                save: function() {
                    return null;
                }
            };

            if (registerEslBlock(blockData.name, config)) {
                blockCount++;
            }
        });

        // Логирование результатов
        console.log('%c✅ eShopLogistic: ' + blockCount + ' blocks registered successfully', 'color: #10a810; font-weight: bold; font-size: 14px;');

        // Разрешаем вставку ESL checkout-блока ВНУТРЬ WooCommerce Checkout блока.
        registerCheckoutInnerBlockSupport(ESL_CHECKOUT_BLOCK);
        
        if (blockCount === blocks.length) {
            console.log('%cAll ' + blockCount + ' blocks are ready for use. They are fully interactive, can be selected, moved, and deleted.', 'color: #0073aa;');
        } else {
            console.warn('%c⚠️ Expected ' + blocks.length + ' blocks but registered only ' + blockCount, 'color: #ff9800;');
        }
    }

    function registerCheckoutInnerBlockSupport(blockName) {
        var attempts = 0;
        var maxAttempts = 20;

        function tryRegister() {
            attempts++;

            var blocksCheckout = window?.wc?.blocksCheckout || window?.wc?.wcBlocksCheckout;
            var registerFilters = blocksCheckout && (
                blocksCheckout.registerCheckoutFilters ||
                blocksCheckout.__experimentalRegisterCheckoutFilters
            );

            if (typeof registerFilters === 'function') {
                registerFilters('eshoplogisticru/checkout-inner-blocks', {
                    additionalCartCheckoutInnerBlockTypes: function(defaultValue) {
                        var list = Array.isArray(defaultValue) ? defaultValue.slice() : [];
                        if (list.indexOf(blockName) === -1) {
                            list.push(blockName);
                        }
                        return list;
                    }
                });

                console.log('eShopLogistic: Registered checkout inner-block support for ' + blockName);
                return;
            }

            if (attempts < maxAttempts) {
                setTimeout(tryRegister, 250);
            }
        }

        tryRegister();
    }
})();
