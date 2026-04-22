<?php
/**
 * Admin menu manager for Deoband Online.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Admin_Panel {

    /**
     * Register hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menus' ) );
    }

    /**
     * Create all required admin pages.
     */
    public static function register_menus() {
        add_menu_page(
            __( 'Deoband Online', 'deoband-online' ),
            __( 'Deoband Online', 'deoband-online' ),
            'manage_options',
            'do-dashboard',
            array( __CLASS__, 'dashboard_page' ),
            'dashicons-welcome-learn-more',
            58
        );

        $menus = array(
            'do-masail-manager'    => array( 'Masail Manager', 'do_render_masail_manager' ),
            'do-questions-manager' => array( 'Questions Manager', 'do_render_masail_manager' ),
            'do-token-settings'    => array( 'Tokens Settings', 'do_render_token_settings_page' ),
            'do-subscription'      => array( 'Subscription Plans', 'do_render_subscription_settings_page' ),
            'do-affiliate'         => array( 'Affiliate Settings', 'do_render_affiliate_settings_page' ),
            'do-api-settings'      => array( 'API Settings', 'do_render_api_settings_page' ),
            'do-system-controls'   => array( 'System Controls', 'do_render_system_controls_page' ),
            'do-translation'       => array( 'Translation Settings', 'do_render_translation_settings_page' ),
            'do-content-builder'   => array( 'Content Builder', 'do_render_content_builder_page' ),
            'do-payment-settings'  => array( 'Payment Settings', 'do_render_payment_settings_page' ),
            'do-notifications'     => array( 'Notifications', 'do_render_notifications_page' ),
            'do-import-settings'   => array( 'Import Settings', 'do_render_import_settings_page' ),
            'do-complaints'        => array( 'Complaint Manager', 'do_render_complaint_admin_page' ),
            'do-news-settings'     => array( 'News System', 'do_render_news_settings_page' ),
            'do-prayer-settings'   => array( 'Prayer Time', 'do_render_prayer_settings_page' ),
        );

        foreach ( $menus as $slug => $config ) {
            add_submenu_page( 'do-dashboard', __( $config[0], 'deoband-online' ), __( $config[0], 'deoband-online' ), 'manage_options', $slug, $config[1] );
        }
    }

    /**
     * Render dashboard.
     */
    public static function dashboard_page() {
        echo '<div class="wrap"><h1>Deoband Online Dashboard</h1><p>Use the left menu to control every module.</p></div>';
    }
}

DO_Admin_Panel::init();
