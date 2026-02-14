<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Queue
{
    const TABLE = 'uade_queue';

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
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            scheduled_at DATETIME NOT NULL,
            attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            platform_status LONGTEXT NULL,
            platform_error LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY post_id (post_id),
            KEY status (status),
            KEY scheduled_at (scheduled_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function has_post($post_id)
    {
        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name()} WHERE post_id = %d LIMIT 1",
            (int) $post_id
        ));

        return ! empty($exists);
    }

    public function enqueue_post($post_id, $manual_approval = false)
    {
        global $wpdb;

        $status       = $manual_approval ? 'pending_approval' : 'pending';
        $scheduled_at = current_time('mysql', true);
        $now          = current_time('mysql', true);

        $wpdb->replace(
            $this->table_name(),
            [
                'post_id'          => (int) $post_id,
                'status'           => $status,
                'scheduled_at'     => $scheduled_at,
                'attempts'         => 0,
                'platform_status'  => wp_json_encode([]),
                'platform_error'   => wp_json_encode([]),
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );
    }

    public function approve_item($id)
    {
        return $this->update_item((int) $id, [
            'status'       => 'pending',
            'scheduled_at' => current_time('mysql', true),
        ]);
    }

    public function retry_item($id)
    {
        return $this->update_item((int) $id, [
            'status'       => 'pending',
            'scheduled_at' => current_time('mysql', true),
        ]);
    }

    public function get_due_items($limit = 5)
    {
        global $wpdb;

        $table = $this->table_name();
        $now   = current_time('mysql', true);

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = %s AND scheduled_at <= %s ORDER BY scheduled_at ASC LIMIT %d",
            'pending',
            $now,
            (int) $limit
        ));
    }

    public function update_item($id, array $data)
    {
        global $wpdb;

        $formats = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['attempts'], true)) {
                $formats[] = '%d';
            } else {
                $formats[] = '%s';
            }
        }

        $data['updated_at'] = current_time('mysql', true);
        $formats[]          = '%s';

        return (bool) $wpdb->update(
            $this->table_name(),
            $data,
            ['id' => (int) $id],
            $formats,
            ['%d']
        );
    }

    public function get_item($id)
    {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name()} WHERE id = %d",
            (int) $id
        ));
    }

    public function get_items($limit = 50)
    {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name()} ORDER BY created_at DESC LIMIT %d",
            (int) $limit
        ));
    }

    public function count_by_status($status)
    {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name()} WHERE status = %s",
            sanitize_text_field($status)
        ));
    }
}
