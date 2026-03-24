<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMP_Popup {
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( __CLASS__, 'render_popup' ) );
    }

    public static function enqueue_assets() {
        $options = lmp_get_options();
        if ( empty( $options['enabled'] ) ) {
            return;
        }

        wp_enqueue_style( 'lmp-popup-style', LMP_PLUGIN_URL . 'assets/css/popup.css', array(), LMP_VERSION );
        wp_enqueue_script( 'lmp-popup-script', LMP_PLUGIN_URL . 'assets/js/popup.js', array( 'jquery' ), LMP_VERSION, true );

        wp_localize_script(
            'lmp-popup-script',
            'lmpPopupData',
            array(
                'ajax_url'          => admin_url( 'admin-ajax.php' ),
                'nonce'             => wp_create_nonce( 'lmp_popup_nonce' ),
                'trigger_type'      => sanitize_text_field( $options['trigger_type'] ),
                'delay_seconds'     => absint( $options['delay_seconds'] ),
                'scroll_percent'    => absint( $options['scroll_percent'] ),
                'show_once_session' => ! empty( $options['show_once_session'] ),
                'redirect_mode'     => sanitize_text_field( $options['redirect_mode'] ),
            )
        );

        $inline_css = ':root{--lmp-button-bg:' . sanitize_hex_color( $options['button_bg_color'] ) . ';--lmp-button-text:' . sanitize_hex_color( $options['button_text_color'] ) . ';--lmp-popup-bg:' . sanitize_hex_color( $options['popup_background'] ) . ';--lmp-popup-text:' . sanitize_hex_color( $options['popup_text_color'] ) . ';--lmp-overlay-opacity:' . esc_attr( $options['overlay_opacity'] ) . ';}';
        wp_add_inline_style( 'lmp-popup-style', $inline_css );
    }

    public static function render_popup() {
        $options = lmp_get_options();

        if ( empty( $options['enabled'] ) ) {
            return;
        }

        $template = LMP_PLUGIN_PATH . 'templates/popup-template.php';
        if ( file_exists( $template ) ) {
            include $template;
        }
    }
}
