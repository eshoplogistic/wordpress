<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\OptionsRepository;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Footer implements ModuleInterface
{
    public function init()
    {
        add_action('wp_footer', [$this, 'addWidgetScript']);
    }

    public function addWidgetScript()
    {
        
        $optionsRepository = new OptionsRepository();
        $widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');

        if(!$widgetKey) return;

        ?>

        <div id="eShopLogisticApp" data-key="<?= $widgetKey ?>"></div>
        <script src="https://api.eshoplogistic.ru/widget/modal/v1/app.js"></script>

        <?php
    }
}