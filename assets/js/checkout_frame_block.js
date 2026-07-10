/**
 * eShopLogistic Checkout Frame for Gutenberg Blocks
 * Адаптированная версия для работы с WooCommerce Blocks
 */

(function() {
    'use strict';

    // Глобальные переменные
    window.widgetInit = false;
    window.keyDelivery = 'door';
    let cityMain = false;
    let errorCity = 0;
    let eslWidget = null;
    let widgetSdkRequested = false;
    let loadingHideTimer = null;
    let loadingSafetyTimer = null;
    const LOADING_SAFETY_TIMEOUT_MS = 15000;

    /**
     * Запустить инициализацию CDN SDK вручную.
     * app.js из CDN в обычном режиме вешается на DOMContentLoaded,
     * поэтому при поздней загрузке (после открытия модалки) нужен явный init().
     */
    function triggerCdnWidgetInit() {
        if (window.eShopLogistic && typeof window.eShopLogistic.init === 'function') {
            window.eShopLogistic.init();
            return true;
        }

        console.warn('eShopLogistic: window.eShopLogistic.init is unavailable');
        return false;
    }

    let widgetSdkLoaded = false;
    let widgetInitTries = 0;
    let widgetEventsBound = false;
    let widgetInitInProgress = false;
    let lastHandledShippingMethod = null;
    // Базовый хеш автоподставленного сервиса в текущей сессии открытия модалки.
    let hashSelectService = '';
    let userInteractedWithWidget = false;
    let suppressCloseOnAutoSelect = true;
    const MAX_WIDGET_INIT_TRIES = 3;

    // Конфигурация из WordPress  
    const config = window.wcEslBlockFrontend || {};

    /**
     * Инициализация типа доставки по умолчанию
     */
    function initDefaultDelivery() {
        const shippingTerminal = document.getElementById('wc_esl_shipping_terminal');
        
        window.keyDelivery = shippingTerminal?.value ? 'terminal' : 'door';
    }

    /**
     * Проверка, скрыт ли элемент
     */
    function isHidden(el) {
        return window.getComputedStyle(el).display === 'none';
    }

    /**
     * Проверка числового значения
     */
    function isNumeric(value) {
        return /^-{0,1}\d+$/.test(value);
    }

    /**
     * Управление состоянием загрузки
     */
    function setLoadingState(isLoading) {
        const PRELOADER_ID = 'wc-esl-block-preloader';
        let preloader = document.getElementById(PRELOADER_ID);

        if (!preloader) {
            preloader = document.createElement('div');
            preloader.id = PRELOADER_ID;
            preloader.className = 'wc-esl-block-preloader';

            if (config.eslLoaderUrl) {
                const img = document.createElement('img');
                img.className = 'wc-esl-block-preloader__img';
                img.src = config.eslLoaderUrl;
                img.width = 150;
                img.height = 150;
                preloader.appendChild(img);
            } else {
                preloader.innerHTML = '<div class="wc-esl-block-preloader__spinner"></div>';
            }

            preloader.style.display = 'none';
            document.body.appendChild(preloader);
        }

        // Небольшая задержка перед скрытием (а не мгновенное display:none) сглаживает
        // мигание прелоадера, когда подряд идёт несколько быстрых show/hide (разные
        // AJAX-запросы завершаются почти одновременно). Если за это время придёт новый
        // "show" — просто отменяем скрытие, не моргая.
        if (loadingHideTimer) {
            clearTimeout(loadingHideTimer);
            loadingHideTimer = null;
        }

        if (isLoading) {
            preloader.style.display = 'block';

            // Страховка от бесконечного прелоадера: если ни один из сценариев
            // (SDK не загрузился из-за блокировщика, AJAX завис, событие виджета
            // не пришло) не вызовет setLoadingState(false) сам, принудительно
            // прячем прелоадер и показываем ошибку по таймауту.
            if (!loadingSafetyTimer) {
                loadingSafetyTimer = setTimeout(() => {
                    loadingSafetyTimer = null;
                    console.warn('eShopLogistic: preloader safety timeout reached, forcing hide');
                    setLoadingState(false);
                    showError('Не удалось загрузить виджет доставки. Обновите страницу или попробуйте позже.');
                }, LOADING_SAFETY_TIMEOUT_MS);
            }
        } else {
            if (loadingSafetyTimer) {
                clearTimeout(loadingSafetyTimer);
                loadingSafetyTimer = null;
            }

            loadingHideTimer = setTimeout(() => {
                preloader.style.display = 'none';
                loadingHideTimer = null;
            }, 250);
        }
    }

    /**
     * Скрыть подсказку "Укажите город для расчёта доставки".
     */
    function hideCityTips() {
        const tips = document.getElementById('tips-city-container');
        if (tips) {
            tips.style.display = 'none';
        }
    }

    /**
     * Получить хеш/подпись события сервиса.
     * Используем objectHash.sha1 при наличии, иначе безопасный fallback через JSON.
     */
    function getServiceSignature(data) {
        try {
            if (window.objectHash && typeof window.objectHash.sha1 === 'function') {
                return window.objectHash.sha1(data);
            }
            return JSON.stringify(data || {});
        } catch (e) {
            return '';
        }
    }

    /**
     * Получить текущее имя города из checkout-полей (как в legacy).
     */
    function getCurrentCheckoutCityName() {
        try {
            // 1. Кастомный ID поля из настроек (передаётся в конфиге, т.к. в Blocks
            // скрытые input'ы легаси-чекаута #eslShippingCityFields не рендерятся)
            const shippingFieldId = config.shippingCityField || '';
            if (shippingFieldId) {
                const el = document.getElementById(shippingFieldId);
                if (el && el.value) return el.value;
            }

            // 2. Legacy shortcode checkout: id="shipping_city"
            const legacyEl = document.getElementById('shipping_city') || document.getElementById('shipping-city');
            if (legacyEl && legacyEl.value) return legacyEl.value;

            // 3. WooCommerce Blocks: autocomplete="shipping city" или "billing city"
            const blocksEl = document.querySelector('input[autocomplete="shipping city"]')
                          || document.querySelector('input[autocomplete="billing city"]');
            if (blocksEl && blocksEl.value) return blocksEl.value;

            // 4. WooCommerce Blocks: data-field-key или name
            const dataEl = document.querySelector('input[data-field-key="city"], input[name="city"]')
                        || document.getElementById('billing_city')
                        || document.getElementById('billing-city');
            if (dataEl && dataEl.value) return dataEl.value;

            // 5. Данные из скрытого поля widgetCityEsl (если name/city есть без fias)
            const cityInput = document.getElementById('widgetCityEsl');
            if (cityInput && cityInput.value) {
                try {
                    const parsed = JSON.parse(cityInput.value);
                    if (parsed && (parsed.city || parsed.name)) return parsed.city || parsed.name;
                } catch (e) {}
            }

            return '';
        } catch (e) {
            return '';
        }
    }

    /**
     * Получить текущий способ доставки
     */
    function getCurrentShippingMethod() {
        // Classic checkout
        const methods = document.querySelectorAll('input[name^="shipping_method"]:checked');
        if (methods.length > 0) {
            return methods[0].value || methods[0].id || null;
        }

        const hiddenMethods = document.querySelectorAll('input[name^="shipping_method"][type="hidden"]');
        if (hiddenMethods.length > 0) {
            return hiddenMethods[0].value || hiddenMethods[0].id || null;
        }

        // WooCommerce Blocks checkout fallback
        const wcBlocksSelected = document.querySelectorAll(
            '.wc-block-components-shipping-rates-control input[type="radio"]:checked, ' +
            'input[type="radio"][name*="shipping_method"]:checked, ' +
            'input[type="radio"][name*="shipping-rate"]:checked'
        );

        if (wcBlocksSelected.length > 0) {
            const selected = wcBlocksSelected[0];
            const label = selected.closest('label');
            const labelText = label ? label.textContent.trim() : '';

            return selected.value || selected.id || labelText || null;
        }

        return null;
    }

    /**
     * Проверка, является ли метод доставки eShopLogistic
     */
    function isEshopMethod(methodName) {
        if (!methodName) return false;

        const normalized = String(methodName).toLowerCase();
        return normalized.indexOf('wc_esl_') !== -1 ||
            normalized.indexOf('eshoplogistic') !== -1 ||
            normalized.indexOf('yandex') !== -1 ||
            normalized.indexOf('яндекс') !== -1;
    }

    /**
     * Получить тип доставки из названия метода
     */
    function getDeliveryType(methodName) {
        if (!methodName || methodName.indexOf('wc_esl_') === -1) {
            const normalized = String(methodName).toLowerCase();

            if (normalized.indexOf('terminal') !== -1 ||
                normalized.indexOf('pickup') !== -1 ||
                normalized.indexOf('пункт выдачи') !== -1 ||
                normalized.indexOf('самовывоз') !== -1) {
                return 'terminal';
            }

            if (normalized.indexOf('door') !== -1 ||
                normalized.indexOf('courier') !== -1 ||
                normalized.indexOf('доставка') !== -1 ||
                normalized.indexOf('курьер') !== -1) {
                return 'door';
            }

            return null;
        }

        if (methodName.indexOf('_door') !== -1) return 'door';
        if (methodName.indexOf('_terminal') !== -1) return 'terminal';
        if (methodName.indexOf('_mixed') !== -1) {
            // window.keyDelivery по умолчанию равен 'door', пока покупатель ни разу
            // не открывал модалку выбора — это не значит, что он выбрал доставку до
            // двери. Если тариф "mixed"-метода уже выбран (в т.ч. восстановлен из
            // сессии) и его подпись явно указывает на ПВЗ/терминал, доверяем подписи,
            // иначе кнопка выбора ПВЗ ошибочно скрывается для терминального тарифа.
            const selectedRadio = document.querySelector(
                '.wc-block-components-shipping-rates-control input[type="radio"]:checked, ' +
                'input[type="radio"][name*="shipping_method"]:checked, ' +
                'input[type="radio"][name*="shipping-rate"]:checked'
            );
            const label = (selectedRadio?.closest('label')?.textContent || '').toLowerCase();

            if (label.indexOf('пункт выдачи') !== -1 ||
                label.indexOf('терминал') !== -1 ||
                label.indexOf('самовывоз') !== -1) {
                return 'terminal';
            }

            if (label.indexOf('курьер') !== -1 || label.indexOf('до двери') !== -1) {
                return 'door';
            }

            return window.keyDelivery;
        }

        return null;
    }

    /**
     * Управление видимостью полей адреса
     */
    function toggleAddressFields(show) {
        // Настройка "Отключить скрытие полей адреса при выборе ПВЗ" — плагин не должен
        // трогать видимость/доступность этих полей вообще (как и в классическом чекауте).
        if (config.offAddressCheck) {
            return;
        }

        const addressFields = document.querySelectorAll(
            '#shipping_address_1_field, #shipping_address_2_field, ' +
            '.wc-block-components-address-form__address_1, ' +
            '.wc-block-components-address-form__address_2'
        );
        
        addressFields.forEach(field => {
            field.style.display = show ? '' : 'none';

            const controls = field.querySelectorAll('input, select, textarea');
            controls.forEach((control) => {
                if (show) {
                    // Для block checkout при возврате в door поле должно быть редактируемым.
                    control.disabled = false;
                    if (control.dataset.eslWasRequired === '1') {
                        control.required = true;
                    }
                    delete control.dataset.eslWasDisabled;
                    delete control.dataset.eslWasRequired;
                    return;
                }

                control.dataset.eslWasDisabled = control.disabled ? '1' : '0';
                control.dataset.eslWasRequired = control.required ? '1' : '0';
                control.required = false;
                control.disabled = true;
            });
        });
    }

    /**
     * Управление видимостью терминалов
     */
    function toggleTerminals(show, mode = 'shipping') {
        const terminalButton = document.getElementById(`wc-esl-terminals-wrap-button-${mode}`);
        
        if (terminalButton) {
            terminalButton.style.display = show ? 'block' : 'none';
            if (show) {
                terminalButton.classList.add('show');
            } else {
                terminalButton.classList.remove('show');
            }
        }
    }

    /**
     * Поиск города
     */
    function searchCity(query, callback, country = 'RU', typeFilter = false) {
        if (!query || query.length < 2) {
            callback([]);
            return;
        }

        const body = {
            action: 'wc_esl_search_cities',
            target: query,
            currentCountry: country,
            nonce: config.nonce
        };
        if (typeFilter) {
            body.typeFilter = typeFilter;
        }

        fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(body)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                callback(data.data);
            } else {
                callback([]);
            }
        })
        .catch(error => {
            console.error('City search error:', error);
            callback([]);
        });
    }

    function getFieldElement(candidates) {
        for (const id of candidates) {
            const el = document.getElementById(id);
            if (el) {
                return el;
            }
        }
        return null;
    }

    function getCheckoutCityElement() {
        const customId = config.shippingCityField || '';
        const fallbackIds = ['shipping_city', 'shipping-city'];

        const ids = customId ? [customId, ...fallbackIds] : fallbackIds;
        return getFieldElement(ids);
    }

    function getCheckoutBillingCityElement() {
        const customId = config.billingCityField || '';
        const fallbackIds = ['billing_city', 'billing-city'];

        const ids = customId ? [customId, ...fallbackIds] : fallbackIds;
        return getFieldElement(ids);
    }

    function getCheckoutCountryValue() {
        const countryEl = getFieldElement(['shipping_country', 'shipping-country']);

        return (countryEl && countryEl.value) ? countryEl.value : 'RU';
    }

    function setInputValue(input, value) {
        if (!input) {
            return;
        }

        const nextValue = value || '';
        const descriptor = Object.getOwnPropertyDescriptor(window.HTMLInputElement.prototype, 'value');
        const setter = descriptor && descriptor.set;
        if (setter) {
            setter.call(input, nextValue);
        } else {
            input.value = nextValue;
        }

        // Помечаем события как намеренное проставление значения (выбор города).
        // В capture-обработчике этот флаг разрешает событию дойти до WC Blocks,
        // тогда как обычные события набора текста блокируются там же.
        const inputEvent = new Event('input', { bubbles: true });
        inputEvent._eslCitySelection = true;
        input.dispatchEvent(inputEvent);

        const changeEvent = new Event('change', { bubbles: true });
        changeEvent._eslCitySelection = true;
        input.dispatchEvent(changeEvent);
    }

    function clearAddressFieldError() {
        const wrappers = document.querySelectorAll('#shipping_address_1_field, .wc-block-components-address-form__address_1');

        wrappers.forEach((wrapper) => {
            wrapper.classList.remove('has-error');

            const validationError = wrapper.querySelector('.wc-block-components-validation-error');
            if (validationError) {
                validationError.style.display = 'none';
                validationError.setAttribute('hidden', 'hidden');
            }
        });

        const addressEl = getFieldElement(['shipping_address_1', 'shipping-address_1']);

        if (addressEl) {
            addressEl.setAttribute('aria-invalid', 'false');
            addressEl.removeAttribute('aria-errormessage');
            addressEl.removeAttribute('title');
            if (typeof addressEl.setCustomValidity === 'function') {
                addressEl.setCustomValidity('');
            }
        }
    }

    function setCheckoutAddressValues(cityData) {
        const fieldMap = {
            city: ['shipping_city', 'shipping-city'],
            state: ['shipping_state', 'shipping-state'],
            postcode: ['shipping_postcode', 'shipping-postcode']
        };

        const cityEl = getFieldElement(fieldMap.city);
        const stateEl = getFieldElement(fieldMap.state);
        const postcodeEl = getFieldElement(fieldMap.postcode);

        setInputValue(cityEl, cityData.city);
        setInputValue(stateEl, cityData.region);
        setInputValue(postcodeEl, cityData.postcode);
    }

    function setCheckoutBillingAddressValues(cityData) {
        const fieldMap = {
            city: ['billing_city', 'billing-city'],
            state: ['billing_state', 'billing-state'],
            postcode: ['billing_postcode', 'billing-postcode']
        };

        const cityEl = getFieldElement(fieldMap.city);
        const stateEl = getFieldElement(fieldMap.state);
        const postcodeEl = getFieldElement(fieldMap.postcode);

        setInputValue(cityEl, cityData.city);
        setInputValue(stateEl, cityData.region);
        setInputValue(postcodeEl, cityData.postcode);
    }

    function updateWidgetCityData(cityData) {
        const cityInput = document.getElementById('widgetCityEsl');
        if (!cityInput) {
            return;
        }

        let current = {};
        try {
            current = cityInput.value ? JSON.parse(cityInput.value) : {};
        } catch (e) {
            current = {};
        }

        const next = {
            ...current,
            ...(cityData.raw && typeof cityData.raw === 'object' ? cityData.raw : {}),
            name: cityData.city || cityData.name || '',
            city: cityData.city || cityData.name || '',
            region: cityData.region || '',
            fias: cityData.fias || '',
            postcode: cityData.postcode || '',
            postal_code: cityData.postcode || cityData.postal_code || '',
            services: cityData.services || []
        };

        cityInput.value = JSON.stringify(next);
    }

    function toWidgetSettlement(cityData) {
        return {
            ...(cityData.raw && typeof cityData.raw === 'object' ? cityData.raw : {}),
            name: cityData.city || cityData.name || '',
            city: cityData.city || cityData.name || '',
            region: cityData.region || '',
            fias: cityData.fias || '',
            postcode: cityData.postcode || '',
            postal_code: cityData.postcode || cityData.postal_code || '',
            services: cityData.services || []
        };
    }

    function clearCityResultList(mode) {
        const list = document.getElementById(`result_wc_esl_search_city_${mode}`);
        if (list) {
            list.remove();
        }
    }

    function renderCitiesList(items, mode = 'shipping') {
        const listId = `result_wc_esl_search_city_${mode}`;

        const escapeAttr = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        if (!items || !Array.isArray(items) || items.length === 0) {
            return `<ul id="${listId}" class="wc-esl-search-city__list" data-mode="${mode}"><li class="wc-esl-search-city__empty">Ничего не найдено</li></ul>`;
        }

        const htmlItems = items.map((item) => {
            const city = item.name || '';
            const region = item.region || '';
            const postcode = item.postal_code || '';
            const fias = item.fias || '';
            const servicesEncoded = encodeURIComponent(JSON.stringify(item.services || []));
            const payloadEncoded = encodeURIComponent(JSON.stringify(item || {}));

            return `<li class="wc-esl-search-city__item" data-mode="${escapeAttr(mode)}" data-city="${escapeAttr(city)}" data-region="${escapeAttr(region)}" data-postcode="${escapeAttr(postcode)}" data-fias="${escapeAttr(fias)}" data-services="${escapeAttr(servicesEncoded)}" data-payload="${escapeAttr(payloadEncoded)}">${escapeHtml(city)}${region ? `, ${escapeHtml(region)}` : ''}</li>`;
        }).join('');

        return `<ul id="${listId}" class="wc-esl-search-city__list" data-mode="${mode}">${htmlItems}</ul>`;
    }

    /**
     * Рендер результатов поиска для модалки #modal-esl-city, сгруппированных по региону
     * (legacy-парность с renderCitiesModal/renderCitiesModalItem из checkout.js/checkout_frame_v2.js).
     */
    function renderCitiesModal(itemsByRegion, mode = 'shipping') {
        const escapeAttr = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const escapeHtml = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        const entries = itemsByRegion && typeof itemsByRegion === 'object' ? Object.entries(itemsByRegion) : [];

        if (entries.length === 0) {
            return '';
        }

        const listId = `result_wc_esl_search_city_${mode}`;
        let html = `<ul class="wc-esl-search-city-modal__list" id="${listId}" data-mode="${mode}">`;

        entries.forEach(([region, items]) => {
            html += `<div class="wc-esl-search-region-modal__list"><p class="title-region">${escapeHtml(region)}</p>`;
            (Array.isArray(items) ? items : []).forEach((item) => {
                const type = item.type || '';
                const name = item.name || '';
                const itemRegion = item.region || '';
                const postcode = item.postal_code || '';
                const fias = item.fias || '';
                const label = `${type ? type + ' ' : ''}${name}${itemRegion ? ' - ' + itemRegion : ''}`;
                const payloadEncoded = encodeURIComponent(JSON.stringify(item || {}));
                const servicesEncoded = encodeURIComponent(JSON.stringify(item.services || []));

                html += `<li class="wc-esl-search-city-modal__item" data-mode="${escapeAttr(mode)}" data-fias="${escapeAttr(fias)}" data-city="${escapeAttr(name)}" data-region="${escapeAttr(itemRegion)}" data-postcode="${escapeAttr(postcode)}" data-services="${escapeAttr(servicesEncoded)}" data-payload="${escapeAttr(payloadEncoded)}">${escapeHtml(label)}</li>`;
            });
            html += '</div>';
        });

        html += '</ul>';

        return html;
    }

    function appendCityResultList(inputEl, mode, items) {
        clearCityResultList(mode);

        const wrapper = inputEl.closest('.wc-block-components-text-input') || inputEl.parentElement;
        if (!wrapper) {
            return;
        }

        wrapper.insertAdjacentHTML('beforeend', renderCitiesList(items, mode));
    }

    function requestShippingAddressUpdate(cityData) {
        const updateMode = 'shipping';
        const addressEl = getFieldElement(['shipping_address_1', 'shipping-address_1']);

        const body = new URLSearchParams({
            action: 'wc_esl_update_shipping_address',
            fias: cityData.fias || '',
            city: cityData.city || '',
            adress: addressEl ? addressEl.value : '',
            region: cityData.region || '',
            postcode: cityData.postcode || '',
            mode: updateMode,
            nonce: config.shippingNonce || config.nonce || ''
        });

        const services = cityData.services;
        if (Array.isArray(services)) {
            services.forEach((service) => {
                body.append('services[]', String(service));
            });
        } else if (services && typeof services === 'object') {
            Object.keys(services).forEach((key) => {
                body.append(`services[${key}]`, String(services[key]));
            });
        }

        return fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body
        }).then((response) => response.json())
          .then((data) => {
              return data;
          });
    }

    function requestBillingAddressUpdate(cityData) {
        const addressEl = getFieldElement(['billing_address_1', 'billing-address_1']);

        const body = new URLSearchParams({
            action: 'wc_esl_update_shipping_address',
            fias: cityData.fias || '',
            city: cityData.city || '',
            adress: addressEl ? addressEl.value : '',
            region: cityData.region || '',
            postcode: cityData.postcode || '',
            mode: 'billing',
            nonce: config.shippingNonce || config.nonce || ''
        });

        const services = cityData.services;
        if (Array.isArray(services)) {
            services.forEach((service) => {
                body.append('services[]', String(service));
            });
        } else if (services && typeof services === 'object') {
            Object.keys(services).forEach((key) => {
                body.append(`services[${key}]`, String(services[key]));
            });
        }

        return fetch(config.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body
        }).then((response) => response.json());
    }

    function setupAddressSelectionForBlocks() {
        // Настройка "Изменить способ выбора города": выключена — обычное поле WC Blocks
        // с выпадающим списком результатов поиска прямо под ним (эта функция). Включена —
        // вместо этого открывается отдельная модалка (setupCityModalForBlocks), поэтому
        // здесь выходим, чтобы не показывать оба варианта поиска одновременно.
        if (config.citySelectModal) {
            return;
        }

        const timers = { shipping: null, billing: null };
        let citySelectionInProgress = false;
        let suppressAutocompleteUntil = 0;

        const runSearch = (mode) => {
            if (Date.now() < suppressAutocompleteUntil) {
                return;
            }

            const inputEl = mode === 'billing' ? getCheckoutBillingCityElement() : getCheckoutCityElement();
            if (!inputEl) {
                return;
            }

            const query = (inputEl.value || '').trim();
            if (query.length < 3) {
                clearCityResultList(mode);
                return;
            }

            const country = getCheckoutCountryValue();
            searchCity(query, (items) => appendCityResultList(inputEl, mode, items), country);
        };

        document.addEventListener('input', (event) => {
            const target = event.target;
            if (!target || !target.id) {
                return;
            }

            const shippingCity = getCheckoutCityElement();
            if (shippingCity && target.id === shippingCity.id) {
                // Блокируем WC Blocks от пересчёта корзины при наборе каждой буквы.
                // Capture-фаза срабатывает до React-делегирования (bubble от React root).
                // События из setInputValue (выбор города) помечены _eslCitySelection
                // и пропускаются, чтобы WC Blocks обновился с финальным значением.
                if (!event._eslCitySelection) {
                    event.stopPropagation();
                }
                if (Date.now() < suppressAutocompleteUntil) {
                    return;
                }
                if (timers.shipping) clearTimeout(timers.shipping);
                timers.shipping = setTimeout(() => runSearch('shipping'), 350);
                return;
            }

            const billingCity = getCheckoutBillingCityElement();
            if (billingCity && target.id === billingCity.id) {
                if (!event._eslCitySelection) {
                    event.stopPropagation();
                }
                if (Date.now() < suppressAutocompleteUntil) {
                    return;
                }
                if (timers.billing) clearTimeout(timers.billing);
                timers.billing = setTimeout(() => runSearch('billing'), 350);
            }
        }, true);

        const handleCitySelection = (event) => {
            const item = event.target && event.target.closest ? event.target.closest('.wc-esl-search-city__item') : null;
            if (!item) {
                if (!event.target.closest || !event.target.closest('.wc-esl-search-city__list')) {
                    clearCityResultList('shipping');
                    clearCityResultList('billing');
                }
                return;
            }

            event.preventDefault();
            if (citySelectionInProgress) {
                return;
            }
            citySelectionInProgress = true;

            const selectionMode = item.getAttribute('data-mode') || 'shipping';

            const cityData = {
                city: item.getAttribute('data-city') || '',
                region: item.getAttribute('data-region') || '',
                postcode: item.getAttribute('data-postcode') || '',
                fias: item.getAttribute('data-fias') || '',
                services: [],
                raw: null
            };

            try {
                const rawServices = item.getAttribute('data-services') || '%5B%5D';
                cityData.services = JSON.parse(decodeURIComponent(rawServices));
            } catch (e) {
                cityData.services = [];
            }

            try {
                const rawPayload = item.getAttribute('data-payload') || '%7B%7D';
                cityData.raw = JSON.parse(decodeURIComponent(rawPayload));
            } catch (e) {
                cityData.raw = null;
            }

            if (selectionMode === 'billing') {
                setCheckoutBillingAddressValues(cityData);
            } else {
                setCheckoutAddressValues(cityData);
            }
            updateWidgetCityData(cityData);
            clearCityResultList(selectionMode);
            hideCityTips();
            suppressAutocompleteUntil = Date.now() + 1500;

            // Сбрасываем кешированные терминалы немедленно, чтобы следующее открытие
            // модалки не показало пункты выдачи старого города.
            const terminalsInput = document.getElementById('wcEslTerminals');
            if (terminalsInput) {
                terminalsInput.value = '[]';
            }

            setLoadingState(true);

            const updateRequest = selectionMode === 'billing'
                ? requestBillingAddressUpdate(cityData)
                : requestShippingAddressUpdate(cityData);

            updateRequest
                .then((response) => {
                    if (!response || response.success !== true) {
                        throw new Error('updateShippingAddress failed');
                    }

                    hideCityTips();

                    const runAfterCheckoutRefresh = () => {
                        const widgetRoot = document.getElementById('eShopLogisticWidgetCart');
                        if (widgetRoot) {
                            widgetRoot.dataset.paramsLoaded = '';
                            const settlement = toWidgetSettlement(cityData);
                            sendWidgetParams(widgetRoot, settlement);
                        }
                    };

                    if (window.jQuery) {
                        window.jQuery('body').one('updated_checkout', function() {
                            runAfterCheckoutRefresh();
                        });
                        window.jQuery('body').trigger('update_esl_city');
                        window.jQuery('body').trigger('update_checkout');
                    } else {
                        setTimeout(runAfterCheckoutRefresh, 700);
                    }

                    refreshCheckoutAfterShippingUpdate();

                    setTimeout(runAfterCheckoutRefresh, 900);

                    // Уведомляем yandex-map.js о смене города — если модалка открыта,
                    // она должна перезагрузить терминалы для нового города.
                    document.dispatchEvent(new CustomEvent('wc-esl-city-changed', { detail: cityData }));
                })
                .catch((error) => {
                    console.error('eShopLogistic: failed to update shipping address', error);
                })
                .finally(() => {
                    setLoadingState(false);
                    citySelectionInProgress = false;
                    suppressAutocompleteUntil = Date.now() + 700;
                });
        };

        // Use mousedown/touchstart to capture selection before input blur rerenders list.
        document.addEventListener('mousedown', handleCitySelection, true);
        document.addEventListener('touchstart', handleCitySelection, true);
        document.addEventListener('click', handleCitySelection, true);
    }

    /**
     * Настройка "Изменить способ выбора города" (включена): вместо текстового поля
     * WC Blocks открывается модалка #modal-esl-city с поиском (legacy-парность с
     * inputFocusCity/inputStartCityModal из checkout_frame_v2.js). Само поле остаётся
     * в DOM (нужно checkout-стору), но визуально перекрывается кнопкой — тот же приём,
     * что и в классическом чекауте (.esl-city-modal-active + .esl_city_button).
     */
    function setupCityModalForBlocks() {
        if (!config.citySelectModal) {
            return;
        }

        const modal = document.getElementById('modal-esl-city');
        const searchInput = document.getElementById('esl_modal-search');
        const resultContainer = document.getElementById('esl_result-search');

        if (!modal || !searchInput || !resultContainer) {
            return;
        }

        // Move modal to document.body to escape WooCommerce Blocks stacking contexts
        // that would otherwise render above position:fixed elements (тот же приём,
        // что и в initModal() для #modal-esl-frame чуть ниже по файлу).
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        // init() и initCheckoutShippingBlock() оба могут вызвать эту функцию на одной
        // загрузке страницы (см. существующий двойной вызов setupAddressSelectionForBlocks
        // выше) — без этого флага обработчики поиска/выбора навешивались бы дважды.
        if (modal.dataset.eslModalBound) {
            return;
        }
        modal.dataset.eslModalBound = '1';

        let citySelectionInProgress = false;
        let searchTimer = null;

        const closeModal = () => {
            modal.style.display = 'none';
        };

        const openModal = (mode) => {
            searchInput.setAttribute('data-mode', mode);
            searchInput.value = '';
            resultContainer.innerHTML = '';
            modal.style.display = 'block';
            searchInput.focus();
        };

        const attachOverlayButton = (inputEl, mode) => {
            if (!inputEl || inputEl.dataset.eslCityModalBound) {
                return;
            }
            inputEl.dataset.eslCityModalBound = '1';

            const wrapper = inputEl.closest('.wc-block-components-text-input') || inputEl.parentElement;
            if (!wrapper) {
                return;
            }
            wrapper.classList.add('esl-city-modal-active');

            // В отличие от classic (где лейбл — отдельный элемент снаружи враппера
            // инпута), в блочной вёрстке лейбл "плавает" внутри того же враппера,
            // что и сам input. Перекрывать весь враппер большой кнопкой (как в
            // classic) нельзя — задевает лейбл, а точную геометрию input JS-ом не
            // подгонишь надёжно (в момент навешивания поле может быть ещё не
            // выложено браузером, offsetWidth/Height будут 0 — кнопка окажется
            // невидимой и некликабельной). Поэтому сам input остаётся видимым
            // (значение показывает он сам), просто становится readOnly, а открытие
            // модалки вешается прямо на его клик — плюс маленькая иконка сбоку
            // как визуальная подсказка.
            inputEl.readOnly = true;

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'esl_city_button';
            button.setAttribute('data-mode', mode);
            button.setAttribute('aria-label', 'Выбрать населённый пункт');
            button.innerHTML =
                '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">' +
                '<path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L8 2.207l6.646 6.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5Z"/>' +
                '<path d="m8 3.293 6 6V13.5a1.5 1.5 0 0 1-1.5 1.5h-9A1.5 1.5 0 0 1 2 13.5V9.293l6-6Z"/>' +
                '</svg>';

            wrapper.appendChild(button);

            const triggerModal = (event) => {
                event.preventDefault();
                openModal(mode);
            };

            // mousedown, а не click — иначе readOnly-поле успевает получить фокус
            // и мигнуть кареткой перед открытием модалки.
            inputEl.addEventListener('mousedown', triggerModal);
            button.addEventListener('click', triggerModal);
        };

        attachOverlayButton(getCheckoutBillingCityElement(), 'billing');
        attachOverlayButton(getCheckoutCityElement(), 'shipping');

        const closeButton = modal.querySelector('.close_modal_window');
        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }
        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        searchInput.addEventListener('keyup', () => {
            const value = searchInput.value.trim();
            const mode = searchInput.getAttribute('data-mode') || 'shipping';

            if (searchTimer) {
                clearTimeout(searchTimer);
            }

            if (value.length < 2) {
                resultContainer.innerHTML = '';
                return;
            }

            searchTimer = setTimeout(() => {
                const country = getCheckoutCountryValue();
                searchCity(value, (itemsByRegion) => {
                    resultContainer.innerHTML = renderCitiesModal(itemsByRegion, mode) ||
                        '<button type="button" id="esl_modal_button-search">Выбрать данный населённый пункт</button>';
                }, country, 'region');
            }, 300);
        });

        resultContainer.addEventListener('click', (event) => {
            const fallbackButton = event.target.closest('#esl_modal_button-search');
            if (fallbackButton) {
                const mode = searchInput.getAttribute('data-mode') || 'shipping';
                const cityData = { city: searchInput.value.trim(), region: '', postcode: '', fias: '', services: [], raw: null };

                if (mode === 'billing') {
                    setCheckoutBillingAddressValues(cityData);
                } else {
                    setCheckoutAddressValues(cityData);
                }
                hideCityTips();
                closeModal();
                return;
            }

            const item = event.target.closest('.wc-esl-search-city-modal__item');
            if (!item || citySelectionInProgress) {
                return;
            }

            citySelectionInProgress = true;

            const mode = item.getAttribute('data-mode') || searchInput.getAttribute('data-mode') || 'shipping';
            const cityData = {
                city: item.getAttribute('data-city') || '',
                region: item.getAttribute('data-region') || '',
                postcode: item.getAttribute('data-postcode') || '',
                fias: item.getAttribute('data-fias') || '',
                services: [],
                raw: null
            };

            try {
                cityData.services = JSON.parse(decodeURIComponent(item.getAttribute('data-services') || '%5B%5D'));
            } catch (e) {
                cityData.services = [];
            }
            try {
                cityData.raw = JSON.parse(decodeURIComponent(item.getAttribute('data-payload') || '%7B%7D'));
            } catch (e) {
                cityData.raw = null;
            }

            if (mode === 'billing') {
                setCheckoutBillingAddressValues(cityData);
            } else {
                setCheckoutAddressValues(cityData);
            }
            updateWidgetCityData(cityData);
            hideCityTips();
            closeModal();

            const terminalsInput = document.getElementById('wcEslTerminals');
            if (terminalsInput) {
                terminalsInput.value = '[]';
            }

            setLoadingState(true);

            const updateRequest = mode === 'billing'
                ? requestBillingAddressUpdate(cityData)
                : requestShippingAddressUpdate(cityData);

            updateRequest
                .then((response) => {
                    if (!response || response.success !== true) {
                        throw new Error('updateShippingAddress failed');
                    }

                    refreshCheckoutAfterShippingUpdate();
                    document.dispatchEvent(new CustomEvent('wc-esl-city-changed', { detail: cityData }));
                })
                .catch((error) => {
                    console.error('eShopLogistic: failed to update shipping address', error);
                })
                .finally(() => {
                    setLoadingState(false);
                    citySelectionInProgress = false;
                });
        });
    }

    /**
     * Сохранить адрес пункта выдачи в сессию (как в legacy setTerminal).
     */
    function setTerminalAddress(terminalAddress, terminalCode) {
        return fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'wc_esl_set_terminal_address',
                terminal: terminalAddress,
                terminal_code: terminalCode || '',
                nonce: config.shippingNonce || config.nonce || ''
            })
        }).then(r => r.json());
    }

    /**
     * Принудительно обновить данные checkout для Blocks и legacy.
     */
    function refreshCheckoutAfterShippingUpdate() {
        // Legacy shortcode checkout fallback.
        if (window.jQuery) {
            window.jQuery('body').trigger('update_checkout');
        }

        // WooCommerce Blocks: принудительно перезапросить корзину у Store API,
        // чтобы PHP пересчитал ставки доставки с актуальными данными ESL-сессии.
        try {
            if (window.wp && window.wp.data && typeof window.wp.data.dispatch === 'function') {
                var cartDispatch = window.wp.data.dispatch('wc/store/cart');
                if (!cartDispatch) return;

                // Метод 1: updateCustomerData — надёжно в любой версии WC Blocks.
                // Посылает PATCH /wc/store/v1/cart/update-customer, ответ содержит
                // пересчитанные ставки доставки (включая ESL с обновлённой сессией).
                if (typeof cartDispatch.updateCustomerData === 'function') {
                    var cartSelect = window.wp.data.select('wc/store/cart');
                    var shippingAddress = {};
                    if (cartSelect && typeof cartSelect.getCustomerData === 'function') {
                        var customerData = cartSelect.getCustomerData();
                        if (customerData && customerData.shipping_address) {
                            shippingAddress = customerData.shipping_address;
                        }
                    }
                    cartDispatch.updateCustomerData({ shipping_address: shippingAddress });
                    return;
                }

                // Метод 2: invalidateResolutionForStore (старые версии WC Blocks).
                if (typeof cartDispatch.invalidateResolutionForStore === 'function') {
                    cartDispatch.invalidateResolutionForStore();
                    var checkoutDispatch = window.wp.data.dispatch('wc/store/checkout');
                    if (checkoutDispatch && typeof checkoutDispatch.invalidateResolutionForStore === 'function') {
                        checkoutDispatch.invalidateResolutionForStore();
                    }
                }
            }
        } catch (e) {
            console.warn('eShopLogistic: Blocks store refresh failed', e);
        }
    }

    /**
     * Отправка данных о доставке на сервер.
     */
    function updateShippingData(action, cityData) {
        return fetch(config.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'wc_esl_update_shipping',
                data: action,
                city: cityData ? cityData.name : '',
                checkout_context: 'blocks',
                nonce: config.shippingNonce || config.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('eShopLogistic: Shipping updated successfully', data);
            }
            
            refreshCheckoutAfterShippingUpdate();
            
            const event = new CustomEvent('wc-esl-shipping-updated', {
                detail: { action, cityData, response: data }
            });
            document.dispatchEvent(event);
            
            return data;
        })
        .catch((error) => {
            console.error('eShopLogistic: updateShippingData request failed', error);

            // Даже при ошибке запроса пробуем синхронизировать checkout.
            refreshCheckoutAfterShippingUpdate();

            const event = new CustomEvent('wc-esl-shipping-updated', {
                detail: { action, cityData, error: true }
            });
            document.dispatchEvent(event);

            return null;
        });
    }

    /**
     * Перезагрузка виджета для видимого контейнера
     */
    function reinitWidget(container) {
        if (!container) {
            console.warn('eShopLogistic: Cannot reinit, container is null');
            return;
        }

        
        // Удаляем старый скрипт
        const oldScript = document.querySelector('script[data-esl-widget-sdk="cart"]');
        if (oldScript) {
            oldScript.remove();
        }

        // Сбрасываем флаги
        widgetSdkLoaded = false;
        widgetSdkRequested = false;
        window.widgetInit = false;
        eslWidget = null;
        widgetEventsBound = false;

        // Загружаем SDK заново
        const script = document.createElement('script');
        script.src = 'https://api.esplc.ru/widgets/cart/app.js';
        script.dataset.eslWidgetSdk = 'cart';

            script.onload = () => {
                widgetSdkLoaded = true;
                script.dataset.loaded = '1';
                
                // Даем больше времени на автомонтирование виджета (SDK + Vue mount + init)
                setTimeout(() => {
                    
                    // Устанавливаем флаги и события
                    eslWidget = container;
                    window.widgetInit = true;
                    setupWidgetEvents(container);
                    
                    // Ждем и отправляем параметры
                    setTimeout(() => {
                        if (!container.dataset.paramsLoaded) {
                            sendWidgetParams(container);
                        }
                    }, 1000);
                }, 2000);
            };

        script.onerror = () => {
            console.error('❌ eShopLogistic: Failed to load widget SDK');
            showError('Не удалось загрузить виджет доставки. Обновите страницу или попробуйте позже.');
        };

        document.head.appendChild(script);
    }

    /**
     * Инициализация виджета eShopLogistic
     */
    function initWidget(container) {
        if (widgetInitInProgress) {
            return;
        }

        if (window.widgetInit || eslWidget) {
            return;
        }

        widgetInitInProgress = true;

        widgetInitTries += 1;
        if (widgetInitTries > MAX_WIDGET_INIT_TRIES) {
            console.warn('eShopLogistic: initWidget stopped to avoid infinite loop');
            widgetInitInProgress = false;
            return;
        }

        if (!config.widgetKey) {
            console.error('eShopLogistic: Widget key is missing in config');
            widgetInitInProgress = false;
            return;
        }
        
        if (!config.checkoutFrameEnabled) {
            console.warn('eShopLogistic: Checkout frame is disabled in settings');
            widgetInitInProgress = false;
            return;
        }

        setLoadingState(true);

        // Проверяем, виден ли контейнер
        const isVisible = container && container.offsetParent !== null;
        

        // Загрузка внешнего скрипта виджета.
        if (!widgetSdkLoaded) {
            if (widgetSdkRequested) {
                widgetInitInProgress = false;
                return;
            }

            const existingScript = document.querySelector('script[data-esl-widget-sdk="cart"]');

            if (existingScript && existingScript.dataset.loaded === '1') {
                widgetSdkLoaded = true;
            }

            const script = existingScript || document.createElement('script');

            if (!existingScript) {
                script.src = 'https://api.esplc.ru/widgets/cart/app.js';
                script.dataset.eslWidgetSdk = 'cart';
            }

            widgetSdkRequested = true;

            script.onload = () => {
                widgetSdkRequested = false;
                widgetSdkLoaded = true;
                script.dataset.loaded = '1';
                
                // После загрузки SDK пробуем инициализировать виджет
                setTimeout(() => {
                    widgetInitInProgress = false;
                    initWidget(container);
                }, 100);
            };

            script.onerror = () => {
                widgetSdkRequested = false;
                console.error('eShopLogistic: failed to load widget SDK');
                showError('Не удалось загрузить виджет доставки. Обновите страницу или попробуйте позже.');
            };

            if (!existingScript) {
                document.head.appendChild(script);
            }

            widgetInitInProgress = false;
            return;
        }

        // SDK загружен
        if (widgetSdkLoaded) {

            // Для block checkout повторно запускаем CDN init, так как первичный
            // DOMContentLoaded-хук в SDK мог уже отработать до открытия модалки.
            triggerCdnWidgetInit();
            
            eslWidget = container;
            window.widgetInit = true;
            setupWidgetEvents(container);
            
            // Принудительно диспатчим событие onLoadApp через 1 секунду
            setTimeout(() => {
                if (!container.dataset.paramsLoaded) {
                    const event = new CustomEvent('eShopLogisticWidgetCart:onLoadApp', {
                        bubbles: true
                    });
                    container.dispatchEvent(event);
                }
            }, 1000);
        }

        widgetInitInProgress = false;
    }

    /**
     * Получить данные виджета из DOM
     */
    function getWidgetData() {
        try {
            const offersEl = document.getElementById('widgetOffersEsl');
            const cityEl = document.getElementById('widgetCityEsl');
            const paymentEl = document.getElementById('widgetPaymentEsl');

            const offers = offersEl ? offersEl.value : '';
            let city = {};
            try {
                const cityValue = cityEl ? cityEl.value : '{}';
                city = JSON.parse(cityValue || '{}');
            } catch (e) {
                console.error('Failed to parse city data:', e);
            }
            const payment = paymentEl ? paymentEl.value : '';

            return { offers, city, payment };
        } catch (e) {
            console.error('Failed to get widget data:', e);
            return { offers: '', city: {}, payment: '' };
        }
    }

    /**
     * Получить город для updateShipping как в legacy: приоритет widgetCityEsl.city.
     */
    function getLegacyShippingCityName(widgetData) {
        if (widgetData && widgetData.city) {
            if (widgetData.city.city) {
                return widgetData.city.city;
            }

            if (widgetData.city.name) {
                return widgetData.city.name;
            }
        }

        return getCurrentCheckoutCityName() || '';
    }

    /**
     * Отправить параметры виджету
     */
    function sendWidgetParams(root, settlementOverride = null) {
        if (!root) {
            console.warn('eShopLogistic: Cannot send params, root is null');
            return;
        }
        
        
        try {
            const data = getWidgetData();
            
            if (!data.offers) {
                console.warn('eShopLogistic: missing offers data');
                return;
            }
            
            if (!data.city || !data.city.fias) {
                // Попытка авто-поиска города по значению поля checkout city
                const cityName = getCurrentCheckoutCityName();
                if (cityName && cityName.length >= 2) {
                    searchCity(cityName, function(items) {
                        if (!items || items.length === 0) {
                            console.warn('eShopLogistic: missing city data - widget cannot initialize without city');
                            return;
                        }
                        const best = items[0];
                        const cityData = {
                            city: best.city || best.name || cityName,
                            region: best.region || '',
                            postcode: best.postcode || '',
                            fias: best.fias || '',
                            services: best.services || [],
                            raw: best
                        };
                        if (!cityData.fias) {
                            console.warn('eShopLogistic: missing city data - widget cannot initialize without city');
                            return;
                        }
                        updateWidgetCityData(cityData);
                        sendWidgetParams(root, settlementOverride || toWidgetSettlement(cityData));
                    });
                } else {
                    console.warn('eShopLogistic: missing city data - widget cannot initialize without city');
                }
                return;
            }

            const paymentId = getCurrentPaymentMethodId();
            const widgetPayment = mapPaymentToWidget(paymentId);

            const params = {
                offers: data.offers,
                payment: widgetPayment
            };

            const settlement = settlementOverride || toWidgetSettlement(data.city);

            root.dispatchEvent(new CustomEvent('eShopLogisticWidgetCart:updateParamsRequest', {
                detail: {
                    settlement,
                    requestParams: params
                }
            }));
            
            root.dataset.paramsLoaded = 'true';
        } catch (e) {
            console.error('Failed to send widget params:', e);
        }
    }

    /**
     * Get current payment method ID from legacy and Blocks checkout UIs.
     */
    function getCurrentPaymentMethodId() {
        const selectors = [
            'input[name="payment_method"]:checked',
            '.wc-block-components-payment-methods input[type="radio"]:checked',
            '.wc-block-checkout__payment-method input[type="radio"]:checked',
            'input[name*="payment-method"]:checked',
            'input[id*="payment-method"]:checked',
            'input[id*="wc-payment-method"]:checked',
            'input[id^="radio-control-"]:checked'
        ];

        for (const selector of selectors) {
            const input = document.querySelector(selector);
            if (!input) {
                continue;
            }

            const value = input.value || '';
            if (value) {
                return value;
            }

            if (input.id) {
                return input.id;
            }
        }

        return 'card';
    }

    /**
     * Map checkout payment method id to widget payment code (legacy-compatible).
     */
    function mapPaymentToWidget(paymentId) {
        const paymentInput = document.getElementById('widgetPaymentEsl');
        if (!paymentInput || !paymentInput.value) {
            return paymentId || 'card';
        }

        try {
            const paymentMap = JSON.parse(paymentInput.value);
            if (!paymentMap || typeof paymentMap !== 'object') {
                return paymentId || 'card';
            }

            const currentId = String(paymentId || '');
            for (const [key, mappedValue] of Object.entries(paymentMap)) {
                if (currentId && key.indexOf(currentId) !== -1) {
                    return mappedValue;
                }
            }
        } catch (e) {
            console.warn('eShopLogistic: failed to parse payment map', e);
        }

        return paymentId || 'card';
    }

    /**
     * Check whether payment-based widget recalculation is enabled.
     */
    function isPaymentCalcEnabled() {
        if (config && typeof config.paymentCalc !== 'undefined') {
            return config.paymentCalc === true || config.paymentCalc === 'true' || config.paymentCalc === 1 || config.paymentCalc === '1';
        }

        const paymentCalcInput = document.getElementById('paymentCalc');
        return Boolean(paymentCalcInput);
    }

    /**
     * Собрать payload как в legacy confirm() для сохранения в сессию.
     */
    function buildLegacyShippingFrameData(deliveryData) {
        const eslData = {
            price: 0,
            time: '',
            name: deliveryData?.service?.name || '',
            key: deliveryData?.service?.code || '',
            mode: deliveryData?.typeDelivery || '',
            address: '',
            terminalAddress: '',
            terminalCode: '',
            comment: '',
            deliveryMethods: '',
            selectPvz: ''
        };

        const terminalInput = document.getElementById('terminalEsl');
        if (terminalInput && terminalInput.value) {
            eslData.selectPvz = terminalInput.value;
        }

        if (deliveryData?.service?.comment) {
            eslData.comment = deliveryData.service.comment;
        }

        if (deliveryData?.deliveryMethods) {
            eslData.deliveryMethods = deliveryData.deliveryMethods;
        }

        const responseData = deliveryData?.service?.responseData?.[deliveryData?.typeDelivery];
        if (responseData) {
            if (responseData.price !== undefined) {
                eslData.price = responseData.price;
            }

            if (responseData.time && responseData.time.value !== undefined && responseData.time.unit !== undefined) {
                eslData.time = `${responseData.time.value} ${responseData.time.unit}`;
            }

            if (responseData.comment) {
                eslData.comment += (eslData.comment ? '<br>' : '') + responseData.comment;
            }
        }

        if (deliveryData?.terminal && typeof deliveryData.terminal === 'object') {
            eslData.terminalCode = deliveryData.terminal.code || '';
            eslData.terminalAddress = deliveryData.terminal.address || '';
            eslData.address = `${deliveryData.terminal.code || ''} ${deliveryData.terminal.address || ''}`.trim();
        }

        return eslData;
    }

    /**
     * Настройка обработчиков событий виджета
     */
    function setupWidgetEvents(container) {
        if (widgetEventsBound) {
            return;
        }

        const root = container || document.getElementById('eShopLogisticWidgetCart');
        if (!root) {
            console.warn('eShopLogistic: Cannot setup events, root not found');
            return;
        }

        widgetEventsBound = true;

        // Фиксируем только реальное пользовательское взаимодействие с виджетом.
        root.addEventListener('pointerdown', () => {
            userInteractedWithWidget = true;
        }, true);

        root.addEventListener('eShopLogisticWidgetCart:onReady', () => {
            setLoadingState(false);
        });

        root.addEventListener('eShopLogisticWidgetCart:onLoadApp', () => {
            setLoadingState(false);
            sendWidgetParams(root);

            // Пока идет автозагрузка/автовыбор, модалку не закрываем.
            suppressCloseOnAutoSelect = true;
        });

        // Дополнительная страховка - если виджет уже инициализирован, попробуем отправить параметры
        if (window.widgetInit && !root.dataset.paramsLoaded) {
            sendWidgetParams(root);
        }

        // Ещё одна страховка с повтором. root.dataset.paramsLoaded фиксирует только
        // факт отправки updateParamsRequest, а не то, что сам SDK был готов её
        // полноценно обработать: на первой загрузке страницы SDK-виджет ещё
        // подгружает свой собственный каталог служб (асинхронно, через свой API) —
        // если наш updateParamsRequest долетает до этого момента, SDK обсчитывает
        // только то немногое, что уже успело подгрузиться (обычно 1 запасная
        // служба, Dostavista), и НЕ пересчитывает список повторно сам по себе.
        // Обычный клик по кнопке "Выбрать способ доставки" срабатывает через
        // несколько секунд после загрузки — этого достаточно, чтобы каталог SDK
        // успел подгрузиться, и повторный (тот же самый) вызов sendWidgetParams
        // внутри клика получает уже полный список. Поэтому здесь повторяем
        // отправку безусловно (а не только пока paramsLoaded пуст) в течение
        // нескольких секунд после монтирования — чтобы хотя бы одна попытка
        // пришлась на момент, когда каталог SDK уже готов, без ожидания клика.
        let paramsRetryAttempts = 0;
        const paramsRetryInterval = setInterval(() => {
            if (window.widgetInit) {
                sendWidgetParams(root);
            }

            paramsRetryAttempts += 1;
            if (paramsRetryAttempts >= 6) {
                clearInterval(paramsRetryInterval);
            }
        }, 1000);

        function handleServiceChange(deliveryData) {
            const hasTerminalSelection = Boolean(
                deliveryData?.terminal &&
                typeof deliveryData.terminal === 'object' &&
                (deliveryData.terminal.code || deliveryData.terminal.address)
            );

            const shippingTerminal = document.getElementById('wc_esl_shipping_terminal');
            const shippingTerminalField = document.getElementById('wc_esl_shipping_terminal_field');
            const doorButton = document.getElementById('buttonModalDoor');

            if (hasTerminalSelection) {
                const terminalAddress = deliveryData.terminal.address || '';
                if (shippingTerminal) shippingTerminal.value = terminalAddress;
                if (shippingTerminalField) shippingTerminalField.style.display = '';
            } else {
                // Legacy flow: для door очищаем ранее выбранный terminal.
                if (shippingTerminal) shippingTerminal.value = '';
                if (shippingTerminalField) shippingTerminalField.style.display = 'none';
            }

            if (deliveryData?.typeDelivery) {
                window.keyDelivery = deliveryData.typeDelivery;

                // Legacy flow: кнопка door доступна только для door-режима.
                if (doorButton) {
                    doorButton.style.display = window.keyDelivery === 'door' ? '' : 'none';
                }
            }

            // Для Blocks переключаем состояние адресных полей сразу после выбора
            // сервиса в виджете, даже если shipping method формально не изменился.
            const nextDeliveryType = deliveryData?.typeDelivery || (hasTerminalSelection ? 'terminal' : 'door');
            if (nextDeliveryType === 'terminal') {
                const shippingAddressEl = getFieldElement(['shipping_address_1', 'shipping-address_1']);
                const terminalAddressValue = (deliveryData?.terminal?.address || shippingTerminal?.value || '').trim();

                // В Blocks address_1 остаётся обязательным в checkout store,
                // поэтому для terminal перед отключением проставляем значение.
                setInputValue(shippingAddressEl, terminalAddressValue || 'Пункт выдачи');
                clearAddressFieldError();
                toggleAddressFields(false);
            } else if (nextDeliveryType === 'door') {
                toggleAddressFields(true);
                const shippingAddressEl = getFieldElement(['shipping_address_1', 'shipping-address_1']);
                setInputValue(shippingAddressEl, '');
                clearAddressFieldError();
            }

            return hasTerminalSelection;
        }

        function handleServicesLoadedState(services = [], isNotAvailable = false) {
            const rootBlock = root.closest('.wc-esl-checkout-shipping-block') || document;
            const desc = rootBlock.querySelector('.esl_desct_delivery');
            const countEl = rootBlock.querySelector('.esl_desct_delivery .count');
            const countTextEl = rootBlock.querySelector('.esl_desct_delivery .countText');
            const addTextEl = rootBlock.querySelector('.esl_desct_delivery .addText');

            if (!desc || !countEl || !countTextEl || !addTextEl) {
                return;
            }

            if (isNotAvailable) {
                countEl.textContent = '0';
                countTextEl.textContent = 'служб';
                addTextEl.textContent = 'Нет доступных вариантов доставки.';
                desc.style.display = '';
                return;
            }

            if (!Array.isArray(services) || services.length === 0) {
                return;
            }

            const count = services.length;
            const countText = count <= 1 ? 'служба' : (count < 5 ? 'службы' : 'служб');
            const nameDelivery = services
                .map((service) => (service && service.name ? service.name : ''))
                .filter(Boolean)
                .join(', ');

            countEl.textContent = String(count);
            countTextEl.textContent = countText;
            addTextEl.textContent = nameDelivery || 'Выбран самый дешевый вариант.';
            desc.style.display = '';
        }

        // Как в legacy: фиксируем хеш карточки из onBalloonOpen.
        root.addEventListener('eShopLogisticWidgetCart:onBalloonOpen', (event) => {
            const balloonHash = getServiceSignature(event.detail);

            // Legacy flow: обновляем UI (включая buttonModalDoor)
            // уже на этапе открытия карточки сервиса.
            handleServiceChange(event.detail);

            // В фазе автоинициализации фиксируем первый baseline-хеш.
            if (suppressCloseOnAutoSelect && !hashSelectService) {
                hashSelectService = balloonHash;
            }
        });

        // Когда список сервисов загружен, считаем фазу авто-инициализации завершенной.
        root.addEventListener('eShopLogisticWidgetCart:onAllServicesLoaded', (event) => {
            suppressCloseOnAutoSelect = false;
            handleServicesLoadedState(event.detail, false);
        });

        root.addEventListener('eShopLogisticWidgetCart:onNotAvailableServices', () => {
            handleServicesLoadedState([], true);
        });

        // Если это событие не приходит у конкретной версии SDK - fallback.
        setTimeout(() => {
            suppressCloseOnAutoSelect = false;
        }, 3000);

        root.addEventListener('eShopLogisticWidgetCart:onSelectedService', (event) => {
            const deliveryData = event.detail;
            const responseData = deliveryData?.service?.responseData?.[deliveryData?.typeDelivery];

            if (!responseData) {
                // Тариф для этого типа доставки ещё не посчитан (например, курьер ждёт
                // ввода адреса) — не сохраняем это как выбор, иначе в сессию уйдёт
                // подтверждённая ставка с ценой 0, которая переживёт перезагрузку страницы.
                console.log('ESL: нет данных тарифа для "' + (deliveryData?.typeDelivery || '') + '", выбор пропущен');
                return;
            }

            const selectedHash = getServiceSignature(deliveryData);
            const frameData = buildLegacyShippingFrameData(deliveryData);
            const hasTerminalSelection = handleServiceChange(deliveryData);

            const widgetData = getWidgetData();
            const cityName = getLegacyShippingCityName(widgetData);

            // Если baseline ещё не зафиксирован, делаем это при первом выборе
            // (типично это авто-выбор единственного доступного варианта).
            if (!hashSelectService) {
                hashSelectService = selectedHash;
            }

            // Legacy flow: сохранить terminal_location в сессию через тот же AJAX что и legacy.
            setLoadingState(true);

            const persistTerminalSelection = (hasTerminalSelection && deliveryData?.terminal)
                ? setTerminalAddress(
                    deliveryData.terminal.address || '',
                    deliveryData.terminal.code || ''
                ).catch(() => ({ success: false }))
                : Promise.resolve({ success: true });

            persistTerminalSelection.then(() => updateShippingData(JSON.stringify(frameData), { name: cityName })).then(() => {
                setLoadingState(false);

                // Для terminal: закрываем модалку при любом явном выборе (не в suppress-окне).
                // Хеш-сравнение использовалось только для door, но для terminal пользователь
                // всегда явно нажимает "Забрать отсюда" — закрываем безусловно.
                // userInteractedWithWidget позволяет закрыть даже если suppressCloseOnAutoSelect ещё true,
                // но пользователь явно кликнул по виджету (pointerdown зафиксирован).
                const canCloseModal = (!suppressCloseOnAutoSelect || userInteractedWithWidget) && hasTerminalSelection;

                if (canCloseModal) {
                    const modal = document.getElementById('modal-esl-frame');
                    if (modal) {
                        setEslModalVisible(modal, false);
                    }
                }

                // Обновляем baseline при каждом выборе.
                hashSelectService = selectedHash;
            });
        });

        root.addEventListener('eShopLogisticWidgetCart:onError', (event) => {
            console.error('❌ Widget error:', event.detail);
            showError('Ошибка загрузки данных о доставке');
            setLoadingState(false);
        });

        root.addEventListener('eShopLogisticWidgetCart:onInvalidName', () => {
            console.error('❌ Invalid city name');
            showError('Неверное название города');
            setLoadingState(false);
        });

        root.addEventListener('eShopLogisticWidgetCart:onInvalidCity', () => {
            console.error('❌ Invalid city');
            errorCity++;
            if (errorCity < 2 && eslWidget && eslWidget.run) {
                setTimeout(() => eslWidget.run('city'), 2000);
            } else {
                showError('Неверно указан город');
            }
        });

        root.addEventListener('eShopLogisticWidgetCart:onInvalidServices', () => {
            console.error('❌ Invalid services');
            showError('Невозможна доставка по указанному адресу');
        });
    }

    /**
     * Показать сообщение об ошибке
     */
    function showError(message) {
        setLoadingState(false);
        
        const errorContainer = document.getElementById('tips-city-container');
        if (errorContainer) {
            errorContainer.innerHTML = message;
            errorContainer.style.display = 'block';
        }

        // Скрыть терминалы
        toggleTerminals(false, 'shipping');
    }

    /**
     * Показать/скрыть модалку выбора служб доставки без display:none и без
     * visibility:hidden. #eShopLogisticWidgetCart живёт внутри этой модалки,
     * и SDK виджета явно проверяет реальную видимость контейнера (подтверждено
     * логами: сначала display:none, потом visibility:hidden — оба варианта
     * SDK на загрузке страницы всё равно досчитывал только 1 запасную службу
     * (Dostavista); полный список появлялся лишь после клика по кнопке,
     * который переключает именно на visibility:visible). checkVisibility()-
     * подобная проверка в SDK, судя по всему, учитывает display и visibility,
     * но не opacity — поэтому прячем модалку через opacity:0 (CSS-видимость
     * "visible" сохраняется) + pointer-events:none, и SDK досчитывает полный
     * список сразу, не дожидаясь открытия модалки пользователем.
     */
    function setEslModalVisible(modal, visible) {
        modal.style.display = 'block';
        modal.style.opacity = visible ? '1' : '0';
        modal.style.pointerEvents = visible ? 'auto' : 'none';
    }

    /**
     * Инициализация модального окна
     */

    function initModal() {
        const modal = document.getElementById('modal-esl-frame');
        if (!modal) return;

        // Move modal to document.body to escape WooCommerce Blocks stacking contexts
        // that would otherwise render above position:fixed z-index:9999 elements.
        if (modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }

        // Legacy behavior: modal must stay hidden until user explicitly opens it
        // (see setEslModalVisible for why this isn't display:none).
        setEslModalVisible(modal, false);

        const closeButton = modal.querySelector('.close_modal_window');
        const doorButton = document.getElementById('buttonModalDoor');

        const closeModal = () => {
            setEslModalVisible(modal, false);
        };

        if (closeButton) {
            closeButton.addEventListener('click', closeModal);
        }

        if (doorButton) {
            doorButton.addEventListener('click', closeModal);
        }

        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeModal();
            }
        });

        // Кнопки открытия модального окна
        const triggerButtons = document.querySelectorAll('.wc-esl-terminals__button');
        triggerButtons.forEach(button => {
            button.addEventListener('click', () => {
                setEslModalVisible(modal, true);

                // При открытии модалки сразу синхронизируем видимость door-кнопки
                // с текущим режимом, чтобы она не ждала следующего события виджета.
                if (doorButton) {
                    doorButton.style.display = window.keyDelivery === 'door' ? '' : 'none';
                }

                userInteractedWithWidget = false;
                suppressCloseOnAutoSelect = true;
                hashSelectService = '';
                
                // После открытия модального окна инициализируем виджет
                if (config.checkoutFrameEnabled) {
                    const widgetContainer = document.getElementById('eShopLogisticWidgetCart');
                    if (widgetContainer) {
                        // Даём время на отрисовку модального окна
                        setTimeout(() => {
                            // Виджет уже загружен и работает (обычно — с самой загрузки
                            // страницы): полный reinitWidget() здесь сносит уже готовый
                            // SDK и запускает новый экземпляр с нуля, из-за чего повторный
                            // поиск города/служб может не успеть завершиться до конца и
                            // показать неполный список (например, только Dostavista) —
                            // именно поэтому раньше требовался повторный ввод города.
                            // Если виджет уже жив — просто обновляем ему параметры.
                            if (widgetSdkLoaded && window.widgetInit && eslWidget) {
                                sendWidgetParams(widgetContainer);
                            } else {
                                reinitWidget(widgetContainer);
                            }
                        }, 100);
                    }
                }
            });
        });

        // No auto-open in Blocks.
    }

    /**
     * Обработка изменения способа доставки
     */
    function handleShippingMethodChange() {
        const currentMethod = getCurrentShippingMethod();
        const isEshop = isEshopMethod(currentMethod);
        const deliveryType = getDeliveryType(currentMethod);
        const hasEslBlock = document.querySelector('.wc-esl-checkout-shipping-block');
        const methodChanged = currentMethod !== lastHandledShippingMethod;

        // Очищаем выбранный терминал только при фактической смене метода доставки.
        // Иначе updated_checkout/перерендеры WooCommerce стирают уже выбранный ПВЗ.
        if (methodChanged) {
            const shippingTerminal = document.getElementById('wc_esl_shipping_terminal');
            const shippingTerminalField = document.getElementById('wc_esl_shipping_terminal_field');
            if (shippingTerminal) {
                shippingTerminal.value = '';
            }
            if (shippingTerminalField) {
                shippingTerminalField.style.display = 'none';
            }
        }

        // Для Gutenberg блока: по умолчанию кнопка видна (блок специально для eShopLogistic)
        // Скрываем только если явно выбран другой метод доставки или выбрана курьерская доставка
        if (hasEslBlock) {
            // Скрываем кнопку ПВЗ если: не наш метод, или явно выбрана доставка до двери
            const shouldHideTerminals = (currentMethod && !isEshop) || deliveryType === 'door';
            const shouldShowTerminals = !shouldHideTerminals;

            toggleTerminals(shouldShowTerminals, 'shipping');

            if (shouldShowTerminals) {
                // Управление видимостью полей адреса в зависимости от типа доставки
                if (deliveryType === 'terminal') {
                    toggleAddressFields(false);
                } else {
                    toggleAddressFields(true);
                }
            } else {
                toggleAddressFields(true);
            }
            lastHandledShippingMethod = currentMethod;
            return;
        }

        // Fallback без блока: работаем только с shipping-режимом.
        if (!currentMethod) {
            return;
        }

        if (isEshop && deliveryType === 'terminal') {
            toggleAddressFields(false);
            toggleTerminals(true, 'shipping');
        } else if (isEshop && deliveryType === 'door') {
            toggleAddressFields(true);
            toggleTerminals(false, 'shipping');
        } else {
            toggleAddressFields(true);
            toggleTerminals(false, 'shipping');
        }

        lastHandledShippingMethod = currentMethod;
    }

    /**
     * Инициализация блока checkout shipping
     */
    function initCheckoutShippingBlock() {
        const blocks = document.querySelectorAll('.wc-esl-checkout-shipping-block');
        
        blocks.forEach(block => {
            if (block.dataset.initialized) return;
            block.dataset.initialized = 'true';

            // Корзинный виджет выключен — инициализацию виджета и модала пропускаем,
            // кнопку обрабатывает yandex-map.js через #wcEslTerminals
            if (!config.checkoutFrameEnabled) {
                handleShippingMethodChange();
                setupAddressSelectionForBlocks();
                setupCityModalForBlocks();
                return;
            }

            // Используем контейнер, отрисованный PHP (совместимо с legacy-разметкой).
            const widgetContainer = block.querySelector('#eShopLogisticWidgetCart');
            if (!widgetContainer) {
                console.warn('eShopLogistic: Widget container #eShopLogisticWidgetCart not found');
                return;
            }

            // Legacy-поведение: инициализация виджета сразу при загрузке страницы.
            initWidget(widgetContainer);

            // Инициализировать модальное окно
            initModal();

            // Синхронизировать видимость кнопки ПВЗ с уже выбранным (восстановленным
            // из сессии) способом доставки — иначе кнопка остаётся скрытой до первого
            // ручного клика по radio, хотя нужный ESL-метод уже выбран. Список radio
            // от WooCommerce Blocks рендерится React'ом асинхронно и может ещё не
            // существовать в момент первого вызова, поэтому повторяем несколько раз.
            let syncAttempts = 0;
            const syncInterval = setInterval(() => {
                handleShippingMethodChange();
                syncAttempts += 1;
                if (syncAttempts >= 6) {
                    clearInterval(syncInterval);
                }
            }, 500);
        });
    }

    function ensureCheckoutShippingBlockInit() {
        let attempts = 0;
        const maxAttempts = 10;
        const retryMs = 500;

        const retry = () => {
            initCheckoutShippingBlock();
            attempts += 1;

            if (attempts < maxAttempts) {
                setTimeout(retry, retryMs);
            }
        };

        retry();
    }

    /**
     * Отслеживание изменений методов доставки (для WooCommerce Blocks)
     */
    function setupShippingMethodObserver() {
        // Отслеживание изменений через нативные события
        document.addEventListener('change', function(e) {
            const target = e.target;
            if (target && target.matches && (
                target.matches('input[name^="shipping_method"]') ||
                target.matches('input[name*="shipping-rate"]') ||
                target.matches('input[name*="radio-control"]') ||
                target.matches('.wc-block-components-radio-control__input')
            )) {
                setTimeout(() => handleShippingMethodChange(), 100);
            }
        }, true);

        // MutationObserver для отслеживания изменений в DOM (для React-компонентов)
        const observer = new MutationObserver(function(mutations) {
            let shouldUpdate = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.type === 'attributes' && 
                    mutation.attributeName === 'checked' &&
                    mutation.target.matches &&
                    mutation.target.matches('input[type="radio"]')) {
                    shouldUpdate = true;
                }
            });
            
            if (shouldUpdate) {
                setTimeout(() => handleShippingMethodChange(), 100);
            }
        });

        // Наблюдаем за контейнером способов доставки
        const shippingContainer = document.querySelector('.wc-block-components-shipping-rates-control') ||
                                  document.querySelector('.wc-block-checkout__shipping-option') ||
                                  document.querySelector('[data-block-name="woocommerce/checkout-shipping-methods-block"]') ||
                                  document.querySelector('.wp-block-woocommerce-checkout-shipping-method-block');
        
        if (shippingContainer) {
            observer.observe(shippingContainer, {
                attributes: true,
                attributeFilter: ['checked'],
                subtree: true
            });
        }
    }

    /**
     * Подписка на события WooCommerce
     */
    function subscribeToWooEvents() {
        if (window.jQuery) {
            const $ = window.jQuery;
            let paymentRefreshLock = false;

            const handlePaymentChangeForWidget = () => {
                if (!isPaymentCalcEnabled() || paymentRefreshLock) {
                    return;
                }

                paymentRefreshLock = true;

                const applyPaymentUpdate = () => {
                    const widgetRoot = document.getElementById('eShopLogisticWidgetCart');
                    if (!widgetRoot) {
                        paymentRefreshLock = false;
                        return;
                    }

                    widgetRoot.dataset.paramsLoaded = '';
                    sendWidgetParams(widgetRoot);

                    const shippingTerminal = document.getElementById('wc_esl_shipping_terminal');
                    const shippingTerminalField = document.getElementById('wc_esl_shipping_terminal_field');
                    if (shippingTerminal) shippingTerminal.value = '';
                    if (shippingTerminalField) shippingTerminalField.style.display = 'none';

                    const desc = document.querySelector('.esl_desct_delivery');
                    if (desc) {
                        desc.innerHTML = '<p>Информация о доставке была обновлена, пожалуйста, выберите подходящий вариант доставки.</p>';
                        desc.style.display = '';
                    }

                    paymentRefreshLock = false;
                };

                // Legacy flow: wait until checkout refresh completes.
                $('body').one('updated_checkout', function() {
                    applyPaymentUpdate();
                });

                // Blocks fallback where updated_checkout may not fire.
                setTimeout(() => {
                    applyPaymentUpdate();
                }, 700);
            };

            // Обновление чекаута
            $('body').on('updated_checkout', function() {
                handleShippingMethodChange();
            });

            // Изменение способа доставки
            $(document).on('change', 'input[name^="shipping_method"]', function() {
                handleShippingMethodChange();
            });

            // Изменение способа оплаты
            $(document).on('change', 'input[name="payment_method"]', function() {
                handlePaymentChangeForWidget();
            });

            // Blocks checkout payment radios.
            document.addEventListener('change', function(e) {
                const target = e.target;
                if (!target || !target.matches) {
                    return;
                }

                if (
                    target.matches('.wc-block-components-payment-methods input[type="radio"]') ||
                    target.matches('.wc-block-checkout__payment-method input[type="radio"]') ||
                    target.matches('input[name*="payment-method"]') ||
                    target.matches('input[id*="wc-payment-method"]') ||
                    target.matches('input[id^="radio-control-wc-payment-method"]')
                ) {
                    handlePaymentChangeForWidget();
                }
            }, true);
        }
    }

    /**
     * Основная инициализация
     */
    function init() {
        initDefaultDelivery();
        ensureCheckoutShippingBlockInit();
        setupAddressSelectionForBlocks();
        setupCityModalForBlocks();
        setupShippingMethodObserver();
        subscribeToWooEvents();
        handleShippingMethodChange();
        setupUseForBillingCheckbox();
    }

    /**
     * Синхронизация ESL-сессии при переключении чекбокса
     * «Использовать этот адрес для выставления счетов» в WC Blocks.
     */
    function setupUseForBillingCheckbox() {
        // Защита от двойного срабатывания сразу из нескольких механизмов обнаружения
        var _lastToggle = 0;

        function onCheckboxToggle(useShippingAsBilling) {
            var now = Date.now();
            if (now - _lastToggle < 500) return;
            _lastToggle = now;

            setTimeout(function () {
                var shippingCityEl = getCheckoutCityElement();
                if (!shippingCityEl || !shippingCityEl.value) return;

                var cityInputEl = document.getElementById('widgetCityEsl');
                var shippingStateEl = getFieldElement(['shipping_state', 'shipping-state']);
                var shippingPostcodeEl = getFieldElement(['shipping_postcode', 'shipping-postcode']);

                var shippingCityData = {
                    city: shippingCityEl.value,
                    region: shippingStateEl ? shippingStateEl.value : '',
                    postcode: shippingPostcodeEl ? shippingPostcodeEl.value : '',
                    fias: '',
                    services: []
                };

                if (cityInputEl && cityInputEl.value) {
                    try {
                        var parsed = JSON.parse(cityInputEl.value);
                        if (parsed && parsed.city === shippingCityEl.value) {
                            shippingCityData = Object.assign({}, shippingCityData, parsed);
                        }
                    } catch (ex) {}
                }

                console.log(shippingCityData);

                var billingUpdatePromise;
                if (useShippingAsBilling) {
                    setCheckoutBillingAddressValues(shippingCityData);
                    billingUpdatePromise = requestBillingAddressUpdate(shippingCityData);
                } else {
                    var billingCityEl = getCheckoutBillingCityElement();
                    var billingStateEl = getFieldElement(['billing_state', 'billing-state']);
                    var billingPostcodeEl = getFieldElement(['billing_postcode', 'billing-postcode']);
                    billingUpdatePromise = requestBillingAddressUpdate({
                        city: billingCityEl && billingCityEl.value ? billingCityEl.value : shippingCityData.city,
                        region: billingStateEl ? billingStateEl.value : '',
                        postcode: billingPostcodeEl ? billingPostcodeEl.value : '',
                        fias: '',
                        services: []
                    });
                }

                Promise.all([
                    requestShippingAddressUpdate(shippingCityData),
                    billingUpdatePromise
                ]).then(function () {
                    document.dispatchEvent(new CustomEvent('wc-esl-city-changed', { detail: shippingCityData }));
                    if (eslWidget) {
                        sendWidgetParams(eslWidget, toWidgetSettlement(shippingCityData));
                    }
                    refreshCheckoutAfterShippingUpdate();
                });
            }, 300);
        }

        // Механизм 1: wp.data.subscribe — надёжно если WC Blocks 7+ и функция существует
        if (window.wp && window.wp.data && typeof window.wp.data.subscribe === 'function') {
            var _prev = null, _init = false;
            window.wp.data.subscribe(function () {
                try {
                    var store = window.wp.data.select('wc/store/checkout');
                    if (!store || typeof store.getUseShippingAsBilling !== 'function') return;
                    var val = !!store.getUseShippingAsBilling();
                    if (!_init) { _prev = val; _init = true; return; }
                    if (val === _prev) return;
                    _prev = val;
                    onCheckboxToggle(val);
                } catch (ex) {}
            });
        }

        // Механизм 2: MutationObserver — следим за видимостью billing-city поля.
        // Работает независимо от версии WC Blocks и имён классов.
        // Когда billing-поля скрыты → "use shipping as billing" включён.
        function isNodeVisible(el) {
            var node = el;
            while (node && node !== document.documentElement) {
                if (!node.nodeType || node.nodeType !== 1) break;
                var cs = window.getComputedStyle(node);
                if (cs.display === 'none' || cs.visibility === 'hidden') return false;
                if (node.getAttribute('aria-hidden') === 'true') return false;
                if (node.hasAttribute('hidden')) return false;
                node = node.parentElement;
            }
            return true;
        }

        function isBillingCityVisible() {
            var el = getCheckoutBillingCityElement();
            return el ? isNodeVisible(el) : false;
        }

        var _prevBillingVisible = null;
        var _mutationThrottle = false;
        var _billingObserver = null;

        function setupMutationObserver() {
            if (_billingObserver) return;
            var checkout = document.querySelector('.wp-block-woocommerce-checkout, .wc-block-checkout');
            if (!checkout) return;

            _prevBillingVisible = isBillingCityVisible();

            _billingObserver = new MutationObserver(function () {
                if (_mutationThrottle) return;
                _mutationThrottle = true;
                setTimeout(function () {
                    _mutationThrottle = false;
                    var nowVisible = isBillingCityVisible();
                    if (_prevBillingVisible === null) { _prevBillingVisible = nowVisible; return; }
                    if (nowVisible === _prevBillingVisible) return;
                    _prevBillingVisible = nowVisible;
                    onCheckboxToggle(!nowVisible);
                }, 100);
            });

            _billingObserver.observe(checkout, {
                childList: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['style', 'class', 'hidden', 'aria-hidden']
            });
        }

        setupMutationObserver();
        setTimeout(setupMutationObserver, 1500);

        // Механизм 3: DOM change event — запасной вариант
        document.addEventListener('change', function (e) {
            var t = e.target;
            if (!t || t.type !== 'checkbox' || !t.closest) return;
            if (t.closest('.wc-block-checkout__use-address-for-billing')) {
                onCheckboxToggle(t.checked);
            }
        }, true);
    }

    // Инициализация при загрузке DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Экспорт функций для глобального доступа
    window.wcEslCheckoutBlock = {
        initWidget,
        searchCity,
        updateShippingData,
        handleShippingMethodChange
    };

})();
