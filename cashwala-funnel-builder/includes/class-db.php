<?php
if (!defined('ABSPATH')) {
    exit;
}

class CWFB_DB {
    const DB_VERSION = '1.0.0';

    public static function activate() {
        self::create_tables();
        update_option('cwfb_db_version', self::DB_VERSION);
    }

    public static function create_tables() {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $funnels_table   = $wpdb->prefix . 'cw_funnels';
        $stats_table     = $wpdb->prefix . 'cw_funnel_stats';

        $sql_funnels = "CREATE TABLE {$funnels_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            landing_page_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            checkout_page_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            thankyou_page_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'inactive',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) {$charset_collate};";

        $sql_stats = "CREATE TABLE {$stats_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            funnel_id BIGINT UNSIGNED NOT NULL,
            step VARCHAR(30) NOT NULL,
            visits BIGINT UNSIGNED NOT NULL DEFAULT 0,
            conversions BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY funnel_step (funnel_id, step),
            KEY funnel_id (funnel_id)
        ) {$charset_collate};";

        dbDelta($sql_funnels);
        dbDelta($sql_stats);
    }
}
