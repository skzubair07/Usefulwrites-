<?php
/**
 * Admin menu registration.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register admin pages.
 *
 * @return void
 */
function cfa_register_admin_menu() {
	add_menu_page(
		__( 'Content Flow', 'content-flow-auto' ),
		__( 'Content Flow', 'content-flow-auto' ),
		'manage_options',
		'content-flow-auto',
		'cfa_render_bulk_generator_page',
		'dashicons-edit-page',
		26
	);

	add_submenu_page(
		'content-flow-auto',
		__( 'Generate Articles', 'content-flow-auto' ),
		__( 'Generate Articles', 'content-flow-auto' ),
		'manage_options',
		'content-flow-auto',
		'cfa_render_bulk_generator_page'
	);

	add_submenu_page(
		'content-flow-auto',
		__( 'Settings', 'content-flow-auto' ),
		__( 'Settings', 'content-flow-auto' ),
		'manage_options',
		'content-flow-auto-settings',
		'cfa_render_settings_page'
	);

	add_submenu_page(
		'content-flow-auto',
		__( 'System Status', 'content-flow-auto' ),
		__( 'System Status', 'content-flow-auto' ),
		'manage_options',
		'content-flow-auto-status',
		'cfa_render_system_status_page'
	);
}
add_action( 'admin_menu', 'cfa_register_admin_menu' );
