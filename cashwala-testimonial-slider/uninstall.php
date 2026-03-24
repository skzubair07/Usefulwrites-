<?php
/**
 * Uninstall plugin.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;
$table_name = $wpdb->prefix . 'cw_testimonials';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

delete_option( 'cwts_settings' );
