<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Log
{
    const TABLE = 'uade_logs';
    const MAX_ENTRIES = 500;

    public function table_name()
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE;
    }

    public function create_table()
    {
        global $wpdb;

        $table_name      = $this->table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT UNSIGNED NOT NULL,
            platform VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL,
            response LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY platform (platform),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function add($post_id, $platform, $status, $response)
    {
        global $wpdb;

        $inserted = $wpdb->insert(
            $this->table_name(),
            [
                'post_id'    => (int) $post_id,
                'platform'   => sanitize_key($platform),
                'status'     => sanitize_text_field($status),
                'response'   => $this->normalize_response($response),
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        if ($inserted) {
            $this->trim_old_entries();
        }

        return (bool) $inserted;
    }

    public function get_latest_by_post($post_id)
    {
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*
             FROM {$this->table_name()} l
             INNER JOIN (
                SELECT platform, MAX(id) AS id
                FROM {$this->table_name()}
                WHERE post_id = %d
                GROUP BY platform
             ) latest ON latest.id = l.id
             ORDER BY l.platform ASC",
            (int) $post_id
        ));

        $map = [];
        foreach ((array) $rows as $row) {
            $map[$row->platform] = $row;
        }

        return $map;
    }

    private function trim_old_entries()
    {
        global $wpdb;

        $table = $this->table_name();
        $limit = (int) self::MAX_ENTRIES;

        $wpdb->query("DELETE FROM {$table} WHERE id NOT IN (SELECT id FROM (SELECT id FROM {$table} ORDER BY id DESC LIMIT {$limit}) latest)");
    }

    private function normalize_response($response)
    {
        if (is_wp_error($response)) {
            $response = [
                'wp_error' => true,
                'codes'    => $response->get_error_codes(),
                'messages' => $response->get_error_messages(),
                'data'     => $response->get_all_error_data(),
            ];
        }

        if (is_scalar($response) || null === $response) {
            return (string) $response;
        }

        $encoded = wp_json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (false === $encoded) {
            return print_r($response, true);
        }

        return $encoded;
    }
}

