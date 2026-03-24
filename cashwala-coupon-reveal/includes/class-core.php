<?php
if (! defined('ABSPATH')) {
    exit;
}

class CWCR_Core
{
    private static $instance;

    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        add_action('init', [$this, 'load_textdomain']);
        CWCR_DB::maybe_upgrade();

        if (is_admin()) {
            CWCR_Admin::instance()->init();
        }

        CWCR_Ajax::instance()->init();
        CWCR_Frontend::instance()->init();
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('cashwala-coupon-reveal', false, dirname(plugin_basename(CWCR_FILE)) . '/languages');
    }
}
