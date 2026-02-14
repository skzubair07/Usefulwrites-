<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Queue_Page
{
    /** @var UADE_Queue */
    private $queue;

    /** @var UADE_Platform_Router */
    private $router;

    public function __construct(UADE_Queue $queue, UADE_Platform_Router $router)
    {
        $this->queue  = $queue;
        $this->router = $router;

        add_action('admin_menu', [$this, 'register_submenu']);
        add_action('admin_post_uade_queue_action', [$this, 'handle_queue_action']);
    }

    public function register_submenu()
    {
        add_submenu_page(
            'uade-settings',
            __('Queue Monitor', 'auto-distribution-engine'),
            __('Queue Monitor', 'auto-distribution-engine'),
            'manage_options',
            'uade-queue',
            [$this, 'render_page']
        );
    }

    public function handle_queue_action()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'auto-distribution-engine'));
        }

        check_admin_referer('uade_queue_action');

        $action = sanitize_text_field(wp_unslash($_POST['queue_action'] ?? ''));
        $id     = absint($_POST['queue_id'] ?? 0);

        if ($id && 'approve' === $action) {
            $this->queue->approve_item($id);
        }

        if ($id && 'retry' === $action) {
            $this->queue->retry_item($id);
        }

        wp_safe_redirect(admin_url('admin.php?page=uade-queue'));
        exit;
    }

    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $items = $this->queue->get_items(100);
        $pending = $this->queue->count_by_status('pending') + $this->queue->count_by_status('pending_approval');
        $failed = $this->queue->count_by_status('failed');
        $completed = $this->queue->count_by_status('completed');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Auto Distribution → Queue Monitor', 'auto-distribution-engine'); ?></h1>
            <p>
                <strong><?php esc_html_e('Pending posts:', 'auto-distribution-engine'); ?></strong> <?php echo esc_html($pending); ?> |
                <strong><?php esc_html_e('Posted platforms:', 'auto-distribution-engine'); ?></strong> <?php echo esc_html($completed); ?> |
                <strong><?php esc_html_e('Failed platforms:', 'auto-distribution-engine'); ?></strong> <?php echo esc_html($failed); ?>
            </p>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Post', 'auto-distribution-engine'); ?></th>
                        <th><?php esc_html_e('Queue Status', 'auto-distribution-engine'); ?></th>
                        <th><?php esc_html_e('Platform Status', 'auto-distribution-engine'); ?></th>
                        <th><?php esc_html_e('Errors', 'auto-distribution-engine'); ?></th>
                        <th><?php esc_html_e('Actions', 'auto-distribution-engine'); ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($items)) : ?>
                    <tr><td colspan="5"><?php esc_html_e('No queue items found.', 'auto-distribution-engine'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($items as $item) : ?>
                        <?php
                        $platform_status = json_decode((string) $item->platform_status, true);
                        $errors = json_decode((string) $item->platform_error, true);
                        $post = get_post((int) $item->post_id);
                        ?>
                        <tr>
                            <td>
                                <?php if ($post) : ?>
                                    <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>"><?php echo esc_html(get_the_title($post)); ?></a>
                                <?php else : ?>
                                    <?php esc_html_e('Post deleted', 'auto-distribution-engine'); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($item->status); ?></td>
                            <td><?php echo esc_html($this->format_map($platform_status)); ?></td>
                            <td><?php echo esc_html($this->format_map($errors)); ?></td>
                            <td>
                                <?php if ('pending_approval' === $item->status) : ?>
                                    <?php $this->render_action_form('approve', (int) $item->id, __('Approve', 'auto-distribution-engine')); ?>
                                <?php endif; ?>
                                <?php if ('failed' === $item->status) : ?>
                                    <?php $this->render_action_form('retry', (int) $item->id, __('Retry', 'auto-distribution-engine')); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function render_action_form($action, $id, $label)
    {
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block; margin-right:5px;">
            <input type="hidden" name="action" value="uade_queue_action" />
            <input type="hidden" name="queue_action" value="<?php echo esc_attr($action); ?>" />
            <input type="hidden" name="queue_id" value="<?php echo esc_attr($id); ?>" />
            <?php wp_nonce_field('uade_queue_action'); ?>
            <button class="button button-secondary" type="submit"><?php echo esc_html($label); ?></button>
        </form>
        <?php
    }

    private function format_map($map)
    {
        if (! is_array($map) || empty($map)) {
            return '-';
        }

        $parts = [];
        foreach ($map as $key => $value) {
            $parts[] = sanitize_text_field($key) . ': ' . sanitize_text_field($value);
        }

        return implode(', ', $parts);
    }
}
