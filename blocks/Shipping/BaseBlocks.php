<?php

namespace eshoplogistic\WCEshopLogistic\Blocks\Shipping;

use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;
use eshoplogistic\WCEshopLogistic\Helpers\EslLogger;

if ( ! defined('ABSPATH')) {
	exit;
}

/**
 * Пересчет тарифов доставки для Blocks
 *
 * Это НЕ самостоятельный метод доставки. Это вспомогательный класс, который пересчитывает
 * тарифы доставки для контекста Blocks checkout (REST API).
 *
 * Используется модулем BlocksShippingFilter для добавления тарифов доставки,
 * когда Legacy Base::calculate_shipping() пропускает расчет из-за того,
 * что is_checkout() возвращает false.
 *
 * @since 2.1.61
 */
class BaseBlocks
{
	/**
	 * Расчет тарифа доставки для контекста Blocks
	 *
	 * Повторяет логику Base::calculate_shipping(), но работает в контексте REST API.
	 *
	 * @param object $shippingMethod Экземпляр legacy-метода доставки
	 * @param array $package Пакет доставки
	 * @return array|null Рассчитанный тариф или null
	 */
	public static function calculateForBlocks($shippingMethod, $package)
	{
		// Проверка, что это REST-контекст Blocks
		if (!defined('REST_REQUEST') || !REST_REQUEST) {
			return null;
		}

		$optionsRepository = new OptionsRepository();
		$frameEnable = $optionsRepository->getOption('wc_esl_shipping_frame_enable');

		try {
			if($frameEnable)
			{
				$rate = $shippingMethod->calculate_shipping_frame($package);
			}else{
				$rate = $shippingMethod->calculate_shipping_basic($package);
			}
		} catch(\Exception $e) {
			EslLogger::debug( '[ESL BaseBlocks::calculateForBlocks] ' . $e->getMessage(), [
				'frame_enable' => $frameEnable,
				'method_id'    => is_object($shippingMethod) && method_exists($shippingMethod, 'getSlug') ? $shippingMethod->getSlug() : null,
			] );
			throw $e;
		}

		return $rate;
	}
}
