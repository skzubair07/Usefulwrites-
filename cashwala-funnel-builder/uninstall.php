<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$funnels_table = $wpdb->prefix . 'cw_funnels';
$stats_table   = $wpdb->prefix . 'cw_funnel_stats';

$wpdb->query("DROP TABLE IF EXISTS {$funnels_table}"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
$wpdb->query("DROP TABLE IF EXISTS {$stats_table}"); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

delete_option('cwfb_db_version');
