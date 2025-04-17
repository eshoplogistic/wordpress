<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

$terminals = !empty($terminals) ? $terminals : '';
$key_ya = !empty($key_ya) ? $key_ya : '';

?>

<input type="hidden" name="wc-esl-terminals" id="wcEslTerminals" value="<?php echo esc_attr(htmlspecialchars($terminals)) ?>" />
<input type="hidden" name="wc-esl-api-key-ya" id="wcEslKeyYa" value="<?php echo esc_attr(htmlspecialchars($key_ya)) ?>" />