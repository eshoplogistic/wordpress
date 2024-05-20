<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
$widgetKey = !empty($widgetKey) ? $widgetKey : '';
$widgetOffersEsl = !empty($widgetOffersEsl) ? json_encode($widgetOffersEsl) : '';
$paymentMethods = !empty($paymentMethods) ? json_encode($paymentMethods) : '';
$widgetCityEsl = !empty($widgetCityEsl) ? json_encode($widgetCityEsl) : '';
$paymentCalc = !empty($paymentCalc) ? $paymentCalc : '';

if ( ! $widgetKey && ! $widgetCityEsl ) {
	exit;
}

?>

<div id="boxEshoplogistic" class="boxEshoplogistic">
    <div id='eShopLogisticWidgetKey' data-key='<?php echo $widgetKey?>'></div>
    <input id='widgetOffersEsl' value='<?php echo $widgetOffersEsl?>' type='hidden'>
    <input id='widgetCityEsl' value='<?php echo $widgetCityEsl?>' type='hidden'>
    <input id='widgetPaymentEsl' value='<?php echo $paymentMethods?>' type='hidden'>
    <?php if($paymentCalc): ?>
        <input id='paymentCalc' value='true' type='hidden'>
    <?php endif;?>
</div>
