<?php
/**
 * Plugin Name: Content Flow Auto
 * Description: Generate AI articles in bulk with API failover and save as draft posts.
 * Version: 1.0.0
 * Author: Content Flow Auto
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: content-flow-auto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CFA_VERSION', '1.0.0' );
define( 'CFA_PLUGIN_FILE', __FILE__ );
define( 'CFA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CFA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CFA_PLUGIN_DIR . 'includes/logger.php';
require_once CFA_PLUGIN_DIR . 'includes/api-switcher.php';
require_once CFA_PLUGIN_DIR . 'includes/ai-generator.php';
require_once CFA_PLUGIN_DIR . 'includes/post-creator.php';

require_once CFA_PLUGIN_DIR . 'admin/dashboard.php';
require_once CFA_PLUGIN_DIR . 'admin/bulk-generator.php';
require_once CFA_PLUGIN_DIR . 'admin/settings.php';
require_once CFA_PLUGIN_DIR . 'admin/system-status.php';

/**
 * Plugin activation callback.
 *
 * @return void
 */
function cfa_activate_plugin() {
	if ( ! get_option( 'cfa_settings' ) ) {
		add_option(
			'cfa_settings',
			array(
				'gemini_api_key'      => '',
				'groq_api_key'        => '',
				'cohere_api_key'      => '',
				'huggingface_api_key' => '',
				'openai_api_key'      => '',
				'api_priority'        => array( 'gemini', 'groq', 'cohere', 'huggingface', 'openai' ),
				'article_length'      => 1000,
				'default_category'    => 0,
				'post_status'         => 'draft',
			)
		);
	}
}
register_activation_hook( __FILE__, 'cfa_activate_plugin' );

/**
 * Enqueue admin assets.
 *
 * @param string $hook Hook suffix.
 * @return void
 */
function cfa_enqueue_admin_assets( $hook ) {
	if ( false === strpos( $hook, 'content-flow-auto' ) ) {
		return;
	}

	wp_enqueue_style( 'cfa-admin-style', CFA_PLUGIN_URL . 'assets/style.css', array(), CFA_VERSION );
	wp_enqueue_script( 'cfa-admin-script', CFA_PLUGIN_URL . 'assets/script.js', array( 'jquery' ), CFA_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'cfa_enqueue_admin_assets' );
