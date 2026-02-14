<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Platform_WhatsApp
{
    public function is_configured(array $settings)
    {
        return ! empty($settings['permanent_access_token']) && ! empty($settings['phone_number_id']);
    }

    public function send(array $payload, array $settings)
    {
        $endpoint = sprintf('https://graph.facebook.com/v19.0/%s/messages', rawurlencode($settings['phone_number_id']));

        $body = [
            'messaging_product' => 'whatsapp',
            'to'                => sanitize_text_field($settings['phone_number_id']),
            'type'              => 'text',
            'text'              => [
                'preview_url' => false,
                'body'        => trim($payload['short_caption'] . "\n" . $payload['url']),
            ],
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => 'Bearer ' . $settings['permanent_access_token'],
                'Content-Type'  => 'application/json',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('whatsapp_error', 'WhatsApp channel post failed.');
        }

        return true;
    }
}
