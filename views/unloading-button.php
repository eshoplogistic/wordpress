<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_esl_shippingMethods    = $wc_esl_shippingMethods ?? array();
$wc_esl_unloadingStatus    = isset($wc_esl_shippingMethods['answer']['state']['status']['code']);
?>

<?php if($wc_esl_unloadingStatus): ?>
    <p class="esl-status__order"><?php echo esc_html('Заказ выгружен'); ?></p>
<?php endif; ?>

<button type="button" id="esl_unloading_form" class="button button-primary" title="<?php echo esc_attr('Выгрузить в кабинет службы доставки'); ?>" <?php echo esc_attr($wc_esl_unloadingStatus ? 'disabled' : '') ?>>
    <span class="dashicons dashicons-share-alt2"></span>
</button>
<button type="button" id="esl_unloading_status" class="button button-primary" title="<?php echo esc_attr('Данные о выгрузке службы доставки'); ?>">
    <span class="dashicons dashicons-clipboard"></span>
</button>
<button type="button" id="esl_unloading_status_update" class="button button-primary" title="<?php echo esc_attr('Обновить статус заказа'); ?>">
    <span class="dashicons dashicons-update-alt"></span>
</button>
<?php if($wc_esl_unloadingStatus): ?>
<button type="button" id="esl_unloading_delete" class="button button-primary" title="<?php echo esc_attr('Удалить выгрузку'); ?>">
    <span class="dashicons dashicons-trash"></span>
</button>
<?php endif; ?>