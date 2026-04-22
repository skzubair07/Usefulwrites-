<?php
/**
 * Personalized "For You" feed module with user behavior tracking.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_ForYou_Module {

    public static function init() {
        add_action( 'wp_ajax_do_track_click', array( __CLASS__, 'ajax_track_click' ) );
    }

    public static function track_search( $user_id, $query ) {
        self::append_user_interest( $user_id, 'searches', sanitize_text_field( $query ) );
    }

    public static function track_click( $user_id, $masail_id ) {
        self::append_user_interest( $user_id, 'clicks', (string) absint( $masail_id ) );
    }

    public static function track_like( $user_id, $masail_id ) {
        self::append_user_interest( $user_id, 'likes', (string) absint( $masail_id ) );
    }

    public static function ajax_track_click() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( is_user_logged_in() ) {
            self::track_click( get_current_user_id(), absint( $_POST['masail_id'] ?? 0 ) );
        }
        wp_send_json_success();
    }

    public static function get_feed( $user_id, $limit = 10 ) {
        global $wpdb;

        $profile = get_user_meta( $user_id, 'do_user_activity', true );
        $profile = is_array( $profile ) ? $profile : array();

        $search_terms = implode( ' ', array_slice( $profile['searches'] ?? array(), -20 ) );
        $liked_ids    = array_map( 'absint', $profile['likes'] ?? array() );

        $words = preg_split( '/\s+/u', strtolower( $search_terms ) );
        $words = array_filter( array_unique( array_map( 'sanitize_text_field', $words ) ) );

        $where_parts = array();
        $params      = array();
        foreach ( array_slice( $words, 0, 8 ) as $word ) {
            if ( strlen( $word ) < 2 ) {
                continue;
            }
            $like = '%' . $wpdb->esc_like( $word ) . '%';
            $where_parts[] = '(question LIKE %s OR keywords LIKE %s OR category LIKE %s)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT * FROM ' . $wpdb->prefix . 'do_masail';
        if ( $where_parts ) {
            $sql .= ' WHERE ' . implode( ' OR ', $where_parts );
        }
        $sql .= ' ORDER BY created_at DESC LIMIT %d';
        $params[] = absint( $limit );

        $items = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );

        if ( ! empty( $liked_ids ) ) {
            foreach ( $items as &$item ) {
                if ( in_array( (int) $item['id'], $liked_ids, true ) ) {
                    $item['personal_score'] = 20;
                } else {
                    $item['personal_score'] = 0;
                }
            }
            unset( $item );

            usort(
                $items,
                static function ( $a, $b ) {
                    return $b['personal_score'] <=> $a['personal_score'];
                }
            );
        }

        return $items;
    }

    private static function append_user_interest( $user_id, $type, $value ) {
        if ( ! $user_id || '' === $value ) {
            return;
        }

        $profile = get_user_meta( $user_id, 'do_user_activity', true );
        $profile = is_array( $profile ) ? $profile : array(
            'searches' => array(),
            'clicks'   => array(),
            'likes'    => array(),
        );

        if ( empty( $profile[ $type ] ) || ! is_array( $profile[ $type ] ) ) {
            $profile[ $type ] = array();
        }

        $profile[ $type ][] = $value;
        $profile[ $type ]   = array_slice( array_values( array_unique( $profile[ $type ] ) ), -50 );

        update_user_meta( $user_id, 'do_user_activity', $profile );
    }
}

DO_ForYou_Module::init();
