<?php
/**
 * Complaint and support chat module.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Complaint_Module {

    public static function init() {
        add_action( 'wp_ajax_do_submit_complaint', array( __CLASS__, 'submit_complaint' ) );
        add_action( 'wp_ajax_do_reply_complaint', array( __CLASS__, 'reply_complaint' ) );
    }

    public static function submit_complaint() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Login required.' ) );
        }

        global $wpdb;
        $subject = sanitize_text_field( wp_unslash( $_POST['subject'] ?? '' ) );
        $message = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );

        $wpdb->insert(
            $wpdb->prefix . 'do_complaints',
            array(
                'user_id'    => get_current_user_id(),
                'subject'    => $subject,
                'status'     => 'open',
                'created_at' => current_time( 'mysql' ),
            )
        );

        $complaint_id = $wpdb->insert_id;
        self::insert_message( $complaint_id, 'user', $message );

        wp_send_json_success( array( 'complaint_id' => $complaint_id ) );
    }

    public static function reply_complaint() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ) );
        }
        $complaint_id = absint( $_POST['complaint_id'] ?? 0 );
        $message      = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
        self::insert_message( $complaint_id, 'admin', $message );
        wp_send_json_success();
    }

    private static function insert_message( $complaint_id, $sender_type, $message ) {
        global $wpdb;
        $image_url = '';

        if ( ! empty( $_FILES['image']['tmp_name'] ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            $upload = wp_handle_upload( $_FILES['image'], array( 'test_form' => false ) );
            if ( empty( $upload['error'] ) ) {
                $image_url = esc_url_raw( $upload['url'] );
            }
        }

        $wpdb->insert(
            $wpdb->prefix . 'do_complaint_messages',
            array(
                'complaint_id' => $complaint_id,
                'sender_type'  => sanitize_text_field( $sender_type ),
                'message'      => $message,
                'image_url'    => $image_url,
                'created_at'   => current_time( 'mysql' ),
            )
        );
    }
}

function do_render_complaint_admin_page() {
    global $wpdb;
    $complaints = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'do_complaints ORDER BY id DESC LIMIT 200', ARRAY_A );
    include DO_PLUGIN_DIR . 'templates/admin-complaints.php';
}

DO_Complaint_Module::init();
