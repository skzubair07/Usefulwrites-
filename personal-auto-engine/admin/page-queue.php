<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Admin_Queue_Page {
    public function __construct(private PADE_Database $database, private PADE_Queue $queue) {
        add_action('admin_menu', [$this, 'register_submenu']);
        add_action('admin_post_pade_queue_action', [$this, 'handle_action']);
    }

    public function register_submenu(): void {
        add_submenu_page('pade-settings', __('PADE Queue', 'personal-auto-engine'), __('Queue', 'personal-auto-engine'), 'manage_options', 'pade-queue', [$this, 'render']);
    }

    public function handle_action(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'personal-auto-engine'));
        }

        check_admin_referer('pade_queue_action');

        $id = absint($_POST['id'] ?? 0);
        $action = sanitize_key($_POST['queue_action'] ?? '');

        if ('approve' === $action && $id > 0) {
            $this->queue->update_status($id, 'approved');
        }

        if ('schedule' === $action && $id > 0) {
            $this->queue->update_status($id, 'scheduled');
        }

        if ('process' === $action) {
            $this->queue->process_queue();
        }

        wp_safe_redirect(admin_url('admin.php?page=pade-queue'));
        exit;
    }

    public function render(): void {
        $items = $this->queue->get_items();

        echo '<div class="wrap"><h1>' . esc_html__('PADE Queue', 'personal-auto-engine') . '</h1>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('pade_queue_action');
        echo '<input type="hidden" name="action" value="pade_queue_action">';
        echo '<button class="button button-primary" name="queue_action" value="process">' . esc_html__('Process Approved Queue', 'personal-auto-engine') . '</button>';
        echo '</form>';

        echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Post ID</th><th>Status</th><th>Retries</th><th>Payload</th><th>Actions</th></tr></thead><tbody>';
        foreach ($items as $item) {
            echo '<tr>';
            echo '<td>' . esc_html((string) $item['id']) . '</td>';
            echo '<td>' . esc_html((string) $item['post_id']) . '</td>';
            echo '<td>' . esc_html((string) $item['status']) . '</td>';
            echo '<td>' . esc_html((string) $item['retry_count']) . '</td>';
            echo '<td><pre>' . esc_html((string) $item['payload_json']) . '</pre></td>';
            echo '<td>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('pade_queue_action');
            echo '<input type="hidden" name="action" value="pade_queue_action">';
            echo '<input type="hidden" name="id" value="' . esc_attr((string) $item['id']) . '">';
            echo '<button class="button" name="queue_action" value="approve">' . esc_html__('Approve', 'personal-auto-engine') . '</button> ';
            echo '<button class="button" name="queue_action" value="schedule">' . esc_html__('Mark Scheduled', 'personal-auto-engine') . '</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}
