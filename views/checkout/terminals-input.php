<?php

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Variables passed via View::render extract are prefixed

if ( ! defined('ABSPATH') ) {
    exit;
}

$wc_esl_terminals = !empty($wc_esl_terminals) ? $wc_esl_terminals : '';
$wc_esl_key_ya = !empty($wc_esl_key_ya) ? $wc_esl_key_ya : '';

?>

<input type="hidden" name="wc-esl-terminals" id="wcEslTerminals" value="<?php echo esc_attr(htmlspecialchars($wc_esl_terminals)) ?>" />
<input type="hidden" name="wc-esl-api-key-ya" id="wcEslKeyYa" value="<?php echo esc_attr(htmlspecialchars($wc_esl_key_ya)) ?>" />