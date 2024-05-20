<?php

namespace eshoplogistic\WCEshopLogistic\Classes;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\Cron\CronUnloading;
use eshoplogistic\WCEshopLogistic\Cron\SettingsCron;
use eshoplogistic\WCEshopLogistic\Cron\UnloadingCron;
use eshoplogistic\WCEshopLogistic\Modules\Activator;
use eshoplogistic\WCEshopLogistic\Modules\AssetsLoader;
use eshoplogistic\WCEshopLogistic\Modules\OptionsPage;
use eshoplogistic\WCEshopLogistic\Modules\Cart;
use eshoplogistic\WCEshopLogistic\Modules\Checkout;
use eshoplogistic\WCEshopLogistic\Modules\Ajax;
use eshoplogistic\WCEshopLogistic\Modules\Shipping;
use eshoplogistic\WCEshopLogistic\Modules\Payment;
use eshoplogistic\WCEshopLogistic\Modules\CheckoutValidator;
use eshoplogistic\WCEshopLogistic\Modules\OrderCreator;
use eshoplogistic\WCEshopLogistic\Modules\Settings;
use eshoplogistic\WCEshopLogistic\Modules\Routes;
use eshoplogistic\WCEshopLogistic\Modules\Footer;
use eshoplogistic\WCEshopLogistic\Modules\Unloading;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class WCEshopLogistic
{
	/**
     * @var WCEshopLogistic
     */
    private static $instance = null;

    /**
     * @var ModuleInterface[]
     */
    private $modules = [];

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function init()
    {
        $plugin = new Plugin();

        $this->initModule(Activator::class);
        $this->initModule(SettingsCron::class);
        $this->initModule(UnloadingCron::class);
        $this->initModule(OptionsPage::class);
        $this->initModule(Ajax::class);
        $this->initModule(AssetsLoader::class);

        if(!$plugin->isEnable()) return;

        $this->initModule(Settings::class);
        $this->initModule(Routes::class);
        $this->initModule(Shipping::class);
        $this->initModule(Payment::class);
        $this->initModule(CheckoutValidator::class);
        $this->initModule(Cart::class);
        $this->initModule(Checkout::class);
        $this->initModule(OrderCreator::class);
        $this->initModule(Footer::class);
        $this->initModule(Unloading::class);
    }

    private function initModule( $module )
    {
        /**
         * @var ModuleInterface $instance
         */
        $instance = new $module();
        $instance->init();

        $this->modules[$module] = $instance;
    }
}