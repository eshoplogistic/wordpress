<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Classes\View;

if (!defined('ABSPATH')) {
    exit;
}

class UnloadingInfo
{

    /**
     * Строит HTML статуса заказа (номер, идентификатор в ТК, текущий статус) для модалки
     * "Статус" на странице заказа. Компонует UnloadingOrder::infoOrder(), не дублируя логику
     * запроса к ESL API.
     *
     * @param int    $orderId
     * @param string $orderType
     *
     * @return string
     */
    public function initReturn($orderId, $orderType)
    {
        $unloadingOrder = new UnloadingOrder();
        $result = $unloadingOrder->infoOrder($orderId, $orderType);
        $html = '';

        $order = wc_get_order($orderId);
        $orderShippings = $order ? $order->get_shipping_methods() : [];
        $shippingMethod = '';
        foreach ($orderShippings as $key => $item) {
            $shippingMethod = wc_get_order_item_meta($item->get_id(), 'esl_shipping_methods', $single = true);
        }

        if (isset($result['data']['messages'])) {
            $html = '<div class="esl-status_infoTitle esl-status_infoTitle--error">' . esc_html($result['data']['messages']) . '</div>';
        }
        if (isset($result['state']['number'])) {
            $html .= '<div class="esl-status_infoTitle">Номер заказа: <input type="text" value="' . esc_attr($result['state']['number']) . '" id="copyText1" disabled><button id="copyBut1" class="button button-primary" onclick="copyToClipboard(copyText1, this)">Скопировать номер</button></div>';
        }
        if (isset($shippingMethod) && $shippingMethod) {
            $shippingMethods = json_decode($shippingMethod, true);
            if (isset($shippingMethods['answer']['order']['id'])) {
                $html .= '<div class="esl-status_infoTitle">Идентификатор заказа в системе "' . esc_html($orderType) . '": ' . esc_html($shippingMethods['answer']['order']['id']) . '</div>';
            }
            if (!empty($shippingMethods['pending_confirmation'])) {
                $html .= '<div class="esl-status_info">Ожидается подтверждение от транспортной компании — трек-номер ещё не получен. Повторное нажатие «Выгрузить» не требуется.</div>';
            }
        }
        if (isset($result['order']['orderId'])) {
            $html .= '<div class="esl-status_infoTitle">Идентификатор заказа: ' . esc_html($result['order']['orderId']) . '</div>';
        }
        if (isset($result['state'])) {
            $html .= '<div class="esl-status_info">Текущий статус: ' . esc_html($result['state']['status']['description']) . '</div>';
        }
        if (isset($result['state']['service_status']['description'])) {
            $html .= '<br><div class="esl-status_info">Описание: ' . esc_html($result['state']['service_status']['description']) . '</div>';
        }

        $print = $this->returnPrint();
        if ($print) {
            $html .= $print;
        }

        if (!$html) {
            $html = '<div class="esl-status_infoTitle esl-status_infoTitle--error">Ошибка при загрузке данных.</div>';
        }

        return $html;
    }

    /**
     * Печать этикеток/накладных ТК — пока не реализовано (см. views/unloading/print.php).
     *
     * @return string
     */
    public function returnPrint()
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
        return View::render('unloading/print', []);
    }
}
