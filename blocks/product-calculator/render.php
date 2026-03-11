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
$wc_esl_product = wc_get_product( $post->ID );

if ( ! $wc_esl_product ) {
    return '';
}

$wc_esl_options_repo = new \eshoplogistic\WCEshopLogistic\DB\OptionsRepository();
$wc_esl_widget_key = ! empty( $attributes['widgetKey'] ) 
    ? sanitize_text_field( $attributes['widgetKey'] )
    : $wc_esl_options_repo->getOption( 'wc_esl_shipping_widget_key' );

if ( ! $wc_esl_widget_key ) {
    return '';
}

$wc_esl_display_mode = $attributes['displayMode'] ?? 'button';
$wc_esl_classes = 'wp-block-eshoplogisticru-product-calculator wc-esl-product-calculator-block';

?>
<div class="<?php echo esc_attr( $wc_esl_classes ); ?>" 
     data-widget-key="<?php echo esc_attr( $wc_esl_widget_key ); ?>" 
     data-display-mode="<?php echo esc_attr( $wc_esl_display_mode ); ?>" 
     data-product-id="<?php echo esc_attr( $wc_esl_product->get_id() ); ?>">
    <?php if ( 'button' === $wc_esl_display_mode ) : ?>
        <button class="wc-esl-calculator-trigger button button-primary">
            <?php echo esc_html( __( 'Quick Order with Delivery', 'eshoplogisticru' ) ); ?>
        </button>
    <?php else : ?>
        <div id="eShopLogisticWidgetBlock" 
             data-lazy-load="true" 
             data-widget-key="<?php echo esc_attr( $wc_esl_widget_key ); ?>"></div>
    <?php endif; ?>
</div>
