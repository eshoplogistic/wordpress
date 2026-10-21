window.addEventListener('load', function(event) {
	eslRun()

	// Модальное окно подтверждения (#modal-esl-confirm) — замена window.confirm(),
	// чтобы диалог выглядел как остальные модалки страницы настроек, а не системный
	// alert браузера.
	window.EslConfirm = (function () {
		let modal = null;
		let messageEl = null;
		let okBtn = null;
		let cancelBtn = null;
		let onConfirmCallback = null;

		function hide() {
			onConfirmCallback = null;
			if (modal) {
				modal.style.display = 'none';
			}
		}

		function init() {
			if (modal) {
				return true;
			}
			modal = document.getElementById('modal-esl-confirm');
			if (!modal) {
				return false;
			}
			messageEl = modal.querySelector('.esl-confirm__message');
			okBtn = modal.querySelector('.esl-confirm__ok');
			cancelBtn = modal.querySelector('.esl-confirm__cancel');

			okBtn.addEventListener('click', function () {
				let callback = onConfirmCallback;
				hide();
				if (callback) {
					callback();
				}
			});
			cancelBtn.addEventListener('click', hide);

			return true;
		}

		function show(message, onConfirm) {
			if (!init()) {
				// На случай, если разметка модалки почему-то не выведена на странице.
				if (window.confirm(message)) {
					onConfirm();
				}
				return;
			}
			messageEl.textContent = message;
			onConfirmCallback = onConfirm;
			modal.style.display = 'block';
		}

		return { show: show };
	})();

	let modalAddField = document.getElementById("modal-esl-add-field")
	let contentAjax = document.getElementById("content-add-field_ajax")
	let modalTerminalSearch = document.getElementById("modal-esl-terminal-search")
	let contentTerminalSearch = document.getElementById("content-terminal-search_ajax")
	let modalFreightSearch = document.getElementById("modal-esl-freight-search")
	let contentFreightSearch = document.getElementById("content-freight-search_ajax")

	document.querySelectorAll(".modal-esl-frame .close_modal_window").forEach(function (span) {
		span.onclick = function () {
			span.closest(".modal-esl-frame").style.display = "none"
		}
	})

	window.onclick = function (event) {
		if (event.target.classList && event.target.classList.contains("modal-esl-frame")) {
			event.target.style.display = "none"
		}
	}

	let bindEvents = {
		clickOnAddField: function (event) {
			let data = {};
			data.action = 'wc_esl_shipping_get_add_field';
			data.type = event.target.getAttribute('data-mode');
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, function(result){
				if(result.success === true){
					contentAjax.innerHTML = result.data;
				}
			});
			modalAddField.style.display = "block"
		},
		clickOnSearchTerminal: function (event) {
			let service = event.target.getAttribute('data-service');
			let target = event.target.getAttribute('data-target');

			modalTerminalSearch.dataset.service = service;
			modalTerminalSearch.dataset.target = target;
			contentTerminalSearch.innerHTML = '';
			document.getElementById('settlementTerminalSearch').value = '';
			document.getElementById('addressTerminalSearch').value = '';
			modalTerminalSearch.style.display = "block"
		},
		clickOnSearchFreight: function (event) {
			let service = event.target.getAttribute('data-service');
			let target = event.target.getAttribute('data-target');

			modalFreightSearch.dataset.service = service;
			modalFreightSearch.dataset.target = target;
			contentFreightSearch.innerHTML = '';
			document.getElementById('freightTypeSearch').value = '';
			modalFreightSearch.style.display = "block"
		},
		onCloseModal: function () {
		},
	}

	let els_add_buttons = document.getElementsByClassName('wc-esl-add__button')
	if (els_add_buttons) {
		for (let i = 0; i < els_add_buttons.length; i++) {
			els_add_buttons[i].addEventListener('click', bindEvents.clickOnAddField, false);
		}

	}

	let els_search_terminal_buttons = document.getElementsByClassName('esl-search-terminal')
	if (els_search_terminal_buttons) {
		for (let i = 0; i < els_search_terminal_buttons.length; i++) {
			els_search_terminal_buttons[i].addEventListener('click', bindEvents.clickOnSearchTerminal, false);
		}
	}

	let els_search_freight_buttons = document.getElementsByClassName('esl-search-freight')
	if (els_search_freight_buttons) {
		for (let i = 0; i < els_search_freight_buttons.length; i++) {
			els_search_freight_buttons[i].addEventListener('click', bindEvents.clickOnSearchFreight, false);
		}
	}

	function runTerminalSearch() {
		let data = {};
		data.action = 'wc_esl_shipping_search_terminal';
		data.service = modalTerminalSearch.dataset.service;
		data.settlement = document.getElementById('settlementTerminalSearch').value;
		data.address = document.getElementById('addressTerminalSearch').value;
		data.nonce = wc_esl_shipping_global.nonce;

		HttpClientEsl.post(data, function (result) {
			if (result.success !== true) return;

			contentTerminalSearch.innerHTML = result.data;

			let els_terminal_items = contentTerminalSearch.getElementsByClassName('esl-terminal-search-modal__item');
			for (let i = 0; i < els_terminal_items.length; i++) {
				els_terminal_items[i].addEventListener('click', function (e) {
					let element = e.target.closest('[data-code]');
					let code = element ? element.dataset.code : null;
					if (!code) return;

					let targetElem = document.getElementsByName(modalTerminalSearch.dataset.target);
					if (targetElem && targetElem[0]) {
						targetElem[0].value = code;
					}

					modalTerminalSearch.style.display = "none"
				}, false);
			}
		});
	}

	let buttonModalTerminalSearch = document.getElementById('buttonModalTerminalSearch')
	if (buttonModalTerminalSearch) {
		buttonModalTerminalSearch.addEventListener('click', runTerminalSearch, false);
	}

	let addressTerminalSearchInput = document.getElementById('addressTerminalSearch')
	if (addressTerminalSearchInput) {
		addressTerminalSearchInput.addEventListener('keypress', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				runTerminalSearch();
			}
		});
	}

	function runFreightSearch() {
		let data = {};
		data.action = 'wc_esl_shipping_search_freight';
		data.service = modalFreightSearch.dataset.service;
		data.name = document.getElementById('freightTypeSearch').value;
		data.nonce = wc_esl_shipping_global.nonce;

		HttpClientEsl.post(data, function (result) {
			if (result.success !== true) return;

			contentFreightSearch.innerHTML = result.data;

			let els_freight_items = contentFreightSearch.getElementsByClassName('esl-freight-search-modal__item');
			for (let i = 0; i < els_freight_items.length; i++) {
				els_freight_items[i].addEventListener('click', function (e) {
					let element = e.target.closest('[data-code]');
					let title = element ? element.dataset.title : null;
					if (!title) return;

					let targetElem = document.getElementsByName(modalFreightSearch.dataset.target);
					if (targetElem && targetElem[0]) {
						targetElem[0].value = title;
					}

					modalFreightSearch.style.display = "none"
				}, false);
			}
		});
	}

	let buttonModalFreightSearch = document.getElementById('buttonModalFreightSearch')
	if (buttonModalFreightSearch) {
		buttonModalFreightSearch.addEventListener('click', runFreightSearch, false);
	}

	let freightTypeSearchInput = document.getElementById('freightTypeSearch')
	if (freightTypeSearchInput) {
		freightTypeSearchInput.addEventListener('keypress', function (event) {
			if (event.key === 'Enter') {
				event.preventDefault();
				runFreightSearch();
			}
		});
	}
});

