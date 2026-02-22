<?php
if (! defined('ABSPATH')) {
    exit;
}
class PADE_Platform_Twitter {
    public function __construct(private PADE_Settings $settings, private PADE_Logger $logger) {}

    public function send(array $payload): array {
        $token = $this->settings->get()['api_credentials']['twitter_token'];
        $ok = ! empty($token);
        $http = $ok ? 200 : 0;
        $response = $ok ? ['ok' => true, 'tweet' => $payload['message']] : ['message' => 'Missing Twitter token'];
        $this->logger->log('twitter', (int) $payload['post_id'], $http, wp_json_encode($response), $ok ? 1 : 0);
        return ['success' => $ok, 'http_code' => $http, 'raw_response' => wp_json_encode($response)];
    }
}
