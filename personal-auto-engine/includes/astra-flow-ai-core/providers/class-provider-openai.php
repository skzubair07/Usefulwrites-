<?php
/**
 * OpenAI provider adapter.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Provider_OpenAI implements Astra_Provider_Interface {
    public function get_key(): string {
        return 'openai';
    }

    public function generate(string $prompt, array $config): array {
        return $this->perform_request($prompt, $config);
    }

    /**
     * @param array<string,mixed> $config
     * @return array{success:bool,output:string,error:?string,http_code:int}
     */
    private function perform_request(string $prompt, array $config): array {
        $endpoint = (string) ($config['endpoint'] ?? '');
        $api_key = (string) ($config['api_key'] ?? '');

        if ($endpoint === '' || $api_key === '') {
            return array('success' => false, 'output' => '', 'error' => 'Missing endpoint or API key.', 'http_code' => 0);
        }

        $payload = $config['payload'] ?? array();
        if (!is_array($payload)) {
            $payload = array();
        }
        $payload['prompt'] = $prompt;

        $headers = $config['headers'] ?? array();
        if (!is_array($headers)) {
            $headers = array();
        }
        $headers = array_merge(array('Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $api_key), $headers);

        $response = wp_remote_post($endpoint, array(
            'headers' => $headers,
            'body'    => wp_json_encode($payload),
            'timeout' => (int) ($config['timeout'] ?? 30),
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'output' => '', 'error' => $response->get_error_message(), 'http_code' => 0);
        }

        $http_code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);
        $output = $this->extract_output($body, (array) ($config['output_path'] ?? array('choices', 0, 'message', 'content')));

        if ($http_code >= 200 && $http_code < 300 && $output !== '') {
            return array('success' => true, 'output' => $output, 'error' => null, 'http_code' => $http_code);
        }

        $error = is_array($body) ? (string) ($body['error']['message'] ?? $body['message'] ?? 'Provider error') : 'Provider error';
        return array('success' => false, 'output' => '', 'error' => $error, 'http_code' => $http_code);
    }

    /** @param array<int|string,mixed> $path */
    private function extract_output($body, array $path): string {
        $value = $body;
        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return '';
            }
            $value = $value[$segment];
        }
        return is_string($value) ? trim($value) : '';
    }
}
