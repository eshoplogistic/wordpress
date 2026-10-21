<?php

namespace eshoplogistic\WCEshopLogistic\Helpers;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
	exit;
}

/**
 * Единая точка входа для отладочных логов плагина в WooCommerce > Статус > Журналы
 * (источник "wc-esl-shipping"). Переменные данные (массивы, флаги и т.п.) нужно передавать
 * вторым аргументом $context, а не встраивать в текст сообщения через var_export/json_encode —
 * тогда лог-вьюер WooCommerce сворачивает их в блок "Дополнительный контекст" вместо
 * нечитаемой простыни прямо в строке лога (тот же механизм, что и у логов "place-order-debug").
 *
 * Также централизует проверку настройки "wc_esl_shipping_plugin_enable_log", чтобы её не
 * дублировать в каждом месте, где нужно что-то залогировать.
 */
class EslLogger
{
	/**
	 * @var bool|null
	 */
	private static $enabled = null;

	public static function debug($message, array $context = array())
	{
		self::write('debug', $message, $context);
	}

	public static function info($message, array $context = array())
	{
		self::write('info', $message, $context);
	}

	private static function write($level, $message, array $context)
	{
		if ( ! self::isEnabled() || ! function_exists('wc_get_logger') )
			return;

		if ( ! isset($context['source']) )
			$context['source'] = 'wc-esl-shipping';

		wc_get_logger()->log($level, $message, $context);
	}

	private static function isEnabled()
	{
		if ( null === self::$enabled ) {
			self::$enabled = '1' === ( new OptionsRepository() )->getOption('wc_esl_shipping_plugin_enable_log');
		}

		return self::$enabled;
	}
}
