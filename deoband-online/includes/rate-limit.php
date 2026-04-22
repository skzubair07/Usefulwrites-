<?php
/**
 * Rate limiter to prevent question spam.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Rate_Limit {

    /**
     * Check action limit by user/IP for given window.
     */
    public static function check( $action, $max = 5, $window = 60 ) {
        $id = get_current_user_id();
        if ( ! $id ) {
            $id = 'ip_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? 'unknown' ) );
        }

        $key   = 'do_rl_' . sanitize_key( $action ) . '_' . $id;
        $count = (int) get_transient( $key );

        if ( $count >= $max ) {
            return new WP_Error( 'rate_limited', 'Rate limit exceeded. Please wait 1 minute.' );
        }

        set_transient( $key, $count + 1, (int) $window );
        return true;
    }
}
