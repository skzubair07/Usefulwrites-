<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLMP_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_cwlmp_generate_license', array( __CLASS__, 'handle_generate' ) );
        add_action( 'admin_post_cwlmp_bulk_generate_license', array( __CLASS__, 'handle_bulk_generate' ) );
        add_action( 'admin_post_cwlmp_update_license', array( __CLASS__, 'handle_update' ) );
        add_action( 'admin_post_cwlmp_export_csv', array( __CLASS__, 'handle_csv_export' ) );
    }

    public static function menu() {
        add_menu_page(
            __( 'CashWala License Manager', 'cashwala-license-manager' ),
            __( 'CashWala Licenses', 'cashwala-license-manager' ),
            'manage_options',
            'cwlmp-licenses',
            array( __CLASS__, 'render_page' ),
            'dashicons-shield-alt',
            56
        );
    }

    public static function assets( $hook ) {
        if ( 'toplevel_page_cwlmp-licenses' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'cwlmp-admin', CWLMP_URL . 'assets/css/admin.css', array(), CWLMP_VERSION );
        wp_enqueue_script( 'cwlmp-admin', CWLMP_URL . 'assets/js/admin.js', array( 'jquery' ), CWLMP_VERSION, true );
    }

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $status   = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $paged    = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
        $per_page = 20;

        $data   = CWLMP_Licenses::search_licenses(
            array(
                'status'   => $status,
                'search'   => $search,
                'paged'    => $paged,
                'per_page' => $per_page,
            )
        );
        $stats  = CWLMP_Analytics::dashboard_stats();
        $vstats = CWLMP_Analytics::validation_stats( 7 );

        $context = array(
            'stats'      => $stats,
            'vstats'     => $vstats,
            'licenses'   => $data['rows'],
            'total'      => $data['total'],
            'paged'      => $paged,
            'per_page'   => $per_page,
            'status'     => $status,
            'search'     => $search,
            'logs'       => CWLMP_Licenses::get_recent_logs( 20 ),
            'generated'  => get_transient( 'cwlmp_recent_generated_' . get_current_user_id() ),
        );

        delete_transient( 'cwlmp_recent_generated_' . get_current_user_id() );

        include CWLMP_PATH . 'templates/license-form.php';
        include CWLMP_PATH . 'templates/license-table.php';
    }

    public static function handle_generate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-license-manager' ) );
        }

        CWLMP_Security::verify_nonce( 'cwlmp_generate_license' );

        $key   = CWLMP_Security::generate_license_key();
        $id    = CWLMP_Licenses::create_license(
            $key,
            array(
                'status'           => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active',
                'expiry_date'      => isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : null,
                'domain_limit'     => isset( $_POST['domain_limit'] ) ? absint( $_POST['domain_limit'] ) : 1,
                'assigned_email'   => isset( $_POST['assigned_email'] ) ? sanitize_email( wp_unslash( $_POST['assigned_email'] ) ) : null,
                'assigned_user_id' => isset( $_POST['assigned_user_id'] ) ? absint( $_POST['assigned_user_id'] ) : null,
                'notes'            => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : null,
            )
        );

        if ( ! is_wp_error( $id ) ) {
            set_transient( 'cwlmp_recent_generated_' . get_current_user_id(), array( $key ), 5 * MINUTE_IN_SECONDS );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=cwlmp-licenses' ) );
        exit;
    }

    public static function handle_bulk_generate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-license-manager' ) );
        }

        CWLMP_Security::verify_nonce( 'cwlmp_bulk_generate_license' );

        $count = isset( $_POST['bulk_count'] ) ? absint( $_POST['bulk_count'] ) : 10;

        $keys = CWLMP_Licenses::bulk_create_licenses(
            $count,
            array(
                'status'           => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active',
                'expiry_date'      => isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : null,
                'domain_limit'     => isset( $_POST['domain_limit'] ) ? absint( $_POST['domain_limit'] ) : 1,
                'assigned_email'   => isset( $_POST['assigned_email'] ) ? sanitize_email( wp_unslash( $_POST['assigned_email'] ) ) : null,
                'assigned_user_id' => isset( $_POST['assigned_user_id'] ) ? absint( $_POST['assigned_user_id'] ) : null,
                'notes'            => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : null,
            )
        );

        set_transient( 'cwlmp_recent_generated_' . get_current_user_id(), $keys, 10 * MINUTE_IN_SECONDS );

        wp_safe_redirect( admin_url( 'admin.php?page=cwlmp-licenses' ) );
        exit;
    }

    public static function handle_update() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-license-manager' ) );
        }

        CWLMP_Security::verify_nonce( 'cwlmp_update_license' );

        $id = isset( $_POST['license_id'] ) ? absint( $_POST['license_id'] ) : 0;

        if ( $id > 0 ) {
            CWLMP_Licenses::update_license(
                $id,
                array(
                    'status'           => isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active',
                    'expiry_date'      => isset( $_POST['expiry_date'] ) ? sanitize_text_field( wp_unslash( $_POST['expiry_date'] ) ) : null,
                    'domain_limit'     => isset( $_POST['domain_limit'] ) ? absint( $_POST['domain_limit'] ) : 1,
                    'bound_domain'     => isset( $_POST['bound_domain'] ) ? sanitize_text_field( wp_unslash( $_POST['bound_domain'] ) ) : null,
                    'assigned_email'   => isset( $_POST['assigned_email'] ) ? sanitize_email( wp_unslash( $_POST['assigned_email'] ) ) : null,
                    'assigned_user_id' => isset( $_POST['assigned_user_id'] ) ? absint( $_POST['assigned_user_id'] ) : null,
                    'notes'            => isset( $_POST['notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) ) : null,
                )
            );
        }

        wp_safe_redirect( admin_url( 'admin.php?page=cwlmp-licenses' ) );
        exit;
    }

    public static function handle_csv_export() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-license-manager' ) );
        }

        check_admin_referer( 'cwlmp_export_csv' );

        $results = CWLMP_Licenses::search_licenses(
            array(
                'paged'    => 1,
                'per_page' => 5000,
            )
        );

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=cashwala-licenses-' . gmdate( 'Ymd-His' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'id', 'key_mask', 'status', 'expiry_date', 'bound_domain', 'assigned_email', 'assigned_user_id', 'created_at' ) );

        foreach ( $results['rows'] as $row ) {
            fputcsv(
                $output,
                array(
                    $row['id'],
                    $row['key_mask'],
                    $row['status'],
                    $row['expiry_date'],
                    $row['bound_domain'],
                    $row['assigned_email'],
                    $row['assigned_user_id'],
                    $row['created_at'],
                )
            );
        }

        fclose( $output );
        exit;
    }
}
