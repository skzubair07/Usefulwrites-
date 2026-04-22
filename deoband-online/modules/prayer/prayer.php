<?php
/**
 * Prayer time module with API fetch + manual override.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Prayer_Module {

    public static function get_prayer_times() {
        $settings = get_option( 'do_prayer_settings', array() );

        if ( ! empty( $settings['manual_override'] ) && is_array( $settings['manual_times'] ?? null ) ) {
            return $settings['manual_times'];
        }

        $api = get_option( 'do_api_settings', array() );
        $url = esc_url_raw( $api['prayer_api_url'] ?? '' );
        if ( ! $url ) {
            return array();
        }

        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) {
            return array();
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        return is_array( $data ) ? $data : array();
    }
}

function do_render_prayer_settings_page() {
    if ( isset( $_POST['do_save_prayer_settings'] ) && check_admin_referer( 'do_prayer_settings_nonce' ) ) {
        $manual_times = array(
            'fajr'    => sanitize_text_field( wp_unslash( $_POST['fajr'] ?? '' ) ),
            'zuhr'    => sanitize_text_field( wp_unslash( $_POST['zuhr'] ?? '' ) ),
            'asr'     => sanitize_text_field( wp_unslash( $_POST['asr'] ?? '' ) ),
            'maghrib' => sanitize_text_field( wp_unslash( $_POST['maghrib'] ?? '' ) ),
            'isha'    => sanitize_text_field( wp_unslash( $_POST['isha'] ?? '' ) ),
        );
        update_option(
            'do_prayer_settings',
            array(
                'manual_override' => ! empty( $_POST['manual_override'] ) ? 1 : 0,
                'manual_times'    => $manual_times,
            )
        );
        echo '<div class="updated"><p>Prayer settings saved.</p></div>';
    }

    $settings = get_option( 'do_prayer_settings', array( 'manual_override' => 0, 'manual_times' => array() ) );
    include DO_PLUGIN_DIR . 'templates/admin-prayer-settings.php';
}
