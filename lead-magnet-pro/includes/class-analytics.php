<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMP_Analytics {
    const OPTION_KEY = 'lmp_analytics';

    public static function init() {
        add_action( 'wp_ajax_nopriv_lmp_track_impression', array( __CLASS__, 'ajax_track_impression' ) );
        add_action( 'wp_ajax_lmp_track_impression', array( __CLASS__, 'ajax_track_impression' ) );
    }

    public static function get_stats() {
        $defaults = array(
            'impressions' => 0,
            'conversions' => 0,
        );

        $stored = get_option( self::OPTION_KEY, array() );
        $stats  = wp_parse_args( $stored, $defaults );

        $stats['impressions'] = (int) $stats['impressions'];
        $stats['conversions'] = (int) $stats['conversions'];
        $stats['rate']        = $stats['impressions'] > 0 ? round( ( $stats['conversions'] / $stats['impressions'] ) * 100, 2 ) : 0;

        return $stats;
    }

    public static function increment( $metric ) {
        $stats = self::get_stats();

        if ( isset( $stats[ $metric ] ) ) {
            $stats[ $metric ]++;
            update_option(
                self::OPTION_KEY,
                array(
                    'impressions' => (int) $stats['impressions'],
                    'conversions' => (int) $stats['conversions'],
                )
            );
        }
    }

    public static function increment_impression() {
        self::increment( 'impressions' );
    }

    public static function increment_conversion() {
        self::increment( 'conversions' );
    }

    public static function ajax_track_impression() {
        check_ajax_referer( 'lmp_popup_nonce', 'nonce' );
        self::increment_impression();
        wp_send_json_success();
    }
}
