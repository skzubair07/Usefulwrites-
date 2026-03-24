<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_funnel_stats");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}cw_funnels");

delete_option('cwfb_db_version');
