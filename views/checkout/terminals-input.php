<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

$terminals = !empty($terminals) ? $terminals : '';

?>

<input type="hidden" name="wc-esl-terminals" id="wcEslTerminals" value="<?= htmlspecialchars($terminals) ?>" />