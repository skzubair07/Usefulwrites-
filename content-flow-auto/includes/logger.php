<?php
/**
 * Logger utilities.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get log file path.
 *
 * @return string
 */
function cfa_get_log_file_path() {
	$upload = wp_upload_dir();
	return trailingslashit( $upload['basedir'] ) . 'content-flow-log.txt';
}

/**
 * Add log line.
 *
 * @param string $message Message.
 * @return void
 */
function cfa_log( $message ) {
	$line = sprintf( "[%s] %s\n", current_time( 'H:i' ), sanitize_text_field( $message ) );
	$file = cfa_get_log_file_path();

	$dir = dirname( $file );
	if ( ! is_dir( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	file_put_contents( $file, $line, FILE_APPEND | LOCK_EX );
}

/**
 * Read log file content.
 *
 * @return string
 */
function cfa_get_logs() {
	$file = cfa_get_log_file_path();
	if ( ! file_exists( $file ) ) {
		return '';
	}

	$content = file_get_contents( $file );
	return is_string( $content ) ? $content : '';
}