// Показ/скрытие полей "Настроек по умолчанию" на вкладке транспортной компании в
// зависимости от типа отправителя/получателя — универсальный механизм по образцу
// moj_sklad (Modules/Iframe.php: visible_by_params_parent + wrapper_class), только через
// data-атрибуты: управляющее поле несёт data-esl-visible-target(2)/data-esl-visible-value(2),
// управляемые поля — data-esl-key с именем группы (см. ExportFileds::tabVisibilityRules()).
// В отличие от формы выгрузки заказа (там поля дизейблятся с приглушением — см.
// eslSyncBaikalSenderLegal и т.п. в assets/js/settings_unloading.js), здесь поля
// скрываются целиком — это форма настроек по умолчанию, а не форма конкретного заказа.
function eslApplyVisibilityRule(target, values, match) {
	document.querySelectorAll('#eslCarrierTabsWrap [data-esl-key="' + target + '"]').forEach(function (wrapper) {
		wrapper.style.display = match ? '' : 'none';

		// Скрытый чекбокс не должен молча оставаться "включённым" в сохранённых настройках —
		// снимаем галку вместе со скрытием (например "Отправлять состав заказа для страховки"
		// без включённого "Объединения грузовых мест").
		if (!match) {
			wrapper.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
				checkbox.checked = false;
			});
		}
	});
}

