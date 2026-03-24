<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWQSP_Download {
	/** @var CWQSP_Logger */
	private $logger;

	public function __construct( CWQSP_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'init', array( $this, 'handle_download' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cwqsp_download_tokens';
	}

	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE " . self::table_name() . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			token VARCHAR(64) NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			email VARCHAR(190) NOT NULL,
			expires_at DATETIME NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY token (token)
		) {$charset};" );
	}

	public function create_token( $order_id, $product_id, $email ) {
		global $wpdb;
		$settings = CWQSP_Admin::get_settings();
		$hours    = max( 1, absint( $settings['download_expiry_hours'] ) );
		$token    = wp_generate_password( 48, false, false );
		$wpdb->insert(
			self::table_name(),
			array(
				'token'      => $token,
				'order_id'   => absint( $order_id ),
				'product_id' => absint( $product_id ),
				'email'      => sanitize_email( $email ),
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + ( HOUR_IN_SECONDS * $hours ) ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s' )
		);
		return add_query_arg( array( 'cwqsp_download' => rawurlencode( $token ) ), home_url( '/' ) );
	}

	public function handle_download() {
		if ( empty( $_GET['cwqsp_download'] ) ) {
			return;
		}
		$token = sanitize_text_field( wp_unslash( $_GET['cwqsp_download'] ) );
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE token=%s', $token ) );
		if ( ! $row ) {
			wp_die( 'Invalid or missing download token.' );
		}
		if ( time() > strtotime( $row->expires_at . ' UTC' ) ) {
			wp_die( 'This download link has expired.' );
		}

		$file_id = absint( get_post_meta( $row->product_id, '_cwqsp_file_id', true ) );
		$path    = $file_id ? get_attached_file( $file_id ) : '';
		if ( empty( $path ) || ! file_exists( $path ) ) {
			$this->logger->log( 'download_error', 'Missing download file', array( 'product_id' => $row->product_id, 'file_id' => $file_id ) );
			wp_die( 'File not found. Contact support.' );
		}

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		exit;
	}
}
