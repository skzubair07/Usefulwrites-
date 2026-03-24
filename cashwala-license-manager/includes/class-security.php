<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLMP_Security {
    public static function sanitize_key_input( $key ) {
        $key = strtoupper( sanitize_text_field( wp_unslash( $key ) ) );

        return preg_replace( '/[^A-Z0-9\-]/', '', $key );
    }

    public static function sanitize_domain( $domain ) {
        $domain = trim( strtolower( sanitize_text_field( wp_unslash( $domain ) ) ) );
        $domain = preg_replace( '#^https?://#', '', $domain );
        $domain = preg_replace( '#/.*$#', '', $domain );

        return preg_replace( '/[^a-z0-9\-\.]/', '', $domain );
    }

    public static function hash_license_key( $key ) {
        return hash( 'sha256', self::sanitize_key_input( $key ) );
    }

    public static function generate_license_key() {
        $segments = array();

        for ( $i = 0; $i < 4; $i++ ) {
            $segments[] = strtoupper( substr( bin2hex( random_bytes( 4 ) ), 0, 8 ) );
        }

        return 'CWLMP-' . implode( '-', $segments );
    }

    public static function mask_key( $key ) {
        $key   = self::sanitize_key_input( $key );
        $last4 = substr( str_replace( '-', '', $key ), -4 );

        return '••••-••••-••••-' . $last4;
    }

    public static function verify_nonce( $action, $field = '_wpnonce' ) {
        if ( ! isset( $_POST[ $field ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ), $action ) ) {
            wp_die( esc_html__( 'Security check failed.', 'cashwala-license-manager' ) );
        }
    }
}
