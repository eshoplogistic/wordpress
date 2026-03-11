<?php
/**
 * Render callback for eshoplogisticru/checkout-form block
 * 
 * @package eshoplogistic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$wc_esl_form_type = $attributes['formType'] ?? 'full';
$wc_esl_classes = 'wp-block-eshoplogisticru-checkout-form wc-esl-checkout-form-block';

?>
<div class="<?php echo esc_attr( $wc_esl_classes ); ?>" data-form-type="<?php echo esc_attr( $wc_esl_form_type ); ?>">
    <!-- Checkout form content will be rendered here via JavaScript -->
</div>
