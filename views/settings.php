<?php

use eshoplogistic\WCEshopLogistic\Api\EshopLogisticApi;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Http\WpHttpClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- View template local variables.

$optionsRepository = new OptionsRepository();
$eshopLogisticApi  = new EshopLogisticApi( new WpHttpClient() );
if ( ! did_action( 'wp_enqueue_media' ) ) {
	wp_enqueue_media();
}

$WC_Checkout = new WC_Checkout();
$list_fields = $WC_Checkout->get_checkout_fields();

$plugin_enable                = isset( $wc_esl_plugin_enable ) ? $wc_esl_plugin_enable : '0';
$plugin_enable_price_shipping = isset( $wc_esl_plugin_enable_price_shipping ) ? $wc_esl_plugin_enable_price_shipping : '1';
$plugin_enable_log            = isset( $wc_esl_plugin_enable_log ) ? $wc_esl_plugin_enable_log : '0';
    $moduleVersion                = '1';
    $api_key                      = ! empty( $wc_esl_api_key ) ? $wc_esl_api_key : '';
$api_key_wcart                = ! empty( $wc_esl_api_key_wcart ) ? $wc_esl_api_key_wcart : '';
$api_key_ya                   = ! empty( $wc_esl_api_key_ya ) ? $wc_esl_api_key_ya : '';
$paymentMethods               = isset( $wc_esl_paymentMethods ) ? $wc_esl_paymentMethods : [];
$secret_code                  = ! empty( $wc_esl_secret_code ) ? $wc_esl_secret_code : '';
$widget_key                   = ! empty( $wc_esl_widget_key ) ? $wc_esl_widget_key : '';
$widget_but                   = ! empty( $wc_esl_widget_but ) ? $wc_esl_widget_but : 'Рассчитать доставку';
$dimension_measurement        = ! empty( $wc_esl_dimension_measurement ) ? $wc_esl_dimension_measurement : 'cm';
$add_form                     = ! empty( $wc_esl_add_form ) ? $wc_esl_add_form : [];
$export_form                  = ! empty( $wc_esl_export_form ) ? $wc_esl_export_form : [];
$frame_enable                 = isset( $wc_esl_frame_enable ) ? $wc_esl_frame_enable : '0';
$status_form                  = isset( $wc_esl_status_form ) ? $wc_esl_status_form : [];
$status_wp                    = isset( $wc_esl_status_wp ) ? $wc_esl_status_wp : [];
$paymentGateways              = isset( $wc_esl_paymentGateways ) ? $wc_esl_paymentGateways : [];
$add_field_form               = isset( $wc_esl_add_field_form ) ? $wc_esl_add_field_form : [];

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
                        <span class="dashicons dashicons-cart" aria-hidden="true"></span>
						<?php esc_html_e( 'Настройки eShopLogistic Shipping', 'eshoplogisticru' ) ?>
                    </h1>
					<?php if ( $moduleVersion ): ?>
                        <h4 class="wc-esl-settings__doc-link"><a href="https://wp-v2.eshoplogistic.ru/documentation-v2/" target="_blank">Документация по
                                настройке</a></h4>
					<?php else: ?>
                        <h4 class="wc-esl-settings__doc-link"><a href="https://wp-v2.eshoplogistic.ru/documentation/" target="_blank">Документация по
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
                    <ul class="nav nav-tabs wc-esl-top-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="esl-top-tab-system-btn" data-toggle="tab" href="#esl-top-tab-system" role="tab"><span class="dashicons dashicons-admin-generic"></span>Основные настройки</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="esl-top-tab-payment-btn" data-toggle="tab" href="#esl-top-tab-payment" role="tab"><span class="dashicons dashicons-money-alt"></span>Оплата и виджет</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="esl-top-tab-extra-btn" data-toggle="tab" href="#esl-top-tab-extra" role="tab"><span class="dashicons dashicons-admin-tools"></span>Дополнительные настройки</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="esl-top-tab-export-btn" data-toggle="tab" href="#esl-top-tab-export" role="tab"><span class="dashicons dashicons-upload"></span>Выгрузка заказов</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="esl-top-tab-status-btn" data-toggle="tab" href="#esl-top-tab-status" role="tab"><span class="dashicons dashicons-update"></span>Синхронизация статусов</a>
                        </li>
                    </ul>

                    <div class="tab-content wc-esl-top-tab-content pt-4">
                    <div class="tab-pane fade show active" id="esl-top-tab-system" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
							<?php esc_html_e('Основные настройки', 'eshoplogisticru' ) ?>

                            <button class="btn btn-primary" id="updateCache">
								<?php esc_html_e( 'Сбросить кэш', 'eshoplogisticru' ) ?>
                            </button>
                        </div>
                        <div class="card-body">

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php esc_html_e( 'Включить / выключить', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePlugin"
                                                name="enable_plugin"
                                            <?php echo esc_attr($plugin_enable === '1' ? 'checked' : '') ?>
                                        >
                                        <label class="custom-control-label" for="enablePlugin"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php esc_html_e( 'Включить / выключить корзинный виджет', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enableFrame"
                                                name="enable_frame"
                                            <?php echo esc_attr($frame_enable === '1' ? 'checked' : '') ?>
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
									<?php esc_html_e( 'Включить / выключить стоимость доставки в сумме заказа', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePluginPriceShipping"
                                                name="enable_plugin_price_shipping"
                                            <?php echo esc_attr($plugin_enable_price_shipping === '1' ? 'checked' : '') ?>
                                        >
                                        <label class="custom-control-label" for="enablePluginPriceShipping"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php esc_html_e( 'Включить / выключить логирование запросов', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePluginLog"
                                                name="enable_plugin_log"
                                            <?php echo esc_attr($plugin_enable_log === '1' ? 'checked' : '') ?>
                                        >
                                        <label class="custom-control-label" for="enablePluginLog">
                                            <div class="help-tip">
                                                <p>
                                                    Если включен данный параметр, все запросы к API будут записываться
                                                    в журнал WooCommerce (источник «wc-esl-shipping»).<br>
                                                    Посмотреть журналы:
                                                    <a href="<?php echo esc_url(admin_url('admin.php?page=wc-status&tab=logs')); ?>">
                                                        WooCommerce → Статус → Журналы</a>
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-5 col-form-label">
									<?php esc_html_e('Единица измерения габаритов ', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <select id="dimensionMeasurement" name="dimension_measurement">
                                        <option value="mm" <?php echo esc_attr($dimension_measurement === 'mm' ? 'selected' : '') ?>>
                                            Миллиметры
                                        </option>
                                        <option value="cm" <?php echo esc_attr($dimension_measurement === 'cm' ? 'selected' : '') ?>>
                                            Сантиметры
                                        </option>
                                        <option value="m" <?php echo esc_attr($dimension_measurement === 'm' ? 'selected' : '') ?>>
                                            Метры
                                        </option>
                                    </select>
                                </div>
                            </div>


                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php esc_html_e( 'API Ключ', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php esc_html_e( 'API Ключ', 'eshoplogisticru' ) ?>"
                                                    id="apiKeyInput"
                                                    name="api_key"
                                                    value="<?php echo esc_attr( $api_key ) ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
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
                            <div class="form-group row align-items-center mb-3" <?php echo esc_attr($style) ?>>
                                <label for="" class="col-sm-2 col-form-label">
									<?php esc_html_e( 'Ключ корзинного виджета', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyWCartForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php esc_html_e( 'Ключ корзинного виджета', 'eshoplogisticru' ) ?>"
                                                    id="apiKeyWCartInput"
                                                    name="api_key_wcart"
                                                    value="<?php echo esc_attr( $api_key_wcart ) ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php esc_html_e( 'API Ключ для яндекс карты', 'eshoplogisticru' ) ?>
                                    <div class="help-tip">
                                        <p>
                                            Для активации поиска на яндекс картах
                                        </p>
                                    </div>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyYaForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php esc_html_e( 'API Ключ для яндекс карты', 'eshoplogisticru' ) ?>"
                                                    id="apiKeyYaInput"
                                                    name="api_ya_key"
                                                    value="<?php echo esc_attr( $api_key_ya ) ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                    </div>
                    <div class="tab-pane fade" id="esl-top-tab-payment" role="tabpanel">
                    <div class="card">
                        <div class="card-header">
							<?php esc_html_e( 'Настройки оплаты', 'eshoplogisticru' ) ?>
                        </div>

                        <div class="card-body">
                            <form action="/" method="post" id="eslPayTypeForm">
                                <table class="table table-striped">
                                    <thead>
                                    <th scope="col">#</th>
                                    <th scope="col"><?php echo esc_html( \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH_RU ) ?></th>
                                    <th scope="col"><?php echo esc_html( \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD_RU ) ?></th>
                                    <th scope="col"><?php echo esc_html( \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS_RU ) ?></th>
                                    <th scope="col"><?php echo esc_html( \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY_RU ) ?></th>
                                    <th scope="col"><?php echo esc_html( \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON_RU ) ?></th>
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
                                                            value="<?php echo esc_attr(\eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH) ?>"
                                                            <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
														        <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH ) ? 'checked' : '' ?>
                                                            <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo esc_attr(\eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD) ?>"
	                                                        <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
                                                                <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD ) ? 'checked' : '' ?>
	                                                        <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo esc_attr(\eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS) ?>"
	                                                        <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
														        <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS ) ? 'checked' : '' ?>
	                                                        <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo esc_attr(\eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY) ?>"
	                                                        <?php if(isset($paymentMethods[ $paymentGateway->id ])):?>
														        <?php echo ( $paymentMethods[ $paymentGateway->id ] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY ) ? 'checked' : '' ?>
	                                                        <?php endif; ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?php echo esc_attr( $paymentGateway->id ) ?>]"
                                                            value="<?php echo ( $moduleVersion ) ? esc_attr(\eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON_V2) : esc_attr(\eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_UPON) ?>"
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
									<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card wc-esl-settings-widget">
                        <div class="card-header">
							<?php
							if ( $moduleVersion ) {
								esc_html_e( 'Виджет в карточку товара', 'eshoplogisticru' );
							} else {
								esc_html_e( 'Виджет', 'eshoplogisticru' );
							}
							?>
                        </div>

                        <div class="card-body" id="eslWidgetFormWrap">
                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php esc_html_e( 'Ключ виджета', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetKeyForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php esc_html_e( 'Ключ виджета', 'eshoplogisticru' ) ?>"
                                                    id="eslWidgetKey"
                                                    name="esl_widget_key"
                                                    value="<?php echo esc_attr( $widget_key ) ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php esc_html_e('Сохранить', 'eshoplogisticru' ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php esc_html_e( 'Секретный код', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetSecretCodeForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php esc_html_e( 'Секретный код', 'eshoplogisticru' ) ?>"
                                                    id="eslWidgetSecretCode"
                                                    name="esl_widget_secret_code"
                                                    value="<?php echo esc_attr( $secret_code ) ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
									<?php esc_html_e( 'Название для кнопки виджета', 'eshoplogisticru' ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetButForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?php esc_html_e( 'Название для кнопки виджета', 'eshoplogisticru' ) ?>"
                                                    id="eslWidgetBut"
                                                    name="esl_widget_but"
                                                    value="<?php echo esc_attr( $widget_but ) ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
													<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    </div>
                    <div class="tab-pane fade" id="esl-top-tab-extra" role="tabpanel">
                    <div class="card wc-esl-settings-others">
                        <div class="card-header">
							<?php esc_html_e( 'Дополнительные настройки eShopLogistic', 'eshoplogisticru' ) ?>
                        </div>

                        <div class="card-body" id="eslOthersFormWrap">
                            <div class="form-group row align-items-center mb-3">
                                <div class="col-sm-12">
                                    <form action="/" method="post" id="eslAddForm">
                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
												<?php esc_html_e( 'Описание подсказки для списка городов', 'eshoplogisticru' ) ?>
                                            </label>
											<?php
											$citiesTips = '';
											if ( isset( $add_form['citiesTips'] ) ) {
												$citiesTips = $add_form['citiesTips'];
											}
											?>
                                            <input
                                                    type="text"
                                                    placeholder="<?php esc_html_e( 'Подсказка для выбора города', 'eshoplogisticru' ) ?>"
                                                    name="citiesTips"
                                                    class="form-control col-sm-8"
                                                    value="<?php echo esc_attr( $citiesTips ) ?>"
                                            />
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Название кнопки для выбора ПВЗ', 'eshoplogisticru' ) ?>
                                            </label>
		                                    <?php
		                                    $pvzName = '';
		                                    if ( isset( $add_form['pvzName'] ) ) {
			                                    $pvzName = $add_form['pvzName'];
		                                    }
		                                    ?>
                                            <input
                                                    type="text"
                                                    placeholder="<?php esc_html_e( 'Название кнопки для выбора ПВЗ', 'eshoplogisticru' ) ?>"
                                                    name="pvzName"
                                                    class="form-control col-sm-8"
                                                    value="<?php echo esc_attr( $pvzName ) ?>"
                                            />
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
												<?php esc_html_e( 'Контролировать способ оплаты', 'eshoplogisticru' ) ?>
                                            </label>
											<?php
											$paymentCalc = '';
											if ( isset( $add_form['paymentCalc'] ) ) {
												$paymentCalc = $add_form['paymentCalc'];
											}
											?>
                                            <input
                                                    type="checkbox"
                                                    placeholder="<?php esc_html_e( 'Контролировать способ оплаты', 'eshoplogisticru' ) ?>"
                                                    name="paymentCalc"
                                                    class="col-sm-8 form-control checkbox"
											<?php echo ( $paymentCalc == 'true' ) ? 'checked=checked' : ''; ?>
                                            />
                                        </div>

										<?php if ( $moduleVersion ): ?>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e('Разрешить оформлять заказ без выбора доставки (корзинный виджет)', 'eshoplogisticru' ) ?>
                                                </label>
												<?php
												$checkDelivery = '';
												if ( isset( $add_form['checkDelivery'] ) ) {
													$checkDelivery = $add_form['checkDelivery'];
												}
												?>
                                                <input
                                                        type="checkbox"
                                                        placeholder="<?php esc_html_e( 'Разрешить оформлять заказ без выбора доставки (корзинный виджет)', 'eshoplogisticru' ) ?>"
                                                        name="checkDelivery"
                                                        class="col-sm-8 form-control checkbox"
												<?php echo ( $checkDelivery == 'true' ) ? 'checked=checked' : ''; ?>
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Изменить способ выбора города', 'eshoplogisticru' ) ?>
                                                </label>
												<?php
												$citySelectModal = '';
												if ( isset( $add_form['citySelectModal'] ) ) {
													$citySelectModal = $add_form['citySelectModal'];
												}
												?>
                                                <input
                                                        type="checkbox"
                                                        placeholder="<?php esc_html_e( 'Изменить способ выбора города', 'eshoplogisticru' ) ?>"
                                                        name="citySelectModal"
                                                        class="col-sm-8 form-control checkbox"
												<?php echo ( $citySelectModal == 'true' ) ? 'checked=checked' : ''; ?>
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
		                                            <?php esc_html_e( 'Изображение для загрузки', 'eshoplogisticru' ) ?>
                                                </label>
                                                <img src="<?php echo esc_url( $eslLodaerImg )?>" width="150"/>
                                                <div class="ml-1">
                                                    <input type="hidden" name="eslLoader" value="<?php echo esc_attr($eslLoader)?>"/>
                                                    <button type="submit" class="upload_image_button button">Загрузить
                                                    </button>
                                                    <button type="submit" class="remove_image_button button">×</button>
                                                </div>
                                            </div>
										<?php endif; ?>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Поле адреса доставки (Billing)', 'eshoplogisticru' ) ?>
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
				                                    echo '<option value="' . esc_attr( $value ) . '"' . esc_attr($selected) . '>' . esc_attr($label['label']) . ' - '.esc_attr($value).'</option>';
			                                    }
			                                    ?>
                                            </select>

                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Поле другого адреса доставки (Shipping)', 'eshoplogisticru' ) ?>
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
				                                    echo '<option value="' . esc_attr( $value ) . '"' . esc_attr($selected) . '>' . esc_attr($label['label']) . ' - '.esc_attr($value).'</option>';
			                                    }
			                                    ?>
                                            </select>

                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Отключить скрытие полей адреса при выборе ПВЗ', 'eshoplogisticru' ) ?>
                                            </label>
		                                    <?php
		                                    $offAddressCheck = '';
		                                    if ( isset( $add_form['offAddressCheck'] ) ) {
			                                    $offAddressCheck = $add_form['offAddressCheck'];
		                                    }
		                                    ?>
                                            <input
                                                    type="checkbox"
                                                    placeholder="<?php esc_html_e( 'Отключить скрытие полей адреса при выборе ПВЗ', 'eshoplogisticru' ) ?>"
                                                    name="offAddressCheck"
                                                    class="col-sm-8 form-control checkbox"
		                                    <?php echo ( $offAddressCheck == 'true' ) ? 'checked=checked' : ''; ?>
                                            />
                                        </div>


                                        <div class="card-header">
		                                    <?php esc_html_e( 'Планировщик выгрузки заказов', 'eshoplogisticru' ) ?>
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Включить планировщик обновления статусов заказа', 'eshoplogisticru' ) ?>
                                            </label>
		                                    <?php
		                                    $cronStatusEnable = '';
		                                    if ( isset( $add_form['cronStatusEnable'] ) ) {
			                                    $cronStatusEnable = $add_form['cronStatusEnable'];
		                                    }
		                                    ?>
                                            <input
                                                    type="checkbox"
                                                    placeholder="<?php esc_html_e( 'Включить планировщик обновления статусов заказа', 'eshoplogisticru' ) ?>"
                                                    name="cronStatusEnable"
                                                    class="col-sm-8 form-control checkbox"
		                                    <?php echo ( $cronStatusEnable == 'true' ) ? 'checked=checked' : ''; ?>
                                            />
                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Статусы заказов для работы планировщика', 'eshoplogisticru' ) ?>
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
			                                        echo '<option value="' . esc_attr( $value ) . '"' . esc_attr($selected) . '>' . esc_attr($label) . '</option>';
		                                        }
		                                        ?>
                                            </select>

                                        </div>

                                        <div class="input-group">
                                            <label for="" class="col-sm-4 col-form-label">
			                                    <?php esc_html_e( 'Период обновления планировщика (в минутах)', 'eshoplogisticru' ) ?>
                                            </label>
		                                    <?php
		                                    $cronStatusTime = '';
		                                    if ( isset( $add_form['cronStatusTime'] ) ) {
			                                    $cronStatusTime = $add_form['cronStatusTime'];
                                                if($cronStatusTime < 60)
                                                    $cronStatusTime = 60;
		                                    }
		                                    ?>
                                            <input
                                                    type="number"
                                                    placeholder="<?php esc_html_e( 'Период обновления планировщика (в минутах)', 'eshoplogisticru' ) ?>"
                                                    name="cronStatusTime"
                                                    class="col-sm-8 form-control"
                                                    min="60"
                                                    value="<?php echo esc_attr( $cronStatusTime ) ?>"
                                            />
                                        </div>

                                        <button class="btn btn-primary float-end" type="submit">
											<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    </div>
                    <div class="tab-pane fade" id="esl-top-tab-export" role="tabpanel">
					<?php if ( $moduleVersion ): ?>
                        <div class="card wc-esl-settings-export">
                            <div class="card-header">
								<?php esc_html_e( 'Настройки выгрузки заказов', 'eshoplogisticru' ) ?>
                            </div>

                            <div class="card-body" id="eslExportFormWrap">
                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-sm-12">
                                        <form action="/" method="post" id="eslExportForm">
                                            <p>Адрес отправителя</p>
                                            <hr>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Имя', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e('Имя', 'eshoplogisticru' ) ?>"
                                                        name="sender-name"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_name ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Телефон', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Телефон', 'eshoplogisticru' ) ?>"
                                                        name="sender-phone"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_phone ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php esc_html_e( 'Название компании', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Название компании', 'eshoplogisticru' ) ?>"
                                                        name="sender-company"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_company ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
			                                        <?php esc_html_e( 'Электронная почта', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Электронная почта', 'eshoplogisticru' ) ?>"
                                                        name="sender-email"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_email ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Продавец: имя', 'eshoplogisticru' ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Передаётся некоторым ТК как отдельные реквизиты продавца (не отправителя груза).
                                                                Заполните, если это требуется вашей транспортной компанией.
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$seller_name = '';
												if ( isset( $export_form['seller-name'] ) ) {
													$seller_name = $export_form['seller-name'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php esc_html_e( 'Имя продавца', 'eshoplogisticru' ) ?>"
                                                        name="seller-name"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $seller_name ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Продавец: телефон', 'eshoplogisticru' ) ?>
                                                </label>
												<?php
												$seller_phone = '';
												if ( isset( $export_form['seller-phone'] ) ) {
													$seller_phone = $export_form['seller-phone'];
												}
												?>
                                                <input
                                                        type="text"
                                                        class="form-control"
                                                        placeholder="<?php esc_html_e( 'Телефон продавца', 'eshoplogisticru' ) ?>"
                                                        name="seller-phone"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $seller_phone ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Регион', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Регион', 'eshoplogisticru' ) ?>"
                                                        name="sender-region"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_region ) ?>"
                                                />
                                            </div>
                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Населённый пункт', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Населённый пункт:', 'eshoplogisticru' ) ?>"
                                                        name="sender-city"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_city ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Улица', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Улица', 'eshoplogisticru' ) ?>"
                                                        name="sender-street"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_street ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Здание', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Здание', 'eshoplogisticru' ) ?>"
                                                        name="sender-house"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_house ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Квартира / офис', 'eshoplogisticru' ) ?>
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
                                                        placeholder="<?php esc_html_e( 'Квартира / офис', 'eshoplogisticru' ) ?>"
                                                        name="sender-room"
                                                        class="col-sm-8"
                                                        value="<?php echo esc_attr( $sender_house ) ?>"
                                                />
                                            </div>

                                            <div class="input-group">
                                                <label for="" class="col-sm-4 col-form-label">
													<?php esc_html_e( 'Статус заказа сразу после выгрузки', 'eshoplogisticru' ) ?>
                                                    <label>
                                                        <div class="help-tip">
                                                            <p>
                                                                Если задано — статус заказа будет изменён на выбранный
                                                                сразу после успешной выгрузки в ТК, не дожидаясь
                                                                получения трек-номера. Если не задано — статус
                                                                обновится позже по обычному сопоставлению статусов ТК
                                                                через крон или кнопку «Обновить статус».
                                                            </p>
                                                        </div>
                                                    </label>
                                                </label>
												<?php
												$after_unloading_status = '';
												if ( isset( $export_form['after-unloading-status'] ) ) {
													$after_unloading_status = $export_form['after-unloading-status'];
												}
												?>
                                                <select name="after-unloading-status" class="form-control col-sm-8">
                                                    <option value=""><?php esc_html_e( '-- Не выбрано --', 'eshoplogisticru' ) ?></option>
													<?php foreach ( $status_wp as $wc_esl_statusKey => $wc_esl_statusLabel ): ?>
                                                        <option value="<?php echo esc_attr($wc_esl_statusKey); ?>" <?php echo esc_attr($after_unloading_status === $wc_esl_statusKey ? 'selected' : ''); ?>><?php echo esc_html($wc_esl_statusLabel); ?></option>
													<?php endforeach; ?>
                                                </select>
                                            </div>

                                            <button class="btn btn-primary float-end" type="submit">
												<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
					<?php endif; ?>

	                <?php if ( $moduleVersion ): ?>
						<?php
						// Список ТК, для которых уже реализованы дополнительные поля выгрузки (ExportFileds).
						// take_payment => true — служба поддерживает опцию "взять оплату с получателя" (Этап 3).
						// fields — специфичные для службы поля адреса отправителя (были перенесены сюда из общего списка).
						$terminalHelp = 'Код терминала в случае самостоятельной доставки на терминал транспортной компании. Узнайте у своего менеджера.';
						$carrierTabs = array(
							'sdek'     => array(
								'label' => 'СДЭК', 'take_payment' => true,
								'fields' => array(
									array( 'name' => 'sender-terminal-sdek', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
								),
							),
							'yandex'   => array(
								'label' => 'Яндекс', 'take_payment' => true,
								'fields' => array(
									array( 'name' => 'sender-terminal-yandex', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
									array( 'name' => 'platform_id-yandex', 'label' => 'Код склада (Яндекс.Доставка)', 'help' => 'Идентификатор склада отправителя в личном кабинете Яндекс.Доставки. Требуется не всем схемам доставки.' ),
								),
							),
							'fivepost' => array(
								'label' => '5POST', 'take_payment' => true,
								'fields' => array(
									array( 'name' => 'sender-terminal-fivepost', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
								),
							),
							'postrf'   => array(
								'label' => 'Почта России', 'take_payment' => true,
								'fields' => array(
									array( 'name' => 'sender-terminal-postrf', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
								),
							),
							'boxberry' => array(
								'label' => 'Boxberry', 'take_payment' => false,
								'fields' => array(
									array( 'name' => 'sender-terminal-boxberry', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
								),
							),
							'delline'  => array(
								'label' => 'Деловые линии', 'take_payment' => false,
								'fields' => array(
									array( 'name' => 'sender-terminal-delline', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
									array( 'name' => 'sender-counter-delline', 'label' => 'Отправитель (ID контрагента)', 'help' => 'Значение ID контрагента из адресной книги в личном кабинете на сайте ДЛ. Игнорируется при отсутствии полного доступа к контрагентам; иначе - обязателен. Значение можно получить в адресной строке браузера при переходе к нужному контрагенту.' ),
									array( 'name' => 'sender-time-from-delline', 'label' => 'Время забора груза c', 'type' => 'time' ),
									array( 'name' => 'sender-time-to-delline', 'label' => 'Время забора груза до', 'type' => 'time' ),
								),
							),
							'pecom'    => array(
								'label' => 'ПЭК', 'take_payment' => false,
								'fields' => array(
									array( 'name' => 'sender-terminal-pecom', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
								),
							),
							'baikal'   => array( 'label' => 'Байкал Сервис', 'take_payment' => false, 'fields' => array() ),
							'kit'      => array(
								'label' => 'Кит', 'take_payment' => false,
								'fields' => array(
									array( 'name' => 'sender-terminal-kit', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
									array( 'name' => 'sender-uid-kit', 'label' => 'Название профиля отправителя', 'help' => 'Доступен в личном кабинете Кит.' ),
								),
							),
							'halva'    => array( 'label' => 'Постаматы «Халва»', 'take_payment' => false, 'fields' => array() ),
							'magnit'   => array(
								'label' => 'Магнит Пост', 'take_payment' => false,
								'fields' => array(
									array( 'name' => 'sender-terminal-magnit', 'label' => 'Код терминала', 'help' => $terminalHelp, 'type' => 'terminal' ),
								),
							),
							'dpd'      => array( 'label' => 'DPD', 'take_payment' => false, 'fields' => array() ),
						);

						$carrierPaymentTypeOptions = array(
							''                => '-- Не выбрано --',
							'already_paid'    => 'Заказ уже оплачен',
							'cash_on_receipt' => 'Наличными при получении',
							'card_on_receipt' => 'Картой при получении',
							'cashless'        => 'Безналичный расчет',
						);
						?>
                        <div class="card wc-esl-settings-status esl-section_add_field">
                            <div class="card-header">
				                <?php esc_html_e( 'Настройки транспортных компаний', 'eshoplogisticru' ) ?>
                            </div>

                            <div class="card-body" id="eslCarrierTabsWrap">
                                <ul class="nav nav-tabs" role="tablist">
									<?php foreach ( $carrierTabs as $carrierSlug => $carrierData ): ?>
                                        <li class="nav-item">
                                            <a class="nav-link<?php echo esc_attr($carrierSlug === 'sdek' ? ' active' : ''); ?>"
                                               id="esl-carrier-tab-<?php echo esc_attr($carrierSlug); ?>-btn"
                                               data-toggle="tab"
                                               href="#esl-carrier-tab-<?php echo esc_attr($carrierSlug); ?>"
                                               role="tab">
												<?php echo esc_html($carrierData['label']); ?>
                                            </a>
                                        </li>
									<?php endforeach; ?>
                                </ul>

                                <div class="tab-content pt-3">
									<?php foreach ( $carrierTabs as $carrierSlug => $carrierData ): ?>
                                        <div class="tab-pane fade<?php echo esc_attr($carrierSlug === 'sdek' ? ' show active' : ''); ?>"
                                             id="esl-carrier-tab-<?php echo esc_attr($carrierSlug); ?>" role="tabpanel">

											<?php foreach ( $carrierData['fields'] as $carrierField ): ?>
                                                <div class="form-group row align-items-center mb-3">
                                                    <label for="" class="col-sm-5 col-form-label">
														<?php echo esc_html($carrierField['label']); ?>
														<?php if ( ! empty( $carrierField['help'] ) ): ?>
                                                        <label>
                                                            <div class="help-tip">
                                                                <p><?php echo esc_html($carrierField['help']); ?></p>
                                                            </div>
                                                        </label>
														<?php endif; ?>
                                                    </label>
                                                    <div class="col-sm-5">
														<?php
														$carrierFieldValue = $export_form[ $carrierField['name'] ] ?? '';
														$carrierFieldType = $carrierField['type'] ?? 'text';
														?>
														<?php if ( $carrierFieldType === 'terminal' ): ?>
                                                        <div class="input-group input-group-inline">
                                                            <input
                                                                    type="text"
                                                                    class="form-control"
                                                                    form="eslExportForm"
                                                                    placeholder="<?php echo esc_attr($carrierField['placeholder'] ?? $carrierField['label']); ?>"
                                                                    name="<?php echo esc_attr($carrierField['name']); ?>"
                                                                    value="<?php echo esc_attr($carrierFieldValue) ?>"
                                                            />
                                                            <div class="input-group-append">
                                                                <button type="button" class="btn btn-primary esl-search-terminal"
                                                                        data-service="<?php echo esc_attr($carrierSlug); ?>"
                                                                        data-target="<?php echo esc_attr($carrierField['name']); ?>">
																	<?php esc_html_e( 'Поиск терминала', 'eshoplogisticru' ) ?>
                                                                </button>
                                                            </div>
                                                        </div>
														<?php else: ?>
                                                        <input
                                                                type="<?php echo esc_attr($carrierFieldType); ?>"
                                                                class="form-control"
                                                                form="eslExportForm"
														<?php if ( $carrierFieldType === 'text' ): ?>
                                                                placeholder="<?php echo esc_attr($carrierField['placeholder'] ?? $carrierField['label']); ?>"
														<?php endif; ?>
                                                                name="<?php echo esc_attr($carrierField['name']); ?>"
                                                                value="<?php echo esc_attr($carrierFieldValue) ?>"
                                                        />
														<?php endif; ?>
                                                    </div>
                                                </div>
											<?php endforeach; ?>

											<?php if ( $carrierSlug === 'delline' ):
												// Заказчик перевозки (ДЛ) — список контрагентов запрашивается через API; при ошибке — обычное текстовое поле.
												$sender_uid = $export_form['sender-uid-delline'] ?? '';
												$counterparties = $eshopLogisticApi->apiServiceCounterparties( 'delline' );
												$counterparties = $counterparties->hasErrors() ? array() : $counterparties->data();
												?>
                                                <div class="form-group row align-items-center mb-3">
                                                    <label for="" class="col-sm-5 col-form-label">
														<?php esc_html_e( 'Заказчик перевозки (Деловые линии)', 'eshoplogisticru' ) ?>
                                                        <label>
                                                            <div class="help-tip">
                                                                <p>
                                                                    Значение UID контрагента из списка контрагентов в личном кабинете на сайте ДЛ.
                                                                    Игнорируется при отсутствии полного доступа к контрагентам; иначе - обязателен.
                                                                </p>
                                                            </div>
                                                        </label>
                                                    </label>
                                                    <div class="col-sm-5">
														<?php if ( isset( $counterparties['counterparties'] ) ): ?>
                                                            <select id="senderUidDelline" name="sender-uid-delline" form="eslExportForm" class="form-control">
																<?php foreach ( $counterparties['counterparties'] as $counterpartyKey => $counterpartyValue ): ?>
                                                                    <option value="<?php echo esc_attr($counterpartyValue['uid']); ?>" <?php echo esc_attr( $sender_uid == $counterpartyValue['uid'] ? 'selected' : '' ); ?>>
																			<?php echo esc_html($counterpartyValue['name']) ?>
                                                                    </option>
																<?php endforeach; ?>
                                                            </select>
														<?php else: ?>
                                                            <input
                                                                    type="text"
                                                                    class="form-control"
                                                                    form="eslExportForm"
                                                                    placeholder="<?php esc_html_e( 'UID', 'eshoplogisticru' ) ?>"
                                                                    name="sender-uid-delline"
                                                                    value="<?php echo esc_attr( $sender_uid ) ?>"
                                                            />
														<?php endif; ?>
                                                    </div>
                                                </div>
											<?php endif; ?>

                                            <div class="form-group row align-items-center mb-3">
                                                <label for="" class="col-sm-5 col-form-label">
													<?php esc_html_e( 'Способ оплаты заказа по умолчанию', 'eshoplogisticru' ) ?>
                                                </label>
                                                <div class="col-sm-5">
													<?php $defaultPaymentType = $export_form[ 'default-payment-type-' . $carrierSlug ] ?? ''; ?>
                                                    <select name="default-payment-type-<?php echo esc_attr($carrierSlug); ?>" form="eslExportForm" class="form-control">
														<?php foreach ( $carrierPaymentTypeOptions as $optValue => $optLabel ): ?>
                                                            <option value="<?php echo esc_attr($optValue); ?>" <?php echo esc_attr($defaultPaymentType === $optValue ? 'selected' : ''); ?>>
																<?php echo esc_html($optLabel); ?>
                                                            </option>
														<?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

											<?php if ( $carrierData['take_payment'] ): ?>
                                                <div class="form-group row align-items-center mb-3">
                                                    <label for="" class="col-sm-5 col-form-label">
														<?php esc_html_e( 'Взять оплату с получателя за доставку по умолчанию', 'eshoplogisticru' ) ?>
                                                    </label>
                                                    <div class="col-sm-5">
                                                        <input type="checkbox"
                                                               name="default-take-payment-<?php echo esc_attr($carrierSlug); ?>"
                                                               form="eslExportForm"
                                                            <?php echo esc_attr( ! empty( $export_form[ 'default-take-payment-' . $carrierSlug ] ) ? 'checked' : '' ); ?>>
                                                    </div>
                                                </div>
											<?php endif; ?>

                                            <div class="form-group row align-items-center mb-3">
                                                <label for="" class="col-sm-5 col-form-label">
													<?php esc_html_e( 'Нулевая объявленная стоимость по умолчанию', 'eshoplogisticru' ) ?>
                                                </label>
                                                <div class="col-sm-5">
                                                    <input type="checkbox"
                                                           name="type-price-null-<?php echo esc_attr($carrierSlug); ?>"
                                                           form="eslExportForm"
                                                        <?php echo esc_attr( ! empty( $export_form[ 'type-price-null-' . $carrierSlug ] ) ? 'checked' : '' ); ?>>
                                                </div>
                                            </div>

                                            <button type="button" class="wc-esl-add__button btn-primary" data-mode="<?php echo esc_attr($carrierSlug); ?>">
												<?php esc_html_e( 'Настройка дополнительных услуг', 'eshoplogisticru' ) ?>
                                            </button>
                                        </div>
									<?php endforeach; ?>
                                </div>

                                <div class="form-group row align-items-center mb-3">
                                    <div class="col-sm-12">
                                        <button type="submit" form="eslExportForm" class="btn btn-primary float-end">
                                            <?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                        </button>
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

                            <div id="modal-esl-terminal-search" class="modal-esl-frame">
                                <div class="modal_content">
                                    <div class="title">
                                        <span class="close_modal_window">×</span>
                                        <p><strong>Выбор терминала ТК, в который вы будете сдавать заказы на доставку</strong><br></p>
                                    </div>
                                    <div class="esl-terminal-search-fields">
                                        <label><?php esc_html_e( 'Для быстрого поиска укажите название улицы', 'eshoplogisticru' ) ?></label>
                                        <input type="text" id="settlementTerminalSearch" class="form-control" placeholder="<?php esc_attr_e( 'Город или населённый пункт', 'eshoplogisticru' ) ?>">
                                        <input type="text" id="addressTerminalSearch" class="form-control" placeholder="<?php esc_attr_e( 'Найти ПВЗ / постамат по его адресу', 'eshoplogisticru' ) ?>">
                                    </div>
                                    <div id="content-terminal-search_ajax"></div>
                                    <div class="footer">
                                        <input id="buttonModalTerminalSearch" class="btn btn-primary" type="button" value="Поиск">
                                    </div>
                                </div>
                            </div>

                        </div>
	                <?php endif; ?>

                    </div>
                    <div class="tab-pane fade" id="esl-top-tab-status" role="tabpanel">
					<?php if ( $moduleVersion ): ?>
                        <div class="card wc-esl-settings-status esl-section_drag">
                            <div class="card-header">
								<?php esc_html_e( 'Настройка статусов', 'eshoplogisticru' ) ?>
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
															<?php echo esc_html( $name ); ?>
                                                        </div>
                                                        <ul class="js-inner-connected sortable"
                                                            name="<?php echo esc_attr($key); ?>"
                                                            aria-dropeffect="move">
															<?php if ( isset( $status_form[ $key ] ) && $status_form[ $key ] ): ?>
																<?php foreach ( $status_form[ $key ] as $item ): ?>
                                                                    <li name="<?php echo esc_attr($item['name']); ?>"
                                                                        data-desc="<?php echo esc_attr($item['desc']); ?>"
                                                                        class="esl-status__wp"
                                                                        role="option" aria-grabbed="false">
                                                                        <span class=""
                                                                              draggable="true"><?php echo esc_attr($item['desc']); ?></span>
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
                                                        <li name="<?php echo esc_attr($key); ?>" data-desc="<?php echo esc_attr($value); ?>"
                                                            class="esl-status__wp" role="option" aria-grabbed="false">
                                                            <span class="" draggable="true"><?php echo esc_html($value); ?></span>
                                                        </li>
													<?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <button class="btn btn-primary float-end" id="statusSave">
									<?php esc_html_e( 'Сохранить', 'eshoplogisticru' ) ?>
                                </button>
                            </div>
                        </div>
					<?php endif; ?>

                    </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>