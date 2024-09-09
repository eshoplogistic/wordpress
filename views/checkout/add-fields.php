<?php

if ( ! defined('ABSPATH') ) {
	exit;
}
$eslBillingCityFields = !empty($eslBillingCityFields) ? $eslBillingCityFields : '';
$eslShippingCityFields = !empty($eslShippingCityFields) ? $eslShippingCityFields : '';
?>

<input id='eslBillingCityFields' value='<?php echo $eslBillingCityFields?>' type='hidden'>
<input id='eslShippingCityFields' value='<?php echo $eslShippingCityFields?>' type='hidden'>