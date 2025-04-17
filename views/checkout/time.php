<?php if($time && !is_array($time)):?>
<div class="wc-esl-shipping-method-time">
	<p><?php echo __( 'Срок:', WC_ESL_DOMAIN ) ?> <span><?php echo esc_attr($time) ?></span></p>
</div>
<?php endif; ?>
<?php if($time && is_array($time)):?>
    <div class="wc-esl-shipping-method-time">
        <p><?php echo __( 'Срок:', WC_ESL_DOMAIN ) ?>
            <span>
                <?php echo esc_attr($time['value']) ?>
                <?php echo esc_attr($time['unit']) ?>
            </span>
        </p>
    </div>
<?php endif; ?>
