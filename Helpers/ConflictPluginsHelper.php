<?php

namespace eshoplogistic\WCEshopLogistic\Helpers;

class ConflictPluginsHelper
{
    private $modulesConflict = [
        'woocs' => 'woocommerce-currency-switcher/index.php',
    ];

    public function init($value = false)
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        foreach ($this->modulesConflict as $key=>$item){
            if (is_plugin_active($item)) {
                $value = $this->$key($value, $key);
            }
        }

        return $value;
    }

    private function woocs($value, $namePlug){
        global $WOOCS;

        $currencies = get_option($namePlug, array());
        //$currenciesSite = $WOOCS->default_currency;
        //$result = isset($currencies[$currenciesSite]) ? $currencies[$currenciesSite] : $value;
        $result = isset($currencies['RUB']['rate']) ? $currencies['RUB']['rate'] : 1;

        return $value / $result;
    }
}