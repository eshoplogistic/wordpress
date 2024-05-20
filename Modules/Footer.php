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
        add_shortcode('shortcode_widget_button', 'shortcode_widget_button_handler');
        add_shortcode('shortcode_widget_button_tab', 'shortcode_widget_button_tab_handler');
        add_shortcode('shortcode_widget_static', 'shortcode_widget_static_handler');
	    update_option( 'use_smilies', false );
        //add_action('wp_footer', [$this, 'addWidgetScript']);
    }

    public function addWidgetScript()
    {
        
        $optionsRepository = new OptionsRepository();
        $widgetKey = $optionsRepository->getOption('wc_esl_shipping_widget_key');

        if(!$widgetKey) return;

        ?>

        <div id="eShopLogisticApp" data-key="<?php echo esc_attr($widgetKey) ?>"></div>
        <!-- <script src="https://api.eshoplogistic.ru/widget/modal/v1/app.js"></script> -->

        <?php

        wp_enqueue_script(
            'wc_esl_app_js',
            WC_ESL_PLUGIN_URL . 'assets/js/app.js',
            [],
            WC_ESL_VERSION,
            true
        );
    }


}
