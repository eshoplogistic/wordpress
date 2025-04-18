<?php

use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$optionsRepository = new OptionsRepository();
$moduleVersion     = $optionsRepository->getOption( 'wc_esl_shipping_plugin_enable_api_v2' );
$eshopLogisticApi  = new EshopLogisticApi( new WpHttpClient() );
if ( ! did_action( 'wp_enqueue_media' ) ) {
	wp_enqueue_media();
}

$WC_Checkout = new WC_Checkout();
$list_fields = $WC_Checkout->get_checkout_fields();

$plugin_enable                = isset( $plugin_enable ) ? $plugin_enable : '0';
$plugin_enable_price_shipping = isset( $plugin_enable_price_shipping ) ? $plugin_enable_price_shipping : '1';
$plugin_enable_log            = isset( $plugin_enable_log ) ? $plugin_enable_log : '0';
$plugin_enable_api_v2         = isset( $plugin_enable_api_v2 ) ? $plugin_enable_api_v2 : '0';
$api_key                      = ! empty( $api_key ) ? $api_key : '';
$api_key_wcart                = ! empty( $api_key_wcart ) ? $api_key_wcart : '';
$api_key_ya                   = ! empty( $api_key_ya ) ? $api_key_ya : '';
$paymentMethods               = isset( $paymentMethods ) ? $paymentMethods : [];
$secret_code                  = ! empty( $secret_code ) ? $secret_code : '';
$widget_key                   = ! empty( $widget_key ) ? $widget_key : '';
$widget_but                   = ! empty( $widget_but ) ? $widget_but : 'Рассчитать доставку';
$dimension_measurement        = ! empty( $dimension_measurement ) ? $dimension_measurement : 'cm';
$add_form                     = ! empty( $add_form ) ? $add_form : [];
$export_form                  = ! empty( $export_form ) ? $export_form : [];
$frame_enable                 = isset( $frame_enable ) ? $frame_enable : '0';
$status_form                  = isset( $status_form ) ? $status_form : [];
$status_wp                    = isset( $status_wp ) ? $status_wp : [];
$status_translate             = [
	'accepted'   => 'Загружен в ЛК перевозчика',
	'need_check' => 'Загружен в ЛК перевозчика, но требуется уточнения',
	'created'    => 'Загружен в ЛК перевозчика и проверен',
	'received'   => 'Принят на склад перевозчика',
	'delivered'  => 'В доставке у перевозчика',
	'awaiting'   => 'Ожидает самовывоза из ПВЗ/постамата',
	'courier'    => 'Передан курьеру',
	'taken'      => 'Доставлен',
	'canceled'   => 'Отменен',
	'return'     => 'Возвращается отправителю',
	'returned'   => 'Возвращен отправителю',
	'n/a'        => 'Не определён',
];
?>

