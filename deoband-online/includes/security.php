<?php
/**
 * Security utilities for permissions, nonce checks, and anti-spam validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Security {

    /**
     * Validate AJAX nonce.
     */
    public static function verify_ajax_nonce( $nonce_key = 'nonce', $action = 'do_ajax_nonce' ) {
        $nonce = isset( $_REQUEST[ $nonce_key ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $nonce_key ] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, $action ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed.' ), 403 );
        }
    }

    /**
     * Require specific capability.
     */
    public static function require_cap( $capability = 'manage_options' ) {
        if ( ! current_user_can( $capability ) ) {
            wp_die( esc_html__( 'You are not allowed to do this action.', 'deoband-online' ) );
        }
    }

    /**
     * Basic anti-spam check for text fields.
     */
    public static function is_spammy_text( $text ) {
        $text = (string) $text;
        if ( strlen( $text ) > 5000 ) {
            return true;
        }
        if ( preg_match( '/https?:\/\//i', $text ) && substr_count( $text, 'http' ) > 5 ) {
            return true;
        }
        return false;
    }
}
