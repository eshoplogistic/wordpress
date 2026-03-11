<?php
/**
 * КОНТЕКСТ: только Blocks checkout
 * Специализированный AJAX-обработчик для обновления доставки в WooCommerce Blocks checkout.
 *
 * Этот обработчик добавляет сброс кэша и JSON-ответы, необходимые для Blocks.
 * Используется вместо базовой логики Modules/Ajax.php::updateShipping(), когда
 * Blocks checkout определяет, что нужно использовать оптимизированную обработку.
 *
 * @package eshoplogisticru
 * @subpackage blocks
 */

namespace eshoplogistic\WCEshopLogistic\Blocks\Ajax;

use eshoplogistic\WCEshopLogistic\Services\SessionService;

class BlocksCheckoutHandler {
	
	/**
	 * Обработка обновления выбранного shipping frame для WooCommerce Blocks checkout.
	 *
	 * Вызывается по AJAX, когда пользователь выбирает службу доставки в виджете Blocks.
	 * Сохраняет выбор в сессию + сбрасывает кэш WooCommerce + возвращает JSON.
	 */
	public function handleShippingUpdate() {
		// Валидация запроса
		if ( ! isset($_POST['data']) ) {
			wp_send_json_error(['message' => 'Missing shipping data']);
			return;
		}

		// Извлечение и санитизация
		$data = isset($_POST['data']) ? $this->sanitizeArray($_POST['data']) : '';
		$data = json_decode(stripslashes($data), true);
		if ( ! is_array($data) ) {
			$data = [];
		}
		$data['city'] = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';

		$sessionService = new SessionService();
		$previousFrame = $sessionService->get('esl_shipping_frame');
		if ( is_string($previousFrame) ) {
			$decodedPrevious = maybe_unserialize($previousFrame);
			if ( is_array($decodedPrevious) ) {
				$previousFrame = $decodedPrevious;
			}
		}

		$frameChanged = $this->hasFrameChanged($previousFrame, $data);

		// Сохранение в сессию
		$sessionService->set('esl_shipping_frame', $data);
		
		if ( !isset($data['address']) || !$data['address'] ) {
			$sessionService->drop('terminal_location');
		}

		// В контексте admin-ajax мы только сохраняем frame и сбрасываем кэш доставки.
		// Фактический пересчет доставки должен происходить в запросе WooCommerce Store API,
		// где BlocksShippingRates принудительно выставляет checkout-контекст.
		if ( $frameChanged && function_exists('WC') && WC()->cart ) {
			// Очистка кэша доставки для принудительного пересчета
			$this->clearShippingCache();
		}

		// Возврат JSON-ответа для Blocks store
		// JS вызовет invalidateResolutionForStore(), что заставит
		// WooCommerce получить заново рассчитанную доставку
		wp_send_json_success([
			'updated' => true,
			'context' => 'blocks',
			'frame' => $data,
		]);
	}

	/**
	 * Очистка кэша доставки WooCommerce.
	 *
	 * Очищает только ключи кэша доставки для тарифов пакетов.
	 */
	private function clearShippingCache() {
		if ( ! function_exists('WC') || ! WC()->session ) {
			return;
		}

		// Получение всех ключей сессии
		$sessionData = WC()->session->get_session_data();
		if ( ! is_array($sessionData) ) {
			return;
		}

		// Удаление только ключей кэша доставки
		foreach ( $sessionData as $key => $value ) {
			if ( strpos( (string) $key, 'shipping_for_package_' ) === 0 ) {
				WC()->session->__unset( $key );
			}
		}
	}

	/**
	 * Рекурсивная санитизация значений массива.
	 *
	 * @param array $array Массив для санитизации
	 * @return array Санитизированный массив
	 */
	private function sanitizeArray($array) {
		if ( ! is_array($array) ) {
			return $array;
		}

		foreach ( $array as $key => $value ) {
			if ( is_array($value) ) {
				$array[$key] = $this->sanitizeArray($value);
			} else {
				$array[$key] = is_string($value) ? sanitize_text_field($value) : $value;
			}
		}
		return $array;
	}

	/**
	 * Сравнение предыдущего и текущего payload frame, чтобы избежать лишнего сброса кэша.
	 *
	 * @param mixed $previous Ранее сохраненный frame
	 * @param array $current Текущий payload frame
	 * @return bool
	 */
	private function hasFrameChanged($previous, array $current): bool {
		if ( ! is_array($previous) ) {
			return true;
		}

		$normalize = static function(array $payload): array {
			return [
				'name' => (string) ($payload['name'] ?? ''),
				'key' => (string) ($payload['key'] ?? ''),
				'mode' => (string) ($payload['mode'] ?? ''),
				'time' => (string) ($payload['time'] ?? ''),
				'city' => (string) ($payload['city'] ?? ''),
				'address' => (string) ($payload['address'] ?? ''),
				'price' => isset($payload['price']['value']) ? (float) $payload['price']['value'] : 0.0,
			];
		};

		return $normalize($previous) !== $normalize($current);
	}
}
