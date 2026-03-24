<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Leads {
	private $logger;

	public function __construct( CW_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'wp_ajax_cw_save_lead', array( $this, 'ajax_save_lead' ) );
		add_action( 'wp_ajax_nopriv_cw_save_lead', array( $this, 'ajax_save_lead' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cw_leads';
	}

	public static function create_table() {
		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(150) NOT NULL,
			email VARCHAR(190) NOT NULL,
			phone VARCHAR(30) NOT NULL,
			product_id BIGINT UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id)
		) {$charset};" );
	}

	public static function insert_lead( $name, $email, $phone, $product_id ) {
		global $wpdb;
		return $wpdb->insert(
			self::table_name(),
			array(
				'name'       => sanitize_text_field( $name ),
				'email'      => sanitize_email( $email ),
				'phone'      => sanitize_text_field( $phone ),
				'product_id' => absint( $product_id ),
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%d', '%s' )
		);
	}

	public static function get_leads( $limit = 100 ) {
		global $wpdb;
		$table = self::table_name();
		if ( 0 === absint( $limit ) ) {
			return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", absint( $limit ) ) );
	}

	public function ajax_save_lead() {
		check_ajax_referer( 'cw_front_nonce', 'nonce' );
		$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		$phone      = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		if ( empty( $name ) || empty( $email ) || empty( $phone ) || empty( $product_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing lead fields.', 'cashwala-shop' ) ) );
		}

		$inserted = self::insert_lead( $name, $email, $phone, $product_id );
		if ( false === $inserted ) {
			$this->logger->log( 'lead_error', 'Lead insert failed', compact( 'email', 'product_id' ) );
			wp_send_json_error( array( 'message' => $this->logger->friendly_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Lead saved.', 'cashwala-shop' ) ) );
	}
}
