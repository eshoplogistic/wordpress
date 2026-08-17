<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed

use eshoplogistic\WCEshopLogistic\Classes\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WP_List_Table' ) == false ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

$wc_esl_orderData          = isset( $wc_esl_orderData ) ? $wc_esl_orderData : array();
$wc_esl_orderItems         = isset( $wc_esl_orderItems ) ? $wc_esl_orderItems : array();
$wc_esl_orderShipping      = isset( $wc_esl_orderShipping ) ? $wc_esl_orderShipping : array();
$wc_esl_address            = isset( $wc_esl_address ) ? $wc_esl_address : array();
$wc_esl_addressShipping    = isset( $wc_esl_addressShipping ) ? $wc_esl_addressShipping : array();
$wc_esl_typeMethod         = isset( $wc_esl_typeMethod ) ? $wc_esl_typeMethod : array();
$wc_esl_additionalFields   = isset( $wc_esl_additionalFields ) ? $wc_esl_additionalFields : array();
$wc_esl_exportFormSettings = isset( $wc_esl_exportFormSettings ) ? $wc_esl_exportFormSettings : array();
$wc_esl_shippingMethods    = isset( $wc_esl_shippingMethods ) ? $wc_esl_shippingMethods : array();
$wc_esl_fieldDelivery      = isset( $wc_esl_fieldDelivery ) ? $wc_esl_fieldDelivery : array();
$wc_esl_orderShippingId    = isset( $wc_esl_orderShippingId ) ? $wc_esl_orderShippingId : '';
$wc_esl_infoApi            = isset( $wc_esl_infoApi ) ? $wc_esl_infoApi : '';
$wc_esl_addFieldSaved      = isset( $wc_esl_addFieldSaved ) ? $wc_esl_addFieldSaved : array();
$wc_esl_street             = isset( $wc_esl_street ) ? $wc_esl_street : '';
$wc_esl_building           = isset( $wc_esl_building ) ? $wc_esl_building : '';
$wc_esl_room               = isset( $wc_esl_room ) ? $wc_esl_room : '';
$wc_esl_district           = isset( $wc_esl_district ) ? $wc_esl_district : '';
$wc_esl_orderNumber        = isset( $wc_esl_orderNumber ) ? $wc_esl_orderNumber : '';

$wc_esl_fulfillment = false;
if(isset($wc_esl_infoApi['services']['pochtalion'])){
    if($wc_esl_typeMethod['name'] == 'sdek' || $wc_esl_typeMethod['name'] == 'boxberry' || $wc_esl_typeMethod['name'] == 'postrf')
	    $wc_esl_fulfillment = $wc_esl_infoApi['services']['pochtalion'];
}

$wc_esl_additionalFieldsRu = array(
	'packages'  => 'Упаковка',
	'cargo'     => 'Груз',
	'recipient' => 'Получатель',
	'other'     => 'Другие услуги',

);

$wc_esl_eslTable = new Table();
$wc_esl_eslTable->prepare_items( $wc_esl_orderItems, $wc_esl_typeMethod );
$wc_esl_placesColumns = $wc_esl_eslTable->get_columns();
$wc_esl_placesItems   = $wc_esl_eslTable->items;

// Сумма для сверки с суммой "Мест" при отправке формы (см. eslGetOrderSumMismatch в
// settings_unloading.js) — считаем от тех же данных, что и сама таблица "Места"
// (цена товара из каталога * кол-во), а не от итога заказа: итог заказа включает
// доставку, и сравнение с ним всегда давало бы ложное расхождение.
$wc_esl_orderSum = 0;
foreach ( (array) $wc_esl_placesItems as $wc_esl_placeRow ) {
	if ( ! $wc_esl_placeRow ) {
		continue;
	}
	$wc_esl_orderSum += (float) ( $wc_esl_placeRow['price'] ?? 0 ) * (float) ( $wc_esl_placeRow['quantity'] ?? 0 );
}
?>

