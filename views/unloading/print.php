<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_esl_deliveryName = $wc_esl_deliveryName ?? '';
$wc_esl_printButtons = $wc_esl_printButtons ?? array();
$wc_esl_paperOptions = $wc_esl_paperOptions ?? array();
?>

<div class="esl-print" data-service="<?php echo esc_attr( $wc_esl_deliveryName ); ?>">
	<div class="esl-status_infoTitle"><?php echo esc_html__( 'Печатные формы:', 'eshoplogisticru' ); ?></div>

	<?php if ( $wc_esl_paperOptions ) : ?>
		<label class="esl-print__paper">
			<?php echo esc_html__( 'Формат печати:', 'eshoplogisticru' ); ?>
			<select class="esl-print-paper">
				<option value=""><?php echo esc_html__( '- Не выбрано -', 'eshoplogisticru' ); ?></option>
				<?php foreach ( $wc_esl_paperOptions as $wc_esl_paperValue ) : ?>
					<option value="<?php echo esc_attr( $wc_esl_paperValue ); ?>"><?php echo esc_html( $wc_esl_paperValue ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
	<?php endif; ?>

	<div class="esl-print__buttons">
		<?php foreach ( $wc_esl_printButtons as $wc_esl_button ) : ?>
			<button type="button" class="button esl-print-button" data-mode="<?php echo esc_attr( $wc_esl_button['mode'] ); ?>">
				<?php echo esc_html( $wc_esl_button['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="esl-print__result"></div>
</div>
