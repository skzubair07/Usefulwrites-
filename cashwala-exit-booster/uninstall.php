<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$table = $wpdb->prefix . 'cw_leads';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'cw_eib_settings' );
delete_option( 'cw_eib_analytics' );
delete_option( 'cw_eib_db_version' );

$log_file = plugin_dir_path( __FILE__ ) . 'logs/cw-eib-error.log';
if ( file_exists( $log_file ) ) {
    wp_delete_file( $log_file );
}
