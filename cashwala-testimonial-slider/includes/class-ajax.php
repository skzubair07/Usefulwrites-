<?php
/**
 * AJAX handlers.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWTS_Ajax {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_cwts_fetch_testimonials', array( $this, 'fetch_testimonials' ) );
		add_action( 'wp_ajax_nopriv_cwts_fetch_testimonials', array( $this, 'fetch_testimonials' ) );
	}

	/**
	 * Securely fetch testimonials.
	 *
	 * @return void
	 */
	public function fetch_testimonials() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'cwts_nonce' ) ) {
			wp_send_json_error(
				array( 'message' => esc_html__( 'Invalid request.', 'cashwala-testimonial-slider' ) ),
				403
			);
		}

		$limit = isset( $_POST['limit'] ) ? absint( wp_unslash( $_POST['limit'] ) ) : 10;
		$ids   = isset( $_POST['ids'] ) ? sanitize_text_field( wp_unslash( $_POST['ids'] ) ) : '';
		$ids   = ! empty( $ids ) ? array_filter( array_map( 'absint', explode( ',', $ids ) ) ) : array();

		$items = CWTS_DB::get_testimonials(
			array(
				'limit' => $limit,
				'ids'   => $ids,
			)
		);

		$payload = array();
		foreach ( $items as $item ) {
			$payload[] = array(
				'id'      => (int) $item['id'],
				'name'    => esc_html( $item['name'] ),
				'photo'   => esc_url( $item['photo'] ),
				'text'    => esc_html( $item['text'] ),
				'rating'  => (int) $item['rating'],
				'company' => esc_html( $item['company'] ),
			);
		}

		wp_send_json_success(
			array(
				'items' => $payload,
			)
		);
	}
}
