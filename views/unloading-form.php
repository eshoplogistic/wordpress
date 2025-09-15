<?php

use eshoplogistic\WCEshopLogistic\Classes\Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WP_List_Table' ) == false ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

$orderData          = isset( $orderData ) ? $orderData : array();
$orderItems         = isset( $orderItems ) ? $orderItems : array();
$orderShipping      = isset( $orderShipping ) ? $orderShipping : array();
$address            = isset( $address ) ? $address : array();
$addressShipping    = isset( $addressShipping ) ? $addressShipping : array();
$typeMethod         = isset( $typeMethod ) ? $typeMethod : array();
$additionalFields   = isset( $additionalFields ) ? $additionalFields : array();
$exportFormSettings = isset( $exportFormSettings ) ? $exportFormSettings : array();
$shippingMethods    = isset( $shippingMethods ) ? $shippingMethods : array();
$fieldDelivery      = isset( $fieldDelivery ) ? $fieldDelivery : array();
$orderShippingId    = isset( $orderShippingId ) ? $orderShippingId : '';
$infoApi            = isset( $infoApi ) ? $infoApi : '';
$addFieldSaved      = isset( $addFieldSaved ) ? $addFieldSaved : array();
$street             = isset( $street ) ? $street : '';
$building           = isset( $building ) ? $building : '';
$room               = isset( $room ) ? $room : '';

$fulfillment = false;
if(isset($infoApi['services']['pochtalion'])){
    if($typeMethod['name'] == 'sdek' || $typeMethod['name'] == 'boxberry' || $typeMethod['name'] == 'postrf')
	    $fulfillment = $infoApi['services']['pochtalion'];
}

$additionalFieldsRu = array(
	'packages'  => 'Упаковка',
	'cargo'     => 'Груз',
	'recipient' => 'Получатель',
	'other'     => 'Другие услуги',

);

