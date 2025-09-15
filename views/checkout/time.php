<?php if($time && !is_array($time)):?>
<div class="wc-esl-shipping-method-time">
	<p><?php esc_html_e( 'Срок:', 'eshoplogisticru' ) ?> <span><?php echo esc_attr($time) ?></span></p>
</div>
<?php endif; ?>
<?php if($time && is_array($time)):?>
    <div class="wc-esl-shipping-method-time">
        <p><?php esc_html_e( 'Срок:', 'eshoplogisticru' ) ?>
            <span>
                <?php echo esc_attr($time['value']) ?>
                <?php echo esc_attr($time['unit']) ?>
            </span>
        </p>
    </div>
<?php endif; ?>
