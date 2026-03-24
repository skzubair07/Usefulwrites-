<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Download {
	private $logger;

	public function __construct( CW_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'init', array( $this, 'handle_download_request' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cw_download_tokens';
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
		$token = wp_generate_password( 32, false, false );
		$wpdb->insert(
			self::table_name(),
			array(
				'token'      => $token,
				'order_id'   => absint( $order_id ),
				'product_id' => absint( $product_id ),
				'email'      => sanitize_email( $email ),
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s' )
		);
		return add_query_arg( array( 'cw_download' => rawurlencode( $token ) ), home_url( '/' ) );
	}

	public function handle_download_request() {
		if ( empty( $_GET['cw_download'] ) ) {
			return;
		}
		$token = sanitize_text_field( wp_unslash( $_GET['cw_download'] ) );
		$this->deliver_file_by_token( $token );
	}

	private function deliver_file_by_token( $token ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE token = %s', $token ) );
		if ( ! $row ) {
			$this->logger->log( 'download_error', 'Invalid download token', array( 'token' => $token ) );
			wp_die( esc_html( $this->logger->friendly_error_message() ) );
		}
		if ( strtotime( $row->expires_at . ' UTC' ) < time() ) {
			$this->logger->log( 'download_error', 'Expired download token', array( 'token' => $token ) );
			wp_die( esc_html__( 'Link expired.', 'cashwala-shop' ) );
		}

		if ( 'cw_combo' === get_post_type( $row->product_id ) ) {
			$file = $this->make_combo_zip( $row->product_id );
		} else {
			$file = get_post_meta( $row->product_id, '_cw_pdf_url', true );
		}

		if ( empty( $file ) ) {
			$this->logger->log( 'download_error', 'File missing for token', array( 'token' => $token, 'product_id' => $row->product_id ) );
			wp_die( esc_html( $this->logger->friendly_error_message() ) );
		}

		$path = $this->resolve_path( $file );
		if ( ! $path || ! file_exists( $path ) ) {
			$this->logger->log( 'download_error', 'File path missing', array( 'path' => $file ) );
			wp_die( esc_html( $this->logger->friendly_error_message() ) );
		}

		nocache_headers();
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . basename( $path ) . '"' );
		header( 'Content-Length: ' . filesize( $path ) );
		readfile( $path );
		exit;
	}

	private function resolve_path( $url_or_path ) {
		if ( file_exists( $url_or_path ) ) {
			return $url_or_path;
		}
		$uploads = wp_upload_dir();
		if ( str_contains( $url_or_path, $uploads['baseurl'] ) ) {
			return str_replace( $uploads['baseurl'], $uploads['basedir'], $url_or_path );
		}
		return false;
	}

	private function make_combo_zip( $combo_id ) {
		$products = (array) get_post_meta( $combo_id, '_cw_combo_products', true );
		if ( empty( $products ) ) {
			return false;
		}
		if ( ! class_exists( 'ZipArchive' ) ) {
			$this->logger->log( 'download_error', 'ZipArchive not available for combo zip', array( 'combo_id' => $combo_id ) );
			return false;
		}
		$uploads = wp_upload_dir();
		$dir     = trailingslashit( $uploads['basedir'] ) . 'cw-combos';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		$zip_path = trailingslashit( $dir ) . 'combo-' . $combo_id . '.zip';
		$zip      = new ZipArchive();
		if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
			return false;
		}
		foreach ( $products as $product_id ) {
			$file = get_post_meta( $product_id, '_cw_pdf_url', true );
			$path = $this->resolve_path( $file );
			if ( $path && file_exists( $path ) ) {
				$zip->addFile( $path, basename( $path ) );
			}
		}
		$zip->close();
		return $zip_path;
	}
}
