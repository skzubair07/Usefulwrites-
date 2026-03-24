<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Logger {
	const OPTION_KEY = 'cw_shop_last_friendly_error';

	public static function maybe_prepare_log_directory() {
		$dir = CW_SHOP_PATH . 'logs';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		$index = trailingslashit( $dir ) . 'index.php';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, "<?php\n// Silence is golden.\n" );
		}

		$htaccess = trailingslashit( $dir ) . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			file_put_contents( $htaccess, "Deny from all\n" );
		}

		$log_file = self::log_path();
		if ( ! file_exists( $log_file ) ) {
			file_put_contents( $log_file, '' );
		}
	}

	public static function log_path() {
		return CW_SHOP_PATH . 'logs/cw-error.log';
	}

	public function log( $type, $message, $context = array() ) {
		self::maybe_prepare_log_directory();
		$stamp  = gmdate( 'Y-m-d H:i:s' );
		$entry  = sprintf( '[%s] [%s] %s', $stamp, sanitize_text_field( $type ), sanitize_text_field( $message ) );
		$clean  = array_map( 'sanitize_text_field', wp_parse_args( $context, array() ) );
		if ( ! empty( $clean ) ) {
			$entry .= ' | ' . wp_json_encode( $clean );
		}
		$entry .= PHP_EOL;
		file_put_contents( self::log_path(), $entry, FILE_APPEND | LOCK_EX );
		error_log( 'CashWala Shop: ' . $entry );
	}

	public function friendly_error_message() {
		return __( 'Something went wrong. Please contact support.', 'cashwala-shop' );
	}

	public function set_friendly_error() {
		update_option( self::OPTION_KEY, $this->friendly_error_message(), false );
	}

	public function pop_friendly_error() {
		$message = get_option( self::OPTION_KEY, '' );
		if ( ! empty( $message ) ) {
			delete_option( self::OPTION_KEY );
		}
		return $message;
	}

	public function read_latest( $lines = 200 ) {
		$file = self::log_path();
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$content = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
		if ( false === $content ) {
			return array();
		}
		return array_slice( $content, - absint( $lines ) );
	}

	public function clear() {
		self::maybe_prepare_log_directory();
		file_put_contents( self::log_path(), '' );
	}
}
