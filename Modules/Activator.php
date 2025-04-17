<?php

namespace eshoplogistic\WCEshopLogistic\Modules;

use eshoplogistic\WCEshopLogistic\Contracts\ModuleInterface;
use eshoplogistic\WCEshopLogistic\DB\Migrator;
use eshoplogistic\WCEshopLogistic\DB\Migrations\CreateShippingMethodsTable;

if ( ! defined('ABSPATH') ) {
    exit;
}

class Activator implements ModuleInterface
{
	public function init()
    {
        register_activation_hook(WC_ESL_PLUGIN_ENTRY, [$this, 'activate']);
        register_deactivation_hook(WC_ESL_PLUGIN_ENTRY, [$this, 'deactivate']);
    }

    public function activate()
    {
        $migrator = new Migrator();

        $migrator->addMigration( new CreateShippingMethodsTable() );

        $migrator->run();

        wp_clear_scheduled_hook(WC_ESL_PREFIX . 'update_settings');

        wp_schedule_event(
            time(),
            'daily',
            WC_ESL_PREFIX . 'update_settings'
        );
    }

    public function deactivate()
    {
        wp_clear_scheduled_hook(WC_ESL_PREFIX . 'update_settings');
    }
}