<?php
if (!defined('ABSPATH')) {
    exit;
}

class CWFB_Core {
    private static $instance = null;
    private $admin;
    private $frontend;
    private $ajax;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->ajax = new CWFB_Ajax();

        if (is_admin()) {
            $this->admin = new CWFB_Admin();
        }

        $this->frontend = new CWFB_Frontend();
    }
}