<div id="modal-esl" class="modal-esl">
    <div class="modal_content">
        <div class="title">
            Выгрузка заказа на доставку
            <span class="close_modal_window">×</span>
        </div>

        <div class="content_inner">
            <main>

                <input id="tab1" type="radio" name="tabs" checked>
                <label for="tab1" name="tabLabel" class="tabLabel" title="Получатель">
                    <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-text-left" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M2 12.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 0 1h-7a.5.5 0 0 1-.5-.5m0-3a.5.5 0 0 1 .5-.5h11a.5.5 0 0 1 0 1h-11a.5.5 0 0 1-.5-.5"/>
                    </svg></span>
                </label>

                <input id="tab2" type="radio" name="tabs">
                <label for="tab2" name="tabLabel" class="tabLabel" title="Отправитель">
                    <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-people" viewBox="0 0 16 16">
                        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1zm-7.978-1L7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002-.014.002zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4m3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0M6.936 9.28a6 6 0 0 0-1.23-.247A7 7 0 0 0 5 9c-4 0-5 3-5 4q0 1 1 1h4.216A2.24 2.24 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816M4.92 10A5.5 5.5 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275ZM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0m3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4"/>
                    </svg></span>
                </label>

                <input id="tab3" type="radio" name="tabs">
                <label for="tab3" name="tabLabel" class="tabLabel" title="Дополнительные услуги">
                    <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-ui-checks" viewBox="0 0 16 16">
                        <path d="M7 2.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zM2 1a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2zm0 8a2 2 0 0 0-2 2v2a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2v-2a2 2 0 0 0-2-2zm.854-3.646a.5.5 0 0 1-.708 0l-1-1a.5.5 0 1 1 .708-.708l.646.647 1.646-1.647a.5.5 0 1 1 .708.708zm0 8a.5.5 0 0 1-.708 0l-1-1a.5.5 0 0 1 .708-.708l.646.647 1.646-1.647a.5.5 0 0 1 .708.708zM7 10.5a.5.5 0 0 1 .5-.5h7a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-7a.5.5 0 0 1-.5-.5zm0-5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 8a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5"/>
                    </svg></span>
                </label>

                <input id="tab4" type="radio" name="tabs">
                <label for="tab4" name="tabLabel" class="tabLabel" title="Места">
                    <span><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-cart" viewBox="0 0 16 16">
                        <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l1.313 7h8.17l1.313-7zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
                    </svg></span>
                </label>

                <input id="buttonModalUnload" type="button" class="button button-primary esl-unload-top" value="Выгрузить">

                <form action="#" id="unloading_form" class="unloading-form unloading-grid">
                    <input type="hidden" name="delivery_id" value="<?php echo esc_attr(mb_strtolower( isset($wc_esl_typeMethod['name']) ? $wc_esl_typeMethod['name'] : '' )); ?>">
                    <input type="hidden" name="order_id" value="<?php echo esc_attr(isset($wc_esl_orderData['id']) ? $wc_esl_orderData['id'] : ''); ?>">
                    <input type="hidden" name="order_status" value="<?php echo esc_attr(isset($wc_esl_orderData['status']) ? $wc_esl_orderData['status'] : ''); ?>">
                    <input type="hidden" name="order_shipping_id" value="<?php echo esc_attr($wc_esl_orderShippingId); ?>">
                    <input type="hidden" name="order_sum" value="<?php echo esc_attr($wc_esl_orderSum); ?>">

                    <section id="content1">

                        <div class="form-box">
                            <span class="form-box-title">Данные получателя</span>

                            <?php if($wc_esl_fulfillment): ?>
                                <div class="form-field checkbox-area">
                                    <label class="label" for="fulfillment">Выгружать заявки в фулфилмент «Почтальон»:</label>
                                    <input class="form-value" id="fulfillment" name="fulfillment" type="checkbox">
                                </div>
                            <?php endif; ?>

                            <?php if(mb_strtolower($wc_esl_typeMethod['name']) === 'integral'):
                                // Интеграл — агрегатор, реально везёт один из этих перевозчиков; вариант влияет
                                // на то, какая ТК обработает заявку на стороне сервиса.
                                $wc_esl_integralVariantList = array(
                                    'sdek' => 'СДЭК',
                                    'fivepost' => '5POST',
                                    'postrf' => 'Почта России',
                                );
                                $wc_esl_integralVariantDefault = $wc_esl_exportFormSettings['delivery-variant-integral'] ?? 'sdek';
                            ?>
                                <div class="form-field">
                                    <label class="label" for="integral-variant">Вариант доставки:</label>
                                    <select id="integral-variant" name="delivery[variant]" form="unloading_form" class="form-value">
                                        <?php foreach ($wc_esl_integralVariantList as $wc_esl_variantKey => $wc_esl_variantName): ?>
                                            <option value="<?php echo esc_attr($wc_esl_variantKey) ?>" <?php echo esc_attr($wc_esl_integralVariantDefault === $wc_esl_variantKey ? 'selected' : '') ?>><?php echo esc_html($wc_esl_variantName) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <div class="form-field">
                                <label class="label" for="delivery_type">Тип доставки:</label>
                                <select id="delivery_type" name="delivery_type" form="unloading_form" class="form-value">
                                    <option value="door" <?php echo esc_attr($wc_esl_typeMethod['type'] === 'door' ? 'selected' : '') ?>>
                                        Курьер
                                    </option>
                                    <option value="terminal" <?php echo esc_attr($wc_esl_typeMethod['type'] === 'terminal' ? 'selected' : '') ?>>
                                        Пункт самовывоза
                                    </option>
                                </select>
                            </div>

                            <div class="form-field esl-terminal-only">
                                <label class="label" for="terminal-code">Код ПВЗ:</label>
                                <input class="form-value" id="terminal-code" name="terminal-code" type="text"
                                       value="<?php echo esc_attr($wc_esl_addressShipping['terminal'] ?? '') ?>">
                            </div>

                            <div class="form-field esl-terminal-only">
                                <label class="label" for="terminal-address">Адрес ПВЗ:</label>
                                <input class="form-value" id="terminal-address" name="terminal-address" type="text"
                                       value="<?php echo esc_attr($wc_esl_addressShipping['terminal_address'] ?? '') ?>">
                            </div>

                            <div class="form-field">
                                <label class="label" for="receiver-name">Имя:</label>
                                <input class="form-value" id="receiver-name" name="receiver-name" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['first_name']) . ' ' . esc_attr($wc_esl_address['last_name']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-phone">Телефон:</label>
                                <input class="form-value" id="receiver-phone" name="receiver-phone" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['phone']) ?>">
                            </div>
                            <?php if ($wc_esl_typeMethod['name'] !== 'postrf'): ?>
                            <div class="form-field">
                                <label class="label" for="receiver-email">Электронная почта:</label>
                                <input class="form-value" id="receiver-email" name="receiver-email" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['email']) ?>">
                            </div>
                            <?php endif; ?>
                            <div class="form-field">
                                <label class="label" for="receiver-region">Регион:</label>
                                <input class="form-value" id="receiver-region" name="receiver-region" type="text"
                                       value="<?php echo( esc_attr($wc_esl_shippingMethods['debug']['shipping_route']['to']['region']) ?? '' ) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-city">Населённый пункт:</label>
                                <input class="form-value" id="receiver-city" name="receiver-city" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['city']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-district">Район:</label>
                                <input class="form-value" id="receiver-district" name="receiver-district" type="text" value="<?php echo esc_attr($wc_esl_district) ?>">
                            </div>
                            <div class="form-field esl-door-only">
                                <label class="label" for="receiver-street">Улица:</label>
                                <input class="form-value" id="receiver-street" name="receiver-street" type="text"
                                       value="<?php echo esc_attr($wc_esl_street) ?>">
                            </div>
                            <div class="form-field esl-door-only">
                                <label class="label" for="receiver-house">Здание:</label>
                                <input class="form-value" id="receiver-house" name="receiver-house" type="text" value="<?php echo esc_attr($wc_esl_building) ?>">
                            </div>
                            <div class="form-field esl-door-only">
                                <label class="label" for="receiver-room">Квартира / офис:</label>
                                <input class="form-value" id="receiver-room" name="receiver-room" type="text" value="<?php echo esc_attr($wc_esl_room) ?>">
                            </div>

                            <?php
                            // Способ оплаты по умолчанию настраивается в разрезе службы доставки (Настройки транспортных компаний).
                            $wc_esl_defaultPaymentType = $wc_esl_exportFormSettings['default-payment-type-' . mb_strtolower($wc_esl_typeMethod['name'])] ?? '';
                            ?>
                            <div class="form-field">
                                <label class="label" for="payment_type">Способ оплаты заказа:</label>
                                <select id="payment_type" name="payment_type" form="unloading_form" class="form-value">
                                    <option value="already_paid" <?php echo esc_attr($wc_esl_defaultPaymentType === 'already_paid' ? 'selected' : ''); ?>>Заказ уже оплачен</option>
                                    <option value="cash_on_receipt" <?php echo esc_attr($wc_esl_defaultPaymentType === 'cash_on_receipt' ? 'selected' : ''); ?>>Наличными при получении</option>
                                    <option value="card_on_receipt" <?php echo esc_attr($wc_esl_defaultPaymentType === 'card_on_receipt' ? 'selected' : ''); ?>>Картой при получении</option>
                                    <option value="cashless" <?php echo esc_attr($wc_esl_defaultPaymentType === 'cashless' ? 'selected' : ''); ?>>Безналичный расчет</option>
                                </select>
                            </div>

							<?php foreach ( $wc_esl_fieldDelivery as $wc_esl_nameArr => $wc_esl_arr ):
								// "Объединить все грузовые места в одно" настраивается только в настройках
								// службы доставки (вкладка ТК) — здесь, в форме выгрузки конкретного заказа,
								// не дублируется (было раньше под таблицей "Места", section#content4).
								if ( $wc_esl_nameArr === 'order[combine_places]' ) {
									continue;
								}
								?>

								<?php foreach ( $wc_esl_arr as $wc_esl_key => $wc_esl_value ):
								$wc_esl_explodeKey = explode( '||', $wc_esl_key );
								$wc_esl_name = $wc_esl_explodeKey[0];
								$wc_esl_type = $wc_esl_explodeKey[1];
								$wc_esl_nameRu = $wc_esl_explodeKey[2] ?? $wc_esl_name;
                                $wc_esl_styleForm = '';
                                $wc_esl_typeDelivery = mb_strtolower( $wc_esl_typeMethod['name']);
                                $wc_esl_nameFiledSaved = $wc_esl_nameArr.'['.$wc_esl_name.']';

                                if($wc_esl_type === 'checkbox')
	                                $wc_esl_styleForm = 'checkbox-area';

                                // «Взять оплату с получателя» — переключает видимость поля суммы, см. esl-take-payment-toggle в JS.
                                $wc_esl_wrapperId = '';
                                $wc_esl_wrapperStyle = '';
                                $wc_esl_startDisabled = false;
                                if($wc_esl_name === 'take_payment')
                                    $wc_esl_styleForm .= ' esl-take-payment-toggle';

                                // ПЭК: показ/скрытие блоков "Данные отправителя"/"Реквизиты организации" по
                                // типу отправителя, и паспорт/реквизиты получателя по типу получателя.
                                // См. esl-pecom-sender-type-toggle / esl-pecom-receiver-type-toggle в JS.
                                if($wc_esl_typeDelivery === 'pecom'){
                                    if($wc_esl_nameArr === 'sender[identity]')
                                        $wc_esl_styleForm .= ' esl-pecom-sender-identity';
                                    if($wc_esl_nameArr === 'sender[requisites]')
                                        $wc_esl_styleForm .= ' esl-pecom-sender-requisites';
                                    if($wc_esl_nameArr === 'receiver[identity]' && $wc_esl_name !== 'type')
                                        $wc_esl_styleForm .= ' esl-pecom-receiver-identity';
                                    if($wc_esl_nameArr === 'receiver[requisites]')
                                        $wc_esl_styleForm .= ' esl-pecom-receiver-requisites';
                                }

                                // Байкал Сервис: "Наименование организации"/ОПФ — только для отправителя-юрлица,
                                // серия/номер паспорта — только для отправителя-физлица; паспорт получателя —
                                // только для получателя-физлица, ИНН/КПП получателя — только для получателя-юрлица.
                                // См. eslSyncBaikalSenderLegalToggle / eslSyncBaikalReceiverTypeToggle в JS.
                                if($wc_esl_typeDelivery === 'baikal'){
                                    if(($wc_esl_nameArr === 'sender' && $wc_esl_name === 'company')
                                        || ($wc_esl_nameArr === 'sender[identity]' && $wc_esl_name === 'type')
                                        || $wc_esl_nameArr === 'sender[requisites]')
                                        $wc_esl_styleForm .= ' esl-baikal-sender-legal-org';
                                    if($wc_esl_nameArr === 'sender[identity]' && in_array($wc_esl_name, array('series', 'number'), true))
                                        $wc_esl_styleForm .= ' esl-baikal-sender-legal-individual';
                                    if($wc_esl_nameArr === 'receiver[identity]' && in_array($wc_esl_name, array('passport_series', 'passport_number'), true))
                                        $wc_esl_styleForm .= ' esl-baikal-receiver-individual';
                                    if($wc_esl_nameArr === 'receiver[requisites]')
                                        $wc_esl_styleForm .= ' esl-baikal-receiver-org';
                                }

                                if($wc_esl_name === 'delivery-custom-cost'){
                                    // Дефолт "взять оплату с получателя" настраивается в разрезе службы доставки
                                    // (Настройки транспортных компаний), а не хранится отдельно по полю формы.
                                    $wc_esl_takePaymentChecked = !empty($wc_esl_exportFormSettings['default-take-payment-' . $wc_esl_typeDelivery]);
                                    $wc_esl_startDisabled = !$wc_esl_takePaymentChecked;
                                    $wc_esl_wrapperId = 'esl-cost-toggle-'.esc_attr($wc_esl_nameArr);
                                    $wc_esl_wrapperStyle = $wc_esl_startDisabled ? 'display:none' : '';
                                }

                                // Уникальный id поля для связки label[for] с самим полем (для клика по подписи и a11y).
                                $wc_esl_fieldId = 'esl-' . trim(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($wc_esl_nameArr . '-' . $wc_esl_name)), '-');
								?>

                                <div class="form-field <?php echo esc_attr($wc_esl_styleForm); ?>"<?php echo $wc_esl_wrapperId ? ' id="' . esc_attr($wc_esl_wrapperId) . '"' : ''; ?><?php echo $wc_esl_wrapperStyle ? ' style="' . esc_attr($wc_esl_wrapperStyle) . '"' : ''; ?>>
									<?php if ( $wc_esl_type !== 'dnone' ): ?>
                                    <label class="label" for="<?php echo esc_attr($wc_esl_fieldId); ?>"><?php echo esc_html($wc_esl_nameRu); ?></label>
									<?php endif; ?>
									<?php if ( $wc_esl_type === 'text' ):
										// Тариф, полученный при расчёте доставки на чекауте (tariffView) — показываем, но не даём
										// менять (аналогично блокировке поля тарифа в moj_sklad); реальное значение уходит отдельным
										// полем 'tariff' (тип dnone), т.к. disabled-поля не попадают в форму при отправке.
										$wc_esl_textDisabled = ($wc_esl_name === 'tariffView') ? 'disabled' : '';
										?>
                                        <input class="form-value" id="<?php echo esc_attr($wc_esl_fieldId); ?>" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="text"
                                               value="<?php echo esc_attr($wc_esl_value)?>" <?php echo $wc_esl_textDisabled; ?>>
									<?php endif; ?>
	                                <?php if ( $wc_esl_type === 'number' ): ?>
                                        <input class="form-value" id="<?php echo esc_attr($wc_esl_fieldId); ?>" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="number"
                                               value="<?php echo esc_attr($wc_esl_value)?>" <?php echo $wc_esl_startDisabled ? 'disabled' : ''; ?>>
	                                <?php endif; ?>
	                                <?php if ( $wc_esl_type === 'time' ): ?>
                                        <input class="form-value" id="<?php echo esc_attr($wc_esl_fieldId); ?>" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="time"
                                               value="<?php echo esc_attr($wc_esl_value)?>">
	                                <?php endif; ?>
	                                <?php if ( $wc_esl_type === 'checkbox' ):
                                        $wc_esl_valueSaved = $wc_esl_value;
                                        if ($wc_esl_name === 'take_payment' && !empty($wc_esl_exportFormSettings['default-take-payment-' . $wc_esl_typeDelivery])) {
                                            // Значение по умолчанию из настроек транспортных компаний.
                                            $wc_esl_valueSaved = 'checked';
                                        }
                                        ?>
                                        <input class="form-value" id="<?php echo esc_attr($wc_esl_fieldId); ?>" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="checkbox" <?php echo esc_attr($wc_esl_valueSaved) ?>>
	                                <?php endif; ?>
	                                <?php if ( $wc_esl_type === 'date' ): ?>
                                        <input class="form-value" id="<?php echo esc_attr($wc_esl_fieldId); ?>" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="date"
                                               value="<?php echo esc_attr($wc_esl_value)?>">
	                                <?php endif; ?>
									<?php if ( $wc_esl_type === 'select' ): ?>
                                        <select id="<?php echo esc_attr($wc_esl_fieldId); ?>" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" form="unloading_form"
                                                class="form-value">
											<?php foreach ( $wc_esl_value as $wc_esl_k => $wc_esl_v ):?>
                                                <?php if(is_array($wc_esl_v) && isset($wc_esl_v['text'])): ?>
                                                    <option value="<?php echo esc_attr($wc_esl_k) ?>" <?php echo !empty($wc_esl_v['selected']) ? 'selected' : '' ?>><?php echo esc_html($wc_esl_v['text']) ?></option>
                                                <?php else: ?>
                                                    <option value="<?php echo esc_attr($wc_esl_k) ?>"><?php echo esc_html($wc_esl_v) ?></option>
                                                <?php endif; ?>
											<?php endforeach; ?>
                                        </select>
									<?php endif; ?>
									<?php if ( $wc_esl_type === 'dnone' ): ?>
                                        <input class="form-value" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="text"
                                               value="<?php echo esc_attr($wc_esl_value)?>" style="display:none" form="unloading_form">
									<?php endif; ?>
                                </div>
							    <?php endforeach; ?>
							<?php endforeach; ?>

                            <div class="form-field">
                                <label class="label" for="esl-unload-price">Стоимость доставки:</label>
                                <input class="form-value" id="esl-unload-price" name="esl-unload-price" type="number"
                                       value="<?php echo esc_attr($wc_esl_orderData['shipping_total']); ?>">
                            </div>

                            <?php
                            // dpd/fivepost/pecom не поддерживают комментарий к заказу. Яндекс.Доставка —
                            // только при курьерской доставке (для ПВЗ поле скрыто), как в moj_sklad.
                            $wc_esl_showComment = !in_array($wc_esl_typeMethod['name'], array('dpd', 'fivepost', 'pecom'), true);
                            $wc_esl_commentDoorOnly = $wc_esl_typeMethod['name'] === 'yandex';
                            ?>
                            <?php if ($wc_esl_showComment): ?>
                            <div class="form-field form-field-full<?php echo $wc_esl_commentDoorOnly ? ' esl-door-only' : ''; ?>">
                                <label class="label" for="comment">Комментарий:</label>
                                <textarea class="form-value" id="comment" name="comment"></textarea>
                            </div>
                            <?php endif; ?>
                        </div>

                    </section>

                    <section id="content2">
                        <div class="form-box">
                            <span class="form-box-title">Данные отправителя</span>
                            <div class="form-field">
                                <label class="label" for="sender-custom-order-id">Номер заказа для ТК:</label>
                                <input class="form-value" id="sender-custom-order-id" name="sender-custom-order-id" type="text" value="<?php echo esc_attr( $wc_esl_orderNumber ) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-name">Имя:</label>
                                <input class="form-value" id="sender-name" name="sender-name" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-name'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-name']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-phone">Телефон:</label>
                                <input class="form-value" id="sender-phone" name="sender-phone" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-phone'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-phone']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-email">Электронная почта:</label>
                                <input class="form-value" id="sender-email" name="sender-email" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-email'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-email']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="pick_up">Способ доставки до терминала ТК:</label>
								<?php $wc_esl_defaultPickUp = (string) ( $wc_esl_exportFormSettings['default-pick-up-' . $wc_esl_typeMethod['name']] ?? '' ); ?>
                                <select id="pick_up" name="pick_up" form="unloading_form" class="form-value">
                                    <?php if($wc_esl_typeMethod['name'] != 'halva'): ?>
                                    <option value="0" <?php echo ( $wc_esl_defaultPickUp === '0' ) ? 'selected' : ''?>>Сами привезём на терминал транспортной компании</option>
                                    <?php endif; ?>
                                    <option value="1" <?php echo ( $wc_esl_defaultPickUp === '1' ) ? 'selected' : ''?>>Груз заберёт транспортная компания</option>
                                </select>
                            </div>
                            <div class="form-field esl-pickup-terminal">
                                <label class="label" for="sender-terminal">Код терминала:</label>
                                <input class="form-value" id="sender-terminal" name="sender-terminal" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings[ 'sender-terminal-' . $wc_esl_typeMethod['name'] ] ) ) ? esc_attr($wc_esl_exportFormSettings[ 'sender-terminal-' . $wc_esl_typeMethod['name'] ]) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-region">Регион:</label>
                                <input class="form-value" id="sender-region" name="sender-region" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-region'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-region']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-city">Населённый пункт:</label>
                                <input class="form-value" id="sender-city" name="sender-city" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-city'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-city']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-street">Улица:</label>
                                <input class="form-value" id="sender-street" name="sender-street" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-street'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-street']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-house">Здание:</label>
                                <input class="form-value" id="sender-house" name="sender-house" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-house'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-house']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-room">Квартира / офис:</label>
                                <input class="form-value" id="sender-room" name="sender-room" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-room'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-room']) : '' ?>">
                            </div>
                        </div>

                    </section>

                    <section id="content4">
                        <div class="esl-table-scroll">
						<?php
						$wc_esl_placesDefaults = array(
							'product_id' => '',
							'name'       => '',
							'quantity'   => '1',
							'price'      => '0',
							'weight'     => '0',
							'width'      => '0',
							'length'     => '0',
							'height'     => '0',
						);
						?>
                            <div class="esl-places__main">
                                <button id="buttonModalUnloadAdd" type="button" class="button button-primary"><?php esc_html_e( 'Добавить место', 'eshoplogisticru' ); ?></button>
                                <table class="esl-places-table">
                                    <colgroup>
										<?php foreach ( $wc_esl_placesColumns as $wc_esl_colKey => $wc_esl_colLabel ): ?>
                                            <col class="esl-col-<?php echo esc_attr( $wc_esl_colKey ); ?>">
										<?php endforeach; ?>
                                    </colgroup>
                                    <thead>
                                        <tr>
											<?php foreach ( $wc_esl_placesColumns as $wc_esl_colKey => $wc_esl_colLabel ): ?>
                                                <th scope="col"><?php echo esc_html( $wc_esl_colLabel ); ?></th>
											<?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
										<?php if ( $wc_esl_placesItems ):
											$wc_esl_rowIndex = 0;
											foreach ( $wc_esl_placesItems as $wc_esl_rec ):
												if ( ! $wc_esl_rec ) {
													continue;
												}
												?>
                                                <tr data-number="<?php echo esc_attr( $wc_esl_rowIndex ); ?>">
													<?php foreach ( $wc_esl_placesColumns as $wc_esl_colKey => $wc_esl_colLabel ): ?>
														<?php if ( $wc_esl_colKey === 'delete' ): ?>
                                                            <td class="column-delete">
																<?php if ( $wc_esl_rowIndex !== 0 ): ?>
                                                                    <button type="button" class="esl-delete_table_elem" title="<?php esc_attr_e( 'Удалить место', 'eshoplogisticru' ); ?>">&times;</button>
																<?php endif; ?>
                                                            </td>
														<?php elseif ( $wc_esl_colKey === 'number' ): ?>
                                                            <td class="column-number"><span class="esl-place-number"><?php echo esc_html( $wc_esl_rowIndex + 1 ); ?></span></td>
														<?php else:
															$wc_esl_cellValue = isset( $wc_esl_rec[ $wc_esl_colKey ] ) ? $wc_esl_rec[ $wc_esl_colKey ] : '';
															if ( is_array( $wc_esl_cellValue ) || is_object( $wc_esl_cellValue ) ) {
																$wc_esl_cellValue = wp_json_encode( $wc_esl_cellValue );
															}
															?>
                                                            <td class="column-<?php echo esc_attr( $wc_esl_colKey ); ?>">
                                                                <input type="text" data-field="<?php echo esc_attr( $wc_esl_colKey ); ?>"
                                                                       name="products[<?php echo esc_attr( $wc_esl_rowIndex ); ?>][<?php echo esc_attr( $wc_esl_colKey ); ?>]"
                                                                       value="<?php echo esc_attr( stripslashes( (string) $wc_esl_cellValue ) ); ?>">
                                                            </td>
														<?php endif; ?>
													<?php endforeach; ?>
                                                </tr>
												<?php
												$wc_esl_rowIndex++;
											endforeach;
										endif; ?>
                                    </tbody>
                                </table>
                                <template class="esl-row-template">
                                    <tr>
										<?php foreach ( $wc_esl_placesColumns as $wc_esl_colKey => $wc_esl_colLabel ): ?>
											<?php if ( $wc_esl_colKey === 'delete' ): ?>
                                                <td class="column-delete"><button type="button" class="esl-delete_table_elem" title="<?php esc_attr_e( 'Удалить место', 'eshoplogisticru' ); ?>">&times;</button></td>
											<?php elseif ( $wc_esl_colKey === 'number' ): ?>
                                                <td class="column-number"><span class="esl-place-number"></span></td>
											<?php else: ?>
                                                <td class="column-<?php echo esc_attr( $wc_esl_colKey ); ?>">
                                                    <input type="text" data-field="<?php echo esc_attr( $wc_esl_colKey ); ?>"
                                                           value="<?php echo esc_attr( isset( $wc_esl_placesDefaults[ $wc_esl_colKey ] ) ? $wc_esl_placesDefaults[ $wc_esl_colKey ] : '' ); ?>">
                                                </td>
											<?php endif; ?>
										<?php endforeach; ?>
                                    </tr>
                                </template>
                            </div>
                        </div>
                    </section>

                    <section id="content3">
						<?php if ( isset( $wc_esl_additionalFields ) && $wc_esl_additionalFields ): ?>
                            <div class="esl-box_add">
								<?php foreach ( $wc_esl_additionalFields as $wc_esl_key => $wc_esl_value ):?>
                                    <p class="esl-box_add-title"><?php echo esc_html($wc_esl_additionalFieldsRu[ $wc_esl_key ] ?? $wc_esl_key) ?></p>
									<?php foreach ( $wc_esl_value as $wc_esl_k => $wc_esl_v ):
										if(!isset($wc_esl_v['name']))
											continue;

                                        $wc_esl_type = mb_strtolower( $wc_esl_typeMethod['name']);
										$wc_esl_valueSaved = '0';
										if(isset($wc_esl_addFieldSaved[$wc_esl_type][$wc_esl_k]) && $wc_esl_addFieldSaved[$wc_esl_type][$wc_esl_k] != '0'){
											$wc_esl_valueSaved = $wc_esl_addFieldSaved[$wc_esl_type][$wc_esl_k];
										}
                                        ?>
                                        <div class="form-field_add">
                                            <label class="label" for="<?php echo esc_attr($wc_esl_k) ?>"><?php echo esc_html($wc_esl_v['name']) ?></label>
											<?php if ( $wc_esl_v['type'] === 'integer' ): ?>
                                                <input class="form-value_add" id="<?php echo esc_attr($wc_esl_k) ?>" name="complement[<?php echo esc_attr($wc_esl_k) ?>]" type="number"
                                                       value="<?php echo esc_attr($wc_esl_valueSaved) ?>" max="<?php echo esc_attr($wc_esl_v['max_value']) ?>">
											<?php else:
												$wc_esl_check = '';
												if($wc_esl_valueSaved != '0')
													$wc_esl_check = 'checked="checked"';
                                                ?>
                                                <input class="form-value_add" id="<?php echo esc_attr($wc_esl_k) ?>" name="complement[<?php echo esc_attr($wc_esl_k) ?>]" type="checkbox" <?php echo esc_attr($wc_esl_check) ?>>
											<?php endif; ?>
                                        </div>
									<?php endforeach; ?>
								<?php endforeach; ?>
                            </div>
						<?php else: ?>
                            <p>Дополнительные услуги отсутствуют.</p>
						<?php endif; ?>
                    </section>
                </form>

            </main>

        </div>

    </div>
