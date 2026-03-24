<?php
/**
 * Plugin Name: CashWala Testimonial Slider Pro
 * Plugin URI: https://cashwala.example.com
 * Description: Premium testimonial slider plugin with modular architecture, shortcode support, and flexible display controls.
 * Version: 1.0.0
 * Author: CashWala
 * Author URI: https://cashwala.example.com
 * Text Domain: cashwala-testimonial-slider
 * Domain Path: /languages
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CWTS_VERSION', '1.0.0' );
define( 'CWTS_PLUGIN_FILE', __FILE__ );
define( 'CWTS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CWTS_BASENAME', plugin_basename( __FILE__ ) );

require_once CWTS_PLUGIN_DIR . 'includes/class-logger.php';
require_once CWTS_PLUGIN_DIR . 'includes/class-db.php';
require_once CWTS_PLUGIN_DIR . 'includes/class-admin.php';
require_once CWTS_PLUGIN_DIR . 'includes/class-ajax.php';
require_once CWTS_PLUGIN_DIR . 'includes/class-frontend.php';
require_once CWTS_PLUGIN_DIR . 'includes/class-core.php';

/**
 * Initialize plugin.
 *
 * @return CWTS_Core
 */
function cwts_plugin() {
	static $plugin = null;

	if ( null === $plugin ) {
		$plugin = new CWTS_Core();
	}

	return $plugin;
}

register_activation_hook(
	__FILE__,
	static function() {
		CWTS_DB::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function() {
		CWTS_Logger::log( 'Plugin deactivated.' );
	}
);

add_action(
	'plugins_loaded',
	static function() {
		cwts_plugin();
	}
);
