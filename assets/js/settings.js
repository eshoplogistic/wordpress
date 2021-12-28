window.addEventListener('load', function(event) {
	eslRun()
});

function eslRun() {
	let AdminSettingsEsl = {
		enablePluginCheckbox: document.getElementById('enablePlugin'),
		enablePluginPriceShippingCheckbox: document.getElementById('enablePluginPriceShipping'),
		enablePluginLogCheckbox: document.getElementById('enablePluginLog'),
		apiKeyInput: document.getElementById('apiKeyInput'),
		apiKeyForm: document.getElementById('apiKeyForm'),
		widgetSecretCodeInput: document.getElementById('eslWidgetSecretCode'),
		widgetSecretCodeForm: document.getElementById('eslWidgetSecretCodeForm'),
		widgetKeyInput: document.getElementById('eslWidgetKey'),
		widgetKeyForm: document.getElementById('eslWidgetKeyForm'),
		eslPayTypeForm: document.getElementById('eslPayTypeForm'),
		generalOptionsWrapperSelector: '.wc-esl-settings-general-options .card-body',
		widgetWrapperSelector: '.wc-esl-settings-widget .card-body',

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

			this.widgetSecretCodeForm.addEventListener('submit', this.submitWidgetSecretCodeForm.bind({
				_self: this
			}));

			this.widgetKeyForm.addEventListener('submit', this.submitWidgetKeyForm.bind({
				_self: this
			}));
		},

		changeEnablePluginCheckbox: function (event) {
			let _self = this._self;
			let status = _self.enablePluginCheckbox.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginStatus(status);
		},

		changePluginStatus: function (status) {
			let data = [];

			data['action'] = 'wc_esl_shipping_change_enable_plugin';
			data['status'] = status;

			HttpClientEsl.post(data, this.callbackChangePluginStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginStatus: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
		},

		changeEnablePluginPriceShippingCheckbox: function (event) {
			let _self = this._self;
			let status = _self.enablePluginPriceShippingCheckbox.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginPriceShippingStatus(status);
		},

		changePluginPriceShippingStatus: function (status) {
			let data = [];

			data['action'] = 'wc_esl_shipping_change_enable_plugin_price_shipping';
			data['status'] = status;

			HttpClientEsl.post(data, this.callbackChangePluginPriceShippingStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginPriceShippingStatus: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
		},


		changeEnablePluginLogCheckbox: function (event) {
			let _self = this._self;
			let status = _self.enablePluginLogCheckbox.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginLogStatus(status);
		},

		changePluginLogStatus: function (status) {
			let data = [];

			data['action'] = 'wc_esl_shipping_change_enable_plugin_log';
			data['status'] = status;
			HttpClientEsl.post(data, this.callbackChangePluginLogStatus.bind({
				_self: this
			}));
		},

		callbackChangePluginLogStatus: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
		},

		submitApiKeyForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let apiKey = _self.apiKeyInput.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeApiKey(apiKey);
		},

		changeApiKey: function (apiKey) {
			let data = [];

			data['action'] = 'wc_esl_shipping_save_api_key';
			data['api_key'] = apiKey;

			HttpClientEsl.post(data, this.callbackChangeApiKey.bind({
				_self: this
			}));
		},

		callbackChangeApiKey: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
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
			let data = [];

			data['action'] = 'wc_esl_shipping_save_widget_secret_code';
			data['secret_code'] = secretCode;

			HttpClientEsl.post(data, this.callbackChangeWidgetSecretCode.bind({
				_self: this
			}));
		},

		callbackChangeWidgetSecretCode: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
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
			let data = [];

			data['action'] = 'wc_esl_shipping_save_widget_key';
			data['widget_key'] = widgetKey;

			HttpClientEsl.post(data, this.callbackChangeWidgetKey.bind({
				_self: this
			}));
		},

		callbackChangeWidgetKey: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
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
	};

	AdminSettingsEsl.init();
};

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
	});

})( jQuery );