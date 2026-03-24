<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWQSP_Logger {
	const LOG_DIR  = 'cashwala-quick-sell-pro';
	const LOG_FILE = 'cwqsp.log';

	public static function maybe_prepare_log_directory() {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . self::LOG_DIR;
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
	}

	public function path() {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . self::LOG_DIR;
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return trailingslashit( $dir ) . self::LOG_FILE;
	}

	public function log( $type, $message, $context = array() ) {
		$line = wp_json_encode(
			array(
				'time'    => current_time( 'mysql' ),
				'type'    => sanitize_key( $type ),
				'message' => sanitize_text_field( $message ),
				'context' => $context,
			)
		);
		file_put_contents( $this->path(), $line . PHP_EOL, FILE_APPEND | LOCK_EX );
	}

	public function read_latest( $count = 200 ) {
		$path = $this->path();
		if ( ! file_exists( $path ) ) {
			return array();
		}
		$lines = file( $path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( ! is_array( $lines ) ) {
			return array();
		}
		return array_slice( array_reverse( $lines ), 0, absint( $count ) );
	}

	public function clear() {
		$path = $this->path();
		if ( file_exists( $path ) ) {
			file_put_contents( $path, '' );
		}
	}
}
