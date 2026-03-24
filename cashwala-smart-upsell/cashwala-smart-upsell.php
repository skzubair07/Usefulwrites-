<?php
/**
 * Plugin Name: CashWala Smart Upsell Pro
 * Description: Increase average order value with targeted, high-converting upsell offers.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-smart-upsell
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CW_UPSELL_VERSION', '1.0.0' );
define( 'CW_UPSELL_FILE', __FILE__ );
define( 'CW_UPSELL_PATH', plugin_dir_path( __FILE__ ) );
define( 'CW_UPSELL_URL', plugin_dir_url( __FILE__ ) );

require_once CW_UPSELL_PATH . 'includes/class-logger.php';
require_once CW_UPSELL_PATH . 'includes/class-db.php';
require_once CW_UPSELL_PATH . 'includes/class-admin.php';
require_once CW_UPSELL_PATH . 'includes/class-frontend.php';
require_once CW_UPSELL_PATH . 'includes/class-ajax.php';
require_once CW_UPSELL_PATH . 'includes/class-core.php';

register_activation_hook( __FILE__, array( 'CW_Upsell_DB', 'activate' ) );

function cw_smart_upsell_bootstrap() {
    CW_Upsell_Core::instance();
}

add_action( 'plugins_loaded', 'cw_smart_upsell_bootstrap' );
