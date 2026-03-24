<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWBP_Cron {
    const HOOK = 'cwbp_cron_tick';

    public static function init() {
        add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
        add_action( self::HOOK, array( __CLASS__, 'run' ) );
    }

    public static function schedule() {
        add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + 60, 'minute', self::HOOK );
        }
    }

    public static function unschedule() {
        $timestamp = wp_next_scheduled( self::HOOK );
        if ( $timestamp ) {
            wp_unschedule_event( $timestamp, self::HOOK );
        }
    }

    public static function run() {
        self::process_email_queue( 25 );
        CWBP_Automation::process_runs( 20 );
    }

    public static function process_email_queue( $batch_size = 25 ) {
        global $wpdb;

        $items = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . cwbp_table_email_queue() . ' WHERE status=%s AND send_after <= %s ORDER BY id ASC LIMIT %d',
                'pending',
                current_time( 'mysql' ),
                absint( $batch_size )
            )
        );

        foreach ( $items as $item ) {
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            $body    = self::message_with_tracking( $item->message, (int) $item->id );

            $sent = wp_mail( $item->email, $item->subject, $body, $headers );
            if ( $sent ) {
                $wpdb->update(
                    cwbp_table_email_queue(),
                    array(
                        'status'  => 'sent',
                        'sent_at' => current_time( 'mysql' ),
                    ),
                    array( 'id' => $item->id ),
                    array( '%s', '%s' ),
                    array( '%d' )
                );
                CWBP_Analytics::track_sent( (int) $item->campaign_id, (int) $item->contact_id );
                self::mark_campaign_counter( (int) $item->campaign_id, 'sent_count' );
            } else {
                $attempts = (int) $item->attempts + 1;
                $status   = $attempts >= (int) $item->max_attempts ? 'failed' : 'pending';
                $delay    = min( 3600, 60 * $attempts );

                $wpdb->update(
                    cwbp_table_email_queue(),
                    array(
                        'attempts'   => $attempts,
                        'status'     => $status,
                        'send_after' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
                        'last_error' => 'wp_mail returned false',
                    ),
                    array( 'id' => $item->id ),
                    array( '%d', '%s', '%s', '%s' ),
                    array( '%d' )
                );

                if ( 'failed' === $status ) {
                    self::mark_campaign_counter( (int) $item->campaign_id, 'fail_count' );
                }
            }
        }
    }

    public static function cron_schedules( $schedules ) {
        if ( ! isset( $schedules['minute'] ) ) {
            $schedules['minute'] = array(
                'interval' => 60,
                'display'  => __( 'Every Minute', 'cashwala-broadcast-pro' ),
            );
        }
        return $schedules;
    }

    protected static function message_with_tracking( $message, $queue_id ) {
        $pixel_url = add_query_arg( 'cwbp_open', $queue_id, home_url( '/' ) );

        $message = preg_replace_callback(
            '#<a[^>]+href=["\']([^"\']+)["\'][^>]*>#i',
            static function ( $matches ) use ( $queue_id ) {
                $tracked = add_query_arg(
                    array(
                        'cwbp_click' => $queue_id,
                        'url'        => rawurlencode( $matches[1] ),
                    ),
                    home_url( '/' )
                );
                return str_replace( $matches[1], esc_url( $tracked ), $matches[0] );
            },
            $message
        );

        return $message . '<img src="' . esc_url( $pixel_url ) . '" alt="" width="1" height="1" style="display:none" />';
    }

    protected static function mark_campaign_counter( $campaign_id, $field ) {
        if ( empty( $campaign_id ) || ! in_array( $field, array( 'sent_count', 'fail_count' ), true ) ) {
            return;
        }

        global $wpdb;
        $wpdb->query( $wpdb->prepare( 'UPDATE ' . cwbp_table_campaigns() . ' SET ' . $field . ' = ' . $field . ' + 1 WHERE id = %d', $campaign_id ) );

        $total = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT total_recipients FROM ' . cwbp_table_campaigns() . ' WHERE id = %d', $campaign_id ) );
        $sent  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT sent_count FROM ' . cwbp_table_campaigns() . ' WHERE id = %d', $campaign_id ) );
        if ( $total > 0 && $sent >= $total ) {
            $wpdb->update(
                cwbp_table_campaigns(),
                array(
                    'status'  => 'completed',
                    'sent_at' => current_time( 'mysql' ),
                ),
                array( 'id' => $campaign_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
        }
    }
}
