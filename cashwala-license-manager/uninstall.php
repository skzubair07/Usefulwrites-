<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$licenses_table = $wpdb->prefix . 'cwlmp_licenses';
$logs_table     = $wpdb->prefix . 'cwlmp_license_logs';

$wpdb->query( "DROP TABLE IF EXISTS {$licenses_table}" );
$wpdb->query( "DROP TABLE IF EXISTS {$logs_table}" );

delete_option( 'cwlmp_db_version' );
