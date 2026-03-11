<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Blocks\Ajax\BlocksCheckoutHandler;
use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Blocks AJAX Router
 * 
 * Перехватывает AJAX запросы от WooCommerce Blocks checkout
 * и направляет их в оптимизированный обработчик BlocksCheckoutHandler.
 * 
 * Регистрируется с приоритетом 5 (раньше Legacy handler с приоритетом 10),
 * проверяет checkout_context и если это 'blocks' - вызывает BlocksCheckoutHandler.
 * Если это НЕ blocks - ничего не делает, пропускает к Legacy обработчику.
 * 
 * @since 2.1.61
 */
class BlocksAjax implements ModuleInterface
{
	public function init()
	{
		if (wp_doing_ajax()) {
			$this->initRoutes();
		}
	}

	/**
	 * Регистрация AJAX роутов с высоким приоритетом
	 * Приоритет 5 = выполнится РАНЬШЕ Legacy хука (приоритет 10)
	 */
	public function initRoutes()
	{
		// Перехват updateShipping для Blocks checkout
		add_action('wp_ajax_nopriv_wc_esl_update_shipping', [$this, 'routeUpdateShipping'], 5);
		add_action('wp_ajax_wc_esl_update_shipping', [$this, 'routeUpdateShipping'], 5);
	}

	/**
	 * Роутер для метода updateShipping
	 * 
	 * Проверяет контекст запроса:
	 * - Если это Blocks (checkout_context === 'blocks') -> направляет в BlocksCheckoutHandler
	 * - Если это Legacy -> ничего не делает, пропускает к Legacy обработчику
	 */
	public function routeUpdateShipping()
	{
		// Проверяем, это Blocks запрос?
		if (!isset($_POST['checkout_context']) || $_POST['checkout_context'] !== 'blocks') {
			// Это Legacy запрос, пропускаем к Legacy обработчику
			return;
		}

		// Это Blocks запрос - используем оптимизированный обработчик
		$handler = new BlocksCheckoutHandler();
		$handler->handleShippingUpdate();

		// wp_die() вызывается внутри BlocksCheckoutHandler::handleShippingUpdate()
		// и останавливает дальнейшую обработку (Legacy handler не вызовется)
	}
}
