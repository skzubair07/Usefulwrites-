<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once UADE_PLUGIN_DIR . 'includes/platforms/telegram.php';
require_once UADE_PLUGIN_DIR . 'includes/platforms/facebook.php';
require_once UADE_PLUGIN_DIR . 'includes/platforms/twitter.php';
require_once UADE_PLUGIN_DIR . 'includes/platforms/linkedin.php';
require_once UADE_PLUGIN_DIR . 'includes/platforms/pinterest.php';
require_once UADE_PLUGIN_DIR . 'includes/platforms/whatsapp.php';

class UADE_Platform_Router
{
    private $platforms = [];

    /** @var UADE_Log */
    private $log;

    public function __construct(UADE_Log $log)
    {
        $this->log = $log;
        $this->platforms = [
            'telegram'  => new UADE_Platform_Telegram(),
            'facebook'  => new UADE_Platform_Facebook(),
            'twitter'   => new UADE_Platform_Twitter(),
            'linkedin'  => new UADE_Platform_LinkedIn(),
            'pinterest' => new UADE_Platform_Pinterest(),
            'whatsapp'  => new UADE_Platform_WhatsApp(),
        ];
    }

    public function distribute_post(WP_Post $post, $queue_item = null)
    {
        $payload = $this->build_payload($post);
        $settings = UADE_Settings_Page::get_settings();
        $delay = max(0, (int) $settings['general']['delay_minutes']) * MINUTE_IN_SECONDS;

        $result = [
            'all_success' => true,
            'status_map'  => [],
            'errors'      => [],
        ];

        foreach ($this->platforms as $key => $platform) {
            $platform_settings = isset($settings[$key]) && is_array($settings[$key]) ? $settings[$key] : [];
            if (! $platform->is_configured($platform_settings)) {
                $result['status_map'][$key] = 'skipped';
                continue;
            }

            $send_result = $platform->send($payload, $platform_settings);
            $normalized = $this->normalize_send_result($send_result);

            if (! $normalized['success']) {
                $result['all_success'] = false;
                $result['status_map'][$key] = 'failed';
                $result['errors'][$key] = $normalized['message'];
            } else {
                $result['status_map'][$key] = 'posted';
            }

            $this->log->add((int) $post->ID, $key, $result['status_map'][$key], $normalized['raw']);

            if ($delay > 0) {
                sleep((int) $delay);
            }
        }

        return $result;
    }

    private function normalize_send_result($send_result)
    {
        if (is_wp_error($send_result)) {
            return [
                'success' => false,
                'message' => $send_result->get_error_message(),
                'raw'     => $send_result,
            ];
        }

        if (is_array($send_result) && array_key_exists('success', $send_result)) {
            return [
                'success' => ! empty($send_result['success']),
                'message' => ! empty($send_result['message']) ? (string) $send_result['message'] : __('Platform API request failed.', 'auto-distribution-engine'),
                'raw'     => array_key_exists('raw', $send_result) ? $send_result['raw'] : $send_result,
            ];
        }

        return [
            'success' => (bool) $send_result,
            'message' => __('Platform API request failed.', 'auto-distribution-engine'),
            'raw'     => $send_result,
        ];
    }

    private function build_payload(WP_Post $post)
    {
        $title = wp_strip_all_tags(get_the_title($post));
        $content = wp_strip_all_tags(strip_shortcodes($post->post_content));
        $words = preg_split('/\s+/', trim($content));
        $short_caption = implode(' ', array_slice(array_filter($words), 0, 25));
        $url = get_permalink($post);
        $image_url = get_the_post_thumbnail_url($post, 'full');

        return [
            'post_id'       => (int) $post->ID,
            'title'         => $title,
            'short_caption' => $short_caption,
            'caption_250'   => mb_substr($short_caption, 0, 250),
            'content'       => $content,
            'url'           => esc_url_raw($url),
            'image_url'     => $image_url ? esc_url_raw($image_url) : '',
            'linkedin_copy' => sprintf('New update: %s — %s', $title, $short_caption),
        ];
    }
}
