<?php
/**
 * Render callback for eshoplogisticru/checkout-form block
 * 
 * @package eshoplogistic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$form_type = $attributes['formType'] ?? 'full';
$classes = 'wp-block-eshoplogisticru-checkout-form wc-esl-checkout-form-block';

?>
<div class="<?php echo esc_attr( $classes ); ?>" data-form-type="<?php echo esc_attr( $form_type ); ?>">
    <!-- Checkout form content will be rendered here via JavaScript -->
</div>
