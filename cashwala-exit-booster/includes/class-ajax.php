<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_EIB_Ajax {

    public function __construct() {
        add_action( 'wp_ajax_cw_eib_save_lead', array( $this, 'save_lead' ) );
        add_action( 'wp_ajax_nopriv_cw_eib_save_lead', array( $this, 'save_lead' ) );
        add_action( 'wp_ajax_cw_eib_track_event', array( $this, 'track_event' ) );
        add_action( 'wp_ajax_nopriv_cw_eib_track_event', array( $this, 'track_event' ) );
    }

    public function save_lead(): void {
        if ( ! check_ajax_referer( 'cw_eib_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid request.', 'cashwala-exit-booster' ) ), 403 );
        }

        $settings = CW_EIB_Core::get_settings();
        $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone    = isset( $_POST['phone'] ) ? preg_replace( '/[^0-9+\-()\s]/', '', wp_unslash( $_POST['phone'] ) ) : '';
        $source   = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : 'popup';

        if ( ! empty( $settings['show_email'] ) && 'email' === $settings['required_validation'] && empty( $email ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Email is required.', 'cashwala-exit-booster' ) ), 400 );
        }

        if ( ! empty( $email ) && ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Please provide a valid email.', 'cashwala-exit-booster' ) ), 400 );
        }

        if ( ! empty( $settings['show_phone'] ) && 'phone' === $settings['required_validation'] && empty( $phone ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Phone is required.', 'cashwala-exit-booster' ) ), 400 );
        }

        if ( empty( $name ) && empty( $email ) && empty( $phone ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Please fill at least one field.', 'cashwala-exit-booster' ) ), 400 );
        }

        $lead_id = CW_EIB_DB::insert_lead(
            array(
                'name'   => $name,
                'email'  => $email,
                'phone'  => $phone,
                'source' => $source,
            )
        );

        if ( false === $lead_id ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Unable to save lead.', 'cashwala-exit-booster' ) ), 500 );
        }

        $this->increment_counter( 'conversions' );

        wp_send_json_success(
            array(
                'message' => esc_html__( 'Lead saved successfully.', 'cashwala-exit-booster' ),
                'lead_id' => $lead_id,
            )
        );
    }

    public function track_event(): void {
        if ( ! check_ajax_referer( 'cw_eib_nonce', 'nonce', false ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Invalid request.', 'cashwala-exit-booster' ) ), 403 );
        }

        $event = isset( $_POST['event'] ) ? sanitize_key( wp_unslash( $_POST['event'] ) ) : '';

        if ( ! in_array( $event, array( 'views', 'clicks', 'conversions' ), true ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Unknown event.', 'cashwala-exit-booster' ) ), 400 );
        }

        $value = $this->increment_counter( $event );

        wp_send_json_success( array( 'count' => $value ) );
    }

    private function increment_counter( string $event ): int {
        $analytics = get_option(
            'cw_eib_analytics',
            array(
                'views'       => 0,
                'clicks'      => 0,
                'conversions' => 0,
            )
        );

        $analytics[ $event ] = isset( $analytics[ $event ] ) ? (int) $analytics[ $event ] + 1 : 1;
        update_option( 'cw_eib_analytics', $analytics );

        return (int) $analytics[ $event ];
    }
}
