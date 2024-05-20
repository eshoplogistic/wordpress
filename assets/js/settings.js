window.addEventListener('load', function(event) {
	eslRun()
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

		changeEnablePluginApiV2Checkbox: function (event) {
			let _self = this._self;
			let status = _self.enablePluginApiV2Checkbox.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changePluginApiV2Status(status);
		},

		changePluginApiV2Status: function (status) {
			let data = [];

			data['action'] = 'wc_esl_shipping_change_enable_plugin_api_v2';
			data['status'] = status;
			HttpClientEsl.post(data, this.callbackChangePluginApiV2Status.bind({
				_self: this
			}));
		},

		callbackChangePluginApiV2Status: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
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

		submitApiKeyWCartForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let apiKey = _self.apiKeyWCartInput.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeApiKeyWCart(apiKey);
		},

		changeApiKeyWCart: function (apiKey) {
			let data = [];

			data['action'] = 'wc_esl_shipping_save_api_key_wcart';
			data['api_key'] = apiKey;

			HttpClientEsl.post(data, this.callbackChangeApiKeyWCart.bind({
				_self: this
			}));
		},

		callbackChangeApiKeyWCart: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
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
			let data = [];

			data['action'] = 'wc_esl_shipping_save_api_key_ya';
			data['api_key_ya'] = apiKeyYa;

			HttpClientEsl.post(data, this.callbackChangeApiKey.bind({
				_self: this
			}));
		},

		callbackChangeApiKeyYa: function (response) {
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

		submitWidgetButForm: function (event) {
			event.preventDefault();

			let _self = this._self;
			let widgetBut = _self.widgetButInput.value;

			PreloaderEsl.show(_self.widgetWrapperSelector);

			_self.changeWidgetBut(widgetBut);
		},

		changeWidgetBut: function (widgetBut) {
			let data = [];

			data['action'] = 'wc_esl_shipping_save_widget_but';
			data['widget_but'] = widgetBut;

			HttpClientEsl.post(data, this.callbackChangeWidgetBut.bind({
				_self: this
			}));
		},

		callbackChangeWidgetBut: function (response) {
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


		changeDimensionMeasurementSelect: function (event) {
			let _self = this._self;
			let status = _self.dimensionMeasurement.value;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeDimensionMeasurementStatus(status);
		},

		changeDimensionMeasurementStatus: function (status) {
			let data = [];

			data['action'] = 'wc_esl_shipping_change_dimension_measurement';
			data['status'] = status;

			console.log(data)

			HttpClientEsl.post(data, this.callbackDimensionMeasurementStatus.bind({
				_self: this
			}));
		},

		callbackDimensionMeasurementStatus: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
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
			let data = [];

			data['action'] = 'wc_esl_shipping_save_add_form';
			data['add_form'] = JSON.stringify(addForm);

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
			let data = [];

			data['action'] = 'wc_esl_shipping_save_export_form';
			data['export_form'] = JSON.stringify(exportForm);

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
			let data = [];

			data['action'] = 'wc_esl_shipping_save_status_form';
			data['export_form'] = JSON.stringify(statusForm);

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
			let status = _self.enableFrameCheckbox.checked;

			PreloaderEsl.show(_self.generalOptionsWrapperSelector);

			_self.changeFrameStatus(status);
		},

		changeFrameStatus: function (status) {
			let data = [];

			data['action'] = 'wc_esl_shipping_change_enable_frame';
			data['status'] = status;

			HttpClientEsl.post(data, this.callbackChangeFrameStatus.bind({
				_self: this
			}));
		},

		callbackChangeFrameStatus: function (response) {
			let _self = this._self;
			PushEsl.addItem(response.status, response.msg);
			PreloaderEsl.hide(_self.generalOptionsWrapperSelector);
			console.log(response);
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