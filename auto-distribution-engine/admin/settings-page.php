<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Settings_Page
{
    const OPTION_KEY = 'uade_settings';

    /** @var UADE_Queue */
    private $queue;

    /** @var UADE_Platform_Router */
    private $router;

    public function __construct(UADE_Queue $queue, UADE_Platform_Router $router)
    {
        $this->queue  = $queue;
        $this->router = $router;

        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function register_menu()
    {
        add_menu_page(
            __('Auto Distribution Engine', 'auto-distribution-engine'),
            __('Auto Distribution Engine', 'auto-distribution-engine'),
            'manage_options',
            'uade-settings',
            [$this, 'render_page'],
            'dashicons-share',
            58
        );
    }

    public static function default_settings()
    {
        return [
            'general' => [
                'enabled'         => 0,
                'daily_limit'     => 10,
                'delay_minutes'   => 1,
                'manual_approval' => 0,
            ],
            'telegram' => ['bot_token' => '', 'channel_id' => ''],
            'facebook' => ['page_access_token' => '', 'page_id' => ''],
            'twitter'  => ['api_key' => '', 'api_secret' => '', 'access_token' => '', 'access_token_secret' => ''],
            'linkedin' => ['client_id' => '', 'client_secret' => '', 'access_token' => '', 'organization_id' => ''],
            'pinterest'=> ['app_id' => '', 'app_secret' => '', 'access_token' => '', 'board_id' => ''],
            'whatsapp' => ['permanent_access_token' => '', 'phone_number_id' => ''],
        ];
    }

    public static function get_settings()
    {
        $settings = get_option(self::OPTION_KEY, []);

        return wp_parse_args($settings, self::default_settings());
    }

    public static function sanitize_settings($input)
    {
        $defaults = self::default_settings();
        $sanitized = $defaults;

        $sanitized['general']['enabled'] = empty($input['general']['enabled']) ? 0 : 1;
        $sanitized['general']['manual_approval'] = empty($input['general']['manual_approval']) ? 0 : 1;
        $sanitized['general']['daily_limit'] = max(1, absint($input['general']['daily_limit'] ?? $defaults['general']['daily_limit']));
        $sanitized['general']['delay_minutes'] = max(0, absint($input['general']['delay_minutes'] ?? $defaults['general']['delay_minutes']));

        foreach (['telegram', 'facebook', 'twitter', 'linkedin', 'pinterest', 'whatsapp'] as $group) {
            foreach ($defaults[$group] as $key => $value) {
                $sanitized[$group][$key] = sanitize_text_field($input[$group][$key] ?? '');
            }
        }

        return $sanitized;
    }

    public function render_page()
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = self::get_settings();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Auto Distribution Engine', 'auto-distribution-engine'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('uade_settings_group'); ?>
                <h2><?php esc_html_e('General', 'auto-distribution-engine'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Auto Posting', 'auto-distribution-engine'); ?></th>
                        <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[general][enabled]" value="1" <?php checked(1, (int) $settings['general']['enabled']); ?> /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Daily Post Limit', 'auto-distribution-engine'); ?></th>
                        <td><input type="number" min="1" name="<?php echo esc_attr(self::OPTION_KEY); ?>[general][daily_limit]" value="<?php echo esc_attr($settings['general']['daily_limit']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Random Delay Between Platforms (minutes)', 'auto-distribution-engine'); ?></th>
                        <td><input type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[general][delay_minutes]" value="<?php echo esc_attr($settings['general']['delay_minutes']); ?>" /></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Manual Approval Mode', 'auto-distribution-engine'); ?></th>
                        <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[general][manual_approval]" value="1" <?php checked(1, (int) $settings['general']['manual_approval']); ?> /></td>
                    </tr>
                </table>

                <?php $this->render_section('Telegram', 'telegram', $settings['telegram']); ?>
                <?php $this->render_section('Facebook Page', 'facebook', $settings['facebook']); ?>
                <?php $this->render_section('Twitter/X', 'twitter', $settings['twitter']); ?>
                <?php $this->render_section('LinkedIn', 'linkedin', $settings['linkedin']); ?>
                <?php $this->render_section('Pinterest', 'pinterest', $settings['pinterest']); ?>
                <?php $this->render_section('WhatsApp Channel', 'whatsapp', $settings['whatsapp']); ?>

                <?php submit_button(__('Save Settings', 'auto-distribution-engine')); ?>
            </form>
        </div>
        <?php
    }

    private function render_section($title, $slug, $fields)
    {
        echo '<h2>' . esc_html($title) . '</h2>';
        echo '<table class="form-table" role="presentation">';
        foreach ($fields as $key => $value) {
            echo '<tr>';
            echo '<th scope="row">' . esc_html(ucwords(str_replace('_', ' ', $key))) . '</th>';
            echo '<td><input class="regular-text" type="text" name="' . esc_attr(self::OPTION_KEY) . '[' . esc_attr($slug) . '][' . esc_attr($key) . ']" value="' . esc_attr($value) . '" autocomplete="off" /></td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
