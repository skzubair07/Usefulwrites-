<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Platform_Twitter
{
    public function is_configured(array $settings)
    {
        return ! empty($settings['api_key']) && ! empty($settings['api_secret']) && ! empty($settings['access_token']) && ! empty($settings['access_token_secret']);
    }

    public function send(array $payload, array $settings)
    {
        $endpoint = 'https://api.twitter.com/2/tweets';
        $message = trim($payload['caption_250'] . ' ' . $payload['url']);
        $body = wp_json_encode([
            'text' => mb_substr($message, 0, 280),
        ]);

        $auth_header = $this->build_oauth_header(
            'POST',
            $endpoint,
            $settings['api_key'],
            $settings['api_secret'],
            $settings['access_token'],
            $settings['access_token_secret']
        );

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization' => $auth_header,
                'Content-Type'  => 'application/json',
            ],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('twitter_error', 'Twitter/X post failed.');
        }

        return true;
    }

    private function build_oauth_header($method, $url, $consumer_key, $consumer_secret, $token, $token_secret)
    {
        $oauth = [
            'oauth_consumer_key'     => $consumer_key,
            'oauth_nonce'            => wp_generate_password(16, false),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_timestamp'        => (string) time(),
            'oauth_token'            => $token,
            'oauth_version'          => '1.0',
        ];

        $signature_base = $this->build_signature_base_string($method, $url, $oauth);
        $signing_key = rawurlencode($consumer_secret) . '&' . rawurlencode($token_secret);
        $oauth['oauth_signature'] = base64_encode(hash_hmac('sha1', $signature_base, $signing_key, true));

        $header = 'OAuth ';
        $values = [];
        foreach ($oauth as $key => $value) {
            $values[] = rawurlencode($key) . '="' . rawurlencode($value) . '"';
        }

        return $header . implode(', ', $values);
    }

    private function build_signature_base_string($method, $url, array $params)
    {
        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = rawurlencode($key) . '=' . rawurlencode($value);
        }

        return strtoupper($method) . '&' . rawurlencode($url) . '&' . rawurlencode(implode('&', $pairs));
    }
}
