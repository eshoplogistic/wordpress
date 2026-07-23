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
	<div class="esl-print__title"><?php echo esc_html__( 'Печатные формы:', 'eshoplogisticru' ); ?></div>

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
				<svg class="esl-print-button__icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
					<path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
					<path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zm7 5V3a1 1 0 0 0-1-1H5a1 1 0 0 0-1 1v3zM4 9.5V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V9.5a.5.5 0 0 0-.5-.5h-7a.5.5 0 0 0-.5.5M2 11a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1V9.5A1.5 1.5 0 0 0 11.5 8h-7A1.5 1.5 0 0 0 3 9.5V11z"/>
				</svg>
				<?php echo esc_html( $wc_esl_button['label'] ); ?>
			</button>
		<?php endforeach; ?>
	</div>

	<div class="esl-print__result"></div>
</div>
