<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Admin_Control_Center_Page {
    public function __construct(
        private PADE_Database $database,
        private PADE_Logger $logger,
        private PADE_Queue $queue,
        private PADE_AI_Engine $ai_engine,
        private PADE_Router $router
    ) {
        add_action('admin_menu', [$this, 'register_submenu']);
        add_action('admin_post_pade_test_platform', [$this, 'handle_api_test']);
    }

    public function register_submenu(): void {
        add_submenu_page('pade-settings', __('PADE Control Center', 'personal-auto-engine'), __('Control Center', 'personal-auto-engine'), 'manage_options', 'pade-control-center', [$this, 'render']);
    }

    public function handle_api_test(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'personal-auto-engine'));
        }

        check_admin_referer('pade_test_platform');

        $platform = sanitize_key($_POST['platform'] ?? 'telegram');
        $this->router->dispatch($platform, ['post_id' => 0, 'message' => 'PADE API test']);

        wp_safe_redirect(admin_url('admin.php?page=pade-control-center&tested=' . $platform));
        exit;
    }

    public function render(): void {
        echo '<div class="wrap"><h1>' . esc_html__('PADE Diagnostic Control Center', 'personal-auto-engine') . '</h1>';
        pade_render_control_center_block($this->database, $this->logger, $this->queue, $this->router);
        echo '</div>';
    }
}

function pade_render_control_center_block(PADE_Database $database, PADE_Logger $logger, PADE_Queue $queue, PADE_Router $router): void {
    global $wp_version;

    $memory_limit = (string) ini_get('memory_limit');
    $cron_status = wp_next_scheduled('pade_process_queue_event') ? __('Scheduled', 'personal-auto-engine') : __('Not Scheduled', 'personal-auto-engine');
    $summary = $queue->queue_health_summary();
    $logs = $logger->get_recent_logs(50);

    echo '<table class="widefat striped"><tbody>';
    echo '<tr><th>' . esc_html__('PHP Version', 'personal-auto-engine') . '</th><td>' . esc_html(PHP_VERSION) . '</td></tr>';
    echo '<tr><th>' . esc_html__('WordPress Version', 'personal-auto-engine') . '</th><td>' . esc_html($wp_version) . '</td></tr>';
    echo '<tr><th>' . esc_html__('Memory Limit', 'personal-auto-engine') . '</th><td>' . esc_html($memory_limit) . '</td></tr>';
    echo '<tr><th>' . esc_html__('WP_DEBUG', 'personal-auto-engine') . '</th><td>' . esc_html(defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . '</td></tr>';
    echo '<tr><th>' . esc_html__('Cron Status', 'personal-auto-engine') . '</th><td>' . esc_html($cron_status) . '</td></tr>';
    echo '<tr><th>' . esc_html__('Queue Health', 'personal-auto-engine') . '</th><td><pre>' . esc_html(wp_json_encode($summary, JSON_PRETTY_PRINT)) . '</pre></td></tr>';
    echo '<tr><th>' . esc_html__('Last AI Response', 'personal-auto-engine') . '</th><td><pre>' . esc_html($logger->last_ai_response()) . '</pre></td></tr>';
    echo '</tbody></table>';

    echo '<h2>' . esc_html__('API Test Buttons', 'personal-auto-engine') . '</h2><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
    wp_nonce_field('pade_test_platform');
    echo '<input type="hidden" name="action" value="pade_test_platform">';
    foreach ($router->platforms() as $platform) {
        echo '<button class="button" name="platform" value="' . esc_attr($platform) . '">' . esc_html(sprintf(__('Test %s', 'personal-auto-engine'), ucfirst($platform))) . '</button> ';
    }
    echo '</form>';

    echo '<h2>' . esc_html__('Last 50 Logs', 'personal-auto-engine') . '</h2>';
    echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Timestamp</th><th>Platform</th><th>Post ID</th><th>HTTP</th><th>Success</th><th>Raw Response</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        echo '<tr>';
        echo '<td>' . esc_html((string) $log['id']) . '</td>';
        echo '<td>' . esc_html((string) $log['timestamp']) . '</td>';
        echo '<td>' . esc_html((string) $log['platform']) . '</td>';
        echo '<td>' . esc_html((string) $log['post_id']) . '</td>';
        echo '<td>' . esc_html((string) $log['http_code']) . '</td>';
        echo '<td>' . esc_html((string) $log['success_flag']) . '</td>';
        echo '<td><pre>' . esc_html((string) $log['raw_response']) . '</pre></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}
