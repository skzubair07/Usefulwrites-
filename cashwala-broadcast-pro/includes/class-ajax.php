<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Ajax {
    public static function init() {
        add_action( 'wp_ajax_cwbp_add_contact', array( __CLASS__, 'add_contact' ) );
    }

    public static function add_contact() {
        check_ajax_referer( 'cwbp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Unauthorized', 'cashwala-broadcast-pro' ) ), 403 );
        }

        $name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $email  = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $phone  = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $source = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'manual';
        $status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'lead';

        $result = CWBP_Contacts::add_contact( $name, $email, $phone, $source, $status );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array( 'message' => $result->get_error_message() ), 400 );
        }

        wp_send_json_success( array( 'contact_id' => $result ) );
    }
}
