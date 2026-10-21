<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed

if ( ! defined('ABSPATH') ) {
	exit;
}
$wc_esl_eslBillingCityFields = !empty($wc_esl_eslBillingCityFields) ? $wc_esl_eslBillingCityFields : '';
$wc_esl_eslShippingCityFields = !empty($wc_esl_eslShippingCityFields) ? $wc_esl_eslShippingCityFields : '';
$wc_esl_offAddressCheck = $wc_esl_offAddressCheck ?? false;
?>

<input id='eslBillingCityFields' value='<?php echo esc_attr($wc_esl_eslBillingCityFields)?>' type='hidden'>
<input id='eslShippingCityFields' value='<?php echo esc_attr($wc_esl_eslShippingCityFields)?>' type='hidden'>
<?php if ( $wc_esl_offAddressCheck ) : ?>
    <input id='offAddressCheck' value='<?php echo esc_attr($wc_esl_offAddressCheck)?>' type='hidden'>
<?php endif; ?>
