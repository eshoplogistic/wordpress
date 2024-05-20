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
                    <input type="hidden" name="delivery_id" value="<?php echo mb_strtolower( $typeMethod['name'] ) ?>">
                    <input type="hidden" name="order_id" value="<?php echo $orderData['id'] ?>">
                    <input type="hidden" name="order_status" value="<?php echo $orderData['status'] ?>">
                    <input type="hidden" name="order_shipping_id" value="<?php echo $orderShippingId ?>">

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
                                       value="<?php echo ($addressShipping['terminal'])??'' ?>">
                            </div>

                            <div class="form-field">
                                <label class="label" for="terminal-address">Адрес ПВЗ:</label>
                                <input class="form-value" name="terminal-address" type="text"
                                       value="<?php echo ($addressShipping['terminal_address'])??'' ?>">
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
                                       value="<?php echo $orderData['shipping_total'] ?>">
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
								?>

                                <div class="form-field">
                                    <label class="label" for="<?php echo $name ?>"><?php echo $nameRu ?></label>
									<?php if ( $type === 'text' ): ?>
                                        <input class="form-value" name="<?php echo $nameArr?>[<?php echo $name ?>]" type="text"
                                               value="<?php echo $value?>">
									<?php endif; ?>
	                                <?php if ( $type === 'date' ): ?>
                                        <input class="form-value" name="<?php echo $nameArr?>[<?php echo $name ?>]" type="date"
                                               value="<?php echo $value?>">
	                                <?php endif; ?>
									<?php if ( $type === 'select' ): ?>
                                        <select name="<?php echo $nameArr?>[<?php echo $name ?>]" form="unloading_form"
                                                class="form-value">
											<?php foreach ( $value as $k => $v ):?>
                                                <?php if(is_array($v) && isset($v['text'])):?>
                                                    <option value="<?php echo $k ?>" <?php echo ($v['selected'] == true)?'selected':'' ?>><?php echo $v['text'] ?></option>
                                                <?php else: ?>
                                                    <option value="<?php echo $k ?>"><?php echo $v ?></option>
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
                                       value="<?php echo $address['first_name'] . ' ' . $address['last_name'] ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-phone">Телефон:</label>
                                <input class="form-value" name="receiver-phone" type="text"
                                       value="<?php echo $address['phone'] ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-region">Регион:</label>
                                <input class="form-value" name="receiver-region" type="text"
                                       value="<?php echo( $shippingMethods['debug']['shipping_route']['to']['region'] ?? '' ) ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-city">Населённый пункт:</label>
                                <input class="form-value" name="receiver-city" type="text"
                                       value="<?php echo $address['city'] ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-street">Улица:</label>
                                <input class="form-value" name="receiver-street" type="text"
                                       value="<?php echo $address['address_1'] ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-house">Здание:</label>
                                <input class="form-value" name="receiver-house" type="text">
                            </div>
                            <div class="form-field">
                                <label class="label" for="receiver-room">Квартира / офис:</label>
                                <input class="form-value" name="receiver-room" type="text">
                            </div>
                        </div>

                        <div class="form-box">
                            <div class="form-field">
                                <label class="label" for="sender-name">Имя:</label>
                                <input class="form-value" name="sender-name" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-name'] ) ) ? $exportFormSettings['sender-name'] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-phone">Телефон:</label>
                                <input class="form-value" name="sender-phone" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-phone'] ) ) ? $exportFormSettings['sender-phone'] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label">Способ доставки до терминала ТК:</label>
                                <select name="pick_up" form="unloading_form" class="form-value">
                                    <?php if($typeMethod['name'] != 'halva'): ?>
                                    <option value="0">Сами привезём на терминал транспортной компании</option>
                                    <?php endif; ?>
                                    <option value="1">Груз заберёт транспортная компания</option>
                                </select>
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-terminal">Код терминала:</label>
                                <input class="form-value" name="sender-terminal" type="text"
                                       value="<?php echo ( isset( $exportFormSettings[ 'sender-terminal-' . $typeMethod['name'] ] ) ) ? $exportFormSettings[ 'sender-terminal-' . $typeMethod['name'] ] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-region">Регион:</label>
                                <input class="form-value" name="sender-region" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-region'] ) ) ? $exportFormSettings['sender-region'] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-city">Населённый пункт:</label>
                                <input class="form-value" name="sender-city" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-city'] ) ) ? $exportFormSettings['sender-city'] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-street">Улица:</label>
                                <input class="form-value" name="sender-street" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-street'] ) ) ? $exportFormSettings['sender-street'] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-house">Здание:</label>
                                <input class="form-value" name="sender-house" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-house'] ) ) ? $exportFormSettings['sender-house'] : '' ?>">
                            </div>
                            <div class="form-field">
                                <label class="label" for="sender-room">Квартира / офис:</label>
                                <input class="form-value" name="sender-room" type="text"
                                       value="<?php echo ( isset( $exportFormSettings['sender-room'] ) ) ? $exportFormSettings['sender-room'] : '' ?>">
                            </div>
                        </div>

                    </section>

                    <section id="content3">
						<?php
						$eslTable->prepare_items( $orderItems );
						$eslTable->display();
						?>
                    </section>

                    <section id="content4">
						<?php if ( isset( $additionalFields ) && $additionalFields ): ?>
                            <div class="esl-box_add">
								<?php foreach ( $additionalFields as $key => $value ): ?>
                                    <p><?php echo ( $additionalFieldsRu[ $key ] ) ?? $key ?></p>
									<?php foreach ( $value as $k => $v ): ?>
                                        <div class="form-field_add">
                                            <label class="label" for="<?php echo $k ?>"><?php echo $v['name'] ?></label>
											<?php if ( $v['type'] === 'integer' ): ?>
                                                <input class="form-value_add" name="<?php echo $k ?>" type="number"
                                                       value="0" max="<?php echo $v['max_value'] ?>">
											<?php else: ?>
                                                <input class="form-value_add" name="<?php echo $k ?>" type="checkbox">
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

<input type="hidden" id="order_info_id" name="order_id" value="<?php echo $orderData['id'] ?>">
<input type="hidden" id="order_info_type" name="order_type" value="<?php echo mb_strtolower( $typeMethod['name'] ) ?>">