function eslSyncVisibilityController(el) {
	let value = el.type === 'checkbox' ? (el.checked ? '1' : '0') : el.value;

	if (el.dataset.eslVisibleTarget && el.dataset.eslVisibleValue !== undefined) {
		let values = el.dataset.eslVisibleValue.split(',');
		eslApplyVisibilityRule(el.dataset.eslVisibleTarget, values, values.includes(String(value)));
	}
	if (el.dataset.eslVisibleTarget2 && el.dataset.eslVisibleValue2 !== undefined) {
		let values2 = el.dataset.eslVisibleValue2.split(',');
		eslApplyVisibilityRule(el.dataset.eslVisibleTarget2, values2, values2.includes(String(value)));
	}
}

function eslSyncAllVisibilityControllers() {
	document.querySelectorAll('#eslCarrierTabsWrap [data-esl-visible-target]').forEach(eslSyncVisibilityController);
}

document.addEventListener('change', function (e) {
	if (e.target.dataset && e.target.dataset.eslVisibleTarget && e.target.closest('#eslCarrierTabsWrap')) {
		eslSyncVisibilityController(e.target);
	}
});

function eslRun() {
	let AdminSettingsEsl = {
		enablePluginCheckbox: document.getElementById('enablePlugin'),
		enablePluginPriceShippingCheckbox: document.getElementById('enablePluginPriceShipping'),
		enablePluginLogCheckbox: document.getElementById('enablePluginLog'),
		apiKeyInput: document.getElementById('apiKeyInput'),
		apiKeyForm: document.getElementById('apiKeyForm'),
		apiKeyStatusBlock: document.getElementById('apiKeyStatusBlock'),
		apiKeyStatusBadge: document.getElementById('apiKeyStatusBadge'),
		apiKeyStatusErrorMsg: document.getElementById('apiKeyStatusErrorMsg'),
		apiKeyStatusBalance: document.getElementById('apiKeyStatusBalance'),
		apiKeyStatusPaidDaysText: document.getElementById('apiKeyStatusPaidDaysText'),
		apiKeyStatusFreeDaysBlock: document.getElementById('apiKeyStatusFreeDaysBlock'),
		apiKeyStatusFreeDays: document.getElementById('apiKeyStatusFreeDays'),
		apiKeyWCartInput: document.getElementById('apiKeyWCartInput'),
		apiKeyWCartForm: document.getElementById('apiKeyWCartForm'),
		apiKeyYaInput: document.getElementById('apiKeyYaInput'),
		apiKeyYaForm: document.getElementById('apiKeyYaForm'),
		widgetSecretCodeInput: document.getElementById('eslWidgetSecretCode'),
		widgetSecretCodeForm: document.getElementById('eslWidgetSecretCodeForm'),
		widgetKeyInput: document.getElementById('eslWidgetKey'),
		widgetKeyForm: document.getElementById('eslWidgetKeyForm'),
		widgetButInput: document.getElementById('eslWidgetBut'),
		widgetButForm: document.getElementById('eslWidgetButForm'),
		eslPayTypeForm: document.getElementById('eslPayTypeForm'),
		generalOptionsWrapperSelector: '.wc-esl-settings-general-options .card-body',
		widgetWrapperSelector: '.wc-esl-settings-widget .card-body',
		addWrapperSelector: '.wc-esl-settings-others .card-body',
		exportWrapperSelector: '.wc-esl-settings-export .card-body',
		carrierTabsWrapperSelector: '#eslCarrierTabsWrap',
		statusWrapperSelector: '#eslStatusFormWrap',
		dimensionMeasurement: document.getElementById('dimensionMeasurement'),
		addForm: document.getElementById('eslAddForm'),
		exportForm: document.getElementById('eslExportForm'),
		enableFrameCheckbox: document.getElementById('enableFrame'),
		statusSave: document.getElementById('statusSave'),
		eslAddFieldForm: 'eslAddFieldForm',
		buttonAddFieldForm: document.getElementById('buttonModalAddField'),
		eslAddFieldSelector: '.modal-esl-frame .modal_content',

		init: function () {
			this.enablePluginCheckbox.addEventListener('change', this.changeEnablePluginCheckbox.bind({
				_self: this
			}));

			this.enablePluginPriceShippingCheckbox.addEventListener('change', this.changeEnablePluginPriceShippingCheckbox.bind({
				_self: this
			}));

			this.enablePluginLogCheckbox.addEventListener('change', this.changeEnablePluginLogCheckbox.bind({
				_self: this
			}));

			this.apiKeyForm.addEventListener('submit', this.submitApiKeyForm.bind({
				_self: this
			}));

			this.apiKeyWCartForm.addEventListener('submit', this.submitApiKeyWCartForm.bind({
				_self: this
			}));

			this.apiKeyYaForm.addEventListener('submit', this.submitApiKeyYaForm.bind({
				_self: this
			}));

			this.widgetSecretCodeForm.addEventListener('submit', this.submitWidgetSecretCodeForm.bind({
				_self: this
			}));

			this.widgetKeyForm.addEventListener('submit', this.submitWidgetKeyForm.bind({
				_self: this
			}));
			this.widgetButForm.addEventListener('submit', this.submitWidgetButForm.bind({
				_self: this
			}));
			this.dimensionMeasurement.addEventListener('change', this.changeDimensionMeasurementSelect.bind({
				_self: this
			}));
			this.addForm.addEventListener('submit', this.submitAddForm.bind({
				_self: this
			}));

			this.enableFrameCheckbox.addEventListener('change', this.changeEnableFrameCheckbox.bind({
				_self: this
			}));
			if(this.statusSave){
				this.statusSave.addEventListener('click', this.statusSaveForm.bind({
					_self: this
				}));
				sortable('.sortable', {
					connectWith: 'js-connected'
				});
				sortable('.sortable-copy', {
					copy: true,
					connectWith: 'js-connected'
				});

				// Крестик удаления рендерится в PHP только для статусов, уже сохранённых в базе
				// (see views/settings.php: $status_form). Свежая копия статуса, перетащенная из
				// правого списка, приходит без него — добавляем крестик сразу же, не дожидаясь
				// сохранения и перезагрузки страницы. Заодно не даём положить один и тот же
				// статус (по атрибуту name — слаг статуса WooCommerce) дважды в одну и ту же
				// зону: и внутри одной строки ESL-статуса, и обратно в правый пул "доступных"
				// (там оригинал лежит постоянно, так что "возврат" туда — по сути удаление
				// перетащенной копии, а не второй такой же элемент).
				document.querySelectorAll('.sortable, .sortable-copy').forEach(function (list) {
					list.addEventListener('sortupdate', function (e) {
						let destination = e.detail && e.detail.destination ? e.detail.destination.container : null;
						let droppedItem = e.detail ? e.detail.item : null;
						if (!destination || !droppedItem) {
							return;
						}

						let isInnerRow = destination.classList.contains('js-inner-connected');
						let isPool = destination.classList.contains('js-connected');
						if (!isInnerRow && !isPool) {
							return;
						}

						let statusKey = droppedItem.getAttribute('name');
						let isDuplicate = Array.from(destination.querySelectorAll('li.esl-status__wp')).some(function (item) {
							return item !== droppedItem && item.getAttribute('name') === statusKey;
						});

						if (isDuplicate) {
							droppedItem.remove();
							return;
						}

						if (isInnerRow) {
							if (!droppedItem.querySelector('.sortable-delete')) {
								let del = document.createElement('span');
								del.className = 'sortable-delete';
								del.textContent = 'х';
								del.addEventListener('click', function () {
									sortableDelete(del);
								});
								droppedItem.appendChild(del);
							}
						} else {
							let del = droppedItem.querySelector('.sortable-delete');
							if (del) {
								del.remove();
							}
						}
					});
				});
			}
			if(this.exportForm){
				this.exportForm.addEventListener('submit', this.submitExportForm.bind({
					_self: this
				}));
			}

			// Поля "Дополнительных настроек" каждой службы (тарифы, ОПФ, объединение мест
			// и т.п.) грузятся лениво по клику на вкладку — часть служб дёргает живой API.
			// Активная по умолчанию вкладка грузится сразу, остальные — при первом клике.
			let _selfTabs = this;
			document.querySelectorAll(this.carrierTabsWrapperSelector + ' .nav-link[data-toggle="tab"]').forEach(function (link) {
				link.addEventListener('click', function () {
					let targetSelector = link.getAttribute('href');
					let pane = targetSelector ? document.querySelector(targetSelector) : null;
					let container = pane ? pane.querySelector('.esl-carrier-extra-fields') : null;
					_selfTabs.loadCarrierExtraFields(container);
				});
			});
			this.loadCarrierExtraFields(document.querySelector(this.carrierTabsWrapperSelector + ' .tab-pane.show.active .esl-carrier-extra-fields'));
			eslSyncAllVisibilityControllers();
			if(this.buttonAddFieldForm){
				this.buttonAddFieldForm.addEventListener('click', this.submitAddFieldForm.bind({
					_self: this
				}));
			}
		},

		submitAddFieldForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let form = document.getElementById(_self.eslAddFieldForm);
			let type = form.getAttribute('data-type');
			let result = [];
			let data = new FormData(form);
			for (let [key, value] of data) {
				result.push({name:key, value:value});
			}

			PreloaderEsl.show(_self.eslAddFieldSelector);
			_self.changeAddField(result, type);
		},

		changeAddField: function (result, type) {
			let data = {};

			data.action = 'wc_esl_shipping_save_add_field';
			data.result = JSON.stringify(result);
			data.type = type;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeAddField.bind({
				_self: this
			}));
		},

		callbackChangeAddField: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.eslAddFieldSelector);
		},

		changeEnablePluginCheckbox: function (event) {
			let _self = this._self;
			let status = event.target.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginStatus(status);
		},

		changePluginStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangePluginStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginStatus: function (response) {
			let _self = this._self;
			
			if (!response) {
				PushEsl.addItem('error', 'Нет ответа от сервера');
				PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
				return;
			}
			
			if (response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				// После успешного сохранения обновляем состояние чекбокса в соответствии с ответом
				if (response.data && response.data.wc_esl_shipping_plugin_enable) {
					const newValue = response.data.wc_esl_shipping_plugin_enable === 1 || response.data.wc_esl_shipping_plugin_enable === '1';
					_self.enablePluginCheckbox.checked = newValue;
				}
			} else {
				PushEsl.addItem(response.status, response.msg);
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},

		changeEnablePluginPriceShippingCheckbox: function (event) {
			let _self = this._self;
			let status = event.target.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginPriceShippingStatus(status);
		},

		changePluginPriceShippingStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin_price_shipping';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangePluginPriceShippingStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginPriceShippingStatus: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				// После успешного сохранения обновляем состояние чекбокса в соответствии с ответом
				if (response.data && response.data.wc_esl_shipping_plugin_enable_price_shipping) {
					const newValue = response.data.wc_esl_shipping_plugin_enable_price_shipping === 1 || response.data.wc_esl_shipping_plugin_enable_price_shipping === '1';
					_self.enablePluginPriceShippingCheckbox.checked = newValue;
				}
			} else {
				console.error('Ответ не содержит status');
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},


		changeEnablePluginLogCheckbox: function (event) {
			let _self = this._self;
			let status = event.target.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginLogStatus(status);
		},

		changePluginLogStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin_log';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangePluginLogStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginLogStatus: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_plugin_enable_log) {
					const newValue = response.data.wc_esl_shipping_plugin_enable_log === 1 || response.data.wc_esl_shipping_plugin_enable_log === '1';
					_self.enablePluginLogCheckbox.checked = newValue;
				}
			} else {
				console.error('Ответ не содержит status');
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},

		submitApiKeyForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let apiKey = _self.apiKeyInput.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeApiKey(apiKey);
		},

		changeApiKey: function (apiKey) {
			let data = {};

			data.action = 'wc_esl_shipping_save_api_key';
			data.api_key = apiKey;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeApiKey.bind({
				_self: this
			}));
		},

		callbackChangeApiKey: function (response) {
			let _self = this._self;
			if (response) {
				PushEsl.addItem(response.status || 'error', response.msg || 'Ошибка сохранения');
				let data = response.data || {};
				// Обновляем значение в форме (ключ сохраняется, даже если запрос
				// состояния аккаунта завершился ошибкой, например из-за баланса)
				if (data.wc_esl_shipping_api_key) {
					_self.apiKeyInput.value = data.wc_esl_shipping_api_key;
				}
				_self.updateApiKeyStatus(data);
			} else {
				PushEsl.addItem('error', 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},

		updateApiKeyStatus: function (data) {
			let _self = this;

			if (!_self.apiKeyStatusBlock) {
				return;
			}

			_self.apiKeyStatusBlock.style.display = '';

			let syncError = data.wc_esl_shipping_account_sync_error || '';
			let blocked = data.wc_esl_shipping_account_blocked === '1';

			_self.apiKeyStatusBadge.classList.remove('badge-danger', 'badge-success', 'badge-warning');
			if (syncError) {
				_self.apiKeyStatusBadge.textContent = 'Ошибка синхронизации';
				_self.apiKeyStatusBadge.classList.add('badge-warning');
			} else if (blocked) {
				_self.apiKeyStatusBadge.textContent = 'Заблокирован';
				_self.apiKeyStatusBadge.classList.add('badge-danger');
			} else {
				_self.apiKeyStatusBadge.textContent = 'Активен';
				_self.apiKeyStatusBadge.classList.add('badge-success');
			}

			if (_self.apiKeyStatusErrorMsg) {
				_self.apiKeyStatusErrorMsg.textContent = syncError;
				_self.apiKeyStatusErrorMsg.style.display = syncError ? '' : 'none';
			}

			_self.apiKeyStatusBalance.textContent = data.wc_esl_shipping_account_balance || '';
			_self.apiKeyStatusPaidDaysText.textContent = data.wc_esl_shipping_account_paid_days_text || '';

			let freeDays = data.wc_esl_shipping_account_free_days || '';
			_self.apiKeyStatusFreeDays.textContent = freeDays;
			_self.apiKeyStatusFreeDaysBlock.style.display = (freeDays && freeDays !== '0') ? '' : 'none';
		},

		submitApiKeyWCartForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let apiKey = _self.apiKeyWCartInput.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeApiKeyWCart(apiKey);
		},

		changeApiKeyWCart: function (apiKey) {
			let data = {};

			data.action = 'wc_esl_shipping_save_api_key_wcart';
			data.api_key = apiKey;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeApiKeyWCart.bind({
				_self: this
			}));
		},

		callbackChangeApiKeyWCart: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				// Обновляем значение в форме
				if (response.data) {
					_self.apiKeyWCartInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},

		submitApiKeyYaForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let apiKeyYa = _self.apiKeyYaInput.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeApiKeyYa(apiKeyYa);
		},

		changeApiKeyYa: function (apiKeyYa) {
			let data = {};

			data.action = 'wc_esl_shipping_save_api_key_ya';
			data.api_key_ya = apiKeyYa;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeApiKey.bind({
				_self: this
			}));
		},

		callbackChangeApiKeyYa: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					_self.apiKeyYaInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},

		submitWidgetSecretCodeForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let secretCode = _self.widgetSecretCodeInput.value;

			PreloaderEsl.show(_self.widgetWrapperSelector);

			_self.changeWidgetSecretCode(secretCode);
		},

		changeWidgetSecretCode: function (secretCode) {
			let data = {};

			data.action = 'wc_esl_shipping_save_widget_secret_code';
			data.secret_code = secretCode;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeWidgetSecretCode.bind({
				_self: this
			}));
		},

		callbackChangeWidgetSecretCode: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					_self.widgetSecretCodeInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.widgetWrapperSelector);
		},

		submitWidgetKeyForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let widgetKey = _self.widgetKeyInput.value;

			PreloaderEsl.show(_self.widgetWrapperSelector);

			_self.changeWidgetKey(widgetKey);
		},

		changeWidgetKey: function (widgetKey) {
			let data = {};

			data.action = 'wc_esl_shipping_save_widget_key';
			data.widget_key = widgetKey;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeWidgetKey.bind({
				_self: this
			}));
		},

		callbackChangeWidgetKey: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					_self.widgetKeyInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.widgetWrapperSelector);
		},

		submitWidgetButForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let widgetBut = _self.widgetButInput.value;

			PreloaderEsl.show(_self.widgetWrapperSelector);

			_self.changeWidgetBut(widgetBut);
		},

		changeWidgetBut: function (widgetBut) {
			let data = {};

			data.action = 'wc_esl_shipping_save_widget_but';
			data.widget_but = widgetBut;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeWidgetBut.bind({
				_self: this
			}));
		},

		callbackChangeWidgetBut: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					_self.widgetButInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.widgetWrapperSelector);
		},


		submitEslPayTypeForm: function (event) {
			event.preventDefault();

			let _self = this._self;

			let inputs = document.querySelectorAll('#eslPayTypeForm input[type="radio"]:checked');

			if(inputs.length > 0) {
				inputs.forEach(function () {});
			}
		},


		changeDimensionMeasurementSelect: function (event) {
			let _self = this._self;
			let status = event.target.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeDimensionMeasurementStatus(status);
		},

		changeDimensionMeasurementStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_dimension_measurement';
			data.status = status;
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackDimensionMeasurementStatus.bind({
				_self: this
			}));
		},

		callbackDimensionMeasurementStatus: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_dimension_measurement) {
					_self.dimensionMeasurement.value = response.data.wc_esl_shipping_dimension_measurement;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},


		submitAddForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let form = _self.addForm;
			let result = [];
			form.querySelectorAll('.checkbox').forEach( element => {
				element.value = element.checked
			})

			let data = new FormData(form);
			for (let [key, value] of data) {
				result.push({name:key, value:value});
			}

			PreloaderEsl.show(_self.addWrapperSelector);

			_self.changeAddForm(result);
		},

		changeAddForm: function (addForm) {
			let data = {};

			data.action = 'wc_esl_shipping_save_add_form';
			data.add_form = JSON.stringify(addForm);
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeAddForm.bind({
				_self: this
			}));
		},

		callbackChangeAddForm: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.addWrapperSelector);
		},

		submitExportForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let form = _self.exportForm;

			// У #eslExportForm две кнопки "Сохранить" в разных карточках — "Адрес
			// отправителя" сверху и "Настройки транспортных компаний" снизу (там поля
			// связаны через form="eslExportForm", а не вложенность). Прелоадер должен
			// появляться там, где реально нажали, а не всегда в верхнем блоке.
			let submitter = event.submitter;
			_self.exportPreloaderTarget = (submitter && submitter.closest(_self.carrierTabsWrapperSelector))
				? _self.carrierTabsWrapperSelector
				: _self.exportWrapperSelector;
			PreloaderEsl.show(_self.exportPreloaderTarget);

			// Сохранение вкладки — полная перезапись опции, поэтому перед сборкой
			// FormData нужно гарантированно догрузить "Дополнительные настройки" всех
			// служб, а не только той вкладки, которую мерчант успел открыть — иначе
			// настройки ещё не открытых вкладок будут потеряны при сохранении.
			_self.ensureAllCarrierExtraFieldsLoaded().then(function () {
				let result = [];
				let data = new FormData(form);
				for (let [key, value] of data) {
					result.push({name:key, value:value});
				}

				_self.changeExportForm(result);
			});
		},

		loadCarrierExtraFields: function (containerEl) {
			if (!containerEl || containerEl.getAttribute('data-loaded') === '1') {
				return Promise.resolve();
			}
			containerEl.setAttribute('data-loaded', '1');

			return new Promise(function (resolve) {
				let data = {};
				data.action = 'wc_esl_shipping_get_export_fields';
				data.type = containerEl.getAttribute('data-carrier');
				data.nonce = wc_esl_shipping_global.nonce;

				HttpClientEsl.post(data, function (result) {
					if (result && result.success === true) {
						containerEl.innerHTML = result.data;
						eslSyncAllVisibilityControllers();
					}
					resolve();
				});
			});
		},

		ensureAllCarrierExtraFieldsLoaded: function () {
			let _self = this;
			let promises = [];
			document.querySelectorAll(_self.carrierTabsWrapperSelector + ' .esl-carrier-extra-fields').forEach(function (containerEl) {
				promises.push(_self.loadCarrierExtraFields(containerEl));
			});
			return Promise.all(promises);
		},

		changeExportForm: function (exportForm) {
			let data = {};

			data.action = 'wc_esl_shipping_save_export_form';
			data.export_form = JSON.stringify(exportForm);
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeExportForm.bind({
				_self: this
			}));
		},

		callbackChangeExportForm: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.exportPreloaderTarget || _self.exportWrapperSelector);
		},

		statusSaveForm: function (event) {
			event.preventDefault();
			let _self = this._self;

			let form = sortable('.sortable', 'serialize')
			let length = form.length-1,
				element = null,
				elementParent,
				elementParentName,
				elementLength,
				elementItems,
				result = {}

			for (let i = 0; i <= length; i++) {
				let item,
					itemName,
					itemDesc

				element = form[i]
				elementParent = element.container.node
				elementParentName = elementParent.getAttribute("name")
				elementItems = element.items
				elementLength = elementItems.length
				result[elementParentName] = []

				for (let i = 0; i < elementLength; i++) {
					item = elementItems[i].node
					itemName = item.getAttribute("name")
					itemDesc = item.getAttribute("data-desc")
					result[elementParentName][i] = {'name': itemName, 'desc': itemDesc}
				}
			}

			PreloaderEsl.show(_self.statusWrapperSelector);
			_self.changeStatusSaveForm(result);
		},

		changeStatusSaveForm: function (statusForm) {
			let data = {};

			data.action = 'wc_esl_shipping_save_status_form';
			data.export_form = JSON.stringify(statusForm);
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeStatusSaveForm.bind({
				_self: this
			}));
		},

		callbackChangeStatusSaveForm: function (response) {
			let _self = this._self;
			//PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.statusWrapperSelector);
		},

		changeEnableFrameCheckbox: function (event) {
			let _self = this._self;
			let status = event.target.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeFrameStatus(status);
		},

		changeFrameStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_frame';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			HttpClientEsl.post(data, this.callbackChangeFrameStatus.bind({
				_self: this
			}));
		},

		callbackChangeFrameStatus: function (response) {
			let _self = this._self;
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_frame_enable) {
					const newValue = response.data.wc_esl_shipping_frame_enable === 1 || response.data.wc_esl_shipping_frame_enable === '1';
					_self.enableFrameCheckbox.checked = newValue;
				}
			} else {
				console.error('Ответ не содержит status');
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},


	};

	AdminSettingsEsl.init();
}

