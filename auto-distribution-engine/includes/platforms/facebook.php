<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Platform_Facebook
{
    public function is_configured(array $settings)
    {
        return ! empty($settings['page_access_token']) && ! empty($settings['page_id']);
    }

    public function send(array $payload, array $settings)
    {
        $endpoint = sprintf('https://graph.facebook.com/v19.0/%s/photos', rawurlencode($settings['page_id']));

        $args = [
            'timeout' => 20,
            'body'    => [
                'url'          => $payload['image_url'],
                'caption'      => trim($payload['short_caption'] . "\n" . $payload['url']),
                'access_token' => $settings['page_access_token'],
            ],
        ];

        $response = wp_remote_post($endpoint, $args);
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'raw'     => $response,
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return [
                'success' => false,
                'message' => 'Facebook post failed.',
                'raw'     => $response,
            ];
        }

        return [
            'success' => true,
            'raw'     => $response,
        ];
    }
}
