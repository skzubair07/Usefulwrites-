<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Platform_Telegram
{
    public function is_configured(array $settings)
    {
        return ! empty($settings['bot_token']) && ! empty($settings['channel_id']);
    }

    public function send(array $payload, array $settings)
    {
        $endpoint = sprintf('https://api.telegram.org/bot%s/sendPhoto', rawurlencode($settings['bot_token']));
        $caption = trim($payload['title'] . "\n\n" . $payload['short_caption'] . "\n\n" . $payload['url']);

        $args = [
            'timeout' => 20,
            'body'    => [
                'chat_id'    => sanitize_text_field($settings['channel_id']),
                'photo'      => $payload['image_url'],
                'caption'    => $caption,
                'parse_mode' => 'HTML',
            ],
        ];

        $response = wp_remote_post($endpoint, $args);
        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('telegram_error', 'Telegram post failed.');
        }

        return true;
    }
}
