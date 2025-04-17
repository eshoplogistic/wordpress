<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
$eslBillingCityFields = !empty($eslBillingCityFields) ? $eslBillingCityFields : '';
$eslShippingCityFields = !empty($eslShippingCityFields) ? $eslShippingCityFields : '';
$offAddressCheck = $offAddressCheck ?? false;
?>

<input id='eslBillingCityFields' value='<?php echo $eslBillingCityFields?>' type='hidden'>
<input id='eslShippingCityFields' value='<?php echo $eslShippingCityFields?>' type='hidden'>
<?php if ( $offAddressCheck ) : ?>
    <input id='offAddressCheck' value='<?php echo $offAddressCheck?>' type='hidden'>
<?php endif; ?>
