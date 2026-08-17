<?php

namespace eshoplogistic\WCEshopLogistic\Classes\Shipping;

use DateTime;
use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;

class ExportFileds {

	public function sendExportFields($name){
		$result = array();
		if ( $name === 'boxberry' ) {
			$result = array(
				'order' => array(
					'barcode' => '',
					'type' => '',
					'packing_type' => '',
					'issue'        => '',
					'combine_places' => array(
						'apply' => '',
						'dimensions' => '',
						'weight' => ''
					)
				)
			);
		}
		if ( $name === 'sdek' ) {
			$result = array(
				'order'    => array(
					'type' => '',
				),
				'receiver' => array(
					'type' => '',
				),
				'delivery' => array(
					'tariff' => '',
					'take_payment' => '',
					'delivery-custom-cost' => '',
				)
			);
		}
		if ( $name === 'delline' ) {
			$result = array(
				'sender'   => array(
					'requester'    => '',
					'counterparty' => '',
				),
				'receiver' => array(
					'legal' => '',
				),
				'order'    => array(
					'accept' => '',
					'payer' => '',
				),
				'delivery' => array(
					'mode' => '',
					'produce_date' => '',
					'location_from' => array(
						'pick_up_data' => array(
							'time_from' => '',
							'time_to' => '',
						)
					)
				)
			);
		}

		if ( $name === 'kit' ) {
			$result = array(
				'sender'   => array(
					'requester' => '',
				),
				'receiver' => array(
					'legal' => '',
					'company' => '',
					'requisites' => array(
						'inn' => '',
						'kpp' => '',
						'unp' => '',
						'bin' => '',
					),
				),
				'delivery' => array(
					'variant' => '',
					'location_from' => array(
						'pick_up_data' => array(
							'date' => '',
							'time_from' => '',
							'time_to' => '',
							'comment' => '',
						)
					)
				),
			);
		}

		if( $name === 'postrf'){
			$result = array(
				'delivery' => array(
					'tariff' => '',
					'take_payment' => '',
					'delivery-custom-cost' => '',
					'location_to' => array(
						'address' => array(
							'index' => ''
						)
					)
				),
			);
		}

		if ( $name === 'fivepost' ) {
			$result = array(
				'delivery' => array(
					'take_payment' => '',
					'delivery-custom-cost' => '',
				),
			);
		}

		if ( $name === 'yandex' ) {
			$result = array(
				'delivery' => array(
					'take_payment' => '',
					'delivery-custom-cost' => '',
				),
			);
		}

		if( $name === 'pecom'){
			$result = array(
				'sender' => array(
					'identity' => array(
						'type' => '',
						'series' => '',
						'number' => '',
						'date' => '',
						'first_name' => '',
						'last_name' => '',
						'patronymic' => '',
					),
					'requisites' => array(
						'name' => '',
						'inn' => '',
					),
				),
				'receiver' => array(
					'identity' => array(
						'type' => '',
						'passport_series' => '',
						'passport_number' => '',
						'passport_date_of_issue' => '',
						'passport_date_of_birth' => '',
						'passport_organization' => '',
					),
					'requisites' => array(
						'inn' => '',
						'kpp' => '',
					),
				),
				'order' => array(
					'payer' => '',
				),
				'delivery'   => array(
					'produce_date' => '',
				),
			);
		}

		if( $name === 'halva'){
			$result = array(
				'order' => array(
					'packing' => ''
				)
			);
		}

		if ( $name === 'magnit' ) {
			$result = array(
				'receiver' => array(
					'last_name' => ''
				),
				'order' => array(
					'combine_places' => array(
						'apply' => '',
						'dimensions' => '',
						'weight' => ''
					)
				)
			);
		}

		if( $name === 'baikal'){
			$result = array(
				'sender' => array(
					'legal' => '',
					'company' => '',
					'identity' => array(
						'type' => '',
						'series' => '',
						'number' => '',
					),
					'requisites' => array(
						'inn' => '',
						'kpp' => '',
					),
				),
				'receiver' => array(
					'identity' => array(
						'type' => '',
						'passport_series' => '',
						'passport_number' => '',
					),
					'requisites' => array(
						'inn' => '',
						'kpp' => '',
					),
				),
				'delivery' => array(
					'produce_date' => '',
					'location_from' => array(
						'pick_up_data' => array(
							'time_from' => '',
							'time_to' => '',
						)
					)
				),
				'order' => array(
					'content' => '',
					'payer' => '',
				),
			);
		}

		if ( $name === 'dpd' ) {
			$result = array(
				'receiver' => array(
					'email' => ''
				),
				'order' => array(
					'content' => '',
					'costly' => '',
					'combine_places' => array(
						'apply' => '',
						'dimensions' => '',
						'weight' => ''
					)
				),
				'delivery' => array(
					'produce_date' => '',
					'produce_time' => '',
					'tariff' => '',
				),
			);
		}

		if ( $name === 'integral' ) {
			$result = array(
				'delivery' => array(
					'variant' => '',
				),
			);
		}

		return $result;
	}

	/**
	 * Оборачивает список опций select'а (value => label) в единый формат
	 * value => array('text' => label, 'selected' => bool), где selected
	 * вычисляется по текущему значению из wc_esl_shipping_export_form.
	 * Один и тот же формат читают вкладка настроек и форма выгрузки заказа.
	 */
	private function selectOptions( array $labels, $currentValue ) {
		$options = array();
		foreach ( $labels as $value => $label ) {
			$options[ $value ] = array(
				'text'     => $label,
				'selected' => (string) $value === (string) $currentValue,
			);
		}
		return $options;
	}

	/**
	 * Определяет тариф, реально применённый к заказу — не "самый дешёвый по своему типу"
	 * (data.terminal/data.door), а тот, что покупатель подтвердил во всплывающем окне
	 * "Выберите тариф" виджета. Виджет frame-чекаута (Blocks) передаёт бэкенду только режим
	 * доставки (door/terminal) и итоговую цену — без кода тарифа (см.
	 * checkout_frame_block.js::buildLegacyShippingFrameData() и
	 * Base.php::calculate_shipping_frame()), поэтому сопоставляем цену конкретного тарифа из
	 * полного списка data.tariffs.{mode} со стоимостью доставки, фактически выставленной заказу.
	 *
	 * Возвращает array('code' => string, 'name' => string); пустые строки, если определить
	 * не удалось (например, заказ оформлен до появления data.tariffs в сессии).
	 */
	private function resolveOrderTariff( array $shippingMethods, $order, $deliveryType, array $tariffCatalog ) {
		$mode = ( $deliveryType === 'door' ) ? 'door' : 'terminal';

		$candidates = $shippingMethods['data']['tariffs'][ $mode ] ?? array();
		if ( is_array( $candidates ) && $candidates && is_a( $order, 'WC_Order' ) ) {
			$targetCost = (float) $order->get_shipping_total();
			foreach ( $candidates as $candidate ) {
				$price = $candidate['price']['value'] ?? null;
				if ( $price !== null && (float) $price === $targetCost ) {
					return array(
						'code' => (string) ( $candidate['tariff']['code'] ?? '' ),
						'name' => (string) ( $candidate['tariff']['name'] ?? '' ),
					);
				}
			}
		}

		// Фолбэк: заказы без сохранённого data.tariffs (оформлены до этого фикса, либо цена не
		// совпала ни с одним тарифом из списка) — прежнее поведение ("лучший по типу" тариф), а
		// также легаси (не-frame) чекаут, где на каждую службу+тип регистрируется свой метод с
		// единственным посчитанным тарифом (плоский ключ 'tariff', без вложенности в 'data').
		$fallbackCode = $shippingMethods['data'][ $mode ]['tariff']['code']
			?? $shippingMethods['tariff']['code']
			?? '';
		$fallbackName = $shippingMethods['data'][ $mode ]['tariff']['name']
			?? $shippingMethods['tariff']['name']
			?? '';
		if ( $fallbackName === '' && $fallbackCode !== '' ) {
			$fallbackName = $tariffCatalog[ $fallbackCode ] ?? '';
		}

		return array( 'code' => (string) $fallbackCode, 'name' => (string) $fallbackName );
	}

