<?php

if ( ! defined('ABSPATH')) {
    exit;
}

\spl_autoload_register( function ( $class ) {
    if ( stripos($class, 'eshoplogistic\WCEshopLogistic') !== 0 ) return;

    $classFile = str_replace('\\', '/', substr($class, strlen('eshoplogistic\WCEshopLogistic') + 1) . '.php');
    
    // Поддержка папки blocks/ (в нижнем регистре)
    if ( strpos($classFile, 'Blocks/') === 0 ) {
        $classFile = 'blocks/' . substr($classFile, strlen('Blocks/'));
    }
    
    if (file_exists( __DIR__ . '/' . $classFile)){
        include_once __DIR__ . '/' . $classFile;
    }
});