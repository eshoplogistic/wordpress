<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$shippingMethods    = $shippingMethods ?? array();
$unloadingStatus    = isset($shippingMethods['answer']['state']['status']['code']);
?>

<?php if($unloadingStatus): ?>
    <p class="esl-status__order">Заказ выгружен</p>
<?php endif; ?>

<button type="button" id="esl_unloading_form" class="button button-primary" title="Выгрузить в кабинет службы доставки" <?php echo ($unloadingStatus)?'disabled':''?>>
    <span class="dashicons dashicons-share-alt2"></span>
</button>
<button type="button" id="esl_unloading_status" class="button button-primary" title="Данные о выгрузке службы доставки">
    <span class="dashicons dashicons-clipboard"></span>
</button>
<button type="button" id="esl_unloading_status_update" class="button button-primary" title="Обновить статус заказа">
    <span class="dashicons dashicons-update-alt"></span>
</button>