<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMP_Ajax {
    public static function init() {
        add_action( 'wp_ajax_nopriv_lmp_save_lead', array( __CLASS__, 'save_lead' ) );
        add_action( 'wp_ajax_lmp_save_lead', array( __CLASS__, 'save_lead' ) );
    }

    public static function save_lead() {
        check_ajax_referer( 'lmp_popup_nonce', 'nonce' );

        $options = lmp_get_options();

        $name     = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone    = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $page_url = isset( $_POST['page_url'] ) ? esc_url_raw( wp_unslash( $_POST['page_url'] ) ) : '';

        if ( ! empty( $options['required_name'] ) && empty( $name ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Name is required.', 'lead-magnet-pro' ) ), 422 );
        }

        if ( ! empty( $options['required_email'] ) && empty( $email ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Email is required.', 'lead-magnet-pro' ) ), 422 );
        }

        if ( ! empty( $options['required_phone'] ) && empty( $phone ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Phone is required.', 'lead-magnet-pro' ) ), 422 );
        }

        if ( ! empty( $email ) && ! is_email( $email ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Please enter a valid email.', 'lead-magnet-pro' ) ), 422 );
        }

        $saved = LMP_Leads::save_lead(
            array(
                'name'     => $name,
                'email'    => $email,
                'phone'    => $phone,
                'page_url' => $page_url,
            )
        );

        if ( is_wp_error( $saved ) ) {
            wp_send_json_error( array( 'message' => esc_html__( 'Unable to save lead.', 'lead-magnet-pro' ) ), 500 );
        }

        LMP_Analytics::increment_conversion();

        $redirect_url = '';
        if ( isset( $options['redirect_mode'] ) && 'url' === $options['redirect_mode'] ) {
            $redirect_url = esc_url_raw( $options['redirect_url'] );
        } elseif ( isset( $options['redirect_mode'] ) && 'whatsapp' === $options['redirect_mode'] ) {
            $phone_number = preg_replace( '/\D+/', '', (string) $options['whatsapp_number'] );
            $message      = str_replace(
                array( '{name}', '{email}', '{phone}', '{page_url}', '{site_name}' ),
                array( $name, $email, $phone, $page_url, get_bloginfo( 'name' ) ),
                (string) $options['whatsapp_message']
            );

            if ( ! empty( $phone_number ) ) {
                $redirect_url = 'https://wa.me/' . rawurlencode( $phone_number ) . '?text=' . rawurlencode( $message );
            }
        }

        wp_send_json_success(
            array(
                'message'      => ! empty( $options['success_message'] ) ? esc_html( $options['success_message'] ) : esc_html__( 'Lead captured successfully.', 'lead-magnet-pro' ),
                'redirect_url' => $redirect_url,
            )
        );
    }
}
