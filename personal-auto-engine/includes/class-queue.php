<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Queue {
    private PADE_Database $database;
    private PADE_Settings $settings;
    private PADE_AI_Engine $ai_engine;
    private PADE_Router $router;
    private PADE_Logger $logger;

    public function __construct(PADE_Database $database, PADE_Settings $settings, PADE_AI_Engine $ai_engine, PADE_Router $router, PADE_Logger $logger) {
        $this->database = $database;
        $this->settings = $settings;
        $this->ai_engine = $ai_engine;
        $this->router = $router;
        $this->logger = $logger;

        add_action('pade_process_queue_event', [$this, 'process_scheduled']);
    }

    public function handle_publish(int $post_id, WP_Post $post): void {
        if ('post' !== $post->post_type) {
            return;
        }

        $settings = $this->settings->get();
        if (empty($settings['general']['enabled'])) {
            return;
        }

        if ($this->exists_for_post($post_id)) {
            return;
        }

        $status = empty($settings['general']['manual_approval']) ? 'approved' : 'pending';

        $this->insert_item($post_id, [
            'platforms' => $this->router->platforms(),
            'source' => 'publish',
        ], $status);
    }

    public function insert_bulk_item(array $image_ids, string $platform): void {
        $payload = [
            'platforms' => [$platform],
            'image_ids' => array_map('absint', $image_ids),
            'source' => 'bulk-image',
        ];

        $this->insert_item(0, $payload, 'approved');
    }

    public function insert_item(int $post_id, array $payload, string $status = 'pending'): void {
        global $wpdb;

        $now = current_time('mysql');

        $wpdb->insert(
            $this->database->queue_table(),
            [
                'post_id' => $post_id,
                'payload_json' => wp_json_encode($payload),
                'status' => sanitize_key($status),
                'retry_count' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%s', '%s', '%d', '%s', '%s']
        );
    }

    public function exists_for_post(int $post_id): bool {
        global $wpdb;

        $sql = $wpdb->prepare('SELECT id FROM ' . $this->database->queue_table() . ' WHERE post_id = %d LIMIT 1', $post_id);

        return (bool) $wpdb->get_var($sql);
    }

    public function get_items(string $status = ''): array {
        global $wpdb;

        if (! empty($status)) {
            $sql = $wpdb->prepare('SELECT * FROM ' . $this->database->queue_table() . ' WHERE status = %s ORDER BY id DESC', $status);
            return (array) $wpdb->get_results($sql, ARRAY_A);
        }

        return (array) $wpdb->get_results('SELECT * FROM ' . $this->database->queue_table() . ' ORDER BY id DESC', ARRAY_A);
    }

    public function update_status(int $id, string $status, ?int $retry_count = null): void {
        global $wpdb;

        $data = [
            'status' => sanitize_key($status),
            'updated_at' => current_time('mysql'),
        ];
        $format = ['%s', '%s'];

        if (null !== $retry_count) {
            $data['retry_count'] = $retry_count;
            $format[] = '%d';
        }

        $wpdb->update($this->database->queue_table(), $data, ['id' => $id], $format, ['%d']);
    }

    public function process_scheduled(): void {
        $this->process_queue();
    }

    public function process_queue(): void {
        $items = $this->get_items('approved');

        foreach ($items as $item) {
            $this->update_status((int) $item['id'], 'processing');

            $payload = json_decode((string) $item['payload_json'], true);
            $post = (int) $item['post_id'] > 0 ? get_post((int) $item['post_id']) : null;

            $all_ok = true;
            foreach ((array) ($payload['platforms'] ?? []) as $platform) {
                $message = $post instanceof WP_Post ? $this->ai_engine->generate_caption($post, $platform) : __('Bulk image item', 'personal-auto-engine');

                $result = $this->router->dispatch(
                    $platform,
                    [
                        'post_id' => (int) $item['post_id'],
                        'message' => $message,
                        'images' => $payload['image_ids'] ?? [],
                    ]
                );

                if (empty($result['success'])) {
                    $all_ok = false;
                }
            }

            if ($all_ok) {
                $this->update_status((int) $item['id'], 'completed');
                continue;
            }

            $retry_count = (int) $item['retry_count'] + 1;
            if ($retry_count >= 3) {
                $this->update_status((int) $item['id'], 'failed', $retry_count);
            } else {
                $this->update_status((int) $item['id'], 'approved', $retry_count);
            }
        }
    }

    public function queue_health_summary(): array {
        global $wpdb;

        $rows = (array) $wpdb->get_results('SELECT status, COUNT(*) as total FROM ' . $this->database->queue_table() . ' GROUP BY status', ARRAY_A);
        $summary = [];
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['total'];
        }

        return $summary;
    }
}
