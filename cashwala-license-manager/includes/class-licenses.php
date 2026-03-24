<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLMP_Licenses {
    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'cwlmp_licenses';
    }

    public static function logs_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'cwlmp_license_logs';
    }

    public static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $license_table   = self::table_name();
        $logs_table      = self::logs_table_name();

        $sql_licenses = "CREATE TABLE {$license_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            key_hash CHAR(64) NOT NULL,
            key_mask VARCHAR(40) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            expiry_date DATETIME NULL,
            domain_limit INT UNSIGNED NOT NULL DEFAULT 1,
            bound_domain VARCHAR(191) NULL,
            assigned_email VARCHAR(191) NULL,
            assigned_user_id BIGINT UNSIGNED NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY key_hash_unique (key_hash),
            KEY status_idx (status),
            KEY expiry_idx (expiry_date),
            KEY email_idx (assigned_email),
            KEY user_idx (assigned_user_id)
        ) {$charset_collate};";

        $sql_logs = "CREATE TABLE {$logs_table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            license_id BIGINT UNSIGNED NULL,
            key_hash CHAR(64) NOT NULL,
            domain VARCHAR(191) NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            result VARCHAR(20) NOT NULL,
            message VARCHAR(255) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY key_hash_idx (key_hash),
            KEY license_idx (license_id),
            KEY result_idx (result),
            KEY created_idx (created_at)
        ) {$charset_collate};";

        dbDelta( $sql_licenses );
        dbDelta( $sql_logs );
    }

    public static function create_license( $plain_key, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'status'           => 'active',
            'expiry_date'      => null,
            'domain_limit'     => 1,
            'assigned_email'   => null,
            'assigned_user_id' => null,
            'notes'            => null,
        );

        $args      = wp_parse_args( $args, $defaults );
        $key_hash  = CWLMP_Security::hash_license_key( $plain_key );
        $key_mask  = CWLMP_Security::mask_key( $plain_key );
        $now       = current_time( 'mysql', true );
        $table     = self::table_name();
        $inserted  = $wpdb->insert(
            $table,
            array(
                'key_hash'          => $key_hash,
                'key_mask'          => $key_mask,
                'status'            => self::normalize_status( $args['status'] ),
                'expiry_date'       => self::sanitize_expiry_date( $args['expiry_date'] ),
                'domain_limit'      => max( 1, absint( $args['domain_limit'] ) ),
                'assigned_email'    => ! empty( $args['assigned_email'] ) ? sanitize_email( $args['assigned_email'] ) : null,
                'assigned_user_id'  => ! empty( $args['assigned_user_id'] ) ? absint( $args['assigned_user_id'] ) : null,
                'notes'             => ! empty( $args['notes'] ) ? sanitize_textarea_field( $args['notes'] ) : null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return new WP_Error( 'cwlmp_create_failed', __( 'Failed to create license key.', 'cashwala-license-manager' ) );
        }

        return (int) $wpdb->insert_id;
    }

    public static function bulk_create_licenses( $count, $args = array() ) {
        $count         = min( 500, max( 1, absint( $count ) ) );
        $created       = array();
        $attempt_guard = 0;

        while ( count( $created ) < $count && $attempt_guard < $count * 4 ) {
            $attempt_guard++;
            $key = CWLMP_Security::generate_license_key();
            $id  = self::create_license( $key, $args );

            if ( is_wp_error( $id ) ) {
                continue;
            }

            $created[] = $key;
        }

        return $created;
    }

    public static function get_license_by_key( $plain_key ) {
        global $wpdb;

        $hash  = CWLMP_Security::hash_license_key( $plain_key );
        $table = self::table_name();

        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE key_hash = %s LIMIT 1", $hash ), ARRAY_A );
    }

    public static function update_license( $id, $data ) {
        global $wpdb;

        $allowed = array( 'status', 'expiry_date', 'domain_limit', 'assigned_email', 'assigned_user_id', 'notes', 'bound_domain' );
        $update  = array();
        $format  = array();

        foreach ( $allowed as $field ) {
            if ( ! array_key_exists( $field, $data ) ) {
                continue;
            }

            switch ( $field ) {
                case 'status':
                    $update['status'] = self::normalize_status( $data['status'] );
                    $format[]         = '%s';
                    break;
                case 'expiry_date':
                    $update['expiry_date'] = self::sanitize_expiry_date( $data['expiry_date'] );
                    $format[]              = '%s';
                    break;
                case 'domain_limit':
                    $update['domain_limit'] = max( 1, absint( $data['domain_limit'] ) );
                    $format[]               = '%d';
                    break;
                case 'assigned_email':
                    $update['assigned_email'] = ! empty( $data['assigned_email'] ) ? sanitize_email( $data['assigned_email'] ) : null;
                    $format[]                 = '%s';
                    break;
                case 'assigned_user_id':
                    $update['assigned_user_id'] = ! empty( $data['assigned_user_id'] ) ? absint( $data['assigned_user_id'] ) : null;
                    $format[]                   = '%d';
                    break;
                case 'notes':
                    $update['notes'] = sanitize_textarea_field( $data['notes'] );
                    $format[]        = '%s';
                    break;
                case 'bound_domain':
                    $update['bound_domain'] = ! empty( $data['bound_domain'] ) ? CWLMP_Security::sanitize_domain( $data['bound_domain'] ) : null;
                    $format[]               = '%s';
                    break;
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        $update['updated_at'] = current_time( 'mysql', true );
        $format[]             = '%s';

        return (bool) $wpdb->update( self::table_name(), $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    public static function search_licenses( $args = array() ) {
        global $wpdb;
        $defaults = array(
            'status'   => '',
            'search'   => '',
            'paged'    => 1,
            'per_page' => 20,
        );
        $args     = wp_parse_args( $args, $defaults );
        $table    = self::table_name();
        $where    = array( '1=1' );
        $params   = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $params[] = self::normalize_status( $args['status'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $term    = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where[] = '(assigned_email LIKE %s OR key_mask LIKE %s OR bound_domain LIKE %s)';
            $params  = array_merge( $params, array( $term, $term, $term ) );
        }

        $where_sql = implode( ' AND ', $where );
        $offset    = ( max( 1, absint( $args['paged'] ) ) - 1 ) * max( 1, absint( $args['per_page'] ) );
        $limit     = max( 1, absint( $args['per_page'] ) );

        $total_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
        $data_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";

        $total = (int) $wpdb->get_var( $wpdb->prepare( $total_sql, $params ) );

        $data_params   = $params;
        $data_params[] = $limit;
        $data_params[] = $offset;

        $rows = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ), ARRAY_A );

        return array(
            'total' => $total,
            'rows'  => $rows,
        );
    }

    public static function get_status_counts() {
        global $wpdb;
        $table  = self::table_name();
        $result = $wpdb->get_results( "SELECT status, COUNT(*) as total FROM {$table} GROUP BY status", ARRAY_A );
        $counts = array(
            'total'   => 0,
            'active'  => 0,
            'expired' => 0,
            'revoked' => 0,
        );

        foreach ( $result as $row ) {
            $status                    = self::normalize_status( $row['status'] );
            $counts[ $status ]         = (int) $row['total'];
            $counts['total']          += (int) $row['total'];
        }

        return $counts;
    }

    public static function log_attempt( $data ) {
        global $wpdb;

        $wpdb->insert(
            self::logs_table_name(),
            array(
                'license_id'  => ! empty( $data['license_id'] ) ? absint( $data['license_id'] ) : null,
                'key_hash'    => ! empty( $data['key_hash'] ) ? sanitize_text_field( $data['key_hash'] ) : '',
                'domain'      => ! empty( $data['domain'] ) ? CWLMP_Security::sanitize_domain( $data['domain'] ) : null,
                'ip_address'  => ! empty( $data['ip_address'] ) ? sanitize_text_field( $data['ip_address'] ) : null,
                'user_agent'  => ! empty( $data['user_agent'] ) ? sanitize_textarea_field( $data['user_agent'] ) : null,
                'result'      => ! empty( $data['result'] ) ? sanitize_text_field( $data['result'] ) : 'invalid',
                'message'     => ! empty( $data['message'] ) ? sanitize_text_field( $data['message'] ) : '',
                'created_at'  => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public static function get_recent_logs( $limit = 50 ) {
        global $wpdb;
        $limit = min( 200, max( 1, absint( $limit ) ) );
        $table = self::logs_table_name();

        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
    }

    public static function sync_expired_licenses() {
        global $wpdb;
        $now   = current_time( 'mysql', true );
        $table = self::table_name();

        $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status = 'expired', updated_at = %s WHERE status = 'active' AND expiry_date IS NOT NULL AND expiry_date < %s", $now, $now ) );
    }

    public static function normalize_status( $status ) {
        $status  = sanitize_text_field( $status );
        $allowed = array( 'active', 'expired', 'revoked' );

        if ( ! in_array( $status, $allowed, true ) ) {
            return 'active';
        }

        return $status;
    }

    private static function sanitize_expiry_date( $date ) {
        if ( empty( $date ) ) {
            return null;
        }

        $timestamp = strtotime( sanitize_text_field( $date ) );
        if ( ! $timestamp ) {
            return null;
        }

        return gmdate( 'Y-m-d H:i:s', $timestamp );
    }
}
