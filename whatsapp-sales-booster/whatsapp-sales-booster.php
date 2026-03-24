<?php
/**
 * Plugin Name:       WhatsApp Sales Booster Pro
 * Plugin URI:        https://example.com/
 * Description:       Premium WhatsApp conversion plugin with floating CTA, delayed popup, and click tracking.
 * Version:           1.0.0
 * Author:            Usefulwrites
 * Author URI:        https://example.com/
 * Text Domain:       whatsapp-sales-booster
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'WSB_PLUGIN_VERSION' ) ) {
    define( 'WSB_PLUGIN_VERSION', '1.0.0' );
}

if ( ! defined( 'WSB_PLUGIN_FILE' ) ) {
    define( 'WSB_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WSB_PLUGIN_DIR' ) ) {
    define( 'WSB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WSB_PLUGIN_URL' ) ) {
    define( 'WSB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

require_once WSB_PLUGIN_DIR . 'includes/class-settings.php';
require_once WSB_PLUGIN_DIR . 'includes/class-admin.php';
require_once WSB_PLUGIN_DIR . 'includes/class-frontend.php';

/**
 * Bootstrap plugin.
 */
function wsb_bootstrap() {
    WSB_Settings::init();

    if ( is_admin() ) {
        WSB_Admin::init();
    }

    WSB_Frontend::init();
}
add_action( 'plugins_loaded', 'wsb_bootstrap' );

/**
 * Create default options on activation.
 */
function wsb_activate() {
    if ( false === get_option( WSB_Settings::OPTION_KEY ) ) {
        add_option( WSB_Settings::OPTION_KEY, WSB_Settings::get_defaults() );
    }

    if ( false === get_option( WSB_Settings::CLICK_OPTION_KEY ) ) {
        add_option( WSB_Settings::CLICK_OPTION_KEY, 0 );
    }
}
register_activation_hook( __FILE__, 'wsb_activate' );
