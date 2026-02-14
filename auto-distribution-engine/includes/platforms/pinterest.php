<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Platform_Pinterest
{
    public function is_configured(array $settings)
    {
        return ! empty($settings['access_token']) && ! empty($settings['board_id']);
    }

    public function send(array $payload, array $settings)
    {
        $endpoint = 'https://api.pinterest.com/v5/pins';

        $body = [
            'board_id'    => sanitize_text_field($settings['board_id']),
            'title'       => $payload['title'],
            'description' => $payload['short_caption'],
            'link'        => $payload['url'],
            'media_source' => [
                'source_type' => 'image_url',
                'url'         => $payload['image_url'],
            ],
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $settings['access_token'],
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('pinterest_error', 'Pinterest post failed.');
        }

        return true;
    }
}