function sortableDelete(elem){
	elem.parentNode.remove();
}


(function( $ ) {

	$( document ).ready( function( e ) {
		$('#updateCache').click(function(e) {
			e.preventDefault();

			PreloaderEsl.show('.wc-esl-settings-general-options .card-body');

			$.ajax({
				method: 'POST',
				url: wc_esl_shipping_global.ajaxUrl,
				async: true,
				data: {
					action : 'wc_esl_update_cache',
					nonce : wc_esl_shipping_global.nonce,
				},
				dataType: 'json',
				success: function( response ) {

					PreloaderEsl.hide('.wc-esl-settings-general-options .card-body');
					PushEsl.addItem(response.success ? 'success' : 'error', response.msg);
				}
			});
		});

		$('#eslPayTypeForm').submit(function(e) {
			e.preventDefault();

			let formData = $(this).serialize();

			if(formData.length < 1) return;

			PreloaderEsl.show('#eslPayTypeForm');

			$.ajax({
				method: 'POST',
				url: wc_esl_shipping_global.ajaxUrl,
				async: true,
				data: {
					action : 'wc_esl_save_payment_method',
					nonce : wc_esl_shipping_global.nonce,
					formData
				},
				dataType: 'json',
				success: function( response ) {

					PreloaderEsl.hide('#eslPayTypeForm');
					PushEsl.addItem(response.success ? 'success' : 'error', response.msg);
				}
			});
		});


		$('.upload_image_button').click(function( event ){

			event.preventDefault();

			const button = $(this);

			const customUploader = wp.media({
				title: 'Выберите изображение для загрузки',
				library : {
					type : 'image'
				},
				button: {
					text: 'Выбрать изображение'
				},
				multiple: false
			});

			customUploader.on('select', function() {

				const image = customUploader.state().get('selection').first().toJSON();

				button.parent().prev().attr( 'src', image.url );
				button.prev().val( image.id );

			});

			customUploader.open();
		});

		$('.remove_image_button').click(function( event){

			event.preventDefault();

			const $button = $(this);

			window.EslConfirm.show( "Вы уверены?", function () {
				$button.parent().prev().attr('src', '');
				$button.prev().prev().val('');
			} );
		});
	});

})( jQuery );