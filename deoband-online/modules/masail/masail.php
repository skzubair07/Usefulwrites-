<?php
/**
 * Masail module: CRUD for Q&A records.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Masail_Module {

    public static function init() {
        add_action( 'wp_ajax_do_save_masail', array( __CLASS__, 'save_masail' ) );
        add_action( 'wp_ajax_do_delete_masail', array( __CLASS__, 'delete_masail' ) );
    }

    public static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'do_masail';
    }

    /**
     * Admin save callback for Masail.
     */
    public static function save_masail() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }

        global $wpdb;
        $id = absint( $_POST['id'] ?? 0 );

        $data = array(
            'question'   => sanitize_textarea_field( wp_unslash( $_POST['question'] ?? '' ) ),
            'answer'     => wp_kses_post( wp_unslash( $_POST['answer'] ?? '' ) ),
            'source_url' => esc_url_raw( wp_unslash( $_POST['source_url'] ?? '' ) ),
            'keywords'   => sanitize_text_field( wp_unslash( $_POST['keywords'] ?? '' ) ),
            'category'   => sanitize_text_field( wp_unslash( $_POST['category'] ?? '' ) ),
            'updated_at' => current_time( 'mysql' ),
        );

        if ( $id > 0 ) {
            $wpdb->update( self::table_name(), $data, array( 'id' => $id ) );
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( self::table_name(), $data );
        }

        wp_send_json_success( array( 'message' => 'Saved successfully' ) );
    }

    /**
     * Admin delete callback.
     */
    public static function delete_masail() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ), 403 );
        }

        global $wpdb;
        $id = absint( $_POST['id'] ?? 0 );
        if ( $id > 0 ) {
            $wpdb->delete( self::table_name(), array( 'id' => $id ) );
        }
        wp_send_json_success();
    }

    /**
     * Fetch paginated records for admin/frontend usage.
     */
    public static function get_masail( $args = array() ) {
        global $wpdb;
        $defaults = array(
            'limit'  => 20,
            'offset' => 0,
        );
        $args = wp_parse_args( $args, $defaults );

        $limit  = absint( $args['limit'] );
        $offset = absint( $args['offset'] );

        return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table_name() . ' ORDER BY created_at DESC LIMIT %d OFFSET %d', $limit, $offset ), ARRAY_A );
    }
}

/**
 * Render admin table/list for Masail manager.
 */
function do_render_masail_manager() {
    global $wpdb;
    $items = $wpdb->get_results( 'SELECT * FROM ' . DO_Masail_Module::table_name() . ' ORDER BY id DESC LIMIT 200', ARRAY_A );
    include DO_PLUGIN_DIR . 'templates/admin-masail-manager.php';
}

DO_Masail_Module::init();
