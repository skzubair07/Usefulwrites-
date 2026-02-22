<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Admin_Settings_Page {
    public function __construct(
        private PADE_Settings $settings,
        private PADE_Database $database,
        private PADE_Logger $logger,
        private PADE_Queue $queue,
        private PADE_Router $router
    ) {
        $this->settings->init();
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_post_pade_bulk_image_add', [$this, 'handle_bulk_image_add']);
    }

    public function register_menu(): void {
        add_menu_page(__('PADE Settings', 'personal-auto-engine'), __('PADE', 'personal-auto-engine'), 'manage_options', 'pade-settings', [$this, 'render']);
    }

    public function handle_bulk_image_add(): void {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized.', 'personal-auto-engine'));
        }

        check_admin_referer('pade_bulk_image_add');

        $raw_ids = sanitize_text_field(wp_unslash($_POST['image_ids'] ?? ''));
        $images = array_filter(array_map('absint', array_map('trim', explode(',', $raw_ids))));
        $platform = sanitize_key($_POST['platform'] ?? 'telegram');

        if (! empty($images)) {
            $limit = (int) ($this->settings->get()['general']['daily_post_limit'] ?? 5);
            $images = array_slice($images, 0, $limit);
            foreach ($images as $image_id) {
                update_post_meta($image_id, '_pade_last_used_at', current_time('mysql'));
            }
            $this->queue->insert_bulk_item($images, $platform);
        }

        wp_safe_redirect(admin_url('admin.php?page=pade-settings&tab=instructions&added=1'));
        exit;
    }

    public function render(): void {
        if (! current_user_can('manage_options')) {
            return;
        }

        $tab = sanitize_key($_GET['tab'] ?? 'general');
        $settings = $this->settings->get();
        $tabs = [
            'general' => __('General', 'personal-auto-engine'),
            'api' => __('API Credentials', 'personal-auto-engine'),
            'ai' => __('AI Settings', 'personal-auto-engine'),
            'diagnostics' => __('Diagnostics', 'personal-auto-engine'),
            'instructions' => __('Instructions', 'personal-auto-engine'),
        ];

        echo '<div class="wrap"><h1>' . esc_html__('Personal Auto Distribution Engine', 'personal-auto-engine') . '</h1><h2 class="nav-tab-wrapper">';
        foreach ($tabs as $key => $label) {
            $active = $tab === $key ? ' nav-tab-active' : '';
            echo '<a class="nav-tab' . esc_attr($active) . '" href="' . esc_url(admin_url('admin.php?page=pade-settings&tab=' . $key)) . '">' . esc_html($label) . '</a>';
        }
        echo '</h2>';

        if (in_array($tab, ['general', 'api', 'ai'], true)) {
            echo '<form method="post" action="options.php">';
            settings_fields('pade_settings_group');
            echo '<table class="form-table"><tbody>';

            if ('general' === $tab) {
                echo '<tr><th>' . esc_html__('Enable engine', 'personal-auto-engine') . '</th><td><input type="checkbox" name="pade_settings[general][enabled]" value="1" ' . checked(1, (int) $settings['general']['enabled'], false) . '></td></tr>';
                echo '<tr><th>' . esc_html__('Manual approval mode', 'personal-auto-engine') . '</th><td><input type="checkbox" name="pade_settings[general][manual_approval]" value="1" ' . checked(1, (int) $settings['general']['manual_approval'], false) . '></td></tr>';
                echo '<tr><th>' . esc_html__('Daily post limit', 'personal-auto-engine') . '</th><td><input type="number" min="1" name="pade_settings[general][daily_post_limit]" value="' . esc_attr((string) $settings['general']['daily_post_limit']) . '"></td></tr>';
            }

            if ('api' === $tab) {
                foreach ($settings['api_credentials'] as $key => $value) {
                    echo '<tr><th>' . esc_html(ucwords(str_replace('_', ' ', $key))) . '</th><td><input type="password" class="regular-text" name="pade_settings[api_credentials][' . esc_attr($key) . ']" value="' . esc_attr($value) . '"></td></tr>';
                }
            }

            if ('ai' === $tab) {
                echo '<tr><th>' . esc_html__('OpenAI API key', 'personal-auto-engine') . '</th><td><input type="password" class="regular-text" name="pade_settings[ai_settings][openai_api_key]" value="' . esc_attr($settings['ai_settings']['openai_api_key']) . '"></td></tr>';
                echo '<tr><th>' . esc_html__('Model', 'personal-auto-engine') . '</th><td><input type="text" class="regular-text" name="pade_settings[ai_settings][model]" value="' . esc_attr($settings['ai_settings']['model']) . '"></td></tr>';
                echo '<tr><th>' . esc_html__('Max tokens', 'personal-auto-engine') . '</th><td><input type="number" min="50" name="pade_settings[ai_settings][max_tokens]" value="' . esc_attr((string) $settings['ai_settings']['max_tokens']) . '"></td></tr>';
                foreach ($settings['ai_settings']['prompts'] as $platform => $prompt) {
                    echo '<tr><th>' . esc_html(sprintf(__('Prompt: %s', 'personal-auto-engine'), ucfirst($platform))) . '</th><td><textarea class="large-text" rows="3" name="pade_settings[ai_settings][prompts][' . esc_attr($platform) . ']">' . esc_textarea($prompt) . '</textarea></td></tr>';
                }
            }

            echo '</tbody></table>';
            submit_button(__('Save Settings', 'personal-auto-engine'));
            echo '</form>';
        }

        if ('diagnostics' === $tab) {
            include PADE_PLUGIN_DIR . 'admin/page-control-center.php';
            pade_render_control_center_block($this->database, $this->logger, $this->queue, $this->router);
        }

        if ('instructions' === $tab) {
            echo '<h2>' . esc_html__('Instructions', 'personal-auto-engine') . '</h2>';
            echo '<p>' . esc_html__('Use post publish to auto-add queue items. Approve pending items in Queue page. You can also add Bulk Image items below.', 'personal-auto-engine') . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('pade_bulk_image_add');
            echo '<input type="hidden" name="action" value="pade_bulk_image_add">';
            echo '<p><label>' . esc_html__('Image IDs (comma separated)', 'personal-auto-engine') . '</label><br><input type="text" class="regular-text" name="image_ids"></p>';
            echo '<p><label>' . esc_html__('Platform', 'personal-auto-engine') . '</label><br><select name="platform">';
            foreach ($this->router->platforms() as $platform) {
                echo '<option value="' . esc_attr($platform) . '">' . esc_html(ucfirst($platform)) . '</option>';
            }
            echo '</select></p>';
            submit_button(__('Add Bulk Item', 'personal-auto-engine'));
            echo '</form>';
        }

        echo '</div>';
    }
}
