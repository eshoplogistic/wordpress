<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wc-esl-shipping-method-comment">
	<p><?php echo esc_html($wc_esl_comment) ?></p>
</div>