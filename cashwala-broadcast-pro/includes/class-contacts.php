<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Contacts {

    public static function init() {
        add_action( 'admin_post_cwbp_export_contacts', array( __CLASS__, 'export_csv' ) );
        add_action( 'admin_post_cwbp_delete_contacts', array( __CLASS__, 'bulk_delete' ) );
    }

    public static function add_contact( $name, $email, $phone = '', $source = 'manual', $status = 'lead' ) {
        global $wpdb;

        $name   = sanitize_text_field( $name );
        $email  = sanitize_email( $email );
        $phone  = sanitize_text_field( $phone );
        $source = sanitize_key( $source );
        $status = in_array( $status, array( 'lead', 'buyer' ), true ) ? $status : 'lead';

        if ( empty( $email ) || ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Invalid email address.', 'cashwala-broadcast-pro' ) );
        }

        $existing = self::get_by_email( $email );
        if ( $existing ) {
            $wpdb->update(
                cwbp_table_contacts(),
                array(
                    'name'   => $name ?: $existing->name,
                    'phone'  => $phone ?: $existing->phone,
                    'source' => $source ?: $existing->source,
                    'status' => $status,
                ),
                array( 'id' => $existing->id ),
                array( '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
            do_action( 'cwbp_contact_added', (int) $existing->id, $status );
            return (int) $existing->id;
        }

        $inserted = $wpdb->insert(
            cwbp_table_contacts(),
            array(
                'name'       => $name,
                'email'      => $email,
                'phone'      => $phone,
                'source'     => $source,
                'status'     => $status,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            return new WP_Error( 'db_error', __( 'Could not create contact.', 'cashwala-broadcast-pro' ) );
        }

        $contact_id = (int) $wpdb->insert_id;
        do_action( 'cwbp_contact_added', $contact_id, $status );
        return $contact_id;
    }

    public static function get_by_email( $email ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare( 'SELECT * FROM ' . cwbp_table_contacts() . ' WHERE email = %s LIMIT 1', sanitize_email( $email ) )
        );
    }

    public static function get_contacts( $args = array() ) {
        global $wpdb;
        $defaults = array(
            'search'   => '',
            'status'   => '',
            'limit'    => 50,
            'offset'   => 0,
            'order_by' => 'id',
            'order'    => 'DESC',
        );
        $args = wp_parse_args( $args, $defaults );

        $where  = ' WHERE 1=1 ';
        $values = array();

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where  .= ' AND (name LIKE %s OR email LIKE %s OR phone LIKE %s)';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        if ( in_array( $args['status'], array( 'lead', 'buyer' ), true ) ) {
            $where   .= ' AND status = %s';
            $values[] = $args['status'];
        }

        $allowed_order_by = array( 'id', 'name', 'email', 'status', 'created_at' );
        $order_by         = in_array( $args['order_by'], $allowed_order_by, true ) ? $args['order_by'] : 'id';
        $order            = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

        $sql = 'SELECT * FROM ' . cwbp_table_contacts() . $where . ' ORDER BY ' . $order_by . ' ' . $order . ' LIMIT %d OFFSET %d';
        $values[] = absint( $args['limit'] );
        $values[] = absint( $args['offset'] );

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ) );
    }

    public static function count_contacts( $status = '' ) {
        global $wpdb;
        if ( in_array( $status, array( 'lead', 'buyer' ), true ) ) {
            return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . cwbp_table_contacts() . ' WHERE status = %s', $status ) );
        }

        return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . cwbp_table_contacts() );
    }

    public static function get_for_audience( $audience ) {
        global $wpdb;
        if ( 'buyers' === $audience ) {
            return $wpdb->get_results( "SELECT * FROM " . cwbp_table_contacts() . " WHERE status='buyer'" );
        }

        if ( 'leads' === $audience ) {
            return $wpdb->get_results( "SELECT * FROM " . cwbp_table_contacts() . " WHERE status='lead'" );
        }

        return $wpdb->get_results( 'SELECT * FROM ' . cwbp_table_contacts() );
    }

    public static function bulk_delete() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-broadcast-pro' ) );
        }

        check_admin_referer( 'cwbp_delete_contacts' );

        $ids = isset( $_POST['contact_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['contact_ids'] ) ) : array();
        $ids = array_filter( $ids );

        if ( $ids ) {
            global $wpdb;
            $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
            $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . cwbp_table_contacts() . ' WHERE id IN (' . $placeholders . ')', $ids ) );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=cwbp_contacts' ) );
        exit;
    }

    public static function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-broadcast-pro' ) );
        }

        check_admin_referer( 'cwbp_export_contacts' );

        $contacts = self::get_contacts(
            array(
                'limit'  => 50000,
                'offset' => 0,
                'status' => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
            )
        );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=contacts-' . gmdate( 'Ymd-His' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'ID', 'Name', 'Email', 'Phone', 'Source', 'Status', 'Created At' ) );

        foreach ( $contacts as $contact ) {
            fputcsv(
                $output,
                array(
                    $contact->id,
                    $contact->name,
                    $contact->email,
                    $contact->phone,
                    $contact->source,
                    $contact->status,
                    $contact->created_at,
                )
            );
        }

        fclose( $output );
        exit;
    }
}
