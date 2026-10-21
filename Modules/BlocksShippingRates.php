<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Калькулятор тарифов доставки для Blocks
 *
 * Обеспечивает работу расчета legacy-методов доставки в контексте WooCommerce Blocks Store API.
 *
 * ЗАЧЕМ НУЖНО:
 * Legacy Base::calculate_shipping() исторически работал только когда is_checkout()
 * возвращает true. Этот фильтр форсит is_checkout() для настоящих REST-запросов Store API
 * (REST_REQUEST=true). Но при гидратации блока чекаута WooCommerce Blocks вызывает Store API
 * контроллер напрямую в PHP (Checkout::render() → Hydration::get_rest_api_response_data()),
 * минуя REST-диспетчер — REST_REQUEST там не выставляется, и этот фильтр эту ветку не покрывает.
 * Так как WooCommerce кеширует посчитанные тарифы по хешу пакета вне зависимости от того, кто их
 * запросил, Base::calculate_shipping() больше не гейтит расчёт на is_checkout() вообще — иначе
 * непокрытый этой веткой сценарий кеширует пакет без ESL-тарифа, и тот не появляется больше нигде
 * для этого сочетания город+корзина, пока хеш не изменится (см. Classes/Shipping/Base.php).
 *
 * @since 2.1.61
 */
class BlocksShippingRates implements ModuleInterface
{
	public function init()
	{
		// Регистрируем глобально, но активируем логику только для WooCommerce Store API
		// внутри callback-ов. Это нужно, потому что REST_REQUEST может быть
		// еще не определен на момент инициализации плагина.
		add_filter('woocommerce_is_checkout', [$this, 'forceCheckoutForBlocks'], 999);
		add_filter('woocommerce_package_rates', [$this, 'addBlocksRates'], 100, 2);
	}

	/**
	 * Принудительно включает checkout-контекст для запросов WooCommerce Blocks Store API.
	 *
	 * @param bool $isCheckout Текущее состояние checkout
	 * @return bool
	 */
	public function forceCheckoutForBlocks($isCheckout)
	{
		if ($this->isBlocksStoreApiRequest()) {
			return true;
		}

		return $isCheckout;
	}

	/**
	 * Определяет запросы WooCommerce Blocks Store API.
	 *
	 * @return bool
	 */
	private function isBlocksStoreApiRequest()
	{
		if (!defined('REST_REQUEST') || !REST_REQUEST) {
			return false;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only route detection for Store API context.
		$restRoute = isset($_REQUEST['rest_route']) ? sanitize_text_field(wp_unslash($_REQUEST['rest_route'])) : '';
		if ($restRoute !== '' && strpos($restRoute, '/wc/store/') !== false) {
			return true;
		}
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$requestUri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
		if ($requestUri === '') {
			return false;
		}

		return strpos($requestUri, '/wc/store/') !== false;
	}

	/**
	 * Оставляет тарифы пакета без изменений; используется как защитный хук в потоке Blocks.
	 *
	 * @param array $rates Рассчитанные тарифы доставки
	 * @param array $package Пакет доставки
	 * @return array
	 */
	public function addBlocksRates($rates, $package)
	{
		$hasEslRates = false;
		foreach ($rates as $rate) {
			if (strpos($rate->get_method_id(), 'wc_esl_') === 0) {
				$hasEslRates = true;
				break;
			}
		}
		
		if ($hasEslRates) {
			return $rates;
		}
		
		return $rates;
	}
}
