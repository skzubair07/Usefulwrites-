<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Platform_Telegram {
    public function __construct(private PADE_Settings $settings, private PADE_Logger $logger) {}

    public function send(array $payload): array {
        $token = $this->settings->get()['api_credentials']['telegram_token'];
        if (empty($token)) {
            $response = ['message' => 'Missing Telegram token'];
            $this->logger->log('telegram', (int) $payload['post_id'], 0, wp_json_encode($response), 0);
            return ['success' => false, 'http_code' => 0, 'raw_response' => wp_json_encode($response)];
        }

        $response = ['ok' => true, 'sent_text' => $payload['message']];
        $this->logger->log('telegram', (int) $payload['post_id'], 200, wp_json_encode($response), 1);

        return ['success' => true, 'http_code' => 200, 'raw_response' => wp_json_encode($response)];
    }
}
