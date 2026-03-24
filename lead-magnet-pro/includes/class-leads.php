<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMP_Leads {
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_post_type' ) );
        add_filter( 'manage_lmp_lead_posts_columns', array( __CLASS__, 'set_admin_columns' ) );
        add_action( 'manage_lmp_lead_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
        add_filter( 'manage_edit-lmp_lead_sortable_columns', array( __CLASS__, 'sortable_columns' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'handle_sorting' ) );
        add_action( 'admin_post_lmp_export_leads', array( __CLASS__, 'export_csv' ) );
    }

    public static function register_post_type() {
        register_post_type(
            'lmp_lead',
            array(
                'labels'          => array(
                    'name'          => esc_html__( 'Leads', 'lead-magnet-pro' ),
                    'singular_name' => esc_html__( 'Lead', 'lead-magnet-pro' ),
                ),
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => false,
                'supports'        => array( 'title' ),
                'capability_type' => 'post',
                'map_meta_cap'    => true,
                'menu_icon'       => 'dashicons-groups',
            )
        );
    }

    public static function save_lead( $data ) {
        $name     = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
        $email    = isset( $data['email'] ) ? sanitize_email( $data['email'] ) : '';
        $phone    = isset( $data['phone'] ) ? sanitize_text_field( $data['phone'] ) : '';
        $page_url = isset( $data['page_url'] ) ? esc_url_raw( $data['page_url'] ) : '';
        $time     = current_time( 'mysql' );

        $title_parts = array_filter( array( $name, $phone, $email ) );
        $post_title  = ! empty( $title_parts ) ? implode( ' | ', $title_parts ) : esc_html__( 'New Lead', 'lead-magnet-pro' );

        $post_id = wp_insert_post(
            array(
                'post_type'   => 'lmp_lead',
                'post_status' => 'publish',
                'post_title'  => wp_strip_all_tags( $post_title ),
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        update_post_meta( $post_id, '_lmp_name', $name );
        update_post_meta( $post_id, '_lmp_email', $email );
        update_post_meta( $post_id, '_lmp_phone', $phone );
        update_post_meta( $post_id, '_lmp_page_url', $page_url );
        update_post_meta( $post_id, '_lmp_time', $time );

        return $post_id;
    }

    public static function set_admin_columns( $columns ) {
        return array(
            'cb'           => $columns['cb'],
            'title'        => esc_html__( 'Lead', 'lead-magnet-pro' ),
            'lmp_email'    => esc_html__( 'Email', 'lead-magnet-pro' ),
            'lmp_phone'    => esc_html__( 'Phone', 'lead-magnet-pro' ),
            'lmp_page_url' => esc_html__( 'Source Page', 'lead-magnet-pro' ),
            'date'         => esc_html__( 'Created', 'lead-magnet-pro' ),
        );
    }

    public static function render_admin_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'lmp_email':
                echo esc_html( get_post_meta( $post_id, '_lmp_email', true ) );
                break;
            case 'lmp_phone':
                echo esc_html( get_post_meta( $post_id, '_lmp_phone', true ) );
                break;
            case 'lmp_page_url':
                $url = get_post_meta( $post_id, '_lmp_page_url', true );
                if ( $url ) {
                    echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'View Source', 'lead-magnet-pro' ) . '</a>';
                } else {
                    echo '&mdash;';
                }
                break;
        }
    }

    public static function sortable_columns( $columns ) {
        $columns['lmp_email'] = 'lmp_email';
        $columns['lmp_phone'] = 'lmp_phone';

        return $columns;
    }

    public static function handle_sorting( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( 'lmp_lead' !== $query->get( 'post_type' ) ) {
            return;
        }

        $orderby = $query->get( 'orderby' );

        if ( 'lmp_email' === $orderby ) {
            $query->set( 'meta_key', '_lmp_email' );
            $query->set( 'orderby', 'meta_value' );
        }

        if ( 'lmp_phone' === $orderby ) {
            $query->set( 'meta_key', '_lmp_phone' );
            $query->set( 'orderby', 'meta_value' );
        }
    }

    public static function export_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'lead-magnet-pro' ) );
        }

        check_admin_referer( 'lmp_export_leads_nonce' );

        $leads = get_posts(
            array(
                'post_type'      => 'lmp_lead',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=lmp-leads-' . gmdate( 'Ymd-His' ) . '.csv' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Name', 'Email', 'Phone', 'Source Page', 'Time' ) );

        foreach ( $leads as $lead ) {
            fputcsv(
                $output,
                array(
                    get_post_meta( $lead->ID, '_lmp_name', true ),
                    get_post_meta( $lead->ID, '_lmp_email', true ),
                    get_post_meta( $lead->ID, '_lmp_phone', true ),
                    get_post_meta( $lead->ID, '_lmp_page_url', true ),
                    get_post_meta( $lead->ID, '_lmp_time', true ),
                )
            );
        }

        fclose( $output );
        exit;
    }
}
