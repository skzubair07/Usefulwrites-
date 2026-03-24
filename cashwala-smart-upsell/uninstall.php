<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}cw_upsells" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'cw_upsell_settings' );
delete_option( 'cw_upsell_analytics' );
delete_option( 'cw_upsell_logs' );
