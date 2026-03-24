<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_EIB_Frontend {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_popup' ) );
    }

    public function enqueue_assets(): void {
        if ( ! $this->should_load() ) {
            return;
        }

        wp_enqueue_style(
            'cw-eib-style',
            CW_EIB_URL . 'assets/css/style.css',
            array(),
            CW_EIB_VERSION
        );

        wp_enqueue_script(
            'cw-eib-script',
            CW_EIB_URL . 'assets/js/script.js',
            array(),
            CW_EIB_VERSION,
            true
        );

        $settings = CW_EIB_Core::get_settings();
        $vars     = array(
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'cw_eib_nonce' ),
            'settings'      => $settings,
            'is_mobile'     => wp_is_mobile(),
            'is_test'       => $this->is_test_mode_active(),
            'current_post'  => is_singular() ? get_the_ID() : 0,
            'whatsapp_url'  => $this->build_whatsapp_url( $settings ),
        );

        wp_add_inline_style(
            'cw-eib-style',
            ':root{' .
            '--cw-eib-bg:' . esc_attr( $settings['bg_color'] ) . ';' .
            '--cw-eib-btn:' . esc_attr( $settings['button_color'] ) . ';' .
            '--cw-eib-radius:' . (int) $settings['border_radius'] . 'px;' .
            '--cw-eib-font:' . (int) $settings['font_size'] . 'px;' .
            '}'
        );

        wp_localize_script( 'cw-eib-script', 'CWExitBooster', $vars );
    }

    public function render_popup(): void {
        if ( ! $this->should_load() ) {
            return;
        }

        $settings = CW_EIB_Core::get_settings();
        $template = 'template_1' === $settings['template'] ? 'popup-template-1.php' : 'popup-template-2.php';

        include CW_EIB_PATH . 'templates/' . $template;
    }

    private function should_load(): bool {
        $settings = CW_EIB_Core::get_settings();

        if ( empty( $settings['enabled'] ) ) {
            return false;
        }

        if ( ! empty( $settings['target_posts'] ) && is_singular() ) {
            if ( ! in_array( get_the_ID(), array_map( 'absint', (array) $settings['target_posts'] ), true ) ) {
                return false;
            }
        }

        $device = $settings['target_devices'];
        if ( 'mobile' === $device && ! wp_is_mobile() ) {
            return false;
        }
        if ( 'desktop' === $device && wp_is_mobile() ) {
            return false;
        }

        return true;
    }

    private function build_whatsapp_url( array $settings ): string {
        if ( empty( $settings['whatsapp_number'] ) ) {
            return '';
        }

        return sprintf(
            'https://wa.me/%1$s?text=%2$s',
            rawurlencode( $settings['whatsapp_number'] ),
            rawurlencode( (string) $settings['whatsapp_message'] )
        );
    }

    private function is_test_mode_active(): bool {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        if ( isset( $_GET['cw_eib_test'] ) && '1' === wp_unslash( $_GET['cw_eib_test'] ) ) {
            return true;
        }

        return ! empty( CW_EIB_Core::get_settings()['test_mode'] );
    }
}
