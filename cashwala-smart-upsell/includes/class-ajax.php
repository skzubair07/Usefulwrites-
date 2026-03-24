<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Upsell_Ajax {
    /** @var CW_Upsell_Logger */
    private $logger;

    public function __construct( CW_Upsell_Logger $logger ) {
        $this->logger = $logger;

        add_action( 'wp_ajax_cw_upsell_track', array( $this, 'track' ) );
        add_action( 'wp_ajax_nopriv_cw_upsell_track', array( $this, 'track' ) );
    }

    public function track() {
        check_ajax_referer( 'cw_upsell_nonce', 'nonce' );

        $type      = sanitize_key( $_POST['type'] ?? '' );
        $analytics = get_option( 'cw_upsell_analytics', array( 'views' => 0, 'accepts' => 0, 'skips' => 0 ) );

        if ( 'view' === $type ) {
            $analytics['views'] = absint( $analytics['views'] ?? 0 ) + 1;
        }

        if ( 'accept' === $type ) {
            $analytics['accepts'] = absint( $analytics['accepts'] ?? 0 ) + 1;
        }

        if ( 'skip' === $type ) {
            $analytics['skips'] = absint( $analytics['skips'] ?? 0 ) + 1;
        }

        update_option( 'cw_upsell_analytics', $analytics, false );
        $this->logger->log( 'Upsell interaction tracked.', array( 'type' => $type ) );

        wp_send_json_success(
            array(
                'message'   => 'Tracked',
                'analytics' => $analytics,
            )
        );
    }
}
