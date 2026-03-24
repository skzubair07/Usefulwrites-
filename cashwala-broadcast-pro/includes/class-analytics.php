<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Analytics {

    public static function init() {
        // Placeholder for future hooks.
    }

    public static function track_sent( $campaign_id, $contact_id ) {
        self::insert_event( $campaign_id, $contact_id, 'sent', null );
    }

    public static function track_open( $queue_id ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT campaign_id, contact_id FROM ' . cwbp_table_email_queue() . ' WHERE id=%d', $queue_id ) );
        if ( $row ) {
            self::insert_event( (int) $row->campaign_id, (int) $row->contact_id, 'open', null );
        }
    }

    public static function track_click( $queue_id, $url ) {
        global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT campaign_id, contact_id FROM ' . cwbp_table_email_queue() . ' WHERE id=%d', $queue_id ) );
        if ( $row ) {
            self::insert_event( (int) $row->campaign_id, (int) $row->contact_id, 'click', esc_url_raw( $url ) );
        }
    }

    protected static function insert_event( $campaign_id, $contact_id, $event_type, $meta_value ) {
        global $wpdb;
        $wpdb->insert(
            cwbp_table_analytics(),
            array(
                'campaign_id' => $campaign_id ?: null,
                'contact_id'  => $contact_id ?: null,
                'event_type'  => sanitize_key( $event_type ),
                'meta_value'  => $meta_value,
                'created_at'  => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s' )
        );
    }

    public static function get_dashboard_stats() {
        global $wpdb;

        return array(
            'total_contacts' => CWBP_Contacts::count_contacts(),
            'total_campaigns'=> (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . cwbp_table_campaigns() ),
            'emails_sent'    => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . cwbp_table_analytics() . ' WHERE event_type=%s', 'sent' ) ),
            'total_opens'    => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . cwbp_table_analytics() . ' WHERE event_type=%s', 'open' ) ),
            'total_clicks'   => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . cwbp_table_analytics() . ' WHERE event_type=%s', 'click' ) ),
        );
    }

    public static function campaign_performance( $limit = 20 ) {
        global $wpdb;
        $sql = 'SELECT c.id, c.name, c.subject, c.status, c.sent_count, c.fail_count,
            SUM(CASE WHEN a.event_type="open" THEN 1 ELSE 0 END) AS opens,
            SUM(CASE WHEN a.event_type="click" THEN 1 ELSE 0 END) AS clicks
            FROM ' . cwbp_table_campaigns() . ' c
            LEFT JOIN ' . cwbp_table_analytics() . ' a ON c.id = a.campaign_id
            GROUP BY c.id
            ORDER BY c.id DESC
            LIMIT %d';

        return $wpdb->get_results( $wpdb->prepare( $sql, absint( $limit ) ) );
    }
}
