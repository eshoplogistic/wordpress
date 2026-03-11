<?php
/**
 * Структура поддержки WooCommerce Blocks
 * Модуль BlocksAjax инициализируется в Classes/WCEshopLogistic.php
 * Он регистрирует AJAX-хук с priority 5 (срабатывает ДО legacy-обработчика)
 * Проверяет $_POST['checkout_context'] и маршрутизирует запрос соответствующим образом
 * Вызов wp_send_json_success() вызывает wp_die(), предотвращая выполнение legacy-обработчика
 *
 * @package eshoplogisticru
 * @version 2.1.61
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