	public function exportFields( $name, $shippingMethods = array(), $order = array(), $deliveryType = '' ) {
		$result = array();
		if ( $name === 'boxberry' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				'order' => array(
					'barcode||text||Штрих-код посылки'        => ($exportFormSettings['order-barcode-boxberry']) ?? '',
					'type||select||Тип отправления'         => $this->selectOptions( array(
						0 => 'Посылка',
						2 => 'Курьер Онлайн',
						3 => 'Посылка Онлайн',
						5 => 'Посылка 1й класс'
					), $exportFormSettings['order-type-boxberry'] ?? '' ),
					'packing_type||select||Тип упаковки' => $this->selectOptions( array(
						1 => 'упаковка ИМ',
						2 => 'упаковка Boxberry',
					), $exportFormSettings['order-packing-type-boxberry'] ?? '' ),
					'issue||select||Вид выдачи заказа'        => $this->selectOptions( array(
						0 => 'выдача без вскрытия',
						1 => 'выдача со вскрытием и проверкой комплектности',
						2 => 'выдача части вложения'
					), $exportFormSettings['order-issue-boxberry'] ?? '' )
				),
				'order[combine_places]' => array(
					'apply||checkbox||Объединить все грузовые места в одно' => ($exportFormSettings['combine-places-apply-boxberry'] ?? '') == 'on' ? 'checked' : '',
					'dimensions||text||Габариты итогового грузового места (Д*Ш*В)' => ($exportFormSettings['combine-places-dimensions-boxberry']) ?? '',
					'weight||text||Вес итогового грузового места в кг' => ($exportFormSettings['combine-places-weight-boxberry']) ?? ''
				),
			);
		}
		if ( $name === 'sdek' ) {
			$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
			$tariffs          = $eshopLogisticApi->apiServiceTariffs( $name );
			$tariffs          = $tariffs->data();
			// Тариф не настраивается по умолчанию — показываем тот, что реально применён к заказу,
			// и запрещаем его менять в форме выгрузки, как в moj_sklad (см. resolveOrderTariff()).
			$tariffInfo = $this->resolveOrderTariff( $shippingMethods, $order, $deliveryType, $tariffs );
			$selectedTariffCode = $tariffInfo['code'];
			$selectedTariffLabel = $tariffInfo['name'];
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				'order'    => array(
					'type||select||Тип заказа' => $this->selectOptions( array(
						1 => 'Интернет-магазин',
						2 => 'Доставка',
					), $exportFormSettings['type-order-sdek'] ?? '' ),
				),
				'receiver' => array(
					'type||select||Тип получателя' => $this->selectOptions( array(
						1 => 'Физическое лицо',
						3 => 'Юридическое лицо',
						2 => 'ИП',
					), $exportFormSettings['receiver-type-sdek'] ?? '' ),
				),
				'delivery' => array(
					'tariffView||text||Тариф' => $selectedTariffLabel,
					'tariff||dnone' => $selectedTariffCode,
					'take_payment||checkbox||Взять оплату с получателя за доставку' => '',
					'delivery-custom-cost||number||Сумма к взятию с получателя' => '',
				)
			);
		}
		if ( $name === 'delline' ) {
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$opfCacheKey = WC_ESL_PREFIX . 'opf_types';
			$opfType = get_transient($opfCacheKey);
			if (false === $opfType) {
				$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
				$opfTypeResponse = $eshopLogisticApi->apiServiceOpf();
				if ($opfTypeResponse->hasErrors()) {
					$opfType = array();
				} else {
					$opfType = $opfTypeResponse->data();
					set_transient($opfCacheKey, $opfType, HOUR_IN_SECONDS);
				}
			}
			$opfDelline = array(0 => '- Не выбрано -');
			foreach ((array) $opfType as $key => $value) {
				if (!empty($value['services']['delline'])) {
					$opfDelline[$key] = $value['name'];
				}
			}

			$result = array(
				'sender'   => array(
					'requester||text||Заказчик перевозки'    => ($exportFormSettings['sender-uid-delline'])??'',
					'counterparty||text||Отправитель' => ($exportFormSettings['sender-counter-delline'])??'',
				),
				'receiver' => array(
					'legal||select||Тип получателя' => $this->selectOptions( array(
						1 => 'Физическое лицо',
						3 => 'Юридическое лицо',
						2 => 'ИП',
					), $exportFormSettings['receiver-legal-delline'] ?? '' ),
					'type||select||ОПФ получателя (для юр.лиц)' => $this->selectOptions( $opfDelline, $exportFormSettings['receiver-type-delline'] ?? '' ),
				),
				'order'    => array(
					'accept||select||Принятие заказа в работу' => $this->selectOptions( array(
						0 => 'Нет',
						1 => 'Да',
					), $exportFormSettings['order-accept-delline'] ?? '' ),
					'payer||select||Плательщик' => $this->selectOptions( array(
						'sender' => 'Отправитель',
						'receiver' => 'Получатель',
						'third' => 'Заказчик перевозки',
					), $exportFormSettings['order-payer-delline'] ?? '' ),
				),
				'delivery' => array(
					'mode||select||Вид доставки' => $this->selectOptions( array(
						'auto'    => 'Автодоставка',
						'express' => 'Экспресс-доставка',
						'letter'  => 'Письмо',
						'avia'    => 'Авиадоставка',
						'small'   => 'Доставка малогабаритного груза',
					), $exportFormSettings['delivery-mode-delline'] ?? '' ),
					'produce_date||date||Дата передачи груза' => $produce_date,
				),
				'delivery[location_from][pick_up_data]' => array(
					'time_from||time||Время забора груза c' => ($exportFormSettings['sender-time-from-delline'])??'',
					'time_to||time||Время забора груза до' => ($exportFormSettings['sender-time-to-delline'])??'',
				),
			);
		}

		if ( $name === 'kit' ) {
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				'sender'   => array(
					'requester||text||Название профиля отправителя'    => ($exportFormSettings['sender-uid-kit'])??'',
				),
				'receiver' => array(
					'legal||select||Форма контрагента' => $this->selectOptions( array(
						1   => 'Физическое лицо',
						2   => 'ИП',
						3   => 'Юридическое лицо',
					), $exportFormSettings['receiver-legal-kit'] ?? '' ),
					'company||text||Название организации' => ($exportFormSettings['receiver-company-kit']) ?? '',
				),
				'receiver[requisites]' => array(
					'inn||text||ИНН для юридического лица' => ($exportFormSettings['receiver-inn-kit']) ?? '',
					'kpp||text||КПП для юридического лица' => ($exportFormSettings['receiver-kpp-kit']) ?? '',
					'unp||text||УПН' => ($exportFormSettings['receiver-unp-kit']) ?? '',
					'bin||text||БИН' => ($exportFormSettings['receiver-bin-kit']) ?? '',
				),
				'delivery' => array(
					'variant||select||Вариант доставки' => $this->selectOptions( array(
						1 => 'стандарт',
						3 => 'экспресс',
					), $exportFormSettings['delivery-variant-kit'] ?? '' ),
				),
				'delivery[location_from][pick_up_data]' => array(
					'date||date||Дата забора груза' => $produce_date,
					'time_from||date||Время начала периода' => $produce_date,
					'time_to||date||Время окончания периода' => $produce_date,
					'comment||text||Комментарий' => ($exportFormSettings['pickup-comment-kit']) ?? '',

				)
			);
		}

		if ( $name === 'postrf'){
			$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
			$tariffs          = $eshopLogisticApi->apiServiceTariffs( $name );
			$tariffs          = $tariffs->data();
			// Тариф не настраивается по умолчанию — показываем тот, что реально применён к заказу,
			// и запрещаем его менять в форме выгрузки, как в moj_sklad (см. resolveOrderTariff()).
			$tariffInfo = $this->resolveOrderTariff( $shippingMethods, $order, $deliveryType, $tariffs );
			$selectedTariffCode = $tariffInfo['code'];
			$selectedTariffLabel = $tariffInfo['name'];

			$index = '';
			if($order){
				$orderData = $order->get_data();
				$index = $orderData['billing']['postcode']??'';
			}

			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				'delivery' => array(
					'tariffView||text||Тариф' => $selectedTariffLabel,
					'tariff||dnone' => $selectedTariffCode,
					'take_payment||checkbox||Взять оплату с получателя за доставку' => '',
					'delivery-custom-cost||number||Сумма к взятию с получателя' => '',
				),
				'delivery[location_to][address]' => array(
					'index||text||Индекс адреса доставки' => $index
				)
			);
		}

		if ( $name === 'fivepost' ) {
			$result = array(
				'delivery' => array(
					'take_payment||checkbox||Взять оплату с получателя за доставку' => '',
					'delivery-custom-cost||number||Сумма к взятию с получателя' => '',
				),
			);
		}

		if ( $name === 'yandex' ) {
			$result = array(
				'delivery' => array(
					'take_payment||checkbox||Взять оплату с получателя за доставку' => '',
					'delivery-custom-cost||number||Сумма к взятию с получателя' => '',
				),
			);
		}

		if ( $name === 'pecom' ) {
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');

			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				// Тип отправителя не отправляется в API (у ПЭК под тем же путём sender[identity][type]
				// уже занят "типом документа", см. ниже) — используется только для показа/скрытия
				// блоков "Данные отправителя" (юрлицо/ИП) и "Реквизиты организации" (физлицо) ниже.
				'sender-entity-type-pecom' => array(
					'value||select||Тип отправителя' => $this->selectOptions( array(
						1 => 'Юридическое лицо',
						2 => 'Индивидуальный предприниматель',
						3 => 'Физическое лицо',
					), $exportFormSettings['sender-entity-type-pecom'] ?? '' ),
				),
				'sender[identity]'   => array(
					'type||select||Тип документа отправителя'    => $this->selectOptions( array(
						10 => 'ПАСПОРТ ГРАЖДАНИНА РФ',
						1 => 'ПАСПОРТ ИНОСТРАННОГО ГРАЖДАНИНА',
						2 => 'РАЗРЕШЕННИЕ НА ВРЕМЕННОЕ ПРОЖИВАНИЕ',
						3 => 'ВОДИТЕЛЬСКОЕ УДОСТОВЕРЕНИЕ',
						4 => 'ВИД НА ЖИТЕЛЬСТВО',
						5 => 'ЗАГРАНИЧНЫЙ ПАСПОРТ',
						6 => 'УДОСТОВЕРЕНИЕ БЕЖЕНЦА',
						7 => 'ВРЕМЕННОЕ УДОСТОВЕРЕНИЕ ЛИЧНОСТИ ГРАЖДАНИНА РФ',
						8 => 'СВИДЕТЕЛЬСТВО О ПРЕДОСТАВЛЕНИИ ВРЕМЕННОГО УБЕЖИЩА НА ТЕРРИТОРИИ РФ',
						9 => 'ПАСПОРТ МОРЯКА',
						11 => 'СВИДЕТЕЛЬСТВО О РАССМОТРЕНИИ ХОДАТАЙСТВА О ПРИЗНАНИИ БЕЖЕНЦЕМ',
						12 => 'ВОЕННЫЙ БИЛЕТ',
					), $exportFormSettings['sender-identity-type-pecom'] ?? '' ),
					'series||text||Серия документа' => ($exportFormSettings['sender-identity-series-pecom']) ?? '',
					'number||text||Номер документа' => ($exportFormSettings['sender-identity-number-pecom']) ?? '',
					'date||date||Дата выдачи документа' => ($exportFormSettings['sender-identity-date-pecom']) ?? '',
					'first_name||text||Имя' => ($exportFormSettings['sender-identity-first-name-pecom']) ?? '',
					// В API ПЭК поля идентификации физлица смещены: identity.last_name — это
					// фактически отчество, а identity.patronymic — фамилия. Подписи полей ниже
					// отражают реальный смысл, а не буквальное название JSON-ключа. Ключи опций
					// (sender-identity-last-name-pecom / -patronymic-pecom) берутся по имени
					// JSON-поля, а не по подписи, чтобы не перепутать значения местами.
					'last_name||text||Отчество' => ($exportFormSettings['sender-identity-last-name-pecom']) ?? '',
					'patronymic||text||Фамилия' => ($exportFormSettings['sender-identity-patronymic-pecom']) ?? '',
				),
				'sender[requisites]' => array(
					'name||text||Наименование организации/ИП' => ($exportFormSettings['sender-requisites-name-pecom']) ?? '',
					'inn||text||ИНН отправителя' => ($exportFormSettings['sender-requisites-inn-pecom']) ?? '',
				),
				'receiver[identity]' => array(
					'type||select||Тип получателя' => $this->selectOptions( array(
						1 => 'Физическое лицо',
						2 => 'Индивидуальный предприниматель',
						3 => 'Юридическое лицо',
					), $exportFormSettings['receiver-identity-type-pecom'] ?? '' ),
					'passport_series||text||Серия паспорта получателя' => ($exportFormSettings['receiver-passport-series-pecom']) ?? '',
					'passport_number||text||Номер паспорта получателя' => ($exportFormSettings['receiver-passport-number-pecom']) ?? '',
					'passport_date_of_issue||date||Дата выдачи паспорта получателя' => ($exportFormSettings['receiver-passport-date-issue-pecom']) ?? '',
					'passport_date_of_birth||date||Дата рождения получателя' => ($exportFormSettings['receiver-passport-date-birth-pecom']) ?? '',
					'passport_organization||text||Кем выдан паспорт получателя' => ($exportFormSettings['receiver-passport-org-pecom']) ?? '',
				),
				'receiver[requisites]' => array(
					'inn||text||ИНН получателя' => ($exportFormSettings['receiver-requisites-inn-pecom']) ?? '',
					'kpp||text||КПП получателя' => ($exportFormSettings['receiver-requisites-kpp-pecom']) ?? '',
				),
				'order' => array(
					'payer||select||Плательщик' => $this->selectOptions( array(
						'sender' => 'Отправитель',
						'receiver' => 'Получатель',
					), $exportFormSettings['order-payer-pecom'] ?? '' ),
					'content||text||Характер груза' => ($exportFormSettings['order-content-pecom']) ?? '',
				),
				'delivery' => array(
					'produce_date||date||Дата передачи груза' => $produce_date,
				)
			);
		}

		if ( $name === 'halva' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
			$result = array(
				'order' => array(
					'packing||checkbox||Упаковка' => ($exportFormSettings['order-packing-halva'] ?? '') == 'on' ? 'checked' : '',
				)
			);
		}

		if ( $name === 'magnit' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
			$result = array(
				'receiver' => array(
					'last_name||text||Фамилия получателя' => ($exportFormSettings['receiver-last-name-magnit']) ?? ''
				),
				'order[combine_places]' => array(
					'apply||checkbox||Объединить все грузовые места в одно' => ($exportFormSettings['combine-places-apply-magnit'] ?? '') == 'on' ? 'checked' : '',
					'dimensions||text||Габариты итогового грузового места (Д*Ш*В)' => ($exportFormSettings['combine-places-dimensions-magnit']) ?? '',
					'weight||text||Вес итогового грузового места в кг' => ($exportFormSettings['combine-places-weight-magnit']) ?? ''
				),
			);
		}

		if ( $name === 'baikal' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');

			$senderLegalList = array(
				1 => 'Юридическое лицо',
				2 => 'Физическое лицо',
			);

			$senderOrgFormList = array(
				1 => 'Физическое лицо',
				5 => 'ООО',
				6 => 'ОАО',
				7 => 'ЗАО',
				8 => 'ПАО',
				9 => 'ИП',
				12 => 'АО',
			);

			// ОПФ (организационно-правовые формы) получателя — тот же справочник и кэш, что и у Деловых линий (см. блок delline выше).
			$opfCacheKey = WC_ESL_PREFIX . 'opf_types';
			$opfType = get_transient($opfCacheKey);
			if (false === $opfType) {
				$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
				$opfTypeResponse = $eshopLogisticApi->apiServiceOpf();
				if ($opfTypeResponse->hasErrors()) {
					$opfType = array();
				} else {
					$opfType = $opfTypeResponse->data();
					set_transient($opfCacheKey, $opfType, HOUR_IN_SECONDS);
				}
			}
			$opfBaikal = array('' => '- Не выбрано -');
			foreach ((array) $opfType as $key => $value) {
				if (!empty($value['services']['baikal'])) {
					$opfBaikal[$value['services']['baikal']] = $value['name'];
				}
			}

			$result = array(
				'sender' => array(
					'legal||select||Тип отправителя' => $this->selectOptions( $senderLegalList, $exportFormSettings['sender-type-baikal'] ?? '' ),
					'company||text||Наименование организации' => ($exportFormSettings['sender-company-baikal']) ?? '',
				),
				'sender[identity]' => array(
					'type||select||Правовая форма (ОПФ)' => $this->selectOptions( $senderOrgFormList, $exportFormSettings['sender-org-form-baikal'] ?? '' ),
					'series||text||Серия' => ($exportFormSettings['sender-identity-series-baikal']) ?? '',
					'number||text||Номер' => ($exportFormSettings['sender-identity-number-baikal']) ?? '',
				),
				'sender[requisites]' => array(
					'inn||text||ИНН' => ($exportFormSettings['sender-inn-baikal']) ?? '',
					'kpp||text||КПП' => ($exportFormSettings['sender-kpp-baikal']) ?? '',
				),
				'receiver[identity]' => array(
					'type||select||Тип получателя' => $this->selectOptions( $opfBaikal, $exportFormSettings['receiver-type-baikal'] ?? '' ),
					'passport_series||text||Серия паспорта' => ($exportFormSettings['receiver-passport-series-baikal']) ?? '',
					'passport_number||text||Номер паспорта' => ($exportFormSettings['receiver-passport-number-baikal']) ?? '',
				),
				'receiver[requisites]' => array(
					'inn||text||ИНН' => ($exportFormSettings['receiver-inn-baikal']) ?? '',
					'kpp||text||КПП' => ($exportFormSettings['receiver-kpp-baikal']) ?? '',
				),
				'delivery' => array(
					'produce_date||date||Дата передачи груза' => $produce_date,
				),
				'delivery[location_from][pick_up_data]' => array(
					'time_from||time||Интервал для забора груза c' => ($exportFormSettings['sender-time-from-baikal']) ?? '',
					'time_to||time||Интервал для забора груза до' => ($exportFormSettings['sender-time-to-baikal']) ?? '',
				),
				'order' => array(
					'content||text||Характер груза' => ($exportFormSettings['order-content-baikal']) ?? '',
					'payer||select||Плательщик за доставку' => $this->selectOptions( array(
						'sender' => 'Отправитель',
						'receiver' => 'Получатель',
					), $exportFormSettings['sender-payer-baikal'] ?? '' ),
				),
			);
		}

		if ( $name === 'dpd'){
			$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
			$tariffs          = $eshopLogisticApi->apiServiceTariffs( $name );
			$tariffs          = $tariffs->data();
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');
			// Тариф не настраивается по умолчанию — показываем тот, что реально применён к заказу,
			// и запрещаем его менять в форме выгрузки, как в moj_sklad (см. resolveOrderTariff()).
			$tariffInfo = $this->resolveOrderTariff( $shippingMethods, $order, $deliveryType, $tariffs );
			$selectedTariffCode = $tariffInfo['code'];
			$selectedTariffLabel = $tariffInfo['name'];

			$result = array(
				'receiver' => array(
					'email||text||Адрес электронной почты' => ($exportFormSettings['receiver-email-dpd']) ?? ''
				),
				'order' => array(
					'content||text||Содержимое отправления (что за товары)' => ($exportFormSettings['order-content-dpd']) ?? '',
					'costly||checkbox||Флаг «Ценный груз»' => ($exportFormSettings['order-costly-dpd'] ?? '') == 'on' ? 'checked' : '',
				),
				'order[combine_places]' => array(
					'apply||checkbox||Объединить все грузовые места в одно' => ($exportFormSettings['combine-places-apply-dpd'] ?? '') == 'on' ? 'checked' : '',
					'dimensions||text||Габариты итогового грузового места (Д*Ш*В)' => ($exportFormSettings['combine-places-dimensions-dpd']) ?? '',
					'weight||text||Вес итогового грузового места в кг' => ($exportFormSettings['combine-places-weight-dpd']) ?? ''
				),
				'delivery' => array(
					'produce_date||date||Дата приёма груза' => $produce_date,
					'produce_time||text||Интервал времени приёма груза (Пример: 9-18)' => ($exportFormSettings['delivery-produce-time-dpd']) ?? '',
					'tariffView||text||Тариф' => $selectedTariffLabel,
					'tariff||dnone' => $selectedTariffCode,
				),
			);
		}

		if ( $name === 'integral' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				'order' => array(
					'content||text||Характер груза' => ($exportFormSettings['order-content-integral']) ?? '',
				),
			);
		}

		return $result;
	}

    public function settingsExportForOneDelivery($name)
    {
        // Одинаковый набор полей "объединения мест" для всех служб, у которых он есть —
        // отличаются только сохранённые значения (свой плоский ключ на каждую службу).
        $carriersWithOneDelivery = array(
            'yandex', 'boxberry', 'sdek', 'fivepost', 'delline',
            'baikal', 'magnit', 'kit', 'postrf', 'dpd',
        );

        if ( ! in_array( $name, $carriersWithOneDelivery, true ) ) {
            return array();
        }

        $optionsRepository = new OptionsRepository();
        $exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

        $mergeInOne = ($exportFormSettings['merge-in-one-' . $name] ?? '') == 'on' ? 'checked' : '';

        return array(
            'hr' => array(
                'hr||hr||Объединение грузовых мест' => '',
            ),
            'export_stt_one_delivery' => array(
                'merge_in_one||checkbox||Вместо всех позиций заказа будет сформировано одно грузовое место с суммарной ценой и весом' => $mergeInOne,
                'default_stt_name||text||Название места||Товар' => ($exportFormSettings['default-stt-name-' . $name]) ?? 'Товар',
                'default_stt_one_delivery_width||number||Габариты по умолчанию (ширина)' => ($exportFormSettings['default-stt-width-' . $name]) ?? '',
                'default_stt_one_delivery_length||number||Габариты по умолчанию (длина)' => ($exportFormSettings['default-stt-length-' . $name]) ?? '',
                'default_stt_one_delivery_height||number||Габариты по умолчанию (высота)' => ($exportFormSettings['default-stt-height-' . $name]) ?? '',
            ),
        );
    }

	/**
	 * Карта "куда сохранять" для полей exportFields()/settingsExportForOneDelivery(), которые
	 * показываются во вкладке настроек ТК как "настройки по умолчанию" (ключ вида
	 * 'nameArr.name' => плоское имя в wc_esl_shipping_export_form). Поле, которого нет в этой
	 * карте для своей службы, в общем блоке вкладки НЕ рендерится: оно либо уже есть как
	 * отдельное поле вкладки (см. $carrierTabs в views/settings.php), либо это контекстное/
	 * вычисляемое значение конкретного заказа (даты "+1 день", индекс адреса заказа и т.п.),
	 * либо уже покрыто существующим чекбоксом "default-take-payment-{carrier}".
	 */
	private function tabFieldMap( $carrierSlug ) {
		$sttFields = array(
			'export_stt_one_delivery.merge_in_one'                      => 'merge-in-one-' . $carrierSlug,
			'export_stt_one_delivery.default_stt_name'                  => 'default-stt-name-' . $carrierSlug,
			'export_stt_one_delivery.default_stt_one_delivery_width'    => 'default-stt-width-' . $carrierSlug,
			'export_stt_one_delivery.default_stt_one_delivery_length'   => 'default-stt-length-' . $carrierSlug,
			'export_stt_one_delivery.default_stt_one_delivery_height'   => 'default-stt-height-' . $carrierSlug,
		);

		$map = array(
			'boxberry' => array(
				'order.barcode'                     => 'order-barcode-boxberry',
				'order.type'                        => 'order-type-boxberry',
				'order.packing_type'                => 'order-packing-type-boxberry',
				'order.issue'                        => 'order-issue-boxberry',
				'order[combine_places].apply'        => 'combine-places-apply-boxberry',
				'order[combine_places].dimensions'   => 'combine-places-dimensions-boxberry',
				'order[combine_places].weight'       => 'combine-places-weight-boxberry',
			),
			'sdek' => array(
				'receiver.type'                       => 'receiver-type-sdek',
			),
			'delline' => array(
				'receiver.legal'                      => 'receiver-legal-delline',
				'receiver.type'                       => 'receiver-type-delline',
				'order.accept'                         => 'order-accept-delline',
				'order.payer'                          => 'order-payer-delline',
				'delivery.mode'                        => 'delivery-mode-delline',
			),
			'kit' => array(
				'receiver.legal'                      => 'receiver-legal-kit',
				'receiver.company'                    => 'receiver-company-kit',
				'receiver[requisites].inn'            => 'receiver-inn-kit',
				'receiver[requisites].kpp'            => 'receiver-kpp-kit',
				'receiver[requisites].unp'            => 'receiver-unp-kit',
				'receiver[requisites].bin'            => 'receiver-bin-kit',
				'delivery.variant'                     => 'delivery-variant-kit',
				'delivery[location_from][pick_up_data].comment' => 'pickup-comment-kit',
			),
			'postrf' => array(),
			'fivepost' => array(),
			'yandex'   => array(),
			'pecom'    => array(
				'sender-entity-type-pecom.value'            => 'sender-entity-type-pecom',
				'sender[identity].type'                     => 'sender-identity-type-pecom',
				'sender[identity].series'                   => 'sender-identity-series-pecom',
				'sender[identity].number'                   => 'sender-identity-number-pecom',
				'sender[identity].date'                     => 'sender-identity-date-pecom',
				'sender[identity].first_name'               => 'sender-identity-first-name-pecom',
				'sender[identity].last_name'                => 'sender-identity-last-name-pecom',
				'sender[identity].patronymic'               => 'sender-identity-patronymic-pecom',
				'sender[requisites].name'                   => 'sender-requisites-name-pecom',
				'sender[requisites].inn'                    => 'sender-requisites-inn-pecom',
				'receiver[identity].type'                   => 'receiver-identity-type-pecom',
				'receiver[identity].passport_series'        => 'receiver-passport-series-pecom',
				'receiver[identity].passport_number'        => 'receiver-passport-number-pecom',
				'receiver[identity].passport_date_of_issue' => 'receiver-passport-date-issue-pecom',
				'receiver[identity].passport_date_of_birth' => 'receiver-passport-date-birth-pecom',
				'receiver[identity].passport_organization'  => 'receiver-passport-org-pecom',
				'receiver[requisites].inn'                  => 'receiver-requisites-inn-pecom',
				'receiver[requisites].kpp'                  => 'receiver-requisites-kpp-pecom',
				'order.payer'                                => 'order-payer-pecom',
				'order.content'                             => 'order-content-pecom',
			),
			'halva' => array(
				'order.packing' => 'order-packing-halva',
			),
			'magnit' => array(
				'receiver.last_name'                  => 'receiver-last-name-magnit',
				'order[combine_places].apply'         => 'combine-places-apply-magnit',
				'order[combine_places].dimensions'    => 'combine-places-dimensions-magnit',
				'order[combine_places].weight'        => 'combine-places-weight-magnit',
			),
			'baikal' => array(
				'receiver[identity].type'             => 'receiver-type-baikal',
				'receiver[identity].passport_series'  => 'receiver-passport-series-baikal',
				'receiver[identity].passport_number'  => 'receiver-passport-number-baikal',
				'receiver[requisites].inn'            => 'receiver-inn-baikal',
				'receiver[requisites].kpp'            => 'receiver-kpp-baikal',
				'order.payer'                          => 'sender-payer-baikal',
			),
			'dpd' => array(
				'receiver.email'                      => 'receiver-email-dpd',
				'order.content'                        => 'order-content-dpd',
				'order.costly'                          => 'order-costly-dpd',
				'order[combine_places].apply'          => 'combine-places-apply-dpd',
				'order[combine_places].dimensions'     => 'combine-places-dimensions-dpd',
				'order[combine_places].weight'         => 'combine-places-weight-dpd',
				'delivery.produce_time'                 => 'delivery-produce-time-dpd',
			),
			'integral' => array(),
		);

		$carrierMap = $map[ $carrierSlug ] ?? array();

		$carriersWithOneDelivery = array(
			'yandex', 'boxberry', 'sdek', 'fivepost', 'delline',
			'baikal', 'magnit', 'kit', 'postrf', 'dpd',
		);
		if ( in_array( $carrierSlug, $carriersWithOneDelivery, true ) ) {
			$carrierMap = array_merge( $carrierMap, $sttFields );
		}

		return $carrierMap;
	}

	/**
	 * Рендерит HTML "Дополнительных настроек" (бывший блок модалки "Настройка дополнительных
	 * услуг") для вкладки настроек службы доставки — вызывается лениво по AJAX при активации
	 * вкладки (см. Modules/Ajax.php::getExportExtraFields()).
	 */
	public function renderTabFields( $carrierSlug ) {
		$map           = $this->tabFieldMap( $carrierSlug );
		$fieldDelivery = $this->exportFields( $carrierSlug );
		$visibility    = $this->tabVisibilityRules( $carrierSlug, $fieldDelivery );

		$html  = $this->renderTabFieldGroup( $fieldDelivery, $map, $visibility );
		$html .= $this->renderTabFieldGroup( $this->settingsExportForOneDelivery( $carrierSlug ), $map, $visibility );

		if ( $html === '' ) {
			$html = '<p>' . esc_html__( 'Дополнительных настроек для этой службы нет.', 'eshoplogisticru' ) . '</p>';
		}

		return $html;
	}

	/**
	 * Правила показа/скрытия полей во вкладке — аналог механизма moj_sklad
	 * (Modules/Iframe.php: visible_by_params_parent[2] + wrapper_class), только через
	 * data-атрибуты вместо классов: у управляющего поля — data-esl-visible-target(2) +
	 * data-esl-visible-value(2), у управляемых полей — data-esl-key с именем "группы"
	 * (см. renderTabFieldGroup()/assets/js/settings.js::eslSyncVisibilityController()).
	 *
	 * 'groups' — flatKey => имя группы (для управляемых полей).
	 * 'controllers' — flatKey управляющего поля => до двух правил array('values' => [...], 'target' => 'имя группы').
	 */
	private function tabVisibilityRules( $carrierSlug, array $fieldDelivery ) {
		$groups      = array();
		$controllers = array();

		if ( $carrierSlug === 'baikal' ) {
			foreach ( array( 'sender-company-baikal', 'sender-org-form-baikal', 'sender-inn-baikal', 'sender-kpp-baikal' ) as $flatKey ) {
				$groups[ $flatKey ] = 'baikal-sender-org';
			}
			foreach ( array( 'sender-identity-series-baikal', 'sender-identity-number-baikal' ) as $flatKey ) {
				$groups[ $flatKey ] = 'baikal-sender-individual';
			}
			// Тип отправителя: 1 = юр.лицо (реквизиты организации), 2 = физ.лицо (документ).
			$controllers['sender-type-baikal'] = array(
				array( 'values' => array( '1' ), 'target' => 'baikal-sender-org' ),
				array( 'values' => array( '2' ), 'target' => 'baikal-sender-individual' ),
			);

			foreach ( array( 'receiver-passport-series-baikal', 'receiver-passport-number-baikal' ) as $flatKey ) {
				$groups[ $flatKey ] = 'baikal-receiver-individual';
			}
			foreach ( array( 'receiver-inn-baikal', 'receiver-kpp-baikal' ) as $flatKey ) {
				$groups[ $flatKey ] = 'baikal-receiver-org';
			}
			// Тип получателя — справочник ОПФ транспортной компании (динамический, из API):
			// код '1' = физлицо (паспорт), любой другой заполненный код = организация (ИНН/КПП).
			$opfOptions = $fieldDelivery['receiver[identity]']['type||select||Тип получателя'] ?? array();
			$orgCodes   = array();
			foreach ( array_keys( (array) $opfOptions ) as $code ) {
				if ( (string) $code !== '' && (string) $code !== '1' ) {
					$orgCodes[] = (string) $code;
				}
			}
			$controllers['receiver-type-baikal'] = array(
				array( 'values' => array( '1' ), 'target' => 'baikal-receiver-individual' ),
				array( 'values' => $orgCodes, 'target' => 'baikal-receiver-org' ),
			);
		}

		if ( $carrierSlug === 'pecom' ) {
			foreach ( array( 'sender-requisites-name-pecom', 'sender-requisites-inn-pecom' ) as $flatKey ) {
				$groups[ $flatKey ] = 'pecom-sender-org';
			}
			foreach ( array(
				'sender-identity-type-pecom', 'sender-identity-series-pecom', 'sender-identity-number-pecom',
				'sender-identity-date-pecom', 'sender-identity-first-name-pecom', 'sender-identity-last-name-pecom',
				'sender-identity-patronymic-pecom',
			) as $flatKey ) {
				$groups[ $flatKey ] = 'pecom-sender-identity';
			}
			// Тип отправителя: 1 = юр.лицо, 2 = ИП (реквизиты организации/ИП), 3 = физ.лицо (документ).
			$controllers['sender-entity-type-pecom'] = array(
				array( 'values' => array( '3' ), 'target' => 'pecom-sender-identity' ),
				array( 'values' => array( '1', '2' ), 'target' => 'pecom-sender-org' ),
			);

			foreach ( array(
				'receiver-passport-series-pecom', 'receiver-passport-number-pecom',
				'receiver-passport-date-issue-pecom', 'receiver-passport-date-birth-pecom', 'receiver-passport-org-pecom',
			) as $flatKey ) {
				$groups[ $flatKey ] = 'pecom-receiver-identity';
			}
			foreach ( array( 'receiver-requisites-inn-pecom', 'receiver-requisites-kpp-pecom' ) as $flatKey ) {
				$groups[ $flatKey ] = 'pecom-receiver-org';
			}
			// Тип получателя: 1 = физ.лицо (паспорт), 2 = ИП / 3 = юр.лицо (ИНН/КПП).
			$controllers['receiver-identity-type-pecom'] = array(
				array( 'values' => array( '1' ), 'target' => 'pecom-receiver-identity' ),
				array( 'values' => array( '2', '3' ), 'target' => 'pecom-receiver-org' ),
			);
		}

		return array( 'groups' => $groups, 'controllers' => $controllers );
	}

	private function renderTabFieldGroup( array $fieldGroups, array $map, array $visibility = array( 'groups' => array(), 'controllers' => array() ) ) {
		$html = '';
		foreach ( $fieldGroups as $nameArr => $arr ) {
			if ( $nameArr === 'hr' ) {
				foreach ( $arr as $key => $value ) {
					$parts = explode( '||', $key );
					$label = $parts[2] ?? '';
					if ( $label !== '' ) {
						$html .= '<h4>' . esc_html( $label ) . '</h4>';
					}
				}
				continue;
			}

			foreach ( $arr as $key => $value ) {
				$parts     = explode( '||', $key );
				$name      = $parts[0];
				$typeField = $parts[1] ?? 'text';
				$label     = $parts[2] ?? $name;

				$mapKey = $nameArr . '.' . $name;
				if ( ! isset( $map[ $mapKey ] ) ) {
					continue;
				}
				$flatKey = $map[ $mapKey ];

				$wrapperKey  = $visibility['groups'][ $flatKey ] ?? $flatKey;
				$controlAttr = $this->renderVisibilityControllerAttrs( $flatKey, $visibility['controllers'] );

				$html .= '<div class="form-group row align-items-center mb-3" data-esl-key="' . esc_attr( $wrapperKey ) . '">
					<label class="col-sm-5 col-form-label">' . esc_html( $label ) . '</label>
					<div class="col-sm-5">' . $this->renderTabFieldInput( $flatKey, $typeField, $value, $controlAttr ) . '</div>
				</div>';
			}
		}
		return $html;
	}

	private function renderVisibilityControllerAttrs( $flatKey, array $controllers ) {
		if ( ! isset( $controllers[ $flatKey ] ) ) {
			return '';
		}

		$attrs = '';
		foreach ( array_slice( $controllers[ $flatKey ], 0, 2 ) as $index => $rule ) {
			$suffix = $index === 0 ? '' : ( $index + 1 );
			$attrs .= ' data-esl-visible-target' . $suffix . '="' . esc_attr( $rule['target'] ) . '"';
			$attrs .= ' data-esl-visible-value' . $suffix . '="' . esc_attr( implode( ',', $rule['values'] ) ) . '"';
		}
		return $attrs;
	}

	private function renderTabFieldInput( $flatKey, $typeField, $value, $extraAttrs = '' ) {
		$name = esc_attr( $flatKey );

		switch ( $typeField ) {
			case 'select':
				$options = '';
				foreach ( (array) $value as $optValue => $optData ) {
					if ( is_array( $optData ) && isset( $optData['text'] ) ) {
						$options .= '<option value="' . esc_attr( $optValue ) . '" ' . selected( ! empty( $optData['selected'] ), true, false ) . '>' . esc_html( $optData['text'] ) . '</option>';
					} else {
						$options .= '<option value="' . esc_attr( $optValue ) . '">' . esc_html( $optData ) . '</option>';
					}
				}
				return '<select class="form-control" form="eslExportForm" name="' . $name . '"' . $extraAttrs . '>' . $options . '</select>';

			case 'checkbox':
				$checked = ( $value === 'checked' ) ? 'checked' : '';
				return '<input type="checkbox" form="eslExportForm" name="' . $name . '" ' . $checked . $extraAttrs . '>';

			case 'number':
				return '<input type="number" class="form-control" form="eslExportForm" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $extraAttrs . '>';

			case 'date':
				return '<input type="date" class="form-control" form="eslExportForm" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $extraAttrs . '>';

			case 'time':
				return '<input type="time" class="form-control" form="eslExportForm" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $extraAttrs . '>';

			default:
				return '<input type="text" class="form-control" form="eslExportForm" name="' . $name . '" value="' . esc_attr( $value ) . '"' . $extraAttrs . '>';
		}
	}

}
