<?php

namespace eshoplogistic\WCEshopLogistic\DB;

use eshoplogistic\WCEshopLogistic\DB\Migrations\Migration;

class Migrator
{
    private $db;

    /**
     * @var Migration[]
     */
    private $migrations = [];

    /**
     * @var array
     */
    private $history = [];

    /**
     * @var int
     */
    private $works = 0;

    /**
     * Migrator constructor.
     */
    public function __construct()
    {
        global $wpdb;

        $this->db = $wpdb;
        $this->collate = $wpdb->get_charset_collate();

        $history = get_option( WC_ESL_MIGRATOR_HISTORY_KEY );

        if ( $history ) {
            $this->history = json_decode( $history, true );
        }
        else {
            $this->history = [];
        }

    }

    /**
     * @param string $name
     *
     * @return mixed
     */

    public function __get( $name )
    {
        return $this->$name;
    }

    /**
     * @param Migration $migration
     *
     * @return void
     */
    public function addMigration( $migration )
    {
        if ( !isset( $this->migrations[ $migration->name() ]) )
        {
            $this->migrations[ $migration->name() ] = $migration;
        }
    }

    /**
     *
     * @return void
     */
    public function run()
    {
        foreach ( $this->migrations as $migration ) {

            if (in_array($migration->name(), $this->history)) {
                continue;
            }

            $migration->up( $this->db );

            $this->history[] = $migration->name();
            $this->works++;
        }

        if ( $this->works ) {
            update_option( WC_ESL_MIGRATOR_HISTORY_KEY, json_encode( $this->history ) );
        }
    }

}