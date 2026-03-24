<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Campaigns {

    public static function init() {
        add_action( 'admin_post_cwbp_save_campaign', array( __CLASS__, 'save_campaign' ) );
        add_action( 'admin_post_cwbp_send_whatsapp', array( __CLASS__, 'send_whatsapp' ) );
    }

    public static function save_campaign() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-broadcast-pro' ) );
        }

        check_admin_referer( 'cwbp_save_campaign' );

        $name      = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $subject   = isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
        $message   = isset( $_POST['message'] ) ? wp_kses_post( wp_unslash( $_POST['message'] ) ) : '';
        $audience  = isset( $_POST['audience'] ) ? sanitize_key( wp_unslash( $_POST['audience'] ) ) : 'all';
        $send_mode = isset( $_POST['send_mode'] ) ? sanitize_key( wp_unslash( $_POST['send_mode'] ) ) : 'send_now';
        $scheduled = isset( $_POST['scheduled_at'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_at'] ) ) : '';

        if ( ! in_array( $audience, array( 'all', 'buyers', 'leads' ), true ) ) {
            $audience = 'all';
        }

        if ( ! in_array( $send_mode, array( 'send_now', 'schedule' ), true ) ) {
            $send_mode = 'send_now';
        }

        $scheduled_at = null;
        if ( 'schedule' === $send_mode && $scheduled ) {
            $timestamp    = strtotime( $scheduled );
            $scheduled_at = $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
        }

        global $wpdb;
        $wpdb->insert(
            cwbp_table_campaigns(),
            array(
                'name'         => $name,
                'subject'      => $subject,
                'message'      => $message,
                'audience'     => $audience,
                'send_mode'    => $send_mode,
                'scheduled_at' => $scheduled_at,
                'status'       => 'schedule' === $send_mode ? 'scheduled' : 'queued',
                'created_by'   => get_current_user_id(),
                'created_at'   => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        $campaign_id = (int) $wpdb->insert_id;
        self::enqueue_campaign( $campaign_id );

        wp_safe_redirect( admin_url( 'admin.php?page=cwbp_campaigns&created=1' ) );
        exit;
    }

    public static function enqueue_campaign( $campaign_id ) {
        global $wpdb;

        $campaign = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . cwbp_table_campaigns() . ' WHERE id = %d', $campaign_id ) );
        if ( ! $campaign ) {
            return;
        }

        $contacts = CWBP_Contacts::get_for_audience( $campaign->audience );
        $send_at  = 'schedule' === $campaign->send_mode && ! empty( $campaign->scheduled_at )
            ? $campaign->scheduled_at
            : current_time( 'mysql' );

        foreach ( $contacts as $contact ) {
            $message = self::personalize_message( $campaign->message, $contact );
            $subject = self::personalize_message( $campaign->subject, $contact );

            $wpdb->insert(
                cwbp_table_email_queue(),
                array(
                    'campaign_id'  => $campaign_id,
                    'contact_id'   => $contact->id,
                    'email'        => $contact->email,
                    'subject'      => $subject,
                    'message'      => $message,
                    'send_after'   => $send_at,
                    'status'       => 'pending',
                    'attempts'     => 0,
                    'max_attempts' => 3,
                    'created_at'   => current_time( 'mysql' ),
                ),
                array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
            );
        }

        $wpdb->update(
            cwbp_table_campaigns(),
            array( 'total_recipients' => count( $contacts ) ),
            array( 'id' => $campaign_id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    public static function personalize_message( $content, $contact ) {
        $replacements = array(
            '{name}' => isset( $contact->name ) ? $contact->name : '',
        );
        return strtr( $content, $replacements );
    }

    public static function send_whatsapp() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'cashwala-broadcast-pro' ) );
        }

        check_admin_referer( 'cwbp_send_whatsapp' );

        $message  = isset( $_POST['wa_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wa_message'] ) ) : '';
        $audience = isset( $_POST['wa_audience'] ) ? sanitize_key( wp_unslash( $_POST['wa_audience'] ) ) : 'all';

        $contacts = CWBP_Contacts::get_for_audience( $audience );
        $links    = array();

        foreach ( $contacts as $contact ) {
            if ( empty( $contact->phone ) ) {
                continue;
            }
            $number  = preg_replace( '/[^0-9]/', '', $contact->phone );
            $text    = rawurlencode( self::personalize_message( $message, $contact ) );
            $links[] = 'https://wa.me/' . $number . '?text=' . $text;
        }

        set_transient( 'cwbp_whatsapp_links_' . get_current_user_id(), $links, 10 * MINUTE_IN_SECONDS );
        wp_safe_redirect( admin_url( 'admin.php?page=cwbp_whatsapp&generated=1' ) );
        exit;
    }

    public static function list_campaigns( $limit = 100 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . cwbp_table_campaigns() . ' ORDER BY id DESC LIMIT %d', absint( $limit ) ) );
    }
}
