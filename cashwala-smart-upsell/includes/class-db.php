<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Upsell_DB {
    const TABLE = 'cw_upsells';

    /** @var CW_Upsell_Logger */
    private $logger;

    public function __construct( CW_Upsell_Logger $logger ) {
        $this->logger = $logger;
    }

    public static function activate() {
        global $wpdb;

        $table_name      = $wpdb->prefix . self::TABLE;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            image VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL DEFAULT 0,
            discount_price DECIMAL(10,2) NOT NULL DEFAULT 0,
            link VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );

        if ( ! get_option( 'cw_upsell_settings' ) ) {
            add_option( 'cw_upsell_settings', self::default_settings() );
        }

        if ( ! get_option( 'cw_upsell_analytics' ) ) {
            add_option(
                'cw_upsell_analytics',
                array(
                    'views'   => 0,
                    'accepts' => 0,
                    'skips'   => 0,
                )
            );
        }
    }

    public static function default_settings() {
        return array(
            'enabled'              => 1,
            'trigger_event'        => 'buy_now',
            'display_type'         => 'popup',
            'animation_style'      => 'fade',
            'delay'                => 1000,
            'page_targeting'       => '',
            'device_targeting'     => 'all',
            'layout'               => 'card',
            'background_color'     => '#ffffff',
            'text_color'           => '#1f2937',
            'button_style'         => 'rounded',
            'behavior'             => 'once',
            'close_button'         => 1,
            'show_repeat_visitors' => 1,
        );
    }

    public function table_name() {
        global $wpdb;
        return $wpdb->prefix . self::TABLE;
    }

    public function get_offers() {
        global $wpdb;
        $table = $this->table_name();

        $results = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

        return is_array( $results ) ? $results : array();
    }

    public function get_offer( $id ) {
        global $wpdb;
        $table = $this->table_name();

        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), ARRAY_A );
    }

    public function save_offer( $data ) {
        global $wpdb;
        $table = $this->table_name();

        $prepared = array(
            'title'          => sanitize_text_field( $data['title'] ?? '' ),
            'description'    => sanitize_textarea_field( $data['description'] ?? '' ),
            'image'          => esc_url_raw( $data['image'] ?? '' ),
            'price'          => floatval( $data['price'] ?? 0 ),
            'discount_price' => floatval( $data['discount_price'] ?? 0 ),
            'link'           => esc_url_raw( $data['link'] ?? '' ),
            'created_at'     => current_time( 'mysql' ),
        );

        if ( ! empty( $data['id'] ) ) {
            $updated = $wpdb->update( $table, $prepared, array( 'id' => absint( $data['id'] ) ) );
            return false !== $updated ? absint( $data['id'] ) : 0;
        }

        $inserted = $wpdb->insert( $table, $prepared );
        return $inserted ? (int) $wpdb->insert_id : 0;
    }

    public function delete_offer( $id ) {
        global $wpdb;
        $table = $this->table_name();
        return (bool) $wpdb->delete( $table, array( 'id' => absint( $id ) ) );
    }
}
