<?php
/**
 * Logger class.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWTS_Logger {
	/**
	 * Log message.
	 *
	 * @param string $message Message.
	 * @param string $level Level.
	 * @return void
	 */
	public static function log( $message, $level = 'info' ) {
		$upload_dir = wp_upload_dir();
		$log_file   = trailingslashit( $upload_dir['basedir'] ) . 'cwts-error.log';
		$timestamp  = gmdate( 'Y-m-d H:i:s' );
		$line       = sprintf( "[%s] [%s] %s\n", $timestamp, strtoupper( $level ), wp_strip_all_tags( $message ) );
		file_put_contents( $log_file, $line, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Read log file.
	 *
	 * @return string
	 */
	public static function read_logs() {
		$upload_dir = wp_upload_dir();
		$log_file   = trailingslashit( $upload_dir['basedir'] ) . 'cwts-error.log';

		if ( ! file_exists( $log_file ) ) {
			return __( 'No logs found.', 'cashwala-testimonial-slider' );
		}

		$contents = file_get_contents( $log_file );
		return $contents ? $contents : __( 'No logs found.', 'cashwala-testimonial-slider' );
	}
}
