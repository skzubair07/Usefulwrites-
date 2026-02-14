<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Platform_LinkedIn
{
    public function is_configured(array $settings)
    {
        return ! empty($settings['access_token']) && ! empty($settings['organization_id']);
    }

    public function send(array $payload, array $settings)
    {
        $endpoint = 'https://api.linkedin.com/v2/ugcPosts';

        $body = [
            'author'         => 'urn:li:organization:' . sanitize_text_field($settings['organization_id']),
            'lifecycleState' => 'PUBLISHED',
            'specificContent' => [
                'com.linkedin.ugc.ShareContent' => [
                    'shareCommentary' => [
                        'text' => trim($payload['linkedin_copy'] . ' ' . $payload['url']),
                    ],
                    'shareMediaCategory' => 'ARTICLE',
                    'media' => [
                        [
                            'status'      => 'READY',
                            'description' => ['text' => $payload['short_caption']],
                            'originalUrl' => $payload['url'],
                            'title'       => ['text' => $payload['title']],
                        ],
                    ],
                ],
            ],
            'visibility' => [
                'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
            ],
        ];

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'headers' => [
                'Authorization'             => 'Bearer ' . $settings['access_token'],
                'Content-Type'              => 'application/json',
                'X-Restli-Protocol-Version' => '2.0.0',
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            return new WP_Error('linkedin_error', 'LinkedIn post failed.');
        }

        return true;
    }
}
