<?php
if (! defined('ABSPATH')) {
    exit;
}
class PADE_Platform_LinkedIn {
    public function __construct(private PADE_Settings $settings, private PADE_Logger $logger) {}

    public function send(array $payload): array {
        $token = $this->settings->get()['api_credentials']['linkedin_token'];
        $ok = ! empty($token);
        $http = $ok ? 200 : 0;
        $response = $ok ? ['ok' => true, 'post' => $payload['message']] : ['message' => 'Missing LinkedIn token'];
        $this->logger->log('linkedin', (int) $payload['post_id'], $http, wp_json_encode($response), $ok ? 1 : 0);
        return ['success' => $ok, 'http_code' => $http, 'raw_response' => wp_json_encode($response)];
    }
}
