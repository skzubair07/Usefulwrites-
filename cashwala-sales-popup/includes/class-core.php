<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Sales_Popup_Core {
    /**
     * @var CW_Sales_Popup_Core|null
     */
    private static $instance = null;

    /**
     * @var CW_Sales_Popup_Logger
     */
    public $logger;

    /**
     * @var CW_Sales_Popup_DB
     */
    public $db;

    /**
     * @var CW_Sales_Popup_Ajax
     */
    public $ajax;

    /**
     * @var CW_Sales_Popup_Admin
     */
    public $admin;

    /**
     * @var CW_Sales_Popup_Frontend
     */
    public $frontend;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->logger   = new CW_Sales_Popup_Logger();
        $this->db       = new CW_Sales_Popup_DB();
        $this->ajax     = new CW_Sales_Popup_Ajax( $this->db, $this->logger );
        $this->frontend = new CW_Sales_Popup_Frontend();

        if ( is_admin() ) {
            $this->admin = new CW_Sales_Popup_Admin( $this->db, $this->logger );
        }
    }
}
