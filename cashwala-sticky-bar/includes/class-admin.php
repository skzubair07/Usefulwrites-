<?php

if (!defined('ABSPATH')) {
    exit;
}

class CW_SB_Admin {
    public function init() {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_cw_sb_clear_logs', array($this, 'clear_logs'));
    }

    public function register_menu() {
        add_menu_page(
            __('CashWala Sticky Bar', 'cashwala-sticky-bar'),
            __('CashWala Sticky Bar', 'cashwala-sticky-bar'),
            'manage_options',
            'cw-sb-settings',
            array($this, 'render_settings_page'),
            'dashicons-megaphone',
            56
        );

        add_submenu_page(
            'cw-sb-settings',
            __('Logs', 'cashwala-sticky-bar'),
            __('Logs', 'cashwala-sticky-bar'),
            'manage_options',
            'cw-sb-logs',
            array($this, 'render_logs_page')
        );
    }

    public function register_settings() {
        register_setting('cw_sb_settings_group', CW_SB_OPTION_KEY, array($this, 'sanitize_settings'));
    }

    public function sanitize_settings($input) {
        $defaults = CW_SB_Core::default_settings();
        $output = array();

        $output['enabled'] = isset($input['enabled']) ? 1 : 0;
        $output['position'] = in_array($input['position'] ?? '', array('top', 'bottom'), true) ? $input['position'] : $defaults['position'];
        $output['show_trigger'] = in_array($input['show_trigger'] ?? '', array('delay', 'scroll'), true) ? $input['show_trigger'] : 'delay';
        $output['show_delay'] = max(0, absint($input['show_delay'] ?? $defaults['show_delay']));
        $output['show_scroll_percent'] = min(100, max(1, absint($input['show_scroll_percent'] ?? $defaults['show_scroll_percent'])));
        $output['hide_on_scroll'] = isset($input['hide_on_scroll']) ? 1 : 0;

        $messages = isset($input['messages']) && is_array($input['messages']) ? $input['messages'] : array();
        $output['messages'] = array_values(array_filter(array_map('sanitize_text_field', $messages)));
        if (empty($output['messages'])) {
            $output['messages'] = $defaults['messages'];
        }

        $output['rotation_speed'] = max(1, absint($input['rotation_speed'] ?? $defaults['rotation_speed']));
        $output['promo_text'] = sanitize_text_field($input['promo_text'] ?? '');

        $output['buttons'] = array();
        if (isset($input['buttons']) && is_array($input['buttons'])) {
            foreach ($input['buttons'] as $button) {
                $type = in_array($button['type'] ?? '', array('link', 'whatsapp', 'call'), true) ? $button['type'] : 'link';
                $text = sanitize_text_field($button['text'] ?? '');
                $value = sanitize_text_field($button['value'] ?? '');
                $style = in_array($button['style'] ?? '', array('primary', 'secondary', 'outline'), true) ? $button['style'] : 'primary';

                if ($text !== '' && $value !== '') {
                    $output['buttons'][] = compact('type', 'text', 'value', 'style');
                }
            }
        }
        if (empty($output['buttons'])) {
            $output['buttons'] = $defaults['buttons'];
        }

        $output['countdown_enabled'] = isset($input['countdown_enabled']) ? 1 : 0;
        $output['timer_duration'] = max(0, absint($input['timer_duration'] ?? $defaults['timer_duration']));

        $output['background_color'] = sanitize_hex_color($input['background_color'] ?? $defaults['background_color']) ?: $defaults['background_color'];
        $output['text_color'] = sanitize_hex_color($input['text_color'] ?? $defaults['text_color']) ?: $defaults['text_color'];
        $output['button_bg_color'] = sanitize_hex_color($input['button_bg_color'] ?? $defaults['button_bg_color']) ?: $defaults['button_bg_color'];
        $output['button_text_color'] = sanitize_hex_color($input['button_text_color'] ?? $defaults['button_text_color']) ?: $defaults['button_text_color'];
        $output['font_size'] = max(10, absint($input['font_size'] ?? $defaults['font_size']));
        $output['padding'] = max(4, absint($input['padding'] ?? $defaults['padding']));
        $output['border_radius'] = max(0, absint($input['border_radius'] ?? $defaults['border_radius']));

        $output['close_enabled'] = isset($input['close_enabled']) ? 1 : 0;
        $output['reappear_after'] = max(0, absint($input['reappear_after'] ?? $defaults['reappear_after']));

        $targets = isset($input['target_pages']) && is_array($input['target_pages']) ? $input['target_pages'] : array();
        $output['target_pages'] = array_filter(array_map('absint', $targets));

        $output['device_targeting'] = in_array($input['device_targeting'] ?? '', array('all', 'mobile', 'desktop'), true) ? $input['device_targeting'] : 'all';
        $output['template'] = in_array($input['template'] ?? '', array('template-1', 'template-2'), true) ? $input['template'] : 'template-1';

        return $output;
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'cw-sb') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = CW_SB_Core::get_settings();
        $analytics = CW_SB_Core::get_analytics();
        $pages = get_pages(array('sort_column' => 'post_title', 'sort_order' => 'asc'));
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('CashWala Sticky CTA Bar Pro', 'cashwala-sticky-bar'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('cw_sb_settings_group'); ?>

                <h2><?php esc_html_e('1. General Settings', 'cashwala-sticky-bar'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Enable', 'cashwala-sticky-bar'); ?></th><td><label><input type="checkbox" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[enabled]" value="1" <?php checked($settings['enabled'], 1); ?>> <?php esc_html_e('Enable Sticky Bar', 'cashwala-sticky-bar'); ?></label></td></tr>
                    <tr><th><?php esc_html_e('Position', 'cashwala-sticky-bar'); ?></th><td>
                        <select name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[position]">
                            <option value="top" <?php selected($settings['position'], 'top'); ?>><?php esc_html_e('Top', 'cashwala-sticky-bar'); ?></option>
                            <option value="bottom" <?php selected($settings['position'], 'bottom'); ?>><?php esc_html_e('Bottom', 'cashwala-sticky-bar'); ?></option>
                        </select>
                    </td></tr>
                    <tr><th><?php esc_html_e('Show Trigger', 'cashwala-sticky-bar'); ?></th><td>
                        <select name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[show_trigger]">
                            <option value="delay" <?php selected($settings['show_trigger'], 'delay'); ?>><?php esc_html_e('Delay', 'cashwala-sticky-bar'); ?></option>
                            <option value="scroll" <?php selected($settings['show_trigger'], 'scroll'); ?>><?php esc_html_e('Scroll Percentage', 'cashwala-sticky-bar'); ?></option>
                        </select>
                        <p><label><?php esc_html_e('Show after seconds', 'cashwala-sticky-bar'); ?> <input type="number" min="0" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[show_delay]" value="<?php echo esc_attr($settings['show_delay']); ?>"></label></p>
                        <p><label><?php esc_html_e('Show after scroll %', 'cashwala-sticky-bar'); ?> <input type="number" min="1" max="100" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[show_scroll_percent]" value="<?php echo esc_attr($settings['show_scroll_percent']); ?>"></label></p>
                        <p><label><input type="checkbox" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[hide_on_scroll]" value="1" <?php checked($settings['hide_on_scroll'], 1); ?>> <?php esc_html_e('Hide on scroll down / show on scroll up', 'cashwala-sticky-bar'); ?></label></p>
                    </td></tr>
                    <tr><th><?php esc_html_e('Template', 'cashwala-sticky-bar'); ?></th><td>
                        <select name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[template]">
                            <option value="template-1" <?php selected($settings['template'], 'template-1'); ?>>Template 1</option>
                            <option value="template-2" <?php selected($settings['template'], 'template-2'); ?>>Template 2</option>
                        </select>
                    </td></tr>
                </table>

                <h2><?php esc_html_e('2. Content Settings', 'cashwala-sticky-bar'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Messages', 'cashwala-sticky-bar'); ?></th><td>
                        <div id="cw-sb-messages-wrapper">
                            <?php foreach ($settings['messages'] as $message) : ?>
                                <p><input type="text" class="regular-text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[messages][]" value="<?php echo esc_attr($message); ?>"> <button type="button" class="button cw-sb-remove-row">Remove</button></p>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="cw-sb-add-message">Add Message</button>
                        <p><label><?php esc_html_e('Rotation speed (seconds)', 'cashwala-sticky-bar'); ?> <input type="number" min="1" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[rotation_speed]" value="<?php echo esc_attr($settings['rotation_speed']); ?>"></label></p>
                        <p><label><?php esc_html_e('Promotional text', 'cashwala-sticky-bar'); ?> <input type="text" class="regular-text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[promo_text]" value="<?php echo esc_attr($settings['promo_text']); ?>"></label></p>
                    </td></tr>
                </table>

                <h2><?php esc_html_e('3. Buttons Settings', 'cashwala-sticky-bar'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('CTA Buttons', 'cashwala-sticky-bar'); ?></th><td>
                        <div id="cw-sb-buttons-wrapper">
                            <?php foreach ($settings['buttons'] as $i => $button) : ?>
                                <div class="cw-sb-button-row" style="margin-bottom:12px;padding:10px;border:1px solid #ddd;">
                                    <label>Type
                                        <select name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[buttons][<?php echo esc_attr($i); ?>][type]">
                                            <option value="link" <?php selected($button['type'], 'link'); ?>>Link</option>
                                            <option value="whatsapp" <?php selected($button['type'], 'whatsapp'); ?>>WhatsApp</option>
                                            <option value="call" <?php selected($button['type'], 'call'); ?>>Call</option>
                                        </select>
                                    </label>
                                    <label>Text <input type="text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[buttons][<?php echo esc_attr($i); ?>][text]" value="<?php echo esc_attr($button['text']); ?>"></label>
                                    <label>URL / Number <input type="text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[buttons][<?php echo esc_attr($i); ?>][value]" value="<?php echo esc_attr($button['value']); ?>"></label>
                                    <label>Style
                                        <select name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[buttons][<?php echo esc_attr($i); ?>][style]">
                                            <option value="primary" <?php selected($button['style'], 'primary'); ?>>Primary</option>
                                            <option value="secondary" <?php selected($button['style'], 'secondary'); ?>>Secondary</option>
                                            <option value="outline" <?php selected($button['style'], 'outline'); ?>>Outline</option>
                                        </select>
                                    </label>
                                    <button type="button" class="button cw-sb-remove-row">Remove</button>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button" id="cw-sb-add-button">Add Button</button>
                    </td></tr>
                </table>

                <h2><?php esc_html_e('4. Timer Settings', 'cashwala-sticky-bar'); ?></h2>
                <table class="form-table">
                    <tr><th><?php esc_html_e('Countdown', 'cashwala-sticky-bar'); ?></th><td>
                        <label><input type="checkbox" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[countdown_enabled]" value="1" <?php checked($settings['countdown_enabled'], 1); ?>> <?php esc_html_e('Enable countdown', 'cashwala-sticky-bar'); ?></label>
                        <p><label><?php esc_html_e('Timer duration (seconds)', 'cashwala-sticky-bar'); ?> <input type="number" min="0" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[timer_duration]" value="<?php echo esc_attr($settings['timer_duration']); ?>"></label></p>
                    </td></tr>
                </table>

                <h2><?php esc_html_e('5. Design Settings', 'cashwala-sticky-bar'); ?></h2>
                <table class="form-table">
                    <tr><th>Background</th><td><input class="cw-color" type="text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[background_color]" value="<?php echo esc_attr($settings['background_color']); ?>"></td></tr>
                    <tr><th>Text Color</th><td><input class="cw-color" type="text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[text_color]" value="<?php echo esc_attr($settings['text_color']); ?>"></td></tr>
                    <tr><th>Button BG</th><td><input class="cw-color" type="text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[button_bg_color]" value="<?php echo esc_attr($settings['button_bg_color']); ?>"></td></tr>
                    <tr><th>Button Text</th><td><input class="cw-color" type="text" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[button_text_color]" value="<?php echo esc_attr($settings['button_text_color']); ?>"></td></tr>
                    <tr><th>Font Size</th><td><input type="number" min="10" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[font_size]" value="<?php echo esc_attr($settings['font_size']); ?>"></td></tr>
                    <tr><th>Padding</th><td><input type="number" min="4" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[padding]" value="<?php echo esc_attr($settings['padding']); ?>"></td></tr>
                    <tr><th>Border Radius</th><td><input type="number" min="0" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[border_radius]" value="<?php echo esc_attr($settings['border_radius']); ?>"></td></tr>
                </table>

                <h2><?php esc_html_e('6. Behavior', 'cashwala-sticky-bar'); ?></h2>
                <table class="form-table">
                    <tr><th>Close Button</th><td><label><input type="checkbox" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[close_enabled]" value="1" <?php checked($settings['close_enabled'], 1); ?>> Enable</label></td></tr>
                    <tr><th>Reappear (minutes)</th><td><input type="number" min="0" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[reappear_after]" value="<?php echo esc_attr($settings['reappear_after']); ?>"></td></tr>
                    <tr><th>Device Targeting</th><td>
                        <select name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[device_targeting]">
                            <option value="all" <?php selected($settings['device_targeting'], 'all'); ?>>All</option>
                            <option value="mobile" <?php selected($settings['device_targeting'], 'mobile'); ?>>Mobile only</option>
                            <option value="desktop" <?php selected($settings['device_targeting'], 'desktop'); ?>>Desktop only</option>
                        </select>
                    </td></tr>
                    <tr><th>Page Targeting</th><td>
                        <select multiple size="8" name="<?php echo esc_attr(CW_SB_OPTION_KEY); ?>[target_pages][]">
                            <?php foreach ($pages as $page) : ?>
                                <option value="<?php echo esc_attr($page->ID); ?>" <?php selected(in_array($page->ID, $settings['target_pages'], true)); ?>><?php echo esc_html($page->post_title); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Leave empty to display on all pages.</p>
                    </td></tr>
                </table>

                <h2><?php esc_html_e('7. Analytics', 'cashwala-sticky-bar'); ?></h2>
                <table class="widefat striped" style="max-width: 520px;">
                    <thead><tr><th>Metric</th><th>Value</th></tr></thead>
                    <tbody>
                        <tr><td>Views</td><td><?php echo esc_html((string) (int) $analytics['views']); ?></td></tr>
                        <tr><td>Clicks</td><td><?php echo esc_html((string) (int) $analytics['clicks']); ?></td></tr>
                    </tbody>
                </table>

                <?php submit_button(__('Save Settings', 'cashwala-sticky-bar')); ?>
            </form>
        </div>

        <script>
            (function(){
                const messageWrap = document.getElementById('cw-sb-messages-wrapper');
                const buttonWrap = document.getElementById('cw-sb-buttons-wrapper');
                const optionKey = <?php echo wp_json_encode(CW_SB_OPTION_KEY); ?>;

                document.getElementById('cw-sb-add-message').addEventListener('click', function(){
                    const row = document.createElement('p');
                    row.innerHTML = `<input type="text" class="regular-text" name="${optionKey}[messages][]" value=""> <button type="button" class="button cw-sb-remove-row">Remove</button>`;
                    messageWrap.appendChild(row);
                });

                document.getElementById('cw-sb-add-button').addEventListener('click', function(){
                    const index = buttonWrap.querySelectorAll('.cw-sb-button-row').length;
                    const row = document.createElement('div');
                    row.className = 'cw-sb-button-row';
                    row.style = 'margin-bottom:12px;padding:10px;border:1px solid #ddd;';
                    row.innerHTML = `
                        <label>Type
                            <select name="${optionKey}[buttons][${index}][type]">
                                <option value="link">Link</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="call">Call</option>
                            </select>
                        </label>
                        <label>Text <input type="text" name="${optionKey}[buttons][${index}][text]" value=""></label>
                        <label>URL / Number <input type="text" name="${optionKey}[buttons][${index}][value]" value=""></label>
                        <label>Style
                            <select name="${optionKey}[buttons][${index}][style]">
                                <option value="primary">Primary</option>
                                <option value="secondary">Secondary</option>
                                <option value="outline">Outline</option>
                            </select>
                        </label>
                        <button type="button" class="button cw-sb-remove-row">Remove</button>
                    `;
                    buttonWrap.appendChild(row);
                });

                document.addEventListener('click', function(e){
                    if (e.target.classList.contains('cw-sb-remove-row')) {
                        e.preventDefault();
                        const target = e.target.closest('.cw-sb-button-row') || e.target.closest('p');
                        if (target) target.remove();
                    }
                });

                if (window.jQuery && window.jQuery.fn.wpColorPicker) {
                    window.jQuery('.cw-color').wpColorPicker();
                }
            })();
        </script>
        <?php
    }

    public function render_logs_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $logs = CW_SB_Logger::get_logs();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('CashWala Sticky Bar Logs', 'cashwala-sticky-bar'); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom:16px;">
                <?php wp_nonce_field('cw_sb_clear_logs_action', 'cw_sb_clear_logs_nonce'); ?>
                <input type="hidden" name="action" value="cw_sb_clear_logs">
                <?php submit_button(__('Clear Logs', 'cashwala-sticky-bar'), 'delete', '', false); ?>
            </form>
            <table class="widefat striped">
                <thead><tr><th>Time</th><th>Message</th><th>Context</th></tr></thead>
                <tbody>
                <?php if (empty($logs)) : ?>
                    <tr><td colspan="3">No logs recorded.</td></tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                            <td><code><?php echo esc_html($log['context']); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function clear_logs() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('cw_sb_clear_logs_action', 'cw_sb_clear_logs_nonce');
        CW_SB_Logger::clear();
        wp_safe_redirect(admin_url('admin.php?page=cw-sb-logs'));
        exit;
    }
}
