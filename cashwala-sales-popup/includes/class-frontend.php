<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Sales_Popup_Frontend {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function enqueue_assets() {
        $settings = get_option( 'cw_sales_popup_settings', CW_Sales_Popup_DB::default_settings() );
        if ( empty( $settings['enabled'] ) ) {
            return;
        }

        if ( wp_is_mobile() && empty( $settings['enable_mobile'] ) ) {
            return;
        }

        wp_enqueue_style(
            'cw-sales-popup-style',
            CW_SALES_POPUP_URL . 'assets/css/style.css',
            array(),
            CW_SALES_POPUP_VERSION
        );

        wp_enqueue_script(
            'cw-sales-popup-script',
            CW_SALES_POPUP_URL . 'assets/js/script.js',
            array(),
            CW_SALES_POPUP_VERSION,
            true
        );

        $localized = array(
            'ajax_url'            => admin_url( 'admin-ajax.php' ),
            'nonce'               => wp_create_nonce( 'cw_sales_popup_nonce' ),
            'initial_delay'       => max( 0, absint( $settings['initial_delay'] ?? 3 ) ) * 1000,
            'interval'            => max( 2, absint( $settings['interval'] ?? 7 ) ) * 1000,
            'random_variation'    => absint( $settings['random_variation'] ?? 2 ) * 1000,
            'randomized_timing'   => ! empty( $settings['randomized_timing'] ),
            'loop_enabled'        => ! empty( $settings['loop_enabled'] ),
            'shuffle_entries'     => ! empty( $settings['shuffle_entries'] ),
            'position'            => sanitize_key( $settings['position'] ?? 'bottom-right' ),
            'display_mode'        => sanitize_key( $settings['display_mode'] ?? 'single' ),
            'max_popups'          => max( 1, absint( $settings['max_popups'] ?? 1 ) ),
            'show_duration'       => max( 3, absint( $settings['show_duration'] ?? 5 ) ) * 1000,
            'sound_enabled'       => ! empty( $settings['sound_enabled'] ),
            'sound_url'           => esc_url_raw( $settings['sound_url'] ?? '' ),
            'background_color'    => sanitize_text_field( $settings['background_color'] ?? 'rgba(20,24,39,0.78)' ),
            'text_color'          => sanitize_text_field( $settings['text_color'] ?? '#ffffff' ),
            'border_radius'       => absint( $settings['border_radius'] ?? 14 ),
            'shadow'              => sanitize_text_field( $settings['shadow'] ?? '0 8px 24px rgba(0,0,0,0.2)' ),
        );

        wp_localize_script( 'cw-sales-popup-script', 'CWSalesPopup', $localized );
    }
}
