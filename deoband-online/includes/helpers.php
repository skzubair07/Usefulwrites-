<?php
/**
 * Shared helper methods for Deoband Online.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Helpers {

    /**
     * Safe array getter.
     */
    public static function array_get( $array, $key, $default = '' ) {
        return isset( $array[ $key ] ) ? $array[ $key ] : $default;
    }

    /**
     * Build plugin table names in one place.
     */
    public static function table( $suffix ) {
        global $wpdb;
        return $wpdb->prefix . 'do_' . sanitize_key( $suffix );
    }
}
