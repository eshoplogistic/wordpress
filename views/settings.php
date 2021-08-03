<?php
if ( ! defined('ABSPATH') ) {
    exit;
}

$plugin_enable = isset($plugin_enable) ? $plugin_enable : '0';
$api_key = !empty($api_key) ? $api_key : '';
$paymentMethods = isset($paymentMethods) ? $paymentMethods : [];
$secret_code = !empty($secret_code) ? $secret_code : '';
$widget_key = !empty($widget_key) ? $widget_key : '';

?>

<div id="wcEslSettings" class="wc-esl-settings">

	<div class="wc-esl-settings__header">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <h1 class="wc-esl-settings__title">
                        <?= __( 'Настройки eShop<span>Logistic</span> Shipping', WC_ESL_DOMAIN ) ?>
                    </h1>
                </div>
            </div>
        </div>
	</div>

    <div class="wc-esl-settings__body">

        <div class="container-fluid wc-esl-settings-general-options">
            <div class="row">
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <?= __( 'Основные настройки', WC_ESL_DOMAIN ) ?>

                            <button class="btn btn-primary" id="updateCache">
                                <?= __( 'Сбросить кэш', WC_ESL_DOMAIN ) ?>
                            </button>
                        </div>
                        <div class="card-body">

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
                                    <?= __( 'Включить/выключить', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-5">
                                    <div class="custom-control custom-switch">
                                        <input
                                                type="checkbox"
                                                class="custom-control-input"
                                                id="enablePlugin"
                                                name="enable_plugin"
                                                <?= $plugin_enable === '1' ? 'checked' : '' ?>
                                        >
                                        <label class="custom-control-label" for="enablePlugin"></label>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
                                    <?= __( 'API Ключ', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="apiKeyForm">
                                        <div class="input-group">
                                            <input
                                                    type="text"
                                                    class="form-control"
                                                    placeholder="<?= __( 'API Ключ', WC_ESL_DOMAIN ) ?>"
                                                    id="apiKeyInput"
                                                    name="api_key"
                                                    value="<?= $api_key ?>"
                                            >
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
                                                    <?= __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <?= __( 'Настройки оплаты', WC_ESL_DOMAIN ) ?>
                        </div>

                        <div class="card-body">
                            <form action="/" method="post" id="eslPayTypeForm">
                                <table class="table table-striped">
                                    <thead>
                                        <th scope="col">#</th>
                                        <th scope="col"><?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH ?></th>
                                        <th scope="col"><?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD ?></th>
                                        <th scope="col"><?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS ?></th>
                                        <th scope="col"><?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY ?></th>
                                    </thead>
                                    <tbody>

                                        <?php if(!empty($paymentGateways)) : ?>
                                            <?php foreach ($paymentGateways as $paymentGateway) : ?>

                                            <tr>
                                                <th scope="row"><?= $paymentGateway->title ?></th>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?= $paymentGateway->id ?>]"
                                                            value="<?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH ?>"
                                                            <?= ( $paymentMethods[$paymentGateway->id] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASH ) ? 'checked' : '' ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?= $paymentGateway->id ?>]"
                                                            value="<?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD ?>"
                                                            <?= ( $paymentMethods[$paymentGateway->id] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CARD ) ? 'checked' : '' ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?= $paymentGateway->id ?>]"
                                                            value="<?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS ?>"
                                                            <?= ( $paymentMethods[$paymentGateway->id] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_CASHLESS ) ? 'checked' : '' ?>
                                                    />
                                                </td>
                                                <td>
                                                    <input
                                                            type="radio"
                                                            name="esl_pay_type[<?= $paymentGateway->id ?>]"
                                                            value="<?= \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY ?>"
                                                            <?= ( $paymentMethods[$paymentGateway->id] === \eshoplogistic\WCEshopLogistic\DB\PaymentGatewaysRepository::PAYTYPE_PREPAY ) ? 'checked' : '' ?>
                                                    />
                                                </td>
                                            </tr>

                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>

                                <button class="btn btn-primary" type="submit">
                                    <?= __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card wc-esl-settings-widget">
                        <div class="card-header">
                            <?= __( 'Виджет eShopLogistic', WC_ESL_DOMAIN ) ?>
                        </div>

                        <div class="card-body" id="eslWidgetFormWrap">
                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
                                    <?= __( 'Ключ виджета', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetKeyForm">
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="<?= __( 'Ключ виджета', WC_ESL_DOMAIN ) ?>"
                                                id="eslWidgetKey"
                                                name="esl_widget_key"
                                                value="<?= $widget_key ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
                                                    <?= __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="form-group row align-items-center mb-3">
                                <label for="" class="col-sm-2 col-form-label">
                                    <?= __( 'Секретный код', WC_ESL_DOMAIN ) ?>
                                </label>
                                <div class="col-sm-8">
                                    <form action="/" method="post" id="eslWidgetSecretCodeForm">
                                        <div class="input-group">
                                            <input
                                                type="text"
                                                class="form-control"
                                                placeholder="<?= __( 'Секретный код', WC_ESL_DOMAIN ) ?>"
                                                id="eslWidgetSecretCode"
                                                name="esl_widget_secret_code"
                                                value="<?= $secret_code ?>"
                                            />
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="submit">
                                                    <?= __( 'Сохранить', WC_ESL_DOMAIN ) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
	
</div>