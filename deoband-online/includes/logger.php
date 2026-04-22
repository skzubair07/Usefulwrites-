<?php
/**
 * Central logger for important plugin events.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Logger {

    /**
     * Store log record in database.
     */
    public static function log( $type, $message ) {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'do_logs',
            array(
                'type'       => sanitize_text_field( $type ),
                'message'    => sanitize_textarea_field( $message ),
                'created_at' => current_time( 'mysql' ),
            )
        );
    }
}
