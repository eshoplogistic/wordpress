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
        $rows = '';

        $order = wc_get_order($orderId);
        $orderShippings = $order ? $order->get_shipping_methods() : [];
        $shippingMethod = '';
        foreach ($orderShippings as $key => $item) {
            $shippingMethod = wc_get_order_item_meta($item->get_id(), 'esl_shipping_methods', $single = true);
        }

        $errorHtml = '';
        if (isset($result['data']['messages'])) {
            $errorHtml = '<div class="esl-info-alert esl-info-alert--error">' . esc_html($result['data']['messages']) . '</div>';
        }
        if (isset($result['state']['number'])) {
            $rows .= '<div class="esl-info-row esl-info-row--copy"><span class="esl-info-row__label">' . esc_html__('Номер заказа:', 'eshoplogisticru') . '</span><span class="esl-info-row__value esl-copy-control"><input type="text" value="' . esc_attr($result['state']['number']) . '" id="copyText1" disabled><button id="copyBut1" class="button button-primary esl-copy-btn" onclick="copyToClipboard(copyText1, this)">' . esc_html__('Скопировать номер', 'eshoplogisticru') . '</button></span></div>';
        }
        $hasCarrierOrder = false;
        if (isset($shippingMethod) && $shippingMethod) {
            $shippingMethods = json_decode($shippingMethod, true);
            if (isset($shippingMethods['answer']['order']['id'])) {
                $hasCarrierOrder = true;
                /* translators: %s: carrier service name */
                $rows .= '<div class="esl-info-row"><span class="esl-info-row__label">' . sprintf(esc_html__('Идентификатор заказа в системе "%s":', 'eshoplogisticru'), esc_html($orderType)) . '</span><span class="esl-info-row__value">' . esc_html($shippingMethods['answer']['order']['id']) . '</span></div>';
            }
            if (!empty($shippingMethods['pending_confirmation'])) {
                $rows .= '<div class="esl-info-note">' . esc_html__('Ожидается подтверждение от транспортной компании — трек-номер ещё не получен. Повторное нажатие «Выгрузить» не требуется.', 'eshoplogisticru') . '</div>';
            }
        }
        if (isset($result['order']['orderId'])) {
            $rows .= '<div class="esl-info-row"><span class="esl-info-row__label">' . esc_html__('Идентификатор заказа:', 'eshoplogisticru') . '</span><span class="esl-info-row__value">' . esc_html($result['order']['orderId']) . '</span></div>';
        }
        if (isset($result['state'])) {
            $rows .= '<div class="esl-info-row"><span class="esl-info-row__label">' . esc_html__('Текущий статус:', 'eshoplogisticru') . '</span><span class="esl-info-row__value"><span class="esl-status-badge">' . esc_html($result['state']['status']['description']) . '</span></span></div>';
        }
        if (isset($result['state']['service_status']['description'])) {
            $rows .= '<div class="esl-info-row"><span class="esl-info-row__label">' . esc_html__('Описание:', 'eshoplogisticru') . '</span><span class="esl-info-row__value">' . esc_html($result['state']['service_status']['description']) . '</span></div>';
        }

        $html = $errorHtml;
        if ($rows) {
            $html .= '<div class="esl-info-card">' . $rows . '</div>';
        }

        if ($hasCarrierOrder) {
            $print = $this->returnPrint($orderType);
            if ($print) {
                $html .= $print;
            }
        }

        if (!$html) {
            $html = '<div class="esl-info-alert esl-info-alert--error">' . esc_html__('Ошибка при загрузке данных.', 'eshoplogisticru') . '</div>';
        }

        return $html;
    }

    /**
     * Печать этикеток/накладных/штрихкодов ТК — блок с кнопками под статусом заказа
     * в модалке "Информация о заказе". Портировано из moj_sklad
     * UnloadingPrint::initType()/views/widgets/unloadingprint.html.php: тот же набор
     * режимов на кнопку и форматов бумаги на ТК, сама печать — по клику через
     * wc_esl_shipping_unloading_print (см. Ajax::unloadingPrint()).
     *
     * @param string $orderType Слаг ТК (sdek, pecom, dpd ...).
     *
     * @return string
     */
    public function returnPrint($orderType)
    {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables are passed to View::render which escapes them
        return View::render('unloading/print', [
            'wc_esl_deliveryName' => $orderType,
            'wc_esl_printButtons' => $this->getPrintButtons($orderType),
            'wc_esl_paperOptions' => $this->getPrintPaperOptions($orderType),
        ]);
    }

    /**
     * Набор кнопок печати на ТК, 1:1 с moj_sklad views/widgets/unloadingprint.html.php
     * (delline/sdek/dpd/pecom/yandex — явные ветки, остальные ТК ESL — общий фолбэк
     * "Печать штрихкодов", как в МС же).
     *
     * @param string $orderType
     *
     * @return array<int, array{mode: string, label: string}>
     */
    private function getPrintButtons($orderType)
    {
        $buttons = array(
            'delline' => array(
                array('mode' => 'bill', 'label' => __('Печать счёта', 'eshoplogisticru')),
                array('mode' => 'order', 'label' => __('Печать ТТН', 'eshoplogisticru')),
                array('mode' => 'invoice', 'label' => __('Печать счёт-фактуры', 'eshoplogisticru')),
                array('mode' => 'label', 'label' => __('Печать этикеток', 'eshoplogisticru')),
            ),
            'sdek' => array(
                array('mode' => 'barcodes', 'label' => __('Печать штрихкодов', 'eshoplogisticru')),
                array('mode' => 'order', 'label' => __('Печать накладных', 'eshoplogisticru')),
            ),
            'dpd' => array(
                array('mode' => 'label', 'label' => __('Печать наклеек', 'eshoplogisticru')),
                array('mode' => 'order', 'label' => __('Печать накладной', 'eshoplogisticru')),
            ),
            'pecom' => array(
                array('mode' => 'label', 'label' => __('Печать наклеек', 'eshoplogisticru')),
                array('mode' => 'order', 'label' => __('Печать накладной', 'eshoplogisticru')),
            ),
            'yandex' => array(
                array('mode' => 'barcodes', 'label' => __('Печать штрихкодов', 'eshoplogisticru')),
                array('mode' => 'act', 'label' => __('Акт приёма-передачи', 'eshoplogisticru')),
            ),
        );

        return $buttons[$orderType] ?? array(
            array('mode' => 'barcodes', 'label' => __('Печать штрихкодов', 'eshoplogisticru')),
        );
    }

    /**
     * Список форматов бумаги на выбор для ТК, у которых печатная форма зависит от
     * формата (1:1 с $typePaperPrint в moj_sklad UnloadingPrint).
     *
     * @param string $orderType
     *
     * @return string[]
     */
    private function getPrintPaperOptions($orderType)
    {
        $paperTypes = array(
            'sdek' => array('A4', 'A5', 'A6'),
            'dpd' => array('A5', 'A6'),
            'halva' => array('58x60', '76x51', '70x28_a4'),
        );

        return $paperTypes[$orderType] ?? array();
    }
}
