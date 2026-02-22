<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Database {
    public function queue_table(): string {
        global $wpdb;

        return $wpdb->prefix . 'pade_queue';
    }

    public function logs_table(): string {
        global $wpdb;

        return $wpdb->prefix . 'pade_logs';
    }

    public function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();

        $queue_sql = 'CREATE TABLE ' . $this->queue_table() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            payload_json LONGTEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            retry_count INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY status (status)
        ) $charset_collate;";

        $logs_sql = 'CREATE TABLE ' . $this->logs_table() . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            timestamp DATETIME NOT NULL,
            platform VARCHAR(100) NOT NULL,
            post_id BIGINT UNSIGNED NOT NULL,
            http_code INT NOT NULL,
            success_flag TINYINT(1) NOT NULL,
            raw_response LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY platform (platform)
        ) $charset_collate;";

        dbDelta($queue_sql);
        dbDelta($logs_sql);
    }
}
