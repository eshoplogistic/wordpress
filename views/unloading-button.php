<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shippingMethods    = $shippingMethods ?? array();
$unloadingStatus    = isset($shippingMethods['answer']['state']['status']['code']);
?>

<?php if($unloadingStatus): ?>
    <p class="esl-status__order"><?php echo esc_html('Заказ выгружен'); ?></p>
<?php endif; ?>

<button type="button" id="esl_unloading_form" class="button button-primary" title="<?php echo esc_attr('Выгрузить в кабинет службы доставки'); ?>" <?php echo esc_attr($unloadingStatus ? 'disabled' : '') ?>>
    <span class="dashicons dashicons-share-alt2"></span>
</button>
<button type="button" id="esl_unloading_status" class="button button-primary" title="<?php echo esc_attr('Данные о выгрузке службы доставки'); ?>">
    <span class="dashicons dashicons-clipboard"></span>
</button>
<button type="button" id="esl_unloading_status_update" class="button button-primary" title="<?php echo esc_attr('Обновить статус заказа'); ?>">
    <span class="dashicons dashicons-update-alt"></span>
</button>
<?php if(isset($_GET['eslD'])): ?>
<button type="button" id="esl_unloading_delete" class="button button-primary" title="<?php echo esc_attr('Удалить выгрузку'); ?>">
    <span class="dashicons dashicons-trash"></span>
</button>
<?php endif; ?>