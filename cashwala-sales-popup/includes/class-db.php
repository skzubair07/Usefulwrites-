<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Sales_Popup_DB {
    public static function activate() {
        global $wpdb;

        $table_name      = $wpdb->prefix . 'cw_sales_popup';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(100) NOT NULL,
            city VARCHAR(120) NOT NULL,
            product VARCHAR(180) NOT NULL,
            link VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        if ( ! get_option( 'cw_sales_popup_settings' ) ) {
            add_option( 'cw_sales_popup_settings', self::default_settings() );
        }

        if ( ! get_option( 'cw_sales_popup_analytics' ) ) {
            add_option(
                'cw_sales_popup_analytics',
                array(
                    'impressions' => 0,
                    'clicks'      => 0,
                )
            );
        }

        if ( ! get_option( 'cw_sales_popup_logs' ) ) {
            add_option( 'cw_sales_popup_logs', array() );
        }
    }

    public static function default_settings() {
        return array(
            'enabled'               => 1,
            'data_mode'             => 'hybrid',
            'initial_delay'         => 3,
            'interval'              => 7,
            'random_variation'      => 2,
            'randomized_timing'     => 1,
            'loop_enabled'          => 1,
            'shuffle_entries'       => 1,
            'position'              => 'bottom-right',
            'display_mode'          => 'single',
            'max_popups'            => 1,
            'show_duration'         => 5,
            'cta_enabled'           => 1,
            'cta_text'              => 'View Product',
            'sound_enabled'         => 0,
            'sound_url'             => '',
            'background_color'      => 'rgba(20, 24, 39, 0.78)',
            'text_color'            => '#ffffff',
            'border_radius'         => 14,
            'shadow'                => '0 8px 24px rgba(0,0,0,0.2)',
            'avatar_url'            => '',
            'template'              => 'template-1',
            'enable_mobile'         => 1,
        );
    }

    public function table_name() {
        global $wpdb;

        return $wpdb->prefix . 'cw_sales_popup';
    }

    public function get_entries() {
        global $wpdb;

        $table = $this->table_name();
        $sql   = "SELECT * FROM {$table} ORDER BY id DESC"; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
    }

    public function get_entry( $id ) {
        global $wpdb;

        $table = $this->table_name();
        $sql   = $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id );

        return $wpdb->get_row( $sql, ARRAY_A );
    }

    public function insert_entry( $data ) {
        global $wpdb;

        $table = $this->table_name();

        return $wpdb->insert(
            $table,
            array(
                'name'       => sanitize_text_field( $data['name'] ?? '' ),
                'city'       => sanitize_text_field( $data['city'] ?? '' ),
                'product'    => sanitize_text_field( $data['product'] ?? '' ),
                'link'       => esc_url_raw( $data['link'] ?? '' ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function update_entry( $id, $data ) {
        global $wpdb;

        $table = $this->table_name();

        return $wpdb->update(
            $table,
            array(
                'name'    => sanitize_text_field( $data['name'] ?? '' ),
                'city'    => sanitize_text_field( $data['city'] ?? '' ),
                'product' => sanitize_text_field( $data['product'] ?? '' ),
                'link'    => esc_url_raw( $data['link'] ?? '' ),
            ),
            array( 'id' => absint( $id ) ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );
    }

    public function delete_entry( $id ) {
        global $wpdb;

        $table = $this->table_name();

        return $wpdb->delete( $table, array( 'id' => absint( $id ) ), array( '%d' ) );
    }
}
