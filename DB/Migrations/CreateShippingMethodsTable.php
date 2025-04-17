<?php

namespace eshoplogistic\WCEshopLogistic\DB\Migrations;

if ( ! defined('ABSPATH') ) {
    exit;
}

class CreateShippingMethodsTable extends Migration
{
    /**
     * @return string
     */
    public function name()
    {
        return 'create_shipping_methods_table';
    }

    /**
     * @param mixed $db
     *
     * @return void
     */
    public function up( $db )
    {
        $collate = $db->get_charset_collate();

        $db->query("

            CREATE TABLE IF NOT EXISTS wc_esl_shipping_methods (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                slug VARCHAR(255) NOT NULL,
                name VARCHAR(255) NOT NULL,
                type VARCHAR(55) NOT NULL,
                city_code VARCHAR(255) NOT NULL,
                payments LONGTEXT NOT NULL,
                comment LONGTEXT NOT NULL,
                UNIQUE KEY id (id)
            ) $collate
        ");
    }
}