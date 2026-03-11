<?php
/**
 * Render callback for eshoplogisticru/checkout-shipping block
 * 
 * @package eshoplogistic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$alignment = $attributes['alignment'] ?? 'left';
$classes = 'wp-block-eshoplogisticru-checkout-shipping align' . sanitize_html_class( $alignment );

?>
<div class="<?php echo esc_attr( $classes ); ?> wc-esl-checkout-shipping-block" data-block-type="checkout-shipping">
    <!-- Shipping calculator content will be rendered here via JavaScript -->
</div>
