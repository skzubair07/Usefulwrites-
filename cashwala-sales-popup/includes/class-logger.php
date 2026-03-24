<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Sales_Popup_Logger {
    const OPTION_KEY = 'cw_sales_popup_logs';
    const MAX_LOGS   = 50;

    public function log( $level, $message, $context = array() ) {
        $logs = get_option( self::OPTION_KEY, array() );

        $logs[] = array(
            'time'    => current_time( 'mysql' ),
            'level'   => sanitize_key( $level ),
            'message' => sanitize_text_field( $message ),
            'context' => wp_json_encode( $context ),
        );

        if ( count( $logs ) > self::MAX_LOGS ) {
            $logs = array_slice( $logs, -1 * self::MAX_LOGS );
        }

        update_option( self::OPTION_KEY, $logs, false );
    }

    public function get_logs() {
        $logs = get_option( self::OPTION_KEY, array() );
        return array_reverse( is_array( $logs ) ? $logs : array() );
    }

    public function clear() {
        update_option( self::OPTION_KEY, array(), false );
    }
}
