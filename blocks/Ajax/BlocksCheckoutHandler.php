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

use eshoplogistic\WCEshopLogistic\Helpers\EslLogger;
use eshoplogistic\WCEshopLogistic\Services\SessionService;

class BlocksCheckoutHandler {

	/**
	 * Обработка обновления выбранного shipping frame для WooCommerce Blocks checkout.
	 *
	 * Вызывается по AJAX, когда пользователь выбирает службу доставки в виджете Blocks.
	 * Сохраняет выбор в сессию + сбрасывает кэш WooCommerce + возвращает JSON.
	 */
	public function handleShippingUpdate() {
		if ( ! check_ajax_referer( 'wc-esl-shipping', 'nonce', false ) ) {
			EslLogger::debug( '[ESL BlocksCheckoutHandler] BLOCKED: security check failed' );
			wp_send_json_error(['message' => 'Security check failed']);
			return;
		}

		// Валидация запроса
		if ( ! isset($_POST['data']) ) {
			EslLogger::debug( '[ESL BlocksCheckoutHandler] BLOCKED: missing shipping data' );
			wp_send_json_error(['message' => 'Missing shipping data']);
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON data sanitized after json_decode via sanitizeArray()
		$rawData = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
		$rawData = is_string($rawData) ? $rawData : '';

		$data = $rawData !== '' ? json_decode($rawData, true) : [];
		if ( ! is_array($data) ) {
			$data = [];
		}
		$data = $this->sanitizeArray($data);
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input sanitized via sanitize_text_field()
		$data['city'] = isset($_POST['city']) ? sanitize_text_field(wp_unslash($_POST['city'])) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Input sanitized via sanitize_text_field()
		$forceDoor = isset($_POST['force_door']) && sanitize_text_field(wp_unslash($_POST['force_door'])) === '1';

		$sessionService = new SessionService();
		$sessionService->set('mode_shipping', 'shipping');
		$previousFrame = $sessionService->get('esl_shipping_frame');
		if ( is_string($previousFrame) ) {
			$decodedPrevious = maybe_unserialize($previousFrame);
			if ( is_array($decodedPrevious) ) {
				$previousFrame = $decodedPrevious;
			}
		}

		$frameChanged = $this->hasFrameChanged($previousFrame, $data);

		$mode = isset( $data['mode'] ) ? (string) $data['mode'] : '';
		if ( $forceDoor ) {
			$mode = 'door';
			$data['mode'] = 'door';
		}
		if ( $mode === 'door' ) {
			// Blocks-only: при door не допускаем возврат terminal-адреса из сессии.
			$data['address'] = '';
			$data['terminalAddress'] = '';
			$data['terminalCode'] = '';
			$data['selectPvz'] = '';
		}

		// Сохранение в сессию
		$sessionService->set('esl_shipping_frame', $data);

		// Сбрасываем terminal_location только при явном выборе доставки до двери.
		// terminal_location устанавливается отдельным AJAX-запросом wc_esl_set_terminal_address.
		// При выборе терминального сервиса (mode = 'terminal') конкретный ПВЗ ещё не выбран —
		// сохранённый ранее терминал должен оставаться в сессии до явного выбора door-режима.
		if ( $mode === 'door' ) {
			$sessionService->drop('terminal_location');

			$shippingState = $sessionService->get('shipping');
			if ( is_array($shippingState) ) {
				$shippingState['adress'] = '';
				$shippingState['address'] = '';
				$shippingState['terminalAddress'] = '';
				$shippingState['terminalCode'] = '';
				$sessionService->set('shipping', $shippingState);
			}

			$sessionService->set('shipping_adress', '');

			if ( function_exists('WC') && WC()->customer ) {
				WC()->customer->set_shipping_address_1('');
				WC()->customer->save();
			}
		}

		// В контексте admin-ajax мы только сохраняем frame и сбрасываем кэш доставки.
		// Фактический пересчет доставки должен происходить в запросе WooCommerce Store API,
		// где BlocksShippingRates принудительно выставляет checkout-контекст.
		if ( $frameChanged && function_exists('WC') && WC()->cart ) {
			// Очистка кэша доставки для принудительного пересчета
			$this->clearShippingCache();
		}

		EslLogger::debug( '[ESL BlocksCheckoutHandler] frame updated', [
			'mode'          => $mode,
			'frame_changed' => $frameChanged,
			'frame'         => $data,
		] );

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
