<?php
/**
 * Plugin Name: CashWala Coupon Reveal Pro
 * Description: Premium coupon reveal plugin with popup/inline modes, lead capture, analytics, and secure AJAX.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-coupon-reveal
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CWCR_VERSION', '1.0.0');
define('CWCR_FILE', __FILE__);
define('CWCR_PATH', plugin_dir_path(__FILE__));
define('CWCR_URL', plugin_dir_url(__FILE__));

require_once CWCR_PATH . 'includes/class-logger.php';
require_once CWCR_PATH . 'includes/class-db.php';
require_once CWCR_PATH . 'includes/class-admin.php';
require_once CWCR_PATH . 'includes/class-frontend.php';
require_once CWCR_PATH . 'includes/class-ajax.php';
require_once CWCR_PATH . 'includes/class-core.php';

register_activation_hook(__FILE__, ['CWCR_DB', 'activate']);
register_activation_hook(__FILE__, ['CWCR_Logger', 'activate']);

function cwcr_bootstrap()
{
    $core = CWCR_Core::instance();
    $core->init();
}
add_action('plugins_loaded', 'cwcr_bootstrap');
