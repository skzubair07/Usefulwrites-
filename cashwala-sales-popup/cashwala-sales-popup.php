<?php
/**
 * Plugin Name: CashWala Sales Proof Popup Pro
 * Plugin URI: https://example.com/cashwala-sales-popup
 * Description: Increase conversions with real-time sales proof popups.
 * Version: 1.0.0
 * Author: CashWala
 * Author URI: https://example.com
 * License: GPLv2 or later
 * Text Domain: cashwala-sales-popup
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CW_SALES_POPUP_VERSION', '1.0.0' );
define( 'CW_SALES_POPUP_FILE', __FILE__ );
define( 'CW_SALES_POPUP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CW_SALES_POPUP_URL', plugin_dir_url( __FILE__ ) );

require_once CW_SALES_POPUP_PATH . 'includes/class-logger.php';
require_once CW_SALES_POPUP_PATH . 'includes/class-db.php';
require_once CW_SALES_POPUP_PATH . 'includes/class-ajax.php';
require_once CW_SALES_POPUP_PATH . 'includes/class-admin.php';
require_once CW_SALES_POPUP_PATH . 'includes/class-frontend.php';
require_once CW_SALES_POPUP_PATH . 'includes/class-core.php';

register_activation_hook( __FILE__, array( 'CW_Sales_Popup_DB', 'activate' ) );

function cw_sales_popup_boot() {
    CW_Sales_Popup_Core::instance();
}

cw_sales_popup_boot();
