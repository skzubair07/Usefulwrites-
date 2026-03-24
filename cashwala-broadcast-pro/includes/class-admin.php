<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Admin {

    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    public static function menu() {
        add_menu_page(
            __( 'CashWala Broadcast Pro', 'cashwala-broadcast-pro' ),
            __( 'CashWala Broadcast', 'cashwala-broadcast-pro' ),
            'manage_options',
            'cwbp_dashboard',
            array( __CLASS__, 'dashboard_page' ),
            'dashicons-megaphone',
            56
        );

        add_submenu_page( 'cwbp_dashboard', __( 'Dashboard', 'cashwala-broadcast-pro' ), __( 'Dashboard', 'cashwala-broadcast-pro' ), 'manage_options', 'cwbp_dashboard', array( __CLASS__, 'dashboard_page' ) );
        add_submenu_page( 'cwbp_dashboard', __( 'Contacts', 'cashwala-broadcast-pro' ), __( 'Contacts', 'cashwala-broadcast-pro' ), 'manage_options', 'cwbp_contacts', array( __CLASS__, 'contacts_page' ) );
        add_submenu_page( 'cwbp_dashboard', __( 'Campaigns', 'cashwala-broadcast-pro' ), __( 'Campaigns', 'cashwala-broadcast-pro' ), 'manage_options', 'cwbp_campaigns', array( __CLASS__, 'campaigns_page' ) );
        add_submenu_page( 'cwbp_dashboard', __( 'Automation', 'cashwala-broadcast-pro' ), __( 'Automation', 'cashwala-broadcast-pro' ), 'manage_options', 'cwbp_automation', array( __CLASS__, 'automation_page' ) );
        add_submenu_page( 'cwbp_dashboard', __( 'WhatsApp', 'cashwala-broadcast-pro' ), __( 'WhatsApp', 'cashwala-broadcast-pro' ), 'manage_options', 'cwbp_whatsapp', array( __CLASS__, 'whatsapp_page' ) );
    }

    public static function enqueue( $hook ) {
        if ( false === strpos( $hook, 'cwbp' ) ) {
            return;
        }

        wp_enqueue_style( 'cwbp-admin', CWBP_URL . 'assets/css/admin.css', array(), CWBP_VERSION );
        wp_enqueue_script( 'cwbp-admin', CWBP_URL . 'assets/js/admin.js', array( 'jquery' ), CWBP_VERSION, true );

        wp_localize_script(
            'cwbp-admin',
            'cwbpAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'cwbp_admin_nonce' ),
            )
        );
    }

    public static function dashboard_page() {
        $stats       = CWBP_Analytics::get_dashboard_stats();
        $performance = CWBP_Analytics::campaign_performance();
        include CWBP_DIR . 'templates/dashboard.php';
    }

    public static function contacts_page() {
        $search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
        $contacts = CWBP_Contacts::get_contacts(
            array(
                'search' => $search,
                'status' => $status,
                'limit'  => 200,
            )
        );

        include CWBP_DIR . 'templates/contact-table.php';
    }

    public static function campaigns_page() {
        $campaigns = CWBP_Campaigns::list_campaigns();
        include CWBP_DIR . 'templates/campaign-builder.php';
    }

    public static function automation_page() {
        $automations = CWBP_Automation::list_automations();
        include CWBP_DIR . 'templates/automation-builder.php';
    }

    public static function whatsapp_page() {
        $links = get_transient( 'cwbp_whatsapp_links_' . get_current_user_id() );
        include CWBP_DIR . 'templates/whatsapp.php';
    }
}