$eslTable = new Table();
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
                <label for="tab1" name="tabLabel" class="tabLabel"><span
                            class="dashicons dashicons-admin-generic"></span>Общее</label>

                <input id="tab2" type="radio" name="tabs">
                <label for="tab2" name="tabLabel" class="tabLabel"><span class="dashicons dashicons-admin-users"></span>Получатель
                    / Отправитель</label>

                <input id="tab3" type="radio" name="tabs">
                <label for="tab3" name="tabLabel" class="tabLabel"><span class="dashicons dashicons-admin-home"></span>Места</label>

                <input id="tab4" type="radio" name="tabs">
                <label for="tab4" name="tabLabel" class="tabLabel"><span
                            class="dashicons dashicons-screenoptions"></span>Дополнительные услуги</label>

                <form action="#" id="unloading_form" class="unloading-form unloading-grid">
                    <input type="hidden" name="delivery_id" value="<?php echo esc_attr(mb_strtolower( $typeMethod['name'] )); ?>">
                    <input type="hidden" name="order_id" value="<?php echo esc_attr($orderData['id']); ?>">
                    <input type="hidden" name="order_status" value="<?php echo esc_attr($orderData['status']); ?>">
                    <input type="hidden" name="order_shipping_id" value="<?php echo esc_attr($orderShippingId); ?>">

                    <section id="content1">

                        <div class="form-box">
                            <?php if($fulfillment): ?>
                                <div class="form-field checkbox-area">
                                    <label class="label" for="terminal-code">Выгружать заявки в фулфилмент «Почтальон»:</label>
                                    <input class="form-value" name="fulfillment" type="checkbox">
                                </div>
                            <?php endif; ?>

                            <div class="form-field">
                                <label class="label">Тип доставки:</label>
                                <select name="delivery_type" form="unloading_form" class="form-value">
                                    <option value="door" <?php echo ( $typeMethod['type'] === 'door' ) ? 'selected' : '' ?>>
                                        Курьер
                                    </option>
                                    <option value="terminal" <?php echo ( $typeMethod['type'] === 'terminal' ) ? 'selected' : '' ?>>
                                        Пункт самовывоза
                                    </option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label class="label" for="terminal-code">Код ПВЗ:</label>
                                <input class="form-value" name="terminal-code" type="text"
                                       value="<?php echo (esc_attr($addressShipping['terminal']))??'' ?>">
                            </div>

                            <div class="form-field">
                                <label class="label" for="terminal-address">Адрес ПВЗ:</label>
                                <input class="form-value" name="terminal-address" type="text"
                                       value="<?php echo (esc_attr($addressShipping['terminal_address']))??'' ?>">
                            </div>

                            <div class="form-field">
                                <label class="label">Способ оплаты заказа:</label>
                                <select name="payment_type" form="unloading_form" class="form-value">
                                    <option value="already_paid">Заказ уже оплачен</option>
                                    <option value="cash_on_receipt">Наличными при получении</option>
                                    <option value="card_on_receipt">Картой при получении</option>
                                    <option value="cashless">Безналичный расчет</option>
                                </select>
                            </div>

                            <div class="form-field">
                                <label class="label" for="esl-unload-price">Стоимость доставки:</label>
                                <input class="form-value" name="esl-unload-price" type="text"
                                       value="<?php echo esc_attr($orderData['shipping_total']); ?>">
                            </div>

                            <div class="form-field">
                                <label class="label">Комментарий:</label>
                                <textarea class="form-value" name="comment"></textarea>
                            </div>
                        </div>

                        <div class="form-box">
							<?php foreach ( $fieldDelivery as $nameArr => $arr ):
								?>

								<?php foreach ( $arr as $key => $value ):
								$explodeKey = explode( '||', $key );
								$name = $explodeKey[0];
								$type = $explodeKey[1];
								$nameRu = $explodeKey[2] ?? $name;
                                $styleForm = '';
                                $typeDelivery = mb_strtolower( $typeMethod['name']);
                                $nameFiledSaved = $nameArr.'['.$name.']';

                                if($type === 'checkbox')
	                                $styleForm = 'checkbox-area';
								?>

                                <div class="form-field <?php echo esc_attr($styleForm); ?>">
                                    <label class="label" for="<?php echo esc_attr($name); ?>"><?php echo esc_html($nameRu); ?></label>
									<?php if ( $type === 'text' ):
                                        $valueSaved = '';
                                        if(isset($addFieldSaved[$typeDelivery][$nameFiledSaved])){
                                            $valueSaved = $addFieldSaved[$typeDelivery][$nameFiledSaved];
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($nameArr)?>[<?php echo esc_attr($name) ?>]" type="text"
                                               value="<?php echo esc_attr($valueSaved)?>">
									<?php endif; ?>
	                                <?php if ( $type === 'checkbox' ):
                                        $valueSaved = '';
                                        if(isset($addFieldSaved[$typeDelivery][$nameFiledSaved]) && $addFieldSaved[$typeDelivery][$nameFiledSaved] == 'on'){
                                            $valueSaved = 'checked';
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($nameArr)?>[<?php echo esc_attr($name) ?>]" type="checkbox" <?php echo esc_attr($valueSaved) ?>>
	                                <?php endif; ?>
	                                <?php if ( $type === 'date' ):
                                        $valueSaved = '';
                                        if(isset($addFieldSaved[$typeDelivery][$nameFiledSaved])){
                                            $valueSaved = $addFieldSaved[$typeDelivery][$nameFiledSaved];
                                        }
                                        ?>
                                        <input class="form-value" name="<?php echo esc_attr($nameArr)?>[<?php echo esc_attr($name) ?>]" type="date"
                                               value="<?php echo esc_attr($value)?>">
	                                <?php endif; ?>
									<?php if ( $type === 'select' ): ?>
                                        <select name="<?php echo esc_attr($nameArr)?>[<?php echo esc_attr($name) ?>]" form="unloading_form"
                                                class="form-value">
											<?php foreach ( $value as $k => $v ):?>
                                                <?php if(is_array($v) && isset($v['text'])):
                                                    $valueSaved = '';
                                                    if(isset($addFieldSaved[$typeDelivery][$nameFiledSaved]) && $k == $addFieldSaved[$typeDelivery][$nameFiledSaved]){
                                                        $valueSaved = 'selected';
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr($k) ?>" <?php echo esc_html($valueSaved) ?>><?php echo esc_html($v['text']) ?></option>
                                                <?php else:
                                                    $valueSaved = '';
                                                    if(isset($addFieldSaved[$typeDelivery][$nameFiledSaved]) && $k == $addFieldSaved[$typeDelivery][$nameFiledSaved]){
                                                        $valueSaved = 'selected';
                                                    }
                                                    ?>
                                                    <option value="<?php echo esc_attr($k) ?>" <?php echo esc_html($valueSaved) ?>><?php echo esc_html($v) ?></option>
                                                <?php endif; ?>
											<?php endforeach; ?>
                                        </select>
									<?php endif; ?>
                                </div>
							    <?php endforeach; ?>
							<?php endforeach; ?>
                        </div>

                    </section>

                    <section id="content2">
                        <div class="form-box">
                            <div class="form-field">
                                <label class="label" for="receiver-name">Имя:</label>
                                <input class="form-value" name="receiver-name" type="text"
                                       value="<?php echo esc_attr($address['first_name']) . ' ' . esc_attr($address['last_name']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-phone">Телефон:</label>
                                <input class="form-value" name="receiver-phone" type="text"
                                       value="<?php echo esc_attr($address['phone']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-email">Электронная почта:</label>
                                <input class="form-value" name="receiver-email" type="text"
                                       value="<?php echo esc_attr($address['email']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-region">Регион:</label>
                                <input class="form-value" name="receiver-region" type="text"
                                       value="<?php echo( esc_attr($shippingMethods['debug']['shipping_route']['to']['region']) ?? '' ) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-city">Населённый пункт:</label>
                                <input class="form-value" name="receiver-city" type="text"
                                       value="<?php echo esc_attr($address['city']) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-street">Улица:</label>
                                <input class="form-value" name="receiver-street" type="text"
                                       value="<?php echo esc_attr($street) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-house">Здание:</label>
                                <input class="form-value" name="receiver-house" type="text" value="<?php echo esc_attr($building) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-room">Квартира / офис:</label>
                                <input class="form-value" name="receiver-room" type="text" value="<?php echo esc_attr($room) ?>">
                            </div>
                        </div>

                        <div class="form-box">
                            <div class="form-field">
                                <label class="label" for="sender-name">Имя:</label>
                                <input class="form-value" name="sender-name" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-name'] ) ) ? esc_attr($exportFormSettings['sender-name']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-phone">Телефон:</label>
                                <input class="form-value" name="sender-phone" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-phone'] ) ) ? esc_attr($exportFormSettings['sender-phone']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-company">Название компании:</label>
                                <input class="form-value" name="sender-company" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-company'] ) ) ? esc_attr($exportFormSettings['sender-company']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-email">Электронная почта:</label>
                                <input class="form-value" name="sender-email" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-email'] ) ) ? esc_attr($exportFormSettings['sender-email']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label">Способ доставки до терминала ТК:</label>
                                <select name="pick_up" form="unloading_form" class="form-value">
                                    <?php if($typeMethod['name'] != 'halva'): ?>
                                    <option value="0" <?php echo ( isset( $addFieldSaved[$typeMethod['name']]['pick_up'] ) && $addFieldSaved[$typeMethod['name']]['pick_up']  == 0 ) ? 'selected' : ''?>>Сами привезём на терминал транспортной компании</option>
                                    <?php endif; ?>
                                    <option value="1" <?php echo ( isset( $addFieldSaved[$typeMethod['name']]['pick_up'] ) && $addFieldSaved[$typeMethod['name']]['pick_up']  == 1 ) ? 'selected' : ''?>>Груз заберёт транспортная компания</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-terminal">Код терминала:</label>
                                <input class="form-value" name="sender-terminal" type="text"
                                       value="<?php echo ( isset( $exportFormSettings[ 'sender-terminal-' . $typeMethod['name'] ] ) ) ? esc_attr($exportFormSettings[ 'sender-terminal-' . $typeMethod['name'] ]) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-region">Регион:</label>
                                <input class="form-value" name="sender-region" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-region'] ) ) ? esc_attr($exportFormSettings['sender-region']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-city">Населённый пункт:</label>
                                <input class="form-value" name="sender-city" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-city'] ) ) ? esc_attr($exportFormSettings['sender-city']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-street">Улица:</label>
                                <input class="form-value" name="sender-street" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-street'] ) ) ? esc_attr($exportFormSettings['sender-street']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-house">Здание:</label>
                                <input class="form-value" name="sender-house" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-house'] ) ) ? esc_attr($exportFormSettings['sender-house']) : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-room">Квартира / офис:</label>
                                <input class="form-value" name="sender-room" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-room'] ) ) ? esc_attr($exportFormSettings['sender-room']) : '' ?>">
                            </div>
                        </div>

                    </section>

                    <section id="content3">
						<?php
						$eslTable->prepare_items( $orderItems, $typeMethod );
						$eslTable->display();
						?>
                    </section>

                    <section id="content4">
						<?php if ( isset( $additionalFields ) && $additionalFields ): ?>
                            <div class="esl-box_add">
								<?php foreach ( $additionalFields as $key => $value ):?>
                                    <p><?php echo ( esc_html($additionalFieldsRu[ $key ]) ) ?? esc_html($key) ?></p>
									<?php foreach ( $value as $k => $v ):
										if(!isset($v['name']))
											continue;

                                        $type = mb_strtolower( $typeMethod['name']);
										$valueSaved = '0';
										if(isset($addFieldSaved[$type][$k]) && $addFieldSaved[$type][$k] != '0'){
											$valueSaved = $addFieldSaved[$type][$k];
										}
                                        ?>
                                        <div class="form-field_add">
                                            <label class="label" for="<?php echo esc_attr($k) ?>"><?php echo esc_html($v['name']) ?></label>
											<?php if ( $v['type'] === 'integer' ): ?>
                                                <input class="form-value_add" name="<?php echo esc_attr($k) ?>" type="number"
                                                       value="<?php echo esc_attr($valueSaved) ?>" max="<?php echo esc_attr($v['max_value']) ?>">
											<?php else:
												$check = '';
												if($valueSaved != '0')
													$check = 'checked="checked"';
                                                ?>
                                                <input class="form-value_add" name="<?php echo esc_attr($k) ?>" type="checkbox" <?php echo esc_attr($check) ?>>
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

<input type="hidden" id="order_info_id" name="order_id" value="<?php echo esc_attr($orderData['id']) ?>">
<input type="hidden" id="order_info_type" name="order_type" value="<?php echo esc_attr($typeMethod['name']) ?>">
