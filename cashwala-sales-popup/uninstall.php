<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$table_name = $wpdb->prefix . 'cw_sales_popup';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'cw_sales_popup_settings' );
delete_option( 'cw_sales_popup_analytics' );
delete_option( 'cw_sales_popup_logs' );
