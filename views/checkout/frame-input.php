<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed

if ( ! defined('ABSPATH') ) {
	exit;
}
$wc_esl_widgetKey = !empty($wc_esl_widgetKey) ? $wc_esl_widgetKey : '';
$wc_esl_widgetOffersEsl = !empty($wc_esl_widgetOffersEsl) ? json_encode($wc_esl_widgetOffersEsl) : '';
$wc_esl_paymentMethods = !empty($wc_esl_paymentMethods) ? json_encode($wc_esl_paymentMethods) : '';
$wc_esl_widgetCityEsl = !empty($wc_esl_widgetCityEsl) ? json_encode($wc_esl_widgetCityEsl) : '';
$wc_esl_paymentCalc = !empty($wc_esl_paymentCalc) ? $wc_esl_paymentCalc : '';

if ( ! $wc_esl_widgetKey && ! $wc_esl_widgetCityEsl ) {
	exit;
}

?>

<div id="boxEshoplogistic" class="boxEshoplogistic">
    <div id='eShopLogisticWidgetKey' data-key='<?php echo esc_attr($wc_esl_widgetKey)?>'></div>
    <input id='widgetOffersEsl' value='<?php echo esc_attr($wc_esl_widgetOffersEsl)?>' type='hidden'>
    <input id='widgetCityEsl' value='<?php echo esc_attr($wc_esl_widgetCityEsl)?>' type='hidden'>
    <input id='widgetPaymentEsl' value='<?php echo esc_attr($wc_esl_paymentMethods)?>' type='hidden'>
    <?php if($wc_esl_paymentCalc): ?>
        <input id='paymentCalc' value='true' type='hidden'>
    <?php endif;?>
</div>
