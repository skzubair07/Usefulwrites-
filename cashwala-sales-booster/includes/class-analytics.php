<?php
/**
 * Analytics tracking.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CashWala_SB_Analytics {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_cw_sb_track', array( __CLASS__, 'track_event' ) );
		add_action( 'wp_ajax_nopriv_cw_sb_track', array( __CLASS__, 'track_event' ) );
	}

	/**
	 * Track impression/click via Ajax.
	 *
	 * @return void
	 */
	public static function track_event() {
		check_ajax_referer( 'cw_sb_track_event', 'nonce' );

		$type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		if ( ! in_array( $type, array( 'impression', 'click' ), true ) ) {
			wp_send_json_error( array( 'message' => 'Invalid event type.' ), 400 );
		}

		$data = wp_parse_args( get_option( 'cw_sb_analytics', array() ), array( 'impressions' => 0, 'clicks' => 0 ) );

		if ( 'impression' === $type ) {
			$data['impressions']++;
		} else {
			$data['clicks']++;
		}

		update_option( 'cw_sb_analytics', $data, false );

		wp_send_json_success( $data );
	}
}
