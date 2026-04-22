<?php
/**
 * Trending module with engagement and freshness score.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Trending_Module {

    public static function get_trending( $limit = 10 ) {
        global $wpdb;
        $settings = get_option( 'do_trending_settings', array( 'enabled' => 1 ) );

        if ( empty( $settings['enabled'] ) ) {
            return array();
        }

        $items = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT *, ((likes * 3) + (shares * 2) + views - (TIMESTAMPDIFF(HOUR, created_at, NOW()) / 2)) as trend_score
                 FROM ' . $wpdb->prefix . 'do_masail
                 ORDER BY trend_score DESC
                 LIMIT %d',
                absint( $limit )
            ),
            ARRAY_A
        );

        return self::apply_admin_control( $items );
    }

    /**
     * Apply pin + manual position overrides by admin.
     */
    private static function apply_admin_control( $items ) {
        $manual = get_option( 'do_trending_manual', array() );
        if ( empty( $manual ) || ! is_array( $manual ) ) {
            return $items;
        }

        foreach ( $items as &$item ) {
            $id = (string) $item['id'];
            $item['pinned']   = ! empty( $manual[ $id ]['pinned'] ) ? 1 : 0;
            $item['position'] = isset( $manual[ $id ]['position'] ) ? (int) $manual[ $id ]['position'] : 9999;
        }
        unset( $item );

        usort(
            $items,
            static function ( $a, $b ) {
                if ( $a['pinned'] !== $b['pinned'] ) {
                    return $b['pinned'] <=> $a['pinned'];
                }
                if ( $a['position'] !== $b['position'] ) {
                    return $a['position'] <=> $b['position'];
                }
                return $b['trend_score'] <=> $a['trend_score'];
            }
        );

        return $items;
    }
}
