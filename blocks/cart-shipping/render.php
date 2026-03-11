<?php
/**
 * Render callback for eshoplogisticru/cart-shipping block
 * 
 * @package eshoplogistic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_cart() ) {
    return '';
}

$wc_esl_classes = 'wp-block-eshoplogisticru-cart-shipping wc-esl-cart-shipping-block';

?>
<div class="<?php echo esc_attr( $wc_esl_classes ); ?>" data-block-type="cart-shipping">
    <!-- Cart shipping content will be rendered here via JavaScript -->
</div>
