<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_EIB_Logger {

    public static function log_file_path(): string {
        return CW_EIB_PATH . 'logs/cw-eib-error.log';
    }

    public static function log( string $message ): void {
        $line = sprintf(
            "[%s] %s\n",
            gmdate( 'Y-m-d H:i:s' ),
            sanitize_text_field( $message )
        );

        error_log( $line, 3, self::log_file_path() );
    }

    public static function get_last_errors( int $limit = 50 ): array {
        $file = self::log_file_path();

        if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
            return array();
        }

        $content = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
        if ( false === $content ) {
            return array();
        }

        return array_slice( array_reverse( $content ), 0, $limit );
    }
}
