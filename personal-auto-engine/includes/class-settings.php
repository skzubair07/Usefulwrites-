<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Settings {
    public const OPTION_KEY = 'pade_settings';

    public function init(): void {
        add_action('admin_init', [$this, 'register']);
    }

    public function register(): void {
        register_setting(
            'pade_settings_group',
            self::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
                'default'           => $this->defaults(),
            ]
        );
    }

    public function defaults(): array {
        return [
            'general' => [
                'enabled' => 1,
                'manual_approval' => 1,
                'daily_post_limit' => 5,
            ],
            'api_credentials' => [
                'telegram_token' => '',
                'pinterest_token' => '',
                'facebook_token' => '',
                'twitter_token' => '',
                'linkedin_token' => '',
            ],
            'ai_settings' => [
                'openai_api_key' => '',
                'model' => 'gpt-4o-mini',
                'max_tokens' => 200,
                'prompts' => [
                    'telegram' => 'Write a short Telegram post for {post_title}. Content: {post_content}',
                    'pinterest' => 'Write a Pinterest caption for {post_title}. Content: {post_content}',
                    'facebook' => 'Write a Facebook post for {post_title}. Content: {post_content}',
                    'twitter' => 'Write an X post for {post_title}. Content: {post_content}',
                    'linkedin' => 'Write a professional LinkedIn post for {post_title}. Content: {post_content}',
                ],
            ],
        ];
    }

    public function get(): array {
        return wp_parse_args((array) get_option(self::OPTION_KEY, []), $this->defaults());
    }

    public function sanitize(array $input): array {
        $defaults = $this->defaults();

        $output = $defaults;
        $output['general']['enabled'] = empty($input['general']['enabled']) ? 0 : 1;
        $output['general']['manual_approval'] = empty($input['general']['manual_approval']) ? 0 : 1;
        $output['general']['daily_post_limit'] = max(1, absint($input['general']['daily_post_limit'] ?? 5));

        foreach (array_keys($defaults['api_credentials']) as $key) {
            $output['api_credentials'][$key] = sanitize_text_field($input['api_credentials'][$key] ?? '');
        }

        $output['ai_settings']['openai_api_key'] = sanitize_text_field($input['ai_settings']['openai_api_key'] ?? '');
        $output['ai_settings']['model'] = sanitize_text_field($input['ai_settings']['model'] ?? 'gpt-4o-mini');
        $output['ai_settings']['max_tokens'] = max(50, absint($input['ai_settings']['max_tokens'] ?? 200));

        foreach (array_keys($defaults['ai_settings']['prompts']) as $platform) {
            $output['ai_settings']['prompts'][$platform] = sanitize_textarea_field($input['ai_settings']['prompts'][$platform] ?? $defaults['ai_settings']['prompts'][$platform]);
        }

        return $output;
    }
}
