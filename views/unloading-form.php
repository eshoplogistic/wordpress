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

                <form action="#" id="unloading_form" class="unloading-form unloading-grid">
                    <input type="hidden" name="delivery_id" value="<?php echo esc_attr(mb_strtolower( isset($wc_esl_typeMethod['name']) ? $wc_esl_typeMethod['name'] : '' )); ?>">
                    <input type="hidden" name="order_id" value="<?php echo esc_attr(isset($wc_esl_orderData['id']) ? $wc_esl_orderData['id'] : ''); ?>">
                    <input type="hidden" name="order_status" value="<?php echo esc_attr(isset($wc_esl_orderData['status']) ? $wc_esl_orderData['status'] : ''); ?>">
                    <input type="hidden" name="order_shipping_id" value="<?php echo esc_attr($wc_esl_orderShippingId); ?>">
                    <input type="hidden" name="order_sum" value="<?php echo esc_attr(isset($wc_esl_orderData['total']) ? $wc_esl_orderData['total'] : ''); ?>">

                    <section id="content1">

                        <div class="form-box">
                            <span class="form-box-title">Данные получателя</span>

                            <?php if($wc_esl_fulfillment): ?>
                                <div class="form-field checkbox-area">
                                    <label class="label" for="terminal-code">Выгружать заявки в фулфилмент «Почтальон»:</label>
                                    <input class="form-value" name="fulfillment" type="checkbox">
                                </div>
                            <?php endif; ?>

                            <div class="form-field">
                                <label class="label">Тип доставки:</label>
                                <select name="delivery_type" form="unloading_form" class="form-value">
                                    <option value="door" <?php echo esc_attr($wc_esl_typeMethod['type'] === 'door' ? 'selected' : '') ?>>
                                        Курьер
                                    </option>
                                    <option value="terminal" <?php echo esc_attr($wc_esl_typeMethod['type'] === 'terminal' ? 'selected' : '') ?>>
                                        Пункт самовывоза
                                    </option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label class="label" for="terminal-code">Код ПВЗ:</label>
                                <input class="form-value" name="terminal-code" type="text"
                                       value="<?php echo esc_attr($wc_esl_addressShipping['terminal'] ?? '') ?>">
                            </div>

                            <div class="form-field">
                                <label class="label" for="terminal-address">Адрес ПВЗ:</label>
                                <input class="form-value" name="terminal-address" type="text"
                                       value="<?php echo esc_attr($wc_esl_addressShipping['terminal_address'] ?? '') ?>">
                            </div>

                            <div class="form-field">
                                <label class="label" for="receiver-name">Имя:</label>
                                <input class="form-value" name="receiver-name" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['first_name']) . ' ' . esc_attr($wc_esl_address['last_name']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-phone">Телефон:</label>
                                <input class="form-value" name="receiver-phone" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['phone']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-email">Электронная почта:</label>
                                <input class="form-value" name="receiver-email" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['email']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-region">Регион:</label>
                                <input class="form-value" name="receiver-region" type="text"
                                       value="<?php echo( esc_attr($wc_esl_shippingMethods['debug']['shipping_route']['to']['region']) ?? '' ) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-city">Населённый пункт:</label>
                                <input class="form-value" name="receiver-city" type="text"
                                       value="<?php echo esc_attr($wc_esl_address['city']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-district">Район:</label>
                                <input class="form-value" name="receiver-district" type="text" value="">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-street">Улица:</label>
                                <input class="form-value" name="receiver-street" type="text"
                                       value="<?php echo esc_attr($wc_esl_street) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-house">Здание:</label>
                                <input class="form-value" name="receiver-house" type="text" value="<?php echo esc_attr($wc_esl_building) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-room">Квартира / офис:</label>
                                <input class="form-value" name="receiver-room" type="text" value="<?php echo esc_attr($wc_esl_room) ?>">
                            </div>

                            <?php
                            // Способ оплаты по умолчанию настраивается в разрезе службы доставки (Настройки транспортных компаний).
                            $wc_esl_defaultPaymentType = $wc_esl_exportFormSettings['default-payment-type-' . mb_strtolower($wc_esl_typeMethod['name'])] ?? '';
                            ?>
                            <div class="form-field">
                                <label class="label">Способ оплаты заказа:</label>
                                <select name="payment_type" form="unloading_form" class="form-value">
                                    <option value="already_paid" <?php echo esc_attr($wc_esl_defaultPaymentType === 'already_paid' ? 'selected' : ''); ?>>Заказ уже оплачен</option>
                                    <option value="cash_on_receipt" <?php echo esc_attr($wc_esl_defaultPaymentType === 'cash_on_receipt' ? 'selected' : ''); ?>>Наличными при получении</option>
                                    <option value="card_on_receipt" <?php echo esc_attr($wc_esl_defaultPaymentType === 'card_on_receipt' ? 'selected' : ''); ?>>Картой при получении</option>
                                    <option value="cashless" <?php echo esc_attr($wc_esl_defaultPaymentType === 'cashless' ? 'selected' : ''); ?>>Безналичный расчет</option>
                                </select>
                            </div>

							<?php foreach ( $wc_esl_fieldDelivery as $wc_esl_nameArr => $wc_esl_arr ):
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
                                if($wc_esl_name === 'delivery-custom-cost'){
                                    $wc_esl_takePaymentChecked = isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameArr.'[take_payment]'])
                                        ? ($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameArr.'[take_payment]'] == 'on')
                                        : !empty($wc_esl_exportFormSettings['default-take-payment-' . $wc_esl_typeDelivery]);
                                    $wc_esl_startDisabled = !$wc_esl_takePaymentChecked;
                                    $wc_esl_wrapperId = 'esl-cost-toggle-'.esc_attr($wc_esl_nameArr);
                                    $wc_esl_wrapperStyle = $wc_esl_startDisabled ? 'display:none' : '';
                                }
								?>

                                <div class="form-field <?php echo esc_attr($wc_esl_styleForm); ?>"<?php echo $wc_esl_wrapperId ? ' id="' . esc_attr($wc_esl_wrapperId) . '"' : ''; ?><?php echo $wc_esl_wrapperStyle ? ' style="' . esc_attr($wc_esl_wrapperStyle) . '"' : ''; ?>>
                                    <label class="label" for="<?php echo esc_attr($wc_esl_name); ?>"><?php echo esc_html($wc_esl_nameRu); ?></label>
									<?php if ( $wc_esl_type === 'text' ):
                                        $wc_esl_valueSaved = '';
                                        if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved])){
                                            $wc_esl_valueSaved = $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved];
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="text"
                                               value="<?php echo esc_attr($wc_esl_valueSaved)?>">
									<?php endif; ?>
	                                <?php if ( $wc_esl_type === 'number' ):
                                        $wc_esl_valueSaved = '';
                                        if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved])){
                                            $wc_esl_valueSaved = $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved];
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="number"
                                               value="<?php echo esc_attr($wc_esl_valueSaved)?>" <?php echo $wc_esl_startDisabled ? 'disabled' : ''; ?>>
	                                <?php endif; ?>
	                                <?php if ( $wc_esl_type === 'time' ):
                                        $wc_esl_valueSaved = '';
                                        if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved])){
                                            $wc_esl_valueSaved = $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved];
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="time"
                                               value="<?php echo esc_attr($wc_esl_valueSaved)?>">
	                                <?php endif; ?>
	                                <?php if ( $wc_esl_type === 'checkbox' ):
                                        $wc_esl_valueSaved = '';
                                        if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved]) && $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved] == 'on'){
                                            $wc_esl_valueSaved = 'checked';
                                        } elseif ($wc_esl_name === 'take_payment' && !empty($wc_esl_exportFormSettings['default-take-payment-' . $wc_esl_typeDelivery])) {
                                            // Значение по умолчанию из настроек транспортных компаний, если по заказу ничего не сохранено.
                                            $wc_esl_valueSaved = 'checked';
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="checkbox" <?php echo esc_attr($wc_esl_valueSaved) ?>>
	                                <?php endif; ?>
	                                <?php if ( $wc_esl_type === 'date' ):
                                        $wc_esl_valueSaved = '';
                                        if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved])){
                                            $wc_esl_valueSaved = $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved];
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" type="date"
                                               value="<?php echo esc_attr($wc_esl_value)?>">
	                                <?php endif; ?>
									<?php if ( $wc_esl_type === 'select' ): ?>
                                        <select name="<?php echo esc_attr($wc_esl_nameArr)?>[<?php echo esc_attr($wc_esl_name) ?>]" form="unloading_form"
                                                class="form-value">
											<?php foreach ( $wc_esl_value as $wc_esl_k => $wc_esl_v ):?>
                                                <?php if(is_array($wc_esl_v) && isset($wc_esl_v['text'])):
                                                    $wc_esl_valueSaved = '';
                                                    if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved]) && $wc_esl_k == $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved]){
                                                        $wc_esl_valueSaved = 'selected';
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr($wc_esl_k) ?>" <?php echo esc_html($wc_esl_valueSaved) ?>><?php echo esc_html($wc_esl_v['text']) ?></option>
                                                <?php else:
                                                    $wc_esl_valueSaved = '';
                                                    if(isset($wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved]) && $wc_esl_k == $wc_esl_addFieldSaved[$wc_esl_typeDelivery][$wc_esl_nameFiledSaved]){
                                                        $wc_esl_valueSaved = 'selected';
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr($wc_esl_k) ?>" <?php echo esc_html($wc_esl_valueSaved) ?>><?php echo esc_html($wc_esl_v) ?></option>
                                                <?php endif; ?>
											<?php endforeach; ?>
                                        </select>
									<?php endif; ?>
                                </div>
							    <?php endforeach; ?>
							<?php endforeach; ?>

                            <div class="form-field">
                                <label class="label" for="esl-unload-price">Стоимость доставки:</label>
                                <input class="form-value" name="esl-unload-price" type="text"
                                       value="<?php echo esc_attr($wc_esl_orderData['shipping_total']); ?>">
                            </div>

                            <div class="form-field">
                                <label class="label">Комментарий:</label>
                                <textarea class="form-value" name="comment"></textarea>
                            </div>
                        </div>

                    </section>

                    <section id="content2">
                        <div class="form-box">
                            <span class="form-box-title">Данные отправителя</span>
                            <div class="form-field">
                                <label class="label" for="sender-custom-order-id">Свой номер заказа для ТК (необязательно):</label>
                                <input class="form-value" name="sender-custom-order-id" type="text" value="">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-name">Имя:</label>
                                <input class="form-value" name="sender-name" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-name'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-name']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-phone">Телефон:</label>
                                <input class="form-value" name="sender-phone" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-phone'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-phone']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-company">Название компании:</label>
                                <input class="form-value" name="sender-company" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-company'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-company']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-email">Электронная почта:</label>
                                <input class="form-value" name="sender-email" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-email'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-email']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label">Способ доставки до терминала ТК:</label>
                                <select name="pick_up" form="unloading_form" class="form-value">
                                    <?php if($wc_esl_typeMethod['name'] != 'halva'): ?>
                                    <option value="0" <?php echo ( isset( $wc_esl_addFieldSaved[$wc_esl_typeMethod['name']]['pick_up'] ) && $wc_esl_addFieldSaved[$wc_esl_typeMethod['name']]['pick_up']  == 0 ) ? 'selected' : ''?>>Сами привезём на терминал транспортной компании</option>
                                    <?php endif; ?>
                                    <option value="1" <?php echo ( isset( $wc_esl_addFieldSaved[$wc_esl_typeMethod['name']]['pick_up'] ) && $wc_esl_addFieldSaved[$wc_esl_typeMethod['name']]['pick_up']  == 1 ) ? 'selected' : ''?>>Груз заберёт транспортная компания</option>
                                </select>
                            </div>
                            <div class="form-field esl-pickup-terminal">
                                <label class="label" for="sender-terminal">Код терминала:</label>
                                <input class="form-value" name="sender-terminal" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings[ 'sender-terminal-' . $wc_esl_typeMethod['name'] ] ) ) ? esc_attr($wc_esl_exportFormSettings[ 'sender-terminal-' . $wc_esl_typeMethod['name'] ]) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-region">Регион:</label>
                                <input class="form-value" name="sender-region" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-region'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-region']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-city">Населённый пункт:</label>
                                <input class="form-value" name="sender-city" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-city'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-city']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-street">Улица:</label>
                                <input class="form-value" name="sender-street" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-street'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-street']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-house">Здание:</label>
                                <input class="form-value" name="sender-house" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-house'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-house']) : '' ?>">
                            </div>
                            <div class="form-field esl-pickup-address">
                                <label class="label" for="sender-room">Квартира / офис:</label>
                                <input class="form-value" name="sender-room" type="text"
                                       value="<?php echo ( isset( $wc_esl_exportFormSettings['sender-room'] ) ) ? esc_attr($wc_esl_exportFormSettings['sender-room']) : '' ?>">
                            </div>
                        </div>

                    </section>

                    <section id="content4">
						<?php
						$wc_esl_eslTable->prepare_items( $wc_esl_orderItems, $wc_esl_typeMethod );
						$wc_esl_eslTable->display();
						?>
                    </section>

                    <section id="content3">
						<?php if ( isset( $wc_esl_additionalFields ) && $wc_esl_additionalFields ): ?>
                            <div class="esl-box_add">
								<?php foreach ( $wc_esl_additionalFields as $wc_esl_key => $wc_esl_value ):?>
                                    <p><?php echo ( esc_html($wc_esl_additionalFieldsRu[ $wc_esl_key ]) ) ?? esc_html($wc_esl_key) ?></p>
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
                                                <input class="form-value_add" name="complement[<?php echo esc_attr($wc_esl_k) ?>]" type="number"
                                                       value="<?php echo esc_attr($wc_esl_valueSaved) ?>" max="<?php echo esc_attr($wc_esl_v['max_value']) ?>">
											<?php else:
												$wc_esl_check = '';
												if($wc_esl_valueSaved != '0')
													$wc_esl_check = 'checked="checked"';
                                                ?>
                                                <input class="form-value_add" name="complement[<?php echo esc_attr($wc_esl_k) ?>]" type="checkbox" <?php echo esc_attr($wc_esl_check) ?>>
											<?php endif; ?>
                                        </div>
									<?php endforeach; ?>
								<?php endforeach; ?>
                            </div>
						<?php else: ?>
                            <p>Дополнительные услуги отсутствуют.</p>
						<?php endif; ?>
                    </section>

                    <div class="footer">
                        <input id="buttonModalUnload" type="button" class="button button-primary" value="Выгрузить">
                    </div>
                </form>

            </main>

        </div>

    </div>
</div>

<div id="modal-esl-info" class="modal-esl">
    <div class="modal_content">
        <div class="title">
            Информация о заказе
            <span class="close_modal_window">×</span>
        </div>

        <div class="content_inner">
            <main>
                <p>Данные не загружены</p>
            </main>
        </div>
    </div>
</div>

<input type="hidden" id="order_info_id" name="order_id" value="<?php echo esc_attr($wc_esl_orderData['id']) ?>">
<input type="hidden" id="order_info_type" name="order_type" value="<?php echo esc_attr($wc_esl_typeMethod['name']) ?>">
