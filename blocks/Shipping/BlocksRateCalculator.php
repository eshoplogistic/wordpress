<?php
/**
 * КОНТЕКСТ: Blocks checkout
 * Специализированный калькулятор тарифов доставки для WooCommerce Blocks checkout.
 *
 * В данный момент не используется: legacy-код в Classes/Shipping/Base.php::calculate_shipping()
 * работает для обоих сценариев.
 *
 * Использование в будущем: когда потребуются оптимизации специально для Blocks
 * (например, пошаговый расчет, частичные обновления, обновление тарифов в реальном времени),
 * эта сущность будет содержать специализированную логику.
 *
 * @package eshoplogisticru
 * @subpackage blocks
 */

namespace eshoplogistic\WCEshopLogistic\Blocks\Shipping;

/**
 * Пример того, как может быть устроен калькулятор тарифов специально для Blocks:
 *
 * class BlocksRateCalculator {
 *     /**
 *      * Расчет тарифов доставки специально для Blocks checkout.
 *      *
 *      * Blocks может вызывать это несколько раз, пока клиент заполняет поля формы,
 *      * поэтому эта версия корректно обрабатывает частичные данные.
 *      * /
 *     public function calculate( $package ) {
 *         // Проверка, что мы в контексте Blocks
 *         if ( ! $this->isBlocksContext() ) {
 *             return [];
 *         }
 *         
 *         // Обработка частичных данных (город может быть пустым при заполнении формы)
 *         $city = $this->getCustomerCity();
 *         
 *         // Если город отсутствует, возвращаем пустой результат и даем Blocks повторить позже
 *         if ( ! $city ) {
 *             return [];
 *         }
 *         
 *         // Расчет тарифов с учетом выбора frame из сессии
 *         return $this->calculateWithFrameSelection( $package, $city );
 *     }
 *     
 *     /**
 *      * Применение выбранного frame к рассчитанным тарифам.
 *      * Специфика Blocks: Label должен отражать имя выбранного сервиса.
 *      * /
 *     private function calculateWithFrameSelection( $package, $city ) {
 *         // Получение выбранного frame из сессии
 *         $sessionService = new SessionService();
 *         $frame = $sessionService->get( 'esl_shipping_frame' );
 *         
 *         // Если frame выбран, используем его
 *         if ( $frame && isset( $frame['name'], $frame['price'] ) ) {
 *             return [
 *                 'id' => 'eshoplogistic_blocks',
 *                 'label' => $frame['name'], // Blocks ожидает label из frame
 *                 'cost' => $frame['price']['value'],
 *             ];
 *         }
 *         
 *         // Иначе рассчитываем стандартно
 *         return $this->calculateDefault( $package, $city );
 *     }
 * }
 */
?>
