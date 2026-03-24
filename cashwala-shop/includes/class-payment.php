<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Payment {
	private $logger;
	private $download;
	private $affiliate;
	private $leads;

	public function __construct( CW_Logger $logger, CW_Download $download, CW_Affiliate $affiliate, CW_Leads $leads ) {
		$this->logger    = $logger;
		$this->download  = $download;
		$this->affiliate = $affiliate;
		$this->leads     = $leads;

		add_action( 'wp_ajax_cw_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_nopriv_cw_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_cw_verify_payment', array( $this, 'ajax_verify_payment' ) );
		add_action( 'wp_ajax_nopriv_cw_verify_payment', array( $this, 'ajax_verify_payment' ) );
		add_action( 'rest_api_init', array( $this, 'register_webhook_route' ) );
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
	}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cw_orders';
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
			affiliate_user_id BIGINT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (razorpay_order_id)
		) {$charset};" );
	}

	public function ajax_create_order() {
		check_ajax_referer( 'cw_front_nonce', 'nonce' );
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$name       = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
		$email      = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		$phone      = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );

		if ( ! $product_id || empty( $email ) || empty( $name ) || empty( $phone ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid order input.', 'cashwala-shop' ) ) );
		}
		$amount = $this->get_product_price( $product_id );
		if ( $amount <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product amount.', 'cashwala-shop' ) ) );
		}

		$settings = CW_Admin::get_settings();
		if ( empty( $settings['razorpay_key_id'] ) || empty( $settings['razorpay_key_secret'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Payment not configured.', 'cashwala-shop' ) ) );
		}

		$order_payload = array(
			'amount'          => (int) round( $amount * 100 ),
			'currency'        => 'INR',
			'receipt'         => 'cw_' . time() . '_' . wp_rand( 100, 999 ),
			'payment_capture' => 1,
		);
		$response      = wp_remote_post(
			'https://api.razorpay.com/v1/orders',
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => wp_json_encode( $order_payload ),
				'timeout' => 20,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $settings['razorpay_key_id'] . ':' . $settings['razorpay_key_secret'] ),
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			$this->logger->log( 'payment_failure', 'Razorpay create order request failed', array( 'error' => $response->get_error_message() ) );
			wp_send_json_error( array( 'message' => $this->logger->friendly_error_message() ) );
		}
		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( 200 > $code || $code > 299 || empty( $body['id'] ) ) {
			$this->logger->log( 'payment_failure', 'Razorpay order create unsuccessful', array( 'code' => $code, 'body' => wp_json_encode( $body ) ) );
			wp_send_json_error( array( 'message' => $this->logger->friendly_error_message() ) );
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
				'affiliate_user_id'  => CW_Affiliate::get_referrer_id(),
				'created_at'         => current_time( 'mysql', true ),
				'updated_at'         => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		wp_send_json_success(
			array(
				'order_id'      => $body['id'],
				'amount'        => (int) round( $amount * 100 ),
				'key'           => $settings['razorpay_key_id'],
				'currency'      => 'INR',
				'product_title' => get_the_title( $product_id ),
			)
		);
	}

	public function ajax_verify_payment() {
		check_ajax_referer( 'cw_front_nonce', 'nonce' );
		$order_id     = sanitize_text_field( wp_unslash( $_POST['razorpay_order_id'] ?? '' ) );
		$payment_id   = sanitize_text_field( wp_unslash( $_POST['razorpay_payment_id'] ?? '' ) );
		$signature    = sanitize_text_field( wp_unslash( $_POST['razorpay_signature'] ?? '' ) );
		$verified     = $this->verify_signature( $order_id, $payment_id, $signature );
		if ( ! $verified ) {
			$this->logger->log( 'payment_failure', 'Signature verification failed', compact( 'order_id', 'payment_id' ) );
			wp_send_json_error( array( 'message' => $this->logger->friendly_error_message() ) );
		}

		$result = $this->mark_order_paid( $order_id, $payment_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $this->logger->friendly_error_message() ) );
		}
		wp_send_json_success( $result );
	}

	private function verify_signature( $order_id, $payment_id, $signature ) {
		$settings = CW_Admin::get_settings();
		if ( empty( $settings['razorpay_key_secret'] ) ) {
			return false;
		}
		$payload = $order_id . '|' . $payment_id;
		$hash    = hash_hmac( 'sha256', $payload, $settings['razorpay_key_secret'] );
		return hash_equals( $hash, $signature );
	}

	public function register_webhook_route() {
		register_rest_route(
			'cashwala-shop/v1',
			'/razorpay-webhook',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle_webhook' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function handle_webhook( WP_REST_Request $request ) {
		$settings   = CW_Admin::get_settings();
		$signature  = sanitize_text_field( $request->get_header( 'x-razorpay-signature' ) );
		$raw_body   = $request->get_body();
		$event_data = json_decode( $raw_body, true );

		if ( empty( $settings['razorpay_webhook'] ) ) {
			$this->logger->log( 'webhook_issue', 'Webhook secret not configured' );
			return new WP_REST_Response( array( 'ok' => false ), 400 );
		}

		$expected = hash_hmac( 'sha256', $raw_body, $settings['razorpay_webhook'] );
		if ( ! hash_equals( $expected, $signature ) ) {
			$this->logger->log( 'webhook_issue', 'Webhook signature mismatch' );
			return new WP_REST_Response( array( 'ok' => false ), 401 );
		}

		if ( ( $event_data['event'] ?? '' ) === 'payment.captured' ) {
			$order_id   = sanitize_text_field( $event_data['payload']['payment']['entity']['order_id'] ?? '' );
			$payment_id = sanitize_text_field( $event_data['payload']['payment']['entity']['id'] ?? '' );
			$result     = $this->mark_order_paid( $order_id, $payment_id );
			if ( is_wp_error( $result ) ) {
				$this->logger->log( 'webhook_issue', 'Webhook order mark failed', array( 'order_id' => $order_id ) );
			}
		}
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	private function mark_order_paid( $razorpay_order_id, $payment_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' WHERE razorpay_order_id=%s', $razorpay_order_id ) );
		if ( ! $row ) {
			$this->logger->log( 'payment_failure', 'Order not found for payment marking', array( 'order_id' => $razorpay_order_id ) );
			return new WP_Error( 'not_found', 'Order missing' );
		}

		$wpdb->update(
			self::table_name(),
			array(
				'razorpay_payment_id' => $payment_id,
				'status'              => 'paid',
				'updated_at'          => current_time( 'mysql', true ),
			),
			array( 'id' => $row->id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);

		$download_link = $this->download->create_token( $row->id, $row->product_id, $row->customer_email );
		$this->send_download_email( $row->customer_email, $row->customer_name, $row->product_id, $download_link );
		$this->track_affiliate( $row );

		return array(
			'message'       => __( 'Payment verified', 'cashwala-shop' ),
			'download_link' => esc_url_raw( $download_link ),
		);
	}

	private function track_affiliate( $order_row ) {
		$affiliate_user_id = absint( $order_row->affiliate_user_id );
		if ( $affiliate_user_id <= 0 ) {
			return;
		}
		$percent    = CW_Affiliate::get_commission_percent( $order_row->product_id );
		$commission = ( floatval( $order_row->amount ) * $percent ) / 100;
		if ( $commission > 0 ) {
			$this->affiliate->add_commission( $affiliate_user_id, $order_row->id, $commission );
		}
	}

	private function send_download_email( $email, $name, $product_id, $link ) {
		$settings = CW_Admin::get_settings();
		$subject  = sprintf( __( 'Your download from %s', 'cashwala-shop' ), $settings['business_name'] );
		$message  = sprintf(
			"Hi %s,\n\nThanks for your purchase of %s.\nDownload link (valid for 24 hours):\n%s\n\nRegards,\n%s",
			sanitize_text_field( $name ),
			get_the_title( $product_id ),
			esc_url_raw( $link ),
			sanitize_text_field( $settings['business_name'] )
		);
		$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'From: ' . sanitize_text_field( $settings['business_name'] ) . ' <' . sanitize_email( $settings['from_email'] ) . '>' );
		$sent    = wp_mail( sanitize_email( $email ), $subject, $message, $headers );
		if ( ! $sent ) {
			$this->logger->log( 'email_failure', 'Download email failed', array( 'email' => $email ) );
		}
	}

	public function configure_smtp( $phpmailer ) {
		$settings = CW_Admin::get_settings();
		if ( empty( $settings['smtp_host'] ) ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['smtp_host'];
		$phpmailer->Port       = absint( $settings['smtp_port'] );
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = $settings['smtp_user'];
		$phpmailer->Password   = $settings['smtp_pass'];
		$phpmailer->SMTPSecure = $settings['smtp_secure'];
		$phpmailer->From       = $settings['from_email'];
		$phpmailer->FromName   = $settings['business_name'];
	}

	private function get_product_price( $product_id ) {
		if ( 'cw_combo' === get_post_type( $product_id ) ) {
			return floatval( get_post_meta( $product_id, '_cw_combo_price', true ) );
		}
		return floatval( get_post_meta( $product_id, '_cw_price', true ) );
	}
}
