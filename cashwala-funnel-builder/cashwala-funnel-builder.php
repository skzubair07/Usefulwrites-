<?php
/**
 * Plugin Name: CashWala Funnel Builder Lite
 * Description: Build and track simple 2-3 step funnels inside WordPress.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-funnel-builder
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CWFB_VERSION', '1.0.0');
define('CWFB_FILE', __FILE__);
define('CWFB_PATH', plugin_dir_path(__FILE__));
define('CWFB_URL', plugin_dir_url(__FILE__));

require_once CWFB_PATH . 'includes/class-logger.php';
require_once CWFB_PATH . 'includes/class-db.php';
require_once CWFB_PATH . 'includes/class-admin.php';
require_once CWFB_PATH . 'includes/class-frontend.php';
require_once CWFB_PATH . 'includes/class-ajax.php';
require_once CWFB_PATH . 'includes/class-core.php';

register_activation_hook(__FILE__, array('CWFB_DB', 'activate'));

function cwfb_bootstrap() {
    CWFB_Core::get_instance();
}
add_action('plugins_loaded', 'cwfb_bootstrap');
