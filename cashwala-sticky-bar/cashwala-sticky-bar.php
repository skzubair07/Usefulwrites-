<?php
/**
 * Plugin Name: CashWala Sticky CTA Bar Pro
 * Plugin URI: https://example.com/cashwala-sticky-bar
 * Description: Premium sticky CTA bar plugin with conversion-focused targeting, dynamic messages, countdown timer, and analytics.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-sticky-bar
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CW_SB_VERSION', '1.0.0');
define('CW_SB_FILE', __FILE__);
define('CW_SB_DIR', plugin_dir_path(__FILE__));
define('CW_SB_URL', plugin_dir_url(__FILE__));
define('CW_SB_OPTION_KEY', 'cw_sb_settings');
define('CW_SB_ANALYTICS_KEY', 'cw_sb_analytics');
define('CW_SB_LOG_KEY', 'cw_sb_logs');

require_once CW_SB_DIR . 'includes/class-logger.php';
require_once CW_SB_DIR . 'includes/class-core.php';
require_once CW_SB_DIR . 'includes/class-admin.php';
require_once CW_SB_DIR . 'includes/class-frontend.php';
require_once CW_SB_DIR . 'includes/class-ajax.php';

function cw_sb_bootstrap() {
    $core = new CW_SB_Core();
    $core->init();
}
add_action('plugins_loaded', 'cw_sb_bootstrap');

register_activation_hook(CW_SB_FILE, array('CW_SB_Core', 'activate'));