</div>

<div id="modal-esl-info" class="modal-esl">
    <div class="modal_content">
        <div class="title">
            <span class="esl-modal-title__text">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                    <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                    <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0"/>
                </svg>
                Информация о заказе
            </span>
            <span class="close_modal_window">×</span>
        </div>

        <div class="content_inner">
            <main>
                <p>Данные не загружены</p>
            </main>
        </div>
    </div>
</div>

<div id="modal-esl-confirm" class="modal-esl">
    <div class="modal_content">
        <div class="title">
            <span class="esl-modal-title__text">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-exclamation-triangle" viewBox="0 0 16 16">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.15.15 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.2.2 0 0 1-.054.06.1.1 0 0 1-.066.017H1.146a.1.1 0 0 1-.066-.017.2.2 0 0 1-.054-.06.18.18 0 0 1 .002-.183L7.884 2.073a.15.15 0 0 1 .054-.057m1.044-.45a1.13 1.13 0 0 0-1.96 0L.163 13.233c-.457.778.091 1.767.982 1.767h13.71c.891 0 1.439-.99.982-1.767z"/>
                    <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0M7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0z"/>
                </svg>
                Подтверждение
            </span>
            <span class="close_modal_window">×</span>
        </div>

        <div class="content_inner">
            <main>
                <p class="esl-confirm__message"></p>

                <div class="footer esl-confirm__footer">
                    <button type="button" class="button esl-confirm__cancel">Отмена</button>
                    <button type="button" class="button button-primary esl-confirm__ok">Продолжить</button>
                </div>
            </main>
        </div>
    </div>
</div>

<input type="hidden" id="order_info_id" name="order_id" value="<?php echo esc_attr($wc_esl_orderData['id']) ?>">
<input type="hidden" id="order_info_type" name="order_type" value="<?php echo esc_attr($wc_esl_typeMethod['name']) ?>">
