<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWQSP_Payment {
	/** @var CWQSP_Logger */
	private $logger;

	/** @var CWQSP_Download */
	private $download;

	public function __construct( CWQSP_Logger $logger, CWQSP_Download $download ) {
		$this->logger   = $logger;
		$this->download = $download;

		add_action( 'wp_ajax_cwqsp_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_nopriv_cwqsp_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_cwqsp_verify_payment', array( $this, 'ajax_verify_payment' ) );
		add_action( 'wp_ajax_nopriv_cwqsp_verify_payment', array( $this, 'ajax_verify_payment' ) );
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cwqsp_orders';
	}

	public static function create_table() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		dbDelta( "CREATE TABLE " . self::table_name() . " (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			product_id BIGINT UNSIGNED NOT NULL,
			customer_name VARCHAR(150) NOT NULL,
			customer_email VARCHAR(190) NOT NULL,
			customer_phone VARCHAR(30) NOT NULL,
			amount DECIMAL(12,2) NOT NULL,
			currency VARCHAR(10) NOT NULL DEFAULT 'INR',
			razorpay_order_id VARCHAR(80) NOT NULL,
			razorpay_payment_id VARCHAR(80) DEFAULT '',
			status VARCHAR(20) NOT NULL DEFAULT 'created',
			download_link TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (razorpay_order_id),
			KEY status (status)
		) {$charset};" );
	}

	public function ajax_create_order() {
		check_ajax_referer( 'cwqsp_nonce', 'nonce' );
		$product_id = absint( wp_unslash( $_POST['product_id'] ?? 0 ) );
		$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

		if ( ! $product_id || empty( $name ) || empty( $email ) || empty( $phone ) ) {
			wp_send_json_error( array( 'message' => 'Please fill all required fields.' ) );
		}

		$amount = (float) get_post_meta( $product_id, '_cwqsp_price', true );
		if ( $amount <= 0 ) {
			wp_send_json_error( array( 'message' => 'Invalid product or price.' ) );
		}

		$settings = CWQSP_Admin::get_settings();
		if ( empty( $settings['razorpay_key_id'] ) || empty( $settings['razorpay_key_secret'] ) ) {
			wp_send_json_error( array( 'message' => 'Razorpay is not configured by admin.' ) );
		}

		$response = wp_remote_post(
			'https://api.razorpay.com/v1/orders',
			array(
				'timeout' => 25,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $settings['razorpay_key_id'] . ':' . $settings['razorpay_key_secret'] ),
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'amount'          => (int) round( $amount * 100 ),
						'currency'        => 'INR',
						'receipt'         => 'cwqsp_' . time() . '_' . wp_rand( 100, 999 ),
						'payment_capture' => 1,
					)
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->log( 'payment_error', 'Create order failed', array( 'error' => $response->get_error_message() ) );
			wp_send_json_error( array( 'message' => 'Unable to create order. Please try again.' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['id'] ) ) {
			$this->logger->log( 'payment_error', 'Invalid create order response', array( 'response' => $body ) );
			wp_send_json_error( array( 'message' => 'Unable to create payment order.' ) );
		}

		global $wpdb;
		$wpdb->insert(
			self::table_name(),
			array(
				'product_id'         => $product_id,
				'customer_name'      => $name,
				'customer_email'     => $email,
				'customer_phone'     => $phone,
				'amount'             => $amount,
				'currency'           => 'INR',
				'razorpay_order_id'  => sanitize_text_field( $body['id'] ),
				'razorpay_payment_id'=> '',
				'status'             => 'created',
				'download_link'      => '',
				'created_at'         => current_time( 'mysql', true ),
				'updated_at'         => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		wp_send_json_success(
			array(
				'order_id' => $body['id'],
				'amount'   => (int) round( $amount * 100 ),
				'currency' => 'INR',
				'key'      => $settings['razorpay_key_id'],
				'name'     => get_bloginfo( 'name' ),
				'desc'     => get_the_title( $product_id ),
			)
		);
	}

	public function ajax_verify_payment() {
		check_ajax_referer( 'cwqsp_nonce', 'nonce' );
		$order_id   = sanitize_text_field( wp_unslash( $_POST['razorpay_order_id'] ?? '' ) );
		$payment_id = sanitize_text_field( wp_unslash( $_POST['razorpay_payment_id'] ?? '' ) );
		$signature  = sanitize_text_field( wp_unslash( $_POST['razorpay_signature'] ?? '' ) );

		if ( ! $this->verify_signature( $order_id, $payment_id, $signature ) ) {
			$this->logger->log( 'payment_error', 'Signature mismatch', compact( 'order_id', 'payment_id' ) );
			wp_send_json_error( array( 'message' => 'Payment verification failed.' ) );
		}

		global $wpdb;
		$order = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE razorpay_order_id=%s', $order_id ) );
		if ( ! $order ) {
			wp_send_json_error( array( 'message' => 'Order not found.' ) );
		}

		$download_link = $this->download->create_token( $order->id, $order->product_id, $order->customer_email );
		$wpdb->update(
			self::table_name(),
			array(
				'razorpay_payment_id' => $payment_id,
				'status'              => 'paid',
				'download_link'       => $download_link,
				'updated_at'          => current_time( 'mysql', true ),
			),
			array( 'id' => $order->id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		$this->send_purchase_email( $order, $download_link );
		$thank_you_url = add_query_arg(
			array(
				'cwqsp_thankyou' => 1,
				'order'          => rawurlencode( $order_id ),
			),
			home_url( '/' )
		);

		$redirect_mode = CWQSP_Admin::get_settings()['redirect_mode'];
		$product_redir = get_post_meta( $order->product_id, '_cwqsp_redirect_url', true );

		wp_send_json_success(
			array(
				'message'       => 'Payment successful.',
				'download_link' => $download_link,
				'thank_you_url' => $thank_you_url,
				'redirect_url'  => ( 'product_url' === $redirect_mode && ! empty( $product_redir ) ) ? esc_url_raw( $product_redir ) : $thank_you_url,
			)
		);
	}

	private function verify_signature( $order_id, $payment_id, $signature ) {
		$secret = CWQSP_Admin::get_settings()['razorpay_key_secret'];
		if ( empty( $secret ) || empty( $order_id ) || empty( $payment_id ) || empty( $signature ) ) {
			return false;
		}
		$hash = hash_hmac( 'sha256', $order_id . '|' . $payment_id, $secret );
		return hash_equals( $hash, $signature );
	}

	private function send_purchase_email( $order, $download_link ) {
		$settings = CWQSP_Admin::get_settings();
		$subject  = sprintf( 'Your download for %s', get_the_title( $order->product_id ) );
		$message  = "Hi {$order->customer_name},\n\nYour payment is successful.\n\nProduct: " . get_the_title( $order->product_id ) . "\nDownload: {$download_link}\n\nThank you.";
		$headers  = array( 'Content-Type: text/plain; charset=UTF-8', 'From: ' . $settings['smtp_from_name'] . ' <' . $settings['smtp_from_email'] . '>' );
		$sent     = wp_mail( $order->customer_email, $subject, $message, $headers );
		if ( ! $sent ) {
			$this->logger->log( 'email_error', 'Order email failed', array( 'order_id' => $order->id ) );
		}
	}

	public function register_routes() {
		register_rest_route(
			'cwqsp/v1',
			'/webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function webhook( WP_REST_Request $request ) {
		$settings  = CWQSP_Admin::get_settings();
		$raw       = $request->get_body();
		$signature = sanitize_text_field( $request->get_header( 'x-razorpay-signature' ) );
		$expected  = hash_hmac( 'sha256', $raw, $settings['razorpay_webhook'] );

		if ( empty( $settings['razorpay_webhook'] ) || ! hash_equals( $expected, $signature ) ) {
			$this->logger->log( 'webhook_error', 'Webhook signature mismatch' );
			return new WP_REST_Response( array( 'ok' => false ), 401 );
		}

		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}
}
