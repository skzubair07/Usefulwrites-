<?php

if (! defined('ABSPATH')) {
    exit;
}

class CWFB_Core
{
    private static $instance;

    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        add_action('plugins_loaded', array($this, 'init'));
    }

    public function init()
    {
        $admin = new CWFB_Admin();
        $admin->hooks();

        $frontend = new CWFB_Frontend();
        $frontend->hooks();

        $ajax = new CWFB_Ajax();
        $ajax->hooks();
    }

    public static function activate()
    {
        CWFB_DB::create_tables();
        CWFB_Logger::log('info', 'Plugin activated');
    }

    public static function deactivate()
    {
        CWFB_Logger::log('info', 'Plugin deactivated');
    }
}
