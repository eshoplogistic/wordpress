<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
$eslBillingCityFields = !empty($eslBillingCityFields) ? $eslBillingCityFields : '';
$eslShippingCityFields = !empty($eslShippingCityFields) ? $eslShippingCityFields : '';
$offAddressCheck = $offAddressCheck ?? false;
?>

<input id='eslBillingCityFields' value='<?php echo esc_attr($eslBillingCityFields)?>' type='hidden'>
<input id='eslShippingCityFields' value='<?php echo esc_attr($eslShippingCityFields)?>' type='hidden'>
<?php if ( $offAddressCheck ) : ?>
    <input id='offAddressCheck' value='<?php echo esc_attr($offAddressCheck)?>' type='hidden'>
<?php endif; ?>
