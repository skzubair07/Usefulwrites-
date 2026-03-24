<?php

if (!defined('ABSPATH')) {
    exit;
}

class CW_SB_Core {
    /** @var CW_SB_Admin */
    private $admin;

    /** @var CW_SB_Frontend */
    private $frontend;

    /** @var CW_SB_Ajax */
    private $ajax;

    public function init() {
        $this->admin = new CW_SB_Admin();
        $this->frontend = new CW_SB_Frontend();
        $this->ajax = new CW_SB_Ajax();

        $this->admin->init();
        $this->frontend->init();
        $this->ajax->init();
    }

    public static function default_settings() {
        return array(
            'enabled' => 0,
            'position' => 'bottom',
            'show_trigger' => 'delay',
            'show_delay' => 3,
            'show_scroll_percent' => 30,
            'hide_on_scroll' => 1,
            'messages' => array('Limited-time deal! Grab it now.'),
            'rotation_speed' => 4,
            'promo_text' => 'Special launch offer available now.',
            'buttons' => array(
                array(
                    'type' => 'link',
                    'text' => 'Buy Now',
                    'value' => '#',
                    'style' => 'primary',
                ),
            ),
            'countdown_enabled' => 0,
            'timer_duration' => 900,
            'background_color' => '#111827',
            'text_color' => '#f9fafb',
            'button_bg_color' => '#22c55e',
            'button_text_color' => '#ffffff',
            'font_size' => 16,
            'padding' => 14,
            'border_radius' => 10,
            'close_enabled' => 1,
            'reappear_after' => 1440,
            'target_pages' => array(),
            'device_targeting' => 'all',
            'template' => 'template-1',
        );
    }

    public static function activate() {
        if (!get_option(CW_SB_OPTION_KEY)) {
            add_option(CW_SB_OPTION_KEY, self::default_settings());
        }

        if (!get_option(CW_SB_ANALYTICS_KEY)) {
            add_option(CW_SB_ANALYTICS_KEY, array('views' => 0, 'clicks' => 0));
        }

        if (!get_option(CW_SB_LOG_KEY)) {
            add_option(CW_SB_LOG_KEY, array());
        }
    }

    public static function get_settings() {
        $settings = get_option(CW_SB_OPTION_KEY, array());
        return wp_parse_args($settings, self::default_settings());
    }

    public static function get_analytics() {
        $analytics = get_option(CW_SB_ANALYTICS_KEY, array());
        return wp_parse_args($analytics, array('views' => 0, 'clicks' => 0));
    }

    public static function increment_metric($metric) {
        $allowed = array('views', 'clicks');
        if (!in_array($metric, $allowed, true)) {
            return;
        }

        $analytics = self::get_analytics();
        $analytics[$metric] = (int) $analytics[$metric] + 1;
        update_option(CW_SB_ANALYTICS_KEY, $analytics, false);
    }
}
