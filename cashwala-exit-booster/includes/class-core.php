<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_EIB_Core {

    private static $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->init_defaults();
        new CW_EIB_Ajax();

        if ( is_admin() ) {
            new CW_EIB_Admin();
        } else {
            new CW_EIB_Frontend();
        }
    }

    private function init_defaults(): void {
        $defaults = $this->default_settings();
        $stored   = get_option( 'cw_eib_settings', array() );

        if ( ! is_array( $stored ) || empty( $stored ) ) {
            update_option( 'cw_eib_settings', $defaults );
            return;
        }

        $merged = wp_parse_args( $stored, $defaults );

        if ( $merged !== $stored ) {
            update_option( 'cw_eib_settings', $merged );
        }
    }

    public function default_settings(): array {
        return array(
            'enabled'                  => 1,
            'trigger_type'             => 'exit',
            'delay_seconds'            => 5,
            'scroll_percent'           => 50,
            'popup_variant'            => 'discount',
            'headline'                 => 'Wait! Unlock an Exclusive Offer',
            'subtext'                  => 'Before you leave, grab this limited-time deal.',
            'button_text'              => 'Claim Offer',
            'coupon_code'              => 'SAVE20',
            'countdown_seconds'        => 300,
            'show_name'                => 1,
            'show_email'               => 1,
            'show_phone'               => 0,
            'required_validation'      => 'email',
            'whatsapp_number'          => '',
            'whatsapp_message'         => 'Hi, I want to claim your offer.',
            'bg_color'                 => '#ffffff',
            'button_color'             => '#1f6fff',
            'border_radius'            => 14,
            'font_size'                => 16,
            'target_posts'             => array(),
            'target_devices'           => 'all',
            'frequency'                => 'session_once',
            'template'                 => 'template_1',
            'test_mode'                => 0,
            'cookie_duration_minutes'  => 30,
            'inactivity_seconds'       => 12,
        );
    }

    public static function get_settings(): array {
        $core = self::instance();

        return wp_parse_args( (array) get_option( 'cw_eib_settings', array() ), $core->default_settings() );
    }
}
