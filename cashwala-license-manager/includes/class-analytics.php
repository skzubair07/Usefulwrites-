<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLMP_Analytics {
    public static function dashboard_stats() {
        return CWLMP_Licenses::get_status_counts();
    }

    public static function validation_stats( $days = 7 ) {
        global $wpdb;
        $days      = max( 1, absint( $days ) );
        $table     = CWLMP_Licenses::logs_table_name();
        $threshold = gmdate( 'Y-m-d H:i:s', time() - ( DAY_IN_SECONDS * $days ) );

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT result, COUNT(*) AS total FROM {$table} WHERE created_at >= %s GROUP BY result",
                $threshold
            ),
            ARRAY_A
        );

        $stats = array(
            'valid'   => 0,
            'invalid' => 0,
            'expired' => 0,
            'revoked' => 0,
        );

        foreach ( $rows as $row ) {
            $result = sanitize_key( $row['result'] );
            if ( isset( $stats[ $result ] ) ) {
                $stats[ $result ] = (int) $row['total'];
            }
        }

        return $stats;
    }
}
