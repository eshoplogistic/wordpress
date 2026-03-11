<?php
/**
 * Render callback for eshoplogisticru/product-calculator block
 * 
 * @package eshoplogistic
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_product() ) {
    return '';
}

global $post;
$product = wc_get_product( $post->ID );

if ( ! $product ) {
    return '';
}

$options_repo = new \eshoplogistic\WCEshopLogistic\DB\OptionsRepository();
$widget_key = ! empty( $attributes['widgetKey'] ) 
    ? sanitize_text_field( $attributes['widgetKey'] )
    : $options_repo->getOption( 'wc_esl_shipping_widget_key' );

if ( ! $widget_key ) {
    return '';
}

$display_mode = $attributes['displayMode'] ?? 'button';
$classes = 'wp-block-eshoplogisticru-product-calculator wc-esl-product-calculator-block';

?>
<div class="<?php echo esc_attr( $classes ); ?>" 
     data-widget-key="<?php echo esc_attr( $widget_key ); ?>" 
     data-display-mode="<?php echo esc_attr( $display_mode ); ?>" 
     data-product-id="<?php echo esc_attr( $product->get_id() ); ?>">
    <?php if ( 'button' === $display_mode ) : ?>
        <button class="wc-esl-calculator-trigger button button-primary">
            <?php echo esc_html( __( 'Quick Order with Delivery', 'eshoplogisticru' ) ); ?>
        </button>
    <?php else : ?>
        <div id="eShopLogisticWidgetBlock" 
             data-lazy-load="true" 
             data-widget-key="<?php echo esc_attr( $widget_key ); ?>"></div>
    <?php endif; ?>
</div>
