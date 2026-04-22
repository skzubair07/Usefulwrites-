<?php
/**
 * Plugin Name: Deoband Online
 * Plugin URI: https://example.com/deoband-online
 * Description: Modular, scalable, admin-controlled Islamic Q&A and engagement platform plugin.
 * Version: 1.1.0
 * Author: Deoband Online Team
 * Text Domain: deoband-online
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'DO_VERSION', '1.1.0' );
define( 'DO_PLUGIN_FILE', __FILE__ );
define( 'DO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin bootstrap class.
 */
class DO_Plugin {

    /**
     * Boot all plugin components.
     */
    public static function init() {
        self::load_core();
        self::load_files();

        add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ) );
        add_action( 'init', array( __CLASS__, 'register_assets' ) );
        add_shortcode( 'deoband_online', array( __CLASS__, 'render_frontend' ) );
    }

    /**
     * Load core includes (database, helpers, security).
     */
    private static function load_core() {
        $core_files = array(
            'includes/helpers.php',
            'includes/security.php',
            'includes/database.php',
            'includes/logger.php',
            'includes/rate-limit.php',
        );

        foreach ( $core_files as $file ) {
            $path = DO_PLUGIN_DIR . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }

    /**
     * Include all module and admin files.
     */
    private static function load_files() {
        $files = array(
            'admin/admin-panel.php',
            'admin/settings.php',
            'admin/payments.php',
            'admin/content-builder.php',
            'modules/masail/masail.php',
            'modules/search/search.php',
            'modules/ai/ai.php',
            'modules/tokens/tokens.php',
            'modules/subscription/subscription.php',
            'modules/affiliate/affiliate.php',
            'modules/trending/trending.php',
            'modules/foryou/foryou.php',
            'modules/notifications/notifications.php',
            'modules/api/api.php',
            'modules/import/cron-import.php',
            'modules/complaint/complaint.php',
            'modules/news/news.php',
            'modules/prayer/prayer.php',
            'modules/likeshare/likeshare.php',
            'modules/language/language.php',
        );

        foreach ( $files as $file ) {
            $path = DO_PLUGIN_DIR . $file;
            if ( file_exists( $path ) ) {
                require_once $path;
            }
        }
    }

    /**
     * Register shared style and script.
     */
    public static function register_assets() {
        wp_register_style( 'do-frontend', DO_PLUGIN_URL . 'assets/css/frontend.css', array(), DO_VERSION );
        wp_register_script( 'do-frontend', DO_PLUGIN_URL . 'assets/js/frontend.js', array( 'jquery' ), DO_VERSION, true );

        wp_localize_script(
            'do-frontend',
            'doAjax',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'do_ajax_nonce' ),
            )
        );
    }

    /**
     * Plugin translation loader.
     */
    public static function load_textdomain() {
        load_plugin_textdomain( 'deoband-online', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    /**
     * Render master frontend template.
     *
     * @return string
     */
    public static function render_frontend() {
        wp_enqueue_style( 'do-frontend' );
        wp_enqueue_script( 'do-frontend' );

        ob_start();
        include DO_PLUGIN_DIR . 'templates/frontend.php';
        return ob_get_clean();
    }

    /**
     * Plugin activation callback.
     */
    public static function activate() {
        self::load_core();

        if ( class_exists( 'DO_Database' ) ) {
            DO_Database::install();
            DO_Database::seed_options();
        }
    }
}

register_activation_hook( __FILE__, array( 'DO_Plugin', 'activate' ) );
DO_Plugin::init();
