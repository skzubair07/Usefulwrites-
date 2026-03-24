<?php
/**
 * Plugin Name: CashWala Funnel Builder Lite
 * Plugin URI: https://example.com/cashwala-funnel-builder-lite
 * Description: Build simple 2-3 step funnels with tracking and conversion analytics.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-funnel-builder
 */

if (! defined('ABSPATH')) {
    exit;
}

define('CWFB_VERSION', '1.0.0');
define('CWFB_PLUGIN_FILE', __FILE__);
define('CWFB_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('CWFB_PLUGIN_URL', plugin_dir_url(__FILE__));


require_once CWFB_PLUGIN_PATH . 'includes/class-logger.php';
require_once CWFB_PLUGIN_PATH . 'includes/class-db.php';
require_once CWFB_PLUGIN_PATH . 'includes/class-ajax.php';
require_once CWFB_PLUGIN_PATH . 'includes/class-admin.php';
require_once CWFB_PLUGIN_PATH . 'includes/class-frontend.php';
require_once CWFB_PLUGIN_PATH . 'includes/class-core.php';

register_activation_hook(CWFB_PLUGIN_FILE, array('CWFB_Core', 'activate'));
register_deactivation_hook(CWFB_PLUGIN_FILE, array('CWFB_Core', 'deactivate'));

CWFB_Core::instance();
