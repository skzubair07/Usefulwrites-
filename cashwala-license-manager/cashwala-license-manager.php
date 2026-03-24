<?php
/**
 * Plugin Name: CashWala License Manager Pro
 * Description: Professional WordPress license key management and validation system for plugin sellers.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-license-manager
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CWLMP_VERSION', '1.0.0' );
define( 'CWLMP_FILE', __FILE__ );
define( 'CWLMP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CWLMP_URL', plugin_dir_url( __FILE__ ) );
define( 'CWLMP_DB_VERSION', '1.0.0' );

require_once CWLMP_PATH . 'includes/class-security.php';
require_once CWLMP_PATH . 'includes/class-licenses.php';
require_once CWLMP_PATH . 'includes/class-validation.php';
require_once CWLMP_PATH . 'includes/class-api.php';
require_once CWLMP_PATH . 'includes/class-analytics.php';
require_once CWLMP_PATH . 'includes/class-admin.php';

final class CWLMP_Plugin {
    /** @var CWLMP_Plugin|null */
    private static $instance = null;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
        add_action( 'cwlmp_daily_status_sync', array( 'CWLMP_Licenses', 'sync_expired_licenses' ) );
    }

    public function load_plugin() {
        CWLMP_API::init();
        CWLMP_Admin::init();
    }

    public static function activate() {
        CWLMP_Licenses::create_tables();
        add_option( 'cwlmp_db_version', CWLMP_DB_VERSION );

        if ( ! wp_next_scheduled( 'cwlmp_daily_status_sync' ) ) {
            wp_schedule_event( time(), 'daily', 'cwlmp_daily_status_sync' );
        }
    }

    public static function deactivate() {
        $timestamp = wp_next_scheduled( 'cwlmp_daily_status_sync' );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, 'cwlmp_daily_status_sync' );
        }
    }
}

register_activation_hook( __FILE__, array( 'CWLMP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CWLMP_Plugin', 'deactivate' ) );

CWLMP_Plugin::instance();

if ( ! function_exists( 'cw_validate_license' ) ) {
    /**
     * Validate a license key and domain.
     *
     * @param string $key    License key.
     * @param string $domain Domain to validate.
     *
     * @return bool
     */
    function cw_validate_license( $key, $domain ) {
        $result = CWLMP_Validation::validate( $key, $domain );

        return ! empty( $result['valid'] );
    }
}