<div id="wcEslSettings" class="wc-esl-settings">

    <div class="wc-esl-settings__header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="wc-esl-settings__title">
						<?php echo __( 'Настройки eShop<span>Logistic</span> Shipping', WC_ESL_DOMAIN ) ?>
                    </h1>
					<?php if ( $moduleVersion ): ?>
                        <h4><a href="https://wp-v2.eshoplogistic.ru/documentation-v2/" target="_blank">Документация по
                                настройке</a></h4>
					<?php else: ?>
                        <h4><a href="https://wp-v2.eshoplogistic.ru/documentation/" target="_blank">Документация по
                                настройке</a></h4>
					<?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="wc-esl-settings__body">

        <div class="container-fluid wc-esl-settings-general-options">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
							<?php echo __( 'Основные настройки', WC_ESL_DOMAIN ) ?>

                            <button class="btn btn-primary" id="updateCache">
								<?php echo __( 'Сбросить кэш', WC_ESL_DOMAIN ) ?>
                            </button>
                        </div>
                        <div class="card-body">

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php echo __( 'Включить / выключить', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePlugin"
                                                name="enable_plugin"
											<?php echo $plugin_enable === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="custom-control-label" for="enablePlugin"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php echo __( 'Включить / выключить корзинный виджет', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enableFrame"
                                                name="enable_frame"
											<?php echo $frame_enable === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="custom-control-label" for="enableFrame">
                                            <div class="help-tip">
                                                <p>
                                                    При включении в корзине будет работать виджет выбора службы доставки
                                                    с единой картой ПВЗ. При выключении - стандартная логика (все службы
                                                    по отдельности)
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php echo __( 'Включить / выключить стоимость доставки в сумме заказа', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePluginPriceShipping"
                                                name="enable_plugin_price_shipping"
											<?php echo $plugin_enable_price_shipping === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="custom-control-label" for="enablePluginPriceShipping"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php echo __( 'Включить / выключить логирование запросов', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePluginLog"
                                                name="enable_plugin_log"
											<?php echo $plugin_enable_log === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="custom-control-label" for="enablePluginLog">
                                            <div class="help-tip">
                                                <p>
                                                    Если включен данный параметр, все запросы будут записываться в
                                                    текстовый файл.<br>
                                                    Путь к файлу: <a
                                                            href="<?php echo get_site_url(); ?>/wp-content/plugins/eshoplogisticru/esl.log"><?php echo get_site_url(); ?>
                                                        /wp-content/plugins/eshoplogisticru/esl.log</a>
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php echo __( 'Включить / выключить новую версию api', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePluginApiV2"
                                                name="enable_plugin_api_v2"
											<?php echo $plugin_enable_api_v2 === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="custom-control-label" for="enablePluginApiV2">
                                            <div class="help-tip">
                                                <p>
                                                    Данный параметр включает новую версию API. (личный кабинет <a
                                                            href="https://my.eshoplogistic.ru" target="_blank">my.eshoplogistic.ru</a>)
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php echo __( 'Единица измерения габаритов ', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <select id="dimensionMeasurement" name="dimension_measurement">
                                        <option value="mm" <?php echo $dimension_measurement === 'mm' ? 'selected' : '' ?>>
                                            Миллиметры
                                        </option>
                                        <option value="cm" <?php echo $dimension_measurement === 'cm' ? 'selected' : '' ?>>
                                            Сантиметры
                                        </option>
                                        <option value="m" <?php echo $dimension_measurement === 'm' ? 'selected' : '' ?>>
                                            Метры
                                        </option>
                                    </select>
                                </div>
                            </div>


                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php echo __( 'API Ключ', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php echo __( 'API Ключ', WC_ESL_DOMAIN ) ?>"
                                                    id="apiKeyInput"
                                                    name="api_key"
                                                    value="<?php echo esc_attr( $api_key ) ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

							<?php
							$style = '';
							if ( ! $moduleVersion ) {
								$style = ' style="display:none;"';
							}
							?>
                            <div class="form-group row align-items-center mb-3" <?php echo $style ?>>
                                <label for="" class="col-sm-2 col-form-label">
									<?php echo __( 'Ключ корзинного виджета', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyWCartForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php echo __( 'Ключ корзинного виджета', WC_ESL_DOMAIN ) ?>"
                                                    id="apiKeyWCartInput"
                                                    name="api_key_wcart"
                                                    value="<?php echo esc_attr( $api_key_wcart ) ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php echo __( 'API Ключ для яндекс карты', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyYaForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php echo __( 'API Ключ для яндекс карты', WC_ESL_DOMAIN ) ?>"
                                                    id="apiKeyYaInput"
                                                    name="api_ya_key"
                                                    value="<?php echo esc_attr( $api_key_ya ) ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                                <div class="help-tip">
                                    <p>
                                        Для активации поиска на яндекс картах
                                    </p>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
							<?php echo __( 'Настройки оплаты', WC_ESL_DOMAIN ) ?>
                        </div>

                        <div class="card-body">
                            <form action="/" method="post" id="eslPayTypeForm">
                                <table class="table table-striped">
                                    <thead>
                                    <th scope="col">#</th>
                                    <th scope="col"><?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH_RU ?></th>
                                    <th scope="col"><?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD_RU ?></th>
                                    <th scope="col"><?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS_RU ?></th>
                                    <th scope="col"><?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY_RU ?></th>
                                    <th scope="col"><?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON_RU ?></th>
                                    </thead>
                                    <tbody>

									<?php if ( ! empty( $paymentGateways ) ) : ?>
										<?php foreach ( $paymentGateways as $paymentGateway ) : ?>
                                            <tr>
                                                <th scope="row"><?php echo esc_attr( $paymentGateway->title ) ?></th>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH ?>"
                                                            <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
														        <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH ) ? 'checked' : '' ?>
                                                            <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD ?>"
	                                                        <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
                                                                <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD ) ? 'checked' : '' ?>
	                                                        <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS ?>"
	                                                        <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
														        <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS ) ? 'checked' : '' ?>
	                                                        <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY ?>"
	                                                        <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
														        <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY ) ? 'checked' : '' ?>
	                                                        <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo ( $moduleVersion ) ? \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON_V2 : \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON ?>"
														<?php
														if(isset($paymentMethods[ $paymentGateway->id ])){
                                                            if ( $moduleVersion ) {
                                                                echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON_V2 ) ? 'checked' : '';
                                                            } else {
                                                                echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON ) ? 'checked' : '';
                                                            }
														}
														?>
                                                    />
                                                </td>
                                            </tr>

										<?php endforeach; ?>
									<?php endif; ?>
                                    </tbody>
                                </table>

                                <button class="btn btn-primary" type="submit">
									<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card wc-esl-settings-widget">
                        <div class="card-header">
							<?php
							if ( $moduleVersion ) {
								echo __( 'Виджет в карточку товара', WC_ESL_DOMAIN );
							} else {
								echo __( 'Виджет', WC_ESL_DOMAIN );
							}
							?>
                        </div>

                        <div class="card-body" id="eslWidgetFormWrap">
                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php echo __( 'Ключ виджета', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetKeyForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php echo __( 'Ключ виджета', WC_ESL_DOMAIN ) ?>"
                                                    id="eslWidgetKey"
                                                    name="esl_widget_key"
                                                    value="<?php echo esc_attr( $widget_key ) ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php echo __( 'Секретный код', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetSecretCodeForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php echo __( 'Секретный код', WC_ESL_DOMAIN ) ?>"
                                                    id="eslWidgetSecretCode"
                                                    name="esl_widget_secret_code"
                                                    value="<?php echo esc_attr( $secret_code ) ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php echo __( 'Название для кнопки виджета', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetButForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php echo __( 'Название для кнопки виджета', WC_ESL_DOMAIN ) ?>"
                                                    id="eslWidgetBut"
                                                    name="esl_widget_but"
                                                    value="<?php echo esc_attr( $widget_but ) ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card wc-esl-settings-others">
                        <div class="card-header">
							<?php echo __( 'Дополнительные настройки eShopLogistic', WC_ESL_DOMAIN ) ?>
                        </div>

                        <div class="card-body" id="eslOthersFormWrap">
                            <div class="form-group row align-items-center mb-3">
                                <div class="col-sm-12">
                                    <form action="/" method="post" id="eslAddForm">
                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
												<?php echo __( 'Описание подсказки для списка городов', WC_ESL_DOMAIN ) ?>
                                            </label>
											<?php
											$citiesTips = '';
											if ( isset( $add_form['citiesTips'] ) ) {
												$citiesTips = $add_form['citiesTips'];
											}
											?>
                                            <input
                                                    type="text"
                                                    placeholder="<?php echo __( 'Подсказка для выбора города', WC_ESL_DOMAIN ) ?>"
                                                    name="citiesTips"
                                                    class="form-control col-sm-8"
                                                    value="<?php echo esc_attr( $citiesTips ) ?>"
                                            />
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Название кнопки для выбора ПВЗ', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $pvzName = '';
		                                    if ( isset( $add_form['pvzName'] ) ) {
			                                    $pvzName = $add_form['pvzName'];
		                                    }
		                                    ?>
                                            <input
                                                    type="text"
                                                    placeholder="<?php echo __( 'Название кнопки для выбора ПВЗ', WC_ESL_DOMAIN ) ?>"
                                                    name="pvzName"
                                                    class="form-control col-sm-8"
                                                    value="<?php echo esc_attr( $pvzName ) ?>"
                                            />
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
												<?php echo __( 'Контролировать способ оплаты', WC_ESL_DOMAIN ) ?>
                                            </label>
											<?php
											$paymentCalc = '';
											if ( isset( $add_form['paymentCalc'] ) ) {
												$paymentCalc = $add_form['paymentCalc'];
											}
											?>
                                            <input
                                                    type="checkbox"
                                                    placeholder="<?php echo __( 'Контролировать способ оплаты', WC_ESL_DOMAIN ) ?>"
                                                    name="paymentCalc"
                                                    class="col-sm-8 form-control checkbox"
											<?php echo ( $paymentCalc == 'true' ) ? 'checked=checked' : ''; ?>"
                                            />
                                        </div>

										<?php if ( $moduleVersion ): ?>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Разрешить оформлять заказ без выбора доставки (корзинный виджет)', WC_ESL_DOMAIN ) ?>
                                                </label>
												<?php
												$checkDelivery = '';
												if ( isset( $add_form['checkDelivery'] ) ) {
													$checkDelivery = $add_form['checkDelivery'];
												}
												?>
                                                <input
                                                        type="checkbox"
                                                        placeholder="<?php echo __( 'Разрешить оформлять заказ без выбора доставки (корзинный виджет)', WC_ESL_DOMAIN ) ?>"
                                                        name="checkDelivery"
                                                        class="col-sm-8 form-control checkbox"
												<?php echo ( $checkDelivery == 'true' ) ? 'checked=checked' : ''; ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Изменить способ выбора города', WC_ESL_DOMAIN ) ?>
                                                </label>
												<?php
												$citySelectModal = '';
												if ( isset( $add_form['citySelectModal'] ) ) {
													$citySelectModal = $add_form['citySelectModal'];
												}
												?>
                                                <input
                                                        type="checkbox"
                                                        placeholder="<?php echo __( 'Изменить способ выбора города', WC_ESL_DOMAIN ) ?>"
                                                        name="citySelectModal"
                                                        class="col-sm-8 form-control checkbox"
												<?php echo ( $citySelectModal == 'true' ) ? 'checked=checked' : ''; ?>"
                                                />
                                            </div>

											<?php
											$eslLoader = '';
											$eslLodaerImg = '';
											if ( isset( $add_form['eslLoader'] ) ) {
												$eslLoader = $add_form['eslLoader'];
                                                $eslLodaerImg = wp_get_attachment_image_url($eslLoader, 'full');
											}
											?>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
		                                            <?php echo __( 'Изображение для загрузки', WC_ESL_DOMAIN ) ?>
                                                </label>
                                                <img src="<?php echo $eslLodaerImg?>" width="150"/>
                                                <div class="ml-1">
                                                    <input type="hidden" name="eslLoader" value="<?php echo $eslLoader?>"/>
                                                    <button type="submit" class="upload_image_button button">Загрузить
                                                    </button>
                                                    <button type="submit" class="remove_image_button button">×</button>
                                                </div>
                                            </div>
										<?php endif; ?>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Поле адреса доставки (Billing)', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $billingList = array();
		                                    if(isset($add_form['billingCity']) && !is_array($add_form['billingCity'])){
			                                    $billingList[] = $add_form['billingCity'];
		                                    }elseif ( isset( $add_form['billingCity'] ) ) {
			                                    $billingList = $add_form['billingCity'];
		                                    }
		                                    ?>

                                            <select name="billingCity" style="width: 100%; margin-bottom: 15px;">
                                                <option value="billing_city">По умолчанию</option>
			                                    <?php
			                                    foreach ( $list_fields['billing'] as $value => $label ) {
				                                    $selected = ( in_array( $value, $billingList ) ) ? 'selected' : '';
				                                    echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . $label['label'] . ' - '.$value.'</option>';
			                                    }
			                                    ?>
                                            </select>

                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Поле другого адреса доставки (Shipping)', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $billingList = array();
		                                    if(isset($add_form['shippingCity']) && !is_array($add_form['shippingCity'])){
			                                    $billingList[] = $add_form['shippingCity'];
		                                    }elseif ( isset( $add_form['shippingCity'] ) ) {
			                                    $billingList = $add_form['shippingCity'];
		                                    }
		                                    ?>

                                            <select name="shippingCity" style="width: 100%; margin-bottom: 15px;">
                                                <option value="shipping_city">По умолчанию</option>
			                                    <?php
			                                    foreach ( $list_fields['shipping'] as $value => $label ) {
				                                    $selected = ( in_array( $value, $billingList ) ) ? 'selected' : '';
				                                    echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . $label['label'] . ' - '.$value.'</option>';
			                                    }
			                                    ?>
                                            </select>

                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Отключить скрытие полей адреса при выборе ПВЗ', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $offAddressCheck = '';
		                                    if ( isset( $add_form['offAddressCheck'] ) ) {
			                                    $offAddressCheck = $add_form['offAddressCheck'];
		                                    }
		                                    ?>
                                            <input
                                                    type="checkbox"
                                                    placeholder="<?php echo __( 'Отключить скрытие полей адреса при выборе ПВЗ', WC_ESL_DOMAIN ) ?>"
                                                    name="offAddressCheck"
                                                    class="col-sm-8 form-control checkbox"
		                                    <?php echo ( $offAddressCheck == 'true' ) ? 'checked=checked' : ''; ?>"
                                            />
                                        </div>


                                        <div class="card-header">
		                                    <?php echo __( 'Планировщик выгрузки заказов', WC_ESL_DOMAIN ) ?>
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Включить планировщик обновления статусов заказа', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $cronStatusEnable = '';
		                                    if ( isset( $add_form['cronStatusEnable'] ) ) {
			                                    $cronStatusEnable = $add_form['cronStatusEnable'];
		                                    }
		                                    ?>
                                            <input
                                                    type="checkbox"
                                                    placeholder="<?php echo __( 'Включить планировщик обновления статусов заказа', WC_ESL_DOMAIN ) ?>"
                                                    name="cronStatusEnable"
                                                    class="col-sm-8 form-control checkbox"
		                                    <?php echo ( $cronStatusEnable == 'true' ) ? 'checked=checked' : ''; ?>"
                                            />
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Статусы заказов для работы планировщика', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $statusEnd = array();
                                            if(isset($add_form['statusEnd']) && !is_array($add_form['statusEnd'])){
	                                            $statusEnd[] = $add_form['statusEnd'];
                                            }elseif ( isset( $add_form['statusEnd'] ) ) {
			                                    $statusEnd = $add_form['statusEnd'];
		                                    }
		                                    ?>

                                            <select name="statusEnd" multiple="multiple" style="width: 100%; margin-bottom: 15px;">
		                                        <?php
		                                        foreach ( $status_wp as $value => $label ) {
                                                    if($value == 'wc-completed' || $value == 'wc-cancelled' || $value == 'wc-refunded' || $value == 'wc-failed'  || $value == 'wc-test-status' || $value == 'wc-checkout-draft')
                                                        continue;

			                                        $selected = ( in_array( $value, $statusEnd ) ) ? 'selected' : '';
			                                        echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . $label . '</option>';
		                                        }
		                                        ?>
                                            </select>

                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php echo __( 'Период обновления планировщика (в минутах)', WC_ESL_DOMAIN ) ?>
                                            </label>
		                                    <?php
		                                    $cronStatusTime = '';
		                                    if ( isset( $add_form['cronStatusTime'] ) ) {
			                                    $cronStatusTime = $add_form['cronStatusTime'];
		                                    }
		                                    ?>
                                            <input
                                                    type="number"
                                                    placeholder="<?php echo __( 'Период обновления планировщика (в минутах)', WC_ESL_DOMAIN ) ?>"
                                                    name="cronStatusTime"
                                                    class="col-sm-8 form-control"
                                                    value="<?php echo esc_attr( $cronStatusTime ) ?>"
                                            />
                                        </div>

                                        <button class="btn btn-primary float-end" type="submit">
											<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

					<?php if ( $moduleVersion ): ?>
                        <div class="card wc-esl-settings-export">
                            <div class="card-header">
								<?php echo __( 'Настройки выгрузки заказов', WC_ESL_DOMAIN ) ?>
                            </div>

                            <div class="card-body" id="eslExportFormWrap">
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-sm-12">
                                        <form action="/" method="post" id="eslExportForm">
                                            <p>Адрес отправителя</p>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Код терминала (СДЭК)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_terminal = '';
												if ( isset( $export_form['sender-terminal-sdek'] ) ) {
													$sender_terminal = $export_form['sender-terminal-sdek'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-sdek"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Код терминала (Boxberry)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_terminal = '';
												if ( isset( $export_form['sender-terminal-boxberry'] ) ) {
													$sender_terminal = $export_form['sender-terminal-boxberry'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-boxberry"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Код терминала (Яндекс)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_terminal = '';
												if ( isset( $export_form['sender-terminal-yandex'] ) ) {
													$sender_terminal = $export_form['sender-terminal-yandex'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-yandex"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Код терминала (5POST)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_terminal = '';
												if ( isset( $export_form['sender-terminal-fivepost'] ) ) {
													$sender_terminal = $export_form['sender-terminal-fivepost'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-fivepost"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Код терминала (KIT)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_terminal = '';
		                                        if ( isset( $export_form['sender-terminal-kit'] ) ) {
			                                        $sender_terminal = $export_form['sender-terminal-kit'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-kit"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Код терминала (Почта России)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_terminal = '';
		                                        if ( isset( $export_form['sender-terminal-postrf'] ) ) {
			                                        $sender_terminal = $export_form['sender-terminal-postrf'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-postrf"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Код терминала (ПЭК)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_terminal = '';
		                                        if ( isset( $export_form['sender-terminal-pecom'] ) ) {
			                                        $sender_terminal = $export_form['sender-terminal-pecom'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-pecom"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Код терминала (Магнит Пост)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_terminal = '';
		                                        if ( isset( $export_form['sender-terminal-magnit'] ) ) {
			                                        $sender_terminal = $export_form['sender-terminal-magnit'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-magnit"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Код терминала (Деловые линии)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Код терминала в случае самостоятельной доставки на
                                                                терминал транспортной компании.
                                                                Узнайте у своего менеджера.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_terminal = '';
												if ( isset( $export_form['sender-terminal-delline'] ) ) {
													$sender_terminal = $export_form['sender-terminal-delline'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Код терминала', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-terminal-delline"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_terminal ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Заказчик перевозки (Деловые линии)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Значение UID контрагента из списка контрагентов в личном
                                                                кабинете на сайте ДЛ.
                                                                Игнорируется при отсутствии полного доступа к
                                                                контрагентам; иначе - обязетелен.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_uid = '';
												if ( isset( $export_form['sender-uid-delline'] ) ) {
													$sender_uid = $export_form['sender-uid-delline'];
												}
												$counterparties = $eshopLogisticApi->apiServiceCounterparties( 'delline' );
												if ( ! $counterparties->hasErrors() ) {
													$counterparties = $counterparties->data();
												} else {
													$counterparties = array();
												}
												?>
												<?php if ( isset( $counterparties['counterparties'] ) ): ?>
                                                    <select id="senderUidDelline" name="sender-uid-delline">
														<?php foreach ( $counterparties['counterparties'] as $key => $value ): ?>
                                                            <option value="<?php echo $value['uid'] ?>" <?php echo $sender_uid == $value['uid'] ? 'selected' : '' ?>>
																<?php echo $value['name'] ?>
                                                            </option>
														<?php endforeach; ?>
                                                    </select>
												<?php else: ?>
                                                    <input
                                                            type="text"
                                                            class="form-control"
                                                            placeholder="<?php echo __( 'UID', WC_ESL_DOMAIN ) ?>"
                                                            name="sender-uid-delline"
                                                            class="col-sm-8"
                                                            value="<?php echo esc_attr( $sender_uid ) ?>"
                                                    />
												<?php endif; ?>

                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Отправитель (Деловые линии)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Значение ID контрагента из адресной книги в личном
                                                                кабинете на сайте ДЛ.
                                                                Игнорируется при отсутствии полного доступа к
                                                                контрагентам; иначе - обязетелен.
                                                                Значение можно получить в адресной строке браузера при
                                                                переходе к нужному контрагенту.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_counter = '';
												if ( isset( $export_form['sender-counter-delline'] ) ) {
													$sender_counter = $export_form['sender-counter-delline'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'ID контрагента', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-counter-delline"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_counter ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Название профиля отправителя (Kit)', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Доступен в личном кабинете Кит.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_uid = '';
		                                        if ( isset( $export_form['sender-uid-kit'] ) ) {
			                                        $sender_uid = $export_form['sender-uid-kit'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Название профиля отправителя', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-uid-kit"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_uid ) ?>"
                                                />

                                            </div>

                                            <hr>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Имя', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Имя отправителя.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_name = '';
												if ( isset( $export_form['sender-name'] ) ) {
													$sender_name = $export_form['sender-name'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Имя', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-name"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_name ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Телефон', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Телефон отправителя.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_phone = '';
												if ( isset( $export_form['sender-phone'] ) ) {
													$sender_phone = $export_form['sender-phone'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Телефон', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-phone"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_phone ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Название компании', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Название компании.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_company = '';
		                                        if ( isset( $export_form['sender-company'] ) ) {
			                                        $sender_company = $export_form['sender-company'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Название компании', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-company"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_company ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php echo __( 'Электронная почта', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Электронная почта.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
		                                        <?php
		                                        $sender_email = '';
		                                        if ( isset( $export_form['sender-email'] ) ) {
			                                        $sender_email = $export_form['sender-email'];
		                                        }
		                                        ?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Электронная почта', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-email"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_email ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Регион', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Электронная почта.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_region = '';
												if ( isset( $export_form['sender-region'] ) ) {
													$sender_region = $export_form['sender-region'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Регион', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-region"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_region ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Населённый пункт', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Адрес для забора груза, если забирает транспортная
                                                                компания.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_city = '';
												if ( isset( $export_form['sender-city'] ) ) {
													$sender_city = $export_form['sender-city'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Населённый пункт:', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-city"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_city ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Улица', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Адрес для забора груза, если забирает транспортная
                                                                компания.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_street = '';
												if ( isset( $export_form['sender-street'] ) ) {
													$sender_street = $export_form['sender-street'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Улица', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-street"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_street ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Здание', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Адрес для забора груза, если забирает транспортная
                                                                компания.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_house = '';
												if ( isset( $export_form['sender-house'] ) ) {
													$sender_house = $export_form['sender-house'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Здание', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-house"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_house ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php echo __( 'Квартира / офис', WC_ESL_DOMAIN ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Адрес для забора груза, если забирает транспортная
                                                                компания.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$sender_house = '';
												if ( isset( $export_form['sender-room'] ) ) {
													$sender_house = $export_form['sender-room'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php echo __( 'Квартира / офис', WC_ESL_DOMAIN ) ?>"
                                                        name="sender-room"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_house ) ?>"
                                                />
                                            </div>


                                            <button class="btn btn-primary float-end" type="submit">
												<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>

					<?php if ( $moduleVersion ): ?>
                        <div class="card wc-esl-settings-status esl-section_drag">
                            <div class="card-header">
								<?php echo __( 'Настройка статусов', WC_ESL_DOMAIN ) ?>
                                <label>
                                    <div class="help-tip">
                                        <p>
                                            Перетащите статус доставки в левую часть страницы. <br>Удалить новую связь
                                            статусов можно после сохранения.
                                        </p>
                                    </div>
                                </label>
                            </div>

                            <div class="card-body" id="eslExportFormWrap">
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-sm-12">

                                        <div class="row">
                                            <div class="esl-inner_status col-sm-6">
												<?php foreach ( $status_translate as $key => $value ):
													$name = $key;
													if ( isset( $status_translate[ $key ] ) ) {
														$name = $status_translate[ $key ];
													}
													?>
                                                    <div class="esl-inner_item">
                                                        <div class="esl-status_api">
															<?php echo $name ?>
                                                        </div>
                                                        <ul class="js-inner-connected sortable"
                                                            name="<?php echo $key ?>"
                                                            aria-dropeffect="move">
															<?php if ( isset( $status_form[ $key ] ) && $status_form[ $key ] ): ?>
																<?php foreach ( $status_form[ $key ] as $item ): ?>
                                                                    <li name="<?php echo $item['name'] ?>"
                                                                        data-desc="<?php echo $item['desc'] ?>"
                                                                        class="esl-status__wp"
                                                                        role="option" aria-grabbed="false">
                                                                        <span class=""
                                                                              draggable="true"><?php echo $item['desc'] ?></span>
                                                                        <span class="sortable-delete"
                                                                              onclick="sortableDelete(this)">х</span>
                                                                    </li>
																<?php endforeach; ?>
															<?php endif; ?>
                                                        </ul>
                                                    </div>
												<?php endforeach; ?>
                                            </div>

                                            <div class="esl-inner_item col-sm-6">

                                                <ul class="js-connected sortable-copy" aria-dropeffect="move">
													<?php foreach ( $status_wp as $key => $value ): ?>
                                                        <li name="<?php echo $key ?>" data-desc="<?php echo $value ?>"
                                                            class="esl-status__wp" role="option" aria-grabbed="false">
                                                            <span class="" draggable="true"><?php echo $value ?></span>
                                                        </li>
													<?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <button class="btn btn-primary float-end" id="statusSave">
									<?php echo __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                </button>
                            </div>
                        </div>
					<?php endif; ?>

	                <?php if ( $moduleVersion ): ?>
                        <div class="card wc-esl-settings-status esl-section_add_field">
                            <div class="card-header">
				                <?php echo __( 'Дополнительные услуги', WC_ESL_DOMAIN ) ?>
                            </div>

                            <div class="card-body" id="eslAddFormField">
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-sm-12">

                                        <div class="row">
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="sdek">Настройки для СДЭК</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="yandex">Настройки для Яндекс</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="fivepost">Настройки для 5POST</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="boxberry">Настройки для Boxberry</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="delline">Настройки для Деловые линии</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="pecom">Настройки для ПЭК</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="baikal">Настройки для Байкал Сервис</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="kit">Настройки для Кит</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="halva">Настройки для Постаматы «Халва»</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="postrf">Настройки для Почта России</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="magnit">Настройки для Магнит Пост</button>
                                            </div>
                                            <div class="esl-inner_add col-sm-6 mb-1">
                                                <button type="button" class="wc-esl-add__button btn-primary" data-mode="dpd">Настройки для DPD</button>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div id="modal-esl-add-field" class="modal-esl-frame">
                                <div class="modal_content">
                                    <div class="title">
                                        <span class="close_modal_window">×</span>
                                        <p><strong>Дополнительные услуги</strong><br></p>
                                    </div>
                                    <div id="content-add-field_ajax"></div>
                                    <div class="footer">
                                        <input id="buttonModalAddField" class="btn btn-primary" type="button"  value="Сохранить">
                                    </div>
                                </div>
                            </div>

                        </div>
	                <?php endif; ?>

                </div>
            </div>
        </div>

    </div>

</div>