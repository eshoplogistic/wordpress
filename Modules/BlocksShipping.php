<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Регистрация методов доставки для Blocks
 *
 * Регистрирует оптимизированные для Blocks методы доставки, поддерживающие контекст REST API.
 * Работает вместе с legacy-методами доставки без конфликтов.
 *
 * ВАЖНО: legacy-методы доставки (Classes/Shipping/) остаются без изменений.
 * Методы Blocks (blocks/Shipping/) используются только для WooCommerce Blocks checkout.
 *
 * @since 2.1.61
 */
class BlocksShipping implements ModuleInterface
{
	public function init()
	{
		// Регистрация методов доставки Blocks с более высоким приоритетом
		// Это гарантирует их доступность для REST API-запросов
		add_filter('woocommerce_shipping_methods', [$this, 'registerBlocksShippingMethods'], 20);
	}

	/**
	 * Регистрация оптимизированных для Blocks методов доставки
	 *
	 * Регистрирует методы Blocks только во время REST API-запросов (WooCommerce Blocks).
	 * Legacy checkout продолжает использовать стандартные методы из Classes/Shipping/.
	 *
	 * @param array $methods Существующие методы доставки
	 * @return array Измененные методы доставки
	 */
	public function registerBlocksShippingMethods($methods)
	{
		// Регистрируем методы Blocks только для REST API-контекста (WooCommerce Blocks)
		if (!defined('REST_REQUEST') || !REST_REQUEST) {
			return $methods;
		}

		// Заменяем стандартные классы Base на оптимизированные версии для Blocks
		// Они поддерживают REST API-контекст и AJAX-пересчет
		foreach ($methods as $key => $class) {
			// Проверяем, что это метод доставки eShopLogistic
			if (strpos($class, 'eshoplogistic\WCEshopLogistic\Classes\Shipping\\') === 0) {
				// Заменяем на версию для Blocks
				// Пример: Classes\Shipping\Cdek -> Blocks\Shipping\CdekBlocks
				$blocksClass = str_replace(
					'eshoplogistic\WCEshopLogistic\Classes\Shipping\\',
					'eshoplogistic\WCEshopLogistic\Blocks\Shipping\\',
					$class
				);
				
				// Проверяем, существует ли версия для Blocks
				if (class_exists($blocksClass)) {
					$methods[$key] = $blocksClass;
				}
			}
		}

		return $methods;
	}
}
