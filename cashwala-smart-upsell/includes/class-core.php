<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Upsell_Core {
    private static $instance = null;

    /** @var CW_Upsell_Logger */
    private $logger;

    /** @var CW_Upsell_DB */
    private $db;

    /** @var CW_Upsell_Admin */
    private $admin;

    /** @var CW_Upsell_Frontend */
    private $frontend;

    /** @var CW_Upsell_Ajax */
    private $ajax;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->logger   = new CW_Upsell_Logger();
        $this->db       = new CW_Upsell_DB( $this->logger );
        $this->admin    = new CW_Upsell_Admin( $this->db, $this->logger );
        $this->frontend = new CW_Upsell_Frontend( $this->db, $this->logger );
        $this->ajax     = new CW_Upsell_Ajax( $this->logger );

        add_action( 'init', array( $this, 'load_textdomain' ) );
    }

    public function load_textdomain() {
        load_plugin_textdomain( 'cashwala-smart-upsell', false, dirname( plugin_basename( CW_UPSELL_FILE ) ) . '/languages/' );
    }
}
