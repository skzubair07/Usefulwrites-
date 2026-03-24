<?php
/**
 * Plugin Name: CashWala Exit Intent Booster Pro
 * Plugin URI: https://cashwala.example.com
 * Description: Exit intent conversion booster with advanced popup controls, lead capture, analytics, and error logging.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-exit-booster
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CW_EIB_VERSION', '1.0.0' );
define( 'CW_EIB_FILE', __FILE__ );
define( 'CW_EIB_PATH', plugin_dir_path( __FILE__ ) );
define( 'CW_EIB_URL', plugin_dir_url( __FILE__ ) );
define( 'CW_EIB_BASENAME', plugin_basename( __FILE__ ) );

require_once CW_EIB_PATH . 'includes/class-logger.php';
require_once CW_EIB_PATH . 'includes/class-db.php';
require_once CW_EIB_PATH . 'includes/class-core.php';
require_once CW_EIB_PATH . 'includes/class-admin.php';
require_once CW_EIB_PATH . 'includes/class-frontend.php';
require_once CW_EIB_PATH . 'includes/class-ajax.php';

register_activation_hook(
    __FILE__,
    static function () {
        CW_EIB_DB::create_tables();
        CW_EIB_DB::maybe_upgrade();
    }
);

add_action(
    'plugins_loaded',
    static function () {
        CW_EIB_Core::instance();
    }
);
