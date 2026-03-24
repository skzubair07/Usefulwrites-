<?php
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$table = $wpdb->prefix . 'cw_coupon_leads';
$wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option('cwcr_settings');
delete_option('cwcr_analytics');
delete_option('cwcr_logs');
