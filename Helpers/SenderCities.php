<?php

namespace eshoplogistic\WCEshopLogistic\Helpers;

class SenderCities
{
    public static function sanitize($raw): array
    {
        if (is_string($raw)) $raw = json_decode($raw, true);
        $cities = [];
        foreach (is_array($raw) ? $raw : [] as $city) {
            if (!is_array($city)) continue;
            foreach (['fias', 'name', 'region'] as $field) {
                $city[$field] = isset($city[$field]) && is_scalar($city[$field])
                    ? sanitize_text_field((string) $city[$field]) : '';
            }
            if ($city['fias'] === '' || $city['name'] === '') continue;
            $cities[$city['fias']] = array_intersect_key($city, array_flip(['fias', 'name', 'region']));
        }
        return array_values($cities);
    }

    public static function getList(): array
    {
        return self::sanitize(get_option('wc_esl_shipping_sender_cities', []));
    }

    public static function getByFias($fias): ?array
    {
        if (!is_string($fias) || $fias === '') return null;
        foreach (self::getList() as $city) {
            if ($city['fias'] === $fias) return $city;
        }
        return null;
    }

    public static function getOptions($current = ''): array
    {
        $options = ['' => __('По умолчанию (город из кабинета eShopLogistic)', 'eshoplogisticru')];
        foreach (self::getList() as $city) {
            $options[$city['fias']] = $city['name'] . ($city['region'] !== '' ? ' (' . $city['region'] . ')' : '');
        }
        if (is_string($current) && $current !== '' && !isset($options[$current])) {
            $options[$current] = __('Город удалён — используется город по умолчанию', 'eshoplogisticru');
        }
        return $options;
    }

    public static function getSelected(): string
    {
        $session = new \eshoplogistic\WCEshopLogistic\Services\SessionService();
        $fias = $session->get('sender_fias');
        return self::getByFias($fias) ? $fias : '';
    }
}
