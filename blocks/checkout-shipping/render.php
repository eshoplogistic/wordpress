<?php
/**
 * Render callback for eshoplogisticru/checkout-shipping block
 * 
 * @package eshoplogistic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$wc_esl_alignment = $attributes['alignment'] ?? 'left';
$wc_esl_classes = 'wp-block-eshoplogisticru-checkout-shipping align' . sanitize_html_class( $wc_esl_alignment );

?>
<div class="<?php echo esc_attr( $wc_esl_classes ); ?> wc-esl-checkout-shipping-block" data-block-type="checkout-shipping">
    <!-- Shipping calculator content will be rendered here via JavaScript -->
</div>
