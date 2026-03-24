<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

global $wpdb;

$tables = array(
    $wpdb->prefix . 'cwbp_contacts',
    $wpdb->prefix . 'cwbp_campaigns',
    $wpdb->prefix . 'cwbp_email_queue',
    $wpdb->prefix . 'cwbp_automations',
    $wpdb->prefix . 'cwbp_automation_runs',
    $wpdb->prefix . 'cwbp_analytics',
);

foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS {$table}" );
}

delete_option( 'cwbp_db_version' );
