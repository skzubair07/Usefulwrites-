<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Upsell_Logger {
    const OPTION_KEY = 'cw_upsell_logs';

    public function log( $message, $context = array(), $level = 'info' ) {
        $logs   = get_option( self::OPTION_KEY, array() );
        $logs[] = array(
            'time'    => current_time( 'mysql' ),
            'level'   => sanitize_key( $level ),
            'message' => sanitize_text_field( $message ),
            'context' => wp_json_encode( $context ),
        );

        if ( count( $logs ) > 200 ) {
            $logs = array_slice( $logs, -200 );
        }

        update_option( self::OPTION_KEY, $logs, false );
    }

    public function get_logs() {
        $logs = get_option( self::OPTION_KEY, array() );
        return is_array( $logs ) ? array_reverse( $logs ) : array();
    }

    public function clear() {
        delete_option( self::OPTION_KEY );
    }
}
