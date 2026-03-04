window.addEventListener('load', function(event) {
	eslRun()

	let modalAddField = document.getElementById("modal-esl-add-field")
	let contentAjax = document.getElementById("content-add-field_ajax")
	let span = document.getElementsByClassName("close_modal_window")[0]

	span.onclick = function () {
		modalAddField.style.display = "none"
	}

	window.onclick = function (event) {
		if (event.target === modalAddField) {
			modalAddField.style.display = "none"
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
		onCloseModal: function () {
			console.log('closeModal')
		},
	}

	let els_add_buttons = document.getElementsByClassName('wc-esl-add__button')
	if (els_add_buttons) {
		for (let i = 0; i < els_add_buttons.length; i++) {
			els_add_buttons[i].addEventListener('click', bindEvents.clickOnAddField, false);
		}

	}
});

function eslRun() {
	let AdminSettingsEsl = {
		enablePluginCheckbox: document.getElementById('enablePlugin'),
		enablePluginPriceShippingCheckbox: document.getElementById('enablePluginPriceShipping'),
		enablePluginLogCheckbox: document.getElementById('enablePluginLog'),
		enablePluginApiV2Checkbox: document.getElementById('enablePluginApiV2'),
		apiKeyInput: document.getElementById('apiKeyInput'),
		apiKeyForm: document.getElementById('apiKeyForm'),
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
		statusWrapperSelector: '.wc-esl-settings-status .card-body',
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

			this.enablePluginApiV2Checkbox.addEventListener('change', this.changeEnablePluginApiV2Checkbox.bind({
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
			}
			if(this.exportForm){
				this.exportForm.addEventListener('submit', this.submitExportForm.bind({
					_self: this
				}));
			}
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
			console.log(data)

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
			console.log(response);
		},

		changeEnablePluginCheckbox: function (event) {
			let _self = this._self;
			console.log('changeEnablePluginCheckbox - event.target.checked:', event.target.checked);
			let status = event.target.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginStatus(status);
		},

		changePluginStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			console.log('Отправляем данные:', data);

			HttpClientEsl.post(data, this.callbackChangePluginStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginStatus: function (response) {
			let _self = this._self;
			console.log('Ответ от сервера:', response);
			console.log('Тип ответа:', typeof response);
			console.log('status:', response?.status);
			console.log('msg:', response?.msg);
			
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
					console.log('Обновляем enablePluginCheckbox на:', newValue);
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
			console.log('changeEnablePluginPriceShippingCheckbox - event.target.checked:', status);

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginPriceShippingStatus(status);
		},

		changePluginPriceShippingStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin_price_shipping';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			console.log('Отправляем данные changePluginPriceShippingStatus:', data);

			HttpClientEsl.post(data, this.callbackChangePluginPriceShippingStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginPriceShippingStatus: function (response) {
			let _self = this._self;
			console.log('Ответ changePluginPriceShippingStatus:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				// После успешного сохранения обновляем состояние чекбокса в соответствии с ответом
				if (response.data && response.data.wc_esl_shipping_plugin_enable_price_shipping) {
					const newValue = response.data.wc_esl_shipping_plugin_enable_price_shipping === 1 || response.data.wc_esl_shipping_plugin_enable_price_shipping === '1';
					console.log('Обновляем чекбокс на:', newValue);
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
			console.log('changeEnablePluginLogCheckbox - event.target.checked:', status);

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginLogStatus(status);
		},

		changePluginLogStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin_log';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			console.log('Отправляем данные changePluginLogStatus:', data);

			HttpClientEsl.post(data, this.callbackChangePluginLogStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginLogStatus: function (response) {
			let _self = this._self;
			console.log('Ответ changePluginLogStatus:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_plugin_enable_log) {
					const newValue = response.data.wc_esl_shipping_plugin_enable_log === 1 || response.data.wc_esl_shipping_plugin_enable_log === '1';
					console.log('Обновляем чекбокс на:', newValue);
					_self.enablePluginLogCheckbox.checked = newValue;
				}
			} else {
				console.error('Ответ не содержит status');
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
		},

		changeEnablePluginApiV2Checkbox: function (event) {
			let _self = this._self;
			let status = event.target.checked;
			console.log('changeEnablePluginApiV2Checkbox - event.target.checked:', status);

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginApiV2Status(status);
		},

		changePluginApiV2Status: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_enable_plugin_api_v2';
			data.status = status ? 'true' : 'false';
			data.nonce = wc_esl_shipping_global.nonce;

			console.log('Отправляем данные changePluginApiV2Status:', data);

			HttpClientEsl.post(data, this.callbackChangePluginApiV2Status.bind({
				_self: this
			}));
		},

		callbackChangePluginApiV2Status: function (response) {
			let _self = this._self;
			console.log('Ответ changePluginApiV2Status:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_plugin_enable_api_v2) {
					const newValue = response.data.wc_esl_shipping_plugin_enable_api_v2 === 1 || response.data.wc_esl_shipping_plugin_enable_api_v2 === '1';
					console.log('Обновляем чекбокс на:', newValue);
					_self.enablePluginApiV2Checkbox.checked = newValue;
				}
			} else {
				console.error('Ответ не содержит status');
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			window.location.reload();
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
			console.log('callbackChangeApiKey - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				// Обновляем значение в форме
				if (response.data) {
					console.log('callbackChangeApiKey - обновляем apiKeyInput на:', response.data);
					_self.apiKeyInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
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
			console.log('callbackChangeApiKeyWCart - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				// Обновляем значение в форме
				if (response.data) {
					console.log('callbackChangeApiKeyWCart - обновляем apiKeyWCartInput на:', response.data);
					_self.apiKeyWCartInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
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
			console.log('callbackChangeApiKeyYa - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					console.log('callbackChangeApiKeyYa - обновляем apiKeyYaInput на:', response.data);
					_self.apiKeyYaInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
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
			console.log('callbackChangeWidgetSecretCode - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					console.log('callbackChangeWidgetSecretCode - обновляем widgetSecretCodeInput на:', response.data);
					_self.widgetSecretCodeInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.widgetWrapperSelector);
			console.log(response);
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
			console.log('callbackChangeWidgetKey - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					console.log('callbackChangeWidgetKey - обновляем widgetKeyInput на:', response.data);
					_self.widgetKeyInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.widgetWrapperSelector);
			console.log(response);
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
			console.log('callbackChangeWidgetBut - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data) {
					console.log('callbackChangeWidgetBut - обновляем widgetButInput на:', response.data);
					_self.widgetButInput.value = response.data;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.widgetWrapperSelector);
			console.log(response);
		},


		submitEslPayTypeForm: function (event) {
			event.preventDefault();

			let _self = this._self;

			let inputs = document.querySelectorAll('#eslPayTypeForm input[type="radio"]:checked');

			if(inputs.length > 0) {
				inputs.forEach(function (input) {
					console.log(input.name + ' = ' + input.value);
				});
			}
		},


		changeDimensionMeasurementSelect: function (event) {
			let _self = this._self;
			let status = event.target.value;
			console.log('changeDimensionMeasurementSelect - event.target.value:', status);

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeDimensionMeasurementStatus(status);
		},

		changeDimensionMeasurementStatus: function (status) {
			let data = {};

			data.action = 'wc_esl_shipping_change_dimension_measurement';
			data.status = status;
			data.nonce = wc_esl_shipping_global.nonce;

			console.log(data)

			HttpClientEsl.post(data, this.callbackDimensionMeasurementStatus.bind({
				_self: this
			}));
		},

		callbackDimensionMeasurementStatus: function (response) {
			let _self = this._self;
			console.log('callbackDimensionMeasurementStatus - response:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_dimension_measurement) {
					console.log('callbackDimensionMeasurementStatus - обновляем dimensionMeasurement на:', response.data.wc_esl_shipping_dimension_measurement);
					_self.dimensionMeasurement.value = response.data.wc_esl_shipping_dimension_measurement;
				}
			} else {
				PushEsl.addItem(response?.status || 'error', response?.msg || 'Ошибка сохранения');
			}
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
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
			console.log(response);
		},

		submitExportForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let form = _self.exportForm;
			let result = [];
			let data = new FormData(form);
			for (let [key, value] of data) {
				result.push({name:key, value:value});
			}

			PreloaderEsl.show(_self.exportWrapperSelector);

			_self.changeExportForm(result);
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
			PreloaderEsl.hide(_self.exportWrapperSelector);
			console.log(response);
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
			console.log(response);
		},

		changeEnableFrameCheckbox: function (event) {
			let _self = this._self;
			let status = event.target.checked;
			console.log('changeEnableFrameCheckbox - event.target.checked:', status);

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
			console.log('Ответ changeFrameStatus:', response);
			if (response && response.status === 'success') {
				PushEsl.addItem(response.status, response.msg);
				if (response.data && response.data.wc_esl_shipping_frame_enable) {
					const newValue = response.data.wc_esl_shipping_frame_enable === 1 || response.data.wc_esl_shipping_frame_enable === '1';
					console.log('Обновляем чекбокс на:', newValue);
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

					console.log(response);

					PreloaderEsl.hide('.wc-esl-settings-general-options .card-body');
					PushEsl.addItem(response.success ? 'success' : 'failed', response.msg);
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

					console.log(response);

					PreloaderEsl.hide('#eslPayTypeForm');
					PushEsl.addItem(response.success ? 'success' : 'failed', response.msg);
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

			if ( true == confirm( "Вы уверены?" ) ) {
				const src = $(this).parent().prev().data('src');
				$(this).parent().prev().attr('src', src);
				$(this).prev().prev().val('');
			}
		});
	});

})( jQuery );