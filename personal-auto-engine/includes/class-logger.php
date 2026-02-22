<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Logger {
    private PADE_Database $database;

    public function __construct(PADE_Database $database) {
        $this->database = $database;
    }

    public function log(string $platform, int $post_id, int $http_code, string $raw_response, int $success_flag): void {
        global $wpdb;

        $wpdb->insert(
            $this->database->logs_table(),
            [
                'timestamp'    => current_time('mysql'),
                'platform'     => sanitize_text_field($platform),
                'post_id'      => $post_id,
                'http_code'    => $http_code,
                'success_flag' => $success_flag,
                'raw_response' => (string) $raw_response,
            ],
            ['%s', '%s', '%d', '%d', '%d', '%s']
        );
    }

    public function last_ai_response(): string {
        global $wpdb;

        $sql = $wpdb->prepare(
            'SELECT raw_response FROM ' . $this->database->logs_table() . ' WHERE platform = %s ORDER BY id DESC LIMIT 1',
            'ai-engine'
        );

        $value = $wpdb->get_var($sql);

        return is_string($value) ? $value : '';
    }

    public function get_recent_logs(int $limit = 50): array {
        global $wpdb;

        $safe_limit = max(1, min(200, $limit));
        $sql = $wpdb->prepare(
            'SELECT * FROM ' . $this->database->logs_table() . ' ORDER BY id DESC LIMIT %d',
            $safe_limit
        );

        return (array) $wpdb->get_results($sql, ARRAY_A);
    }
}
