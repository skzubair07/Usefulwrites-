<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_AI_Engine {
    private PADE_Settings $settings;
    private PADE_Logger $logger;

    public function __construct(PADE_Settings $settings, PADE_Logger $logger) {
        $this->settings = $settings;
        $this->logger = $logger;
    }

    public function generate_caption(WP_Post $post, string $platform): string {
        $settings = $this->settings->get();
        $api_key = $settings['ai_settings']['openai_api_key'];
        if (empty($api_key)) {
            return wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
        }

        $template = $settings['ai_settings']['prompts'][$platform] ?? 'Write a social media post for {post_title}. Content: {post_content}';
        $prompt = strtr(
            $template,
            [
                '{post_title}' => $post->post_title,
                '{post_content}' => wp_strip_all_tags($post->post_content),
                '{platform_name}' => $platform,
            ]
        );

        $response = wp_remote_post(
            'https://api.openai.com/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ],
                'body' => wp_json_encode(
                    [
                        'model' => $settings['ai_settings']['model'],
                        'messages' => [
                            ['role' => 'system', 'content' => 'You write concise social distribution copy.'],
                            ['role' => 'user', 'content' => $prompt],
                        ],
                        'max_tokens' => (int) $settings['ai_settings']['max_tokens'],
                    ]
                ),
                'timeout' => 30,
            ]
        );

        if (is_wp_error($response)) {
            $this->logger->log('ai-engine', (int) $post->ID, 0, $response->get_error_message(), 0);

            return wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = (string) wp_remote_retrieve_body($response);
        $success = $code >= 200 && $code < 300 ? 1 : 0;
        $this->logger->log('ai-engine', (int) $post->ID, $code, $body, $success);

        $decoded = json_decode($body, true);

        return sanitize_textarea_field($decoded['choices'][0]['message']['content'] ?? wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...'));
    }
}
