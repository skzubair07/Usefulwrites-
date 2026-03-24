<?php
if (! defined('ABSPATH')) {
    exit;
}

class CWCR_Admin
{
    private static $instance;

    public static function instance()
    {
        if (! self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function register_menu()
    {
        add_menu_page(
            __('CashWala Coupon Reveal', 'cashwala-coupon-reveal'),
            __('CashWala Coupon Reveal', 'cashwala-coupon-reveal'),
            'manage_options',
            'cwcr-settings',
            [$this, 'render_settings_page'],
            'dashicons-tickets-alt',
            56
        );
    }

    public function register_settings()
    {
        register_setting('cwcr_settings_group', 'cwcr_settings', [$this, 'sanitize_settings']);
    }

    public function sanitize_settings($input)
    {
        $defaults = CWCR_DB::default_settings();
        $sanitized = [];

        $sanitized['enabled'] = isset($input['enabled']) ? 1 : 0;
        $sanitized['display_mode'] = in_array($input['display_mode'] ?? 'popup', ['popup', 'inline'], true) ? $input['display_mode'] : 'popup';
        $sanitized['coupon_codes'] = sanitize_textarea_field($input['coupon_codes'] ?? $defaults['coupon_codes']);
        $sanitized['dynamic_coupon'] = isset($input['dynamic_coupon']) ? 1 : 0;
        $sanitized['expiry_minutes'] = max(1, absint($input['expiry_minutes'] ?? $defaults['expiry_minutes']));

        $sanitized['reveal_action'] = in_array($input['reveal_action'] ?? 'click', ['click', 'email', 'timer'], true) ? $input['reveal_action'] : 'click';
        $sanitized['trigger_type'] = in_array($input['trigger_type'] ?? 'page_load', ['page_load', 'delay', 'scroll', 'exit_intent'], true) ? $input['trigger_type'] : 'page_load';
        $sanitized['trigger_delay'] = max(0, absint($input['trigger_delay'] ?? 3));
        $sanitized['trigger_scroll'] = min(100, max(1, absint($input['trigger_scroll'] ?? 50)));
        $sanitized['trigger_exit_intent'] = isset($input['trigger_exit_intent']) ? 1 : 0;

        $sanitized['lead_email'] = isset($input['lead_email']) ? 1 : 0;
        $sanitized['lead_phone'] = isset($input['lead_phone']) ? 1 : 0;
        $sanitized['lead_required'] = isset($input['lead_required']) ? 1 : 0;

        $sanitized['bg_color'] = sanitize_hex_color($input['bg_color'] ?? $defaults['bg_color']) ?: $defaults['bg_color'];
        $sanitized['text_color'] = sanitize_hex_color($input['text_color'] ?? $defaults['text_color']) ?: $defaults['text_color'];
        $sanitized['button_style'] = in_array($input['button_style'] ?? 'rounded', ['rounded', 'pill', 'square'], true) ? $input['button_style'] : 'rounded';
        $sanitized['animation_style'] = in_array($input['animation_style'] ?? 'fade-up', ['fade-up', 'zoom-in', 'slide-in'], true) ? $input['animation_style'] : 'fade-up';

        $sanitized['message_before'] = sanitize_text_field($input['message_before'] ?? $defaults['message_before']);
        $sanitized['message_after'] = sanitize_text_field($input['message_after'] ?? $defaults['message_after']);
        $sanitized['inline_title'] = sanitize_text_field($input['inline_title'] ?? $defaults['inline_title']);

        CWCR_Logger::log('info', 'Settings updated by admin');
        return $sanitized;
    }

    public function render_settings_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = wp_parse_args(get_option('cwcr_settings', []), CWCR_DB::default_settings());
        $analytics = wp_parse_args(get_option('cwcr_analytics', []), ['views' => 0, 'reveals' => 0, 'conversions' => 0]);
        $logs = CWCR_Logger::latest(50);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('CashWala Coupon Reveal', 'cashwala-coupon-reveal'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('cwcr_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr><th colspan="2"><h2><?php esc_html_e('General Settings', 'cashwala-coupon-reveal'); ?></h2></th></tr>
                    <tr>
                        <th><?php esc_html_e('Enable Plugin', 'cashwala-coupon-reveal'); ?></th>
                        <td><label><input type="checkbox" name="cwcr_settings[enabled]" value="1" <?php checked(1, $settings['enabled']); ?> /> <?php esc_html_e('Enabled', 'cashwala-coupon-reveal'); ?></label></td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Display Mode', 'cashwala-coupon-reveal'); ?></th>
                        <td>
                            <select name="cwcr_settings[display_mode]">
                                <option value="popup" <?php selected('popup', $settings['display_mode']); ?>><?php esc_html_e('Popup', 'cashwala-coupon-reveal'); ?></option>
                                <option value="inline" <?php selected('inline', $settings['display_mode']); ?>><?php esc_html_e('Inline', 'cashwala-coupon-reveal'); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr><th colspan="2"><h2><?php esc_html_e('Coupon Settings', 'cashwala-coupon-reveal'); ?></h2></th></tr>
                    <tr>
                        <th><?php esc_html_e('Coupon Code(s)', 'cashwala-coupon-reveal'); ?></th>
                        <td><textarea name="cwcr_settings[coupon_codes]" rows="4" cols="60" placeholder="SAVE10\nDEAL20"><?php echo esc_textarea($settings['coupon_codes']); ?></textarea><p class="description"><?php esc_html_e('One code per line.', 'cashwala-coupon-reveal'); ?></p></td>
                    </tr>
                    <tr><th><?php esc_html_e('Dynamic Coupon', 'cashwala-coupon-reveal'); ?></th><td><label><input type="checkbox" name="cwcr_settings[dynamic_coupon]" value="1" <?php checked(1, $settings['dynamic_coupon']); ?> /> <?php esc_html_e('Select random code from list', 'cashwala-coupon-reveal'); ?></label></td></tr>
                    <tr><th><?php esc_html_e('Expiry Timer (minutes)', 'cashwala-coupon-reveal'); ?></th><td><input type="number" min="1" name="cwcr_settings[expiry_minutes]" value="<?php echo esc_attr($settings['expiry_minutes']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('Reveal Action', 'cashwala-coupon-reveal'); ?></th><td>
                        <select name="cwcr_settings[reveal_action]">
                            <option value="click" <?php selected('click', $settings['reveal_action']); ?>><?php esc_html_e('Button Click', 'cashwala-coupon-reveal'); ?></option>
                            <option value="email" <?php selected('email', $settings['reveal_action']); ?>><?php esc_html_e('Email Submission', 'cashwala-coupon-reveal'); ?></option>
                            <option value="timer" <?php selected('timer', $settings['reveal_action']); ?>><?php esc_html_e('Timer Wait', 'cashwala-coupon-reveal'); ?></option>
                        </select>
                    </td></tr>

                    <tr><th colspan="2"><h2><?php esc_html_e('Trigger Settings', 'cashwala-coupon-reveal'); ?></h2></th></tr>
                    <tr><th><?php esc_html_e('Trigger Type', 'cashwala-coupon-reveal'); ?></th><td>
                        <select name="cwcr_settings[trigger_type]">
                            <option value="page_load" <?php selected('page_load', $settings['trigger_type']); ?>><?php esc_html_e('On Page Load', 'cashwala-coupon-reveal'); ?></option>
                            <option value="delay" <?php selected('delay', $settings['trigger_type']); ?>><?php esc_html_e('After Delay', 'cashwala-coupon-reveal'); ?></option>
                            <option value="scroll" <?php selected('scroll', $settings['trigger_type']); ?>><?php esc_html_e('After Scroll %', 'cashwala-coupon-reveal'); ?></option>
                            <option value="exit_intent" <?php selected('exit_intent', $settings['trigger_type']); ?>><?php esc_html_e('Exit Intent', 'cashwala-coupon-reveal'); ?></option>
                        </select>
                    </td></tr>
                    <tr><th><?php esc_html_e('Delay (seconds)', 'cashwala-coupon-reveal'); ?></th><td><input type="number" min="0" name="cwcr_settings[trigger_delay]" value="<?php echo esc_attr($settings['trigger_delay']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('Scroll Percentage', 'cashwala-coupon-reveal'); ?></th><td><input type="number" min="1" max="100" name="cwcr_settings[trigger_scroll]" value="<?php echo esc_attr($settings['trigger_scroll']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('Enable Exit Intent', 'cashwala-coupon-reveal'); ?></th><td><label><input type="checkbox" name="cwcr_settings[trigger_exit_intent]" value="1" <?php checked(1, $settings['trigger_exit_intent']); ?> /> <?php esc_html_e('Use mouse leave trigger', 'cashwala-coupon-reveal'); ?></label></td></tr>

                    <tr><th colspan="2"><h2><?php esc_html_e('Lead Capture Settings', 'cashwala-coupon-reveal'); ?></h2></th></tr>
                    <tr><th><?php esc_html_e('Enable Email Field', 'cashwala-coupon-reveal'); ?></th><td><label><input type="checkbox" name="cwcr_settings[lead_email]" value="1" <?php checked(1, $settings['lead_email']); ?> /> <?php esc_html_e('Email', 'cashwala-coupon-reveal'); ?></label></td></tr>
                    <tr><th><?php esc_html_e('Enable Phone Field', 'cashwala-coupon-reveal'); ?></th><td><label><input type="checkbox" name="cwcr_settings[lead_phone]" value="1" <?php checked(1, $settings['lead_phone']); ?> /> <?php esc_html_e('Phone', 'cashwala-coupon-reveal'); ?></label></td></tr>
                    <tr><th><?php esc_html_e('Fields Required', 'cashwala-coupon-reveal'); ?></th><td><label><input type="checkbox" name="cwcr_settings[lead_required]" value="1" <?php checked(1, $settings['lead_required']); ?> /> <?php esc_html_e('Make enabled fields required', 'cashwala-coupon-reveal'); ?></label></td></tr>

                    <tr><th colspan="2"><h2><?php esc_html_e('Design Settings', 'cashwala-coupon-reveal'); ?></h2></th></tr>
                    <tr><th><?php esc_html_e('Background Color', 'cashwala-coupon-reveal'); ?></th><td><input type="color" name="cwcr_settings[bg_color]" value="<?php echo esc_attr($settings['bg_color']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('Text Color', 'cashwala-coupon-reveal'); ?></th><td><input type="color" name="cwcr_settings[text_color]" value="<?php echo esc_attr($settings['text_color']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('Button Style', 'cashwala-coupon-reveal'); ?></th><td>
                        <select name="cwcr_settings[button_style]">
                            <option value="rounded" <?php selected('rounded', $settings['button_style']); ?>><?php esc_html_e('Rounded', 'cashwala-coupon-reveal'); ?></option>
                            <option value="pill" <?php selected('pill', $settings['button_style']); ?>><?php esc_html_e('Pill', 'cashwala-coupon-reveal'); ?></option>
                            <option value="square" <?php selected('square', $settings['button_style']); ?>><?php esc_html_e('Square', 'cashwala-coupon-reveal'); ?></option>
                        </select>
                    </td></tr>
                    <tr><th><?php esc_html_e('Animation Style', 'cashwala-coupon-reveal'); ?></th><td>
                        <select name="cwcr_settings[animation_style]">
                            <option value="fade-up" <?php selected('fade-up', $settings['animation_style']); ?>><?php esc_html_e('Fade Up', 'cashwala-coupon-reveal'); ?></option>
                            <option value="zoom-in" <?php selected('zoom-in', $settings['animation_style']); ?>><?php esc_html_e('Zoom In', 'cashwala-coupon-reveal'); ?></option>
                            <option value="slide-in" <?php selected('slide-in', $settings['animation_style']); ?>><?php esc_html_e('Slide In', 'cashwala-coupon-reveal'); ?></option>
                        </select>
                    </td></tr>

                    <tr><th colspan="2"><h2><?php esc_html_e('Messages', 'cashwala-coupon-reveal'); ?></h2></th></tr>
                    <tr><th><?php esc_html_e('Before Reveal Text', 'cashwala-coupon-reveal'); ?></th><td><input type="text" class="regular-text" name="cwcr_settings[message_before]" value="<?php echo esc_attr($settings['message_before']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('After Reveal Text', 'cashwala-coupon-reveal'); ?></th><td><input type="text" class="regular-text" name="cwcr_settings[message_after]" value="<?php echo esc_attr($settings['message_after']); ?>" /></td></tr>
                    <tr><th><?php esc_html_e('Inline Title', 'cashwala-coupon-reveal'); ?></th><td><input type="text" class="regular-text" name="cwcr_settings[inline_title]" value="<?php echo esc_attr($settings['inline_title']); ?>" /></td></tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <hr />
            <h2><?php esc_html_e('Analytics', 'cashwala-coupon-reveal'); ?></h2>
            <p><strong><?php esc_html_e('Views:', 'cashwala-coupon-reveal'); ?></strong> <?php echo esc_html((string) absint($analytics['views'])); ?></p>
            <p><strong><?php esc_html_e('Reveals:', 'cashwala-coupon-reveal'); ?></strong> <?php echo esc_html((string) absint($analytics['reveals'])); ?></p>
            <p><strong><?php esc_html_e('Conversions:', 'cashwala-coupon-reveal'); ?></strong> <?php echo esc_html((string) absint($analytics['conversions'])); ?></p>

            <hr />
            <h2><?php esc_html_e('System Logs (Last 50)', 'cashwala-coupon-reveal'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th><?php esc_html_e('Time', 'cashwala-coupon-reveal'); ?></th><th><?php esc_html_e('Level', 'cashwala-coupon-reveal'); ?></th><th><?php esc_html_e('Message', 'cashwala-coupon-reveal'); ?></th><th><?php esc_html_e('Context', 'cashwala-coupon-reveal'); ?></th></tr></thead>
                <tbody>
                <?php if (empty($logs)) : ?>
                    <tr><td colspan="4"><?php esc_html_e('No logs found.', 'cashwala-coupon-reveal'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html($log['time']); ?></td>
                            <td><?php echo esc_html(strtoupper($log['level'])); ?></td>
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
}
