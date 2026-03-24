<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_EIB_DB {

    public static function table_name(): string {
        global $wpdb;

        return $wpdb->prefix . 'cw_leads';
    }

    public static function create_tables(): void {
        global $wpdb;

        $table_name      = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql             = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(190) NULL,
            email VARCHAR(190) NULL,
            phone VARCHAR(40) NULL,
            source VARCHAR(100) NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        add_option( 'cw_eib_db_version', CW_EIB_VERSION );
        self::maybe_create_log_file();
    }

    public static function maybe_upgrade(): void {
        $version = (string) get_option( 'cw_eib_db_version', '' );
        if ( version_compare( $version, CW_EIB_VERSION, '<' ) ) {
            self::create_tables();
            update_option( 'cw_eib_db_version', CW_EIB_VERSION );
        }
    }

    private static function maybe_create_log_file(): void {
        $log_dir  = CW_EIB_PATH . 'logs';
        $log_file = CW_EIB_Logger::log_file_path();

        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        if ( ! file_exists( $log_file ) ) {
            file_put_contents( $log_file, "" );
        }
    }

    public static function insert_lead( array $data ) {
        global $wpdb;

        $inserted = $wpdb->insert(
            self::table_name(),
            array(
                'name'       => $data['name'] ?? '',
                'email'      => $data['email'] ?? '',
                'phone'      => $data['phone'] ?? '',
                'source'     => $data['source'] ?? 'popup',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            CW_EIB_Logger::log( 'DB insert failed for lead submission.' );

            return false;
        }

        return (int) $wpdb->insert_id;
    }
}
