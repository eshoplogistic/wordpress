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
					'combine_places' => array(
						'apply' => '',
						'dimensions' => '',
						'weight' => ''
					)
				),
				'delivery' => array(
					'tariff' => '',
				)
			);
		}
		if ( $name === 'delline' ) {
			$result = array(
				'sender'   => array(
					'requester'    => '',
					'counterparty' => '',
				),
				'order'    => array(
					'accept' => '',
				),
				'delivery' => array(
					'mode' => '',
					'produce_date' => '',
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
					'location_to' => array(
						'address' => array(
							'index' => ''
						)
					)
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
					)
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
					'identity' => array(
						'type' => '',
						'series' => '',
						'number' => '',
						'inn' => '',
						'kpp' => '',
					)
				),
				'receiver' => array(
					'legal' => '',
					'identity' => array(
						'type' => '',
						'series' => '',
						'number' => '',
						'inn' => '',
						'kpp' => '',
					)
				),
				'delivery' => array(
					'location_from' => array(
						'pick_up_data' => array(
							'date' => '',
							'time_from' => '',
							'time_to' => '',
							'lift' => '',
							'floor' => '',
						)
					)
				),
			);
		}

		return $result;
	}

	public function exportFields( $name, $shippingMethods = array(), $order = array() ) {
		$result = array();
		if ( $name === 'boxberry' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
			$result = array(
				'order' => array(
					'barcode||text||Штрих-код посылки'        => '',
					'type||select||Тип отправления'         => array(
						0 => 'Посылка',
						2 => 'Курьер Онлайн',
						3 => 'Посылка Онлайн',
						5 => 'Посылка 1й класс'
					),
					'packing_type||select||Тип упаковки' => array(
						1 => 'упаковка ИМ',
						2 => 'упаковка Boxberry',
					),
					'issue||select||Вид выдачи заказа'        => array(
						0 => 'выдача без вскрытия',
						1 => 'выдача со вскрытием и проверкой комплектности',
						2 => 'выдача части вложения'
					)
				),
				'order[combine_places]' => array(
					'apply||checkbox||Объединить все грузовые места в одно' => ($exportFormSettings['combine-places-apply'] == 'on')?'checked':'',
					'dimensions||text||Габариты итогового грузового места (Д*Ш*В)' => ($exportFormSettings['combine-places-dimensions'])??'',
					'weight||text||Вес итогового грузового места в кг' => ($exportFormSettings['combine-places-weight'])??''
				),
			);
		}
		if ( $name === 'sdek' ) {
			$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
			$tariffs          = $eshopLogisticApi->apiServiceTariffs( $name );
			$tariffs          = $tariffs->data();
			if ( isset( $shippingMethods['data']['terminal']['tariff'] ) || isset( $shippingMethods['tariff']['code'] ) ) {
				$selectedTariffCode = $shippingMethods['data']['terminal']['tariff']['code'] ?? $shippingMethods['tariff']['code'];
				if ( isset( $tariffs[ $selectedTariffCode ] ) ) {
					$value[ $selectedTariffCode ] = $tariffs[ $selectedTariffCode ];
					unset( $tariffs[ $selectedTariffCode ] );
					$tariffs = $value + $tariffs;
				}
			}
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
			$result = array(
				'order'    => array(
					'type||select||Тип заказа' => array(
						1 => array(
							'selected' => ($exportFormSettings['type-order-sdek'] == 1)??false,
							'text' => 'Интернет-магазин'
						),
						2 => array(
							'selected' => ($exportFormSettings['type-order-sdek'] == 2)??false,
							'text' => 'Доставка'
						),
					),
				),
				'order[combine_places]' => array(
					'apply||checkbox||Объединить все грузовые места в одно' => ($exportFormSettings['combine-places-apply'] == 'on')?'checked':'',
					'dimensions||text||Габариты итогового грузового места (Д*Ш*В)' => ($exportFormSettings['combine-places-dimensions'])??'',
					'weight||text||Вес итогового грузового места в кг' => ($exportFormSettings['combine-places-weight'])??''
				),
				'delivery' => array(
					'tariff||select||Тариф' => $tariffs,
				)
			);
		}
		if ( $name === 'delline' ) {
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');

			$result = array(
				'sender'   => array(
					'requester||text||Заказчик перевозки'    => ($exportFormSettings['sender-uid-delline'])??'',
					'counterparty||text||Отправитель' => ($exportFormSettings['sender-counter-delline'])??'',
				),
				'order'    => array(
					'accept||select||Принятие заказа в работу' => array(
						0 => 'Нет',
						1 => 'Да',
					),
				),
				'delivery' => array(
					'mode||select||Вид доставки' => array(
						'auto'    => 'Автодоставка',
						'express' => 'Экспресс-доставка',
						'letter'  => 'Письмо',
						'avia'    => 'Авиадоставка',
						'small'   => 'Доставка малогабаритного груза',
					),
					'produce_date||date||Дата передачи груза' => $produce_date,
				)
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
					'legal||select||Форма контрагента' => array(
						1   => 'Физическое лицо',
						2   => 'ИП',
						3   => 'Юридическое лицо',
					),
					'company||text||Название организации' => '',
				),
				'receiver[requisites]' => array(
					'inn||text||ИНН для юридического лица' => '',
					'kpp||text||КПП для юридического лица' => '',
					'unp||text||УПН' => '',
					'bin||text||БИН' => '',
				),
				'delivery' => array(
					'variant||select||Вариант доставки' => array(
						1 => 'стандарт',
						3 => 'экспресс',
					),
				),
				'delivery[location_from][pick_up_data]' => array(
					'date||date||Дата забора груза' => $produce_date,
					'time_from||date||Время начала периода' => $produce_date,
					'time_to||date||Время окончания периода' => $produce_date,
					'comment||text||Комментарий' => '',

				)
			);
		}

		if ( $name === 'postrf'){
			$eshopLogisticApi = new EshopLogisticApi( new WpHttpClient() );
			$tariffs          = $eshopLogisticApi->apiServiceTariffs( $name );
			$tariffs          = $tariffs->data();
			if ( isset( $shippingMethods['tariff'] ) ) {
				$selectedTariffCode = $shippingMethods['tariff']['code'];
				if ( isset( $tariffs[ $selectedTariffCode ] ) ) {
					$value[ $selectedTariffCode ] = $tariffs[ $selectedTariffCode ];
					unset( $tariffs[ $selectedTariffCode ] );
					$tariffs = $value + $tariffs;
				}
			}

			$index = '';
			if($order){
				$orderData = $order->get_data();
				$index = $orderData['billing']['postcode']??'';
			}

			$result = array(
				'delivery' => array(
					'tariff||select||Тариф' => $tariffs,
				),
				'delivery[location_to][address]' => array(
					'index||text||Индекс адреса доставки' => $index
				)
			);
		}

		if ( $name === 'pecom' ) {
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');

			$result = array(
				'sender[identity]'   => array(
					'type||select'    => array(
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
					),
					'series||text' => '',
					'number||text' => '',
					'date||date' => '',
				),
				'delivery' => array(
					'produce_date||date' => $produce_date,
				)
			);
		}

		if ( $name === 'halva' ) {
			$result = array(
				'order' => array(
					'packing||checkbox' => '',
				)
			);
		}

		if ( $name === 'magnit' ) {
			$optionsRepository = new OptionsRepository();
			$exportFormSettings = $optionsRepository->getOption('wc_esl_shipping_export_form');
			$result = array(
				'receiver' => array(
					'last_name||text||Фамилия получателя' => ''
				),
				'order[combine_places]' => array(
					'apply||checkbox||Объединить все грузовые места в одно' => ($exportFormSettings['combine-places-apply'] == 'on')?'checked':'',
					'dimensions||text||Габариты итогового грузового места (Д*Ш*В)' => ($exportFormSettings['combine-places-dimensions'])??'',
					'weight||text||Вес итогового грузового места в кг' => ($exportFormSettings['combine-places-weight'])??''
				),
			);
		}

		if ( $name === 'baikal' ) {
			$date = new DateTime();
			$date->modify('+1 day');
			$produce_date = $date->format('Y-m-d');

			$result = array(
				'sender' => array(
					'legal||select||Форма контрагента' => array(
						1   => 'Юридическое лицо',
						2   => 'Физическое лицо'
					),
				),
				'sender[identity]'   => array(
					'type||select||Тип организационно-правовой формы'    => array(
						1 => 'Физическое лицо',
						5 => 'ООО',
						9 => 'ИП',
						12 => 'АО',
					),
					'series||text||Серия документа для физического лица' => '',
					'number||text||Номер документа для физического лица' => '',
					'inn||text||ИНН для юридического лица' => '',
					'kpp||text||КПП для юридического лица' => '',
				),
				'receiver' => array(
					'legal||select||Форма контрагента' => array(
						1   => 'Юридическое лицо',
						2   => 'Физическое лицо'
					),
				),
				'receiver[identity]'   => array(
					'type||select||Тип организационно-правовой формы'    => array(
						1 => 'Физическое лицо',
						5 => 'ООО',
						9 => 'ИП',
						12 => 'АО',
					),
					'series||text||Серия документа для физического лица' => '',
					'number||text||Номер документа для физического лица' => '',
					'inn||text||ИНН для юридического лица' => '',
					'kpp||text||КПП для юридического лица' => '',
				),
				'delivery[location_from][pick_up_data]' => array(
					'date||date||Дата забора груза от отправителя' => $produce_date,
					'time_from||date||Время начала периода забора груза от отправителя' => $produce_date,
					'time_to||date||Время окончания периода забора груза от отправителя' => $produce_date,
					'lift||checkbox||Флаг наличия лифта' => '',
					'floor||text||Количество этажей, если нужен спуск/подъём' => '',

				)
			);
		}

		return $result;
	}

}
