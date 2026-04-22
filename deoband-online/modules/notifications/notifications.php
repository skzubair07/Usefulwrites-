<?php
/**
 * Notification module for broadcasts and event alerts.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Notifications_Module {

    const CRON_HOOK = 'do_send_scheduled_notification';

    public static function init() {
        add_action( 'wp_ajax_do_send_broadcast', array( __CLASS__, 'send_broadcast' ) );
        add_action( self::CRON_HOOK, array( __CLASS__, 'process_scheduled_notification' ), 10, 1 );
    }

    /**
     * Send immediate broadcast (masail / announcement / donation) or schedule it.
     */
    public static function send_broadcast() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
        }

        $message  = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
        $type     = sanitize_key( wp_unslash( $_POST['type'] ?? 'announcement' ) );
        $schedule = sanitize_text_field( wp_unslash( $_POST['schedule_at'] ?? '' ) );

        if ( '' === $message ) {
            wp_send_json_error( array( 'message' => 'Message required.' ) );
        }

        $payload = array(
            'type'    => in_array( $type, array( 'masail', 'announcement', 'donation' ), true ) ? $type : 'announcement',
            'message' => $message,
            'sent_at' => current_time( 'mysql' ),
        );

        if ( $schedule ) {
            $timestamp = strtotime( $schedule );
            if ( $timestamp && $timestamp > time() ) {
                wp_schedule_single_event( $timestamp, self::CRON_HOOK, array( $payload ) );
                wp_send_json_success( array( 'message' => 'Notification scheduled.' ) );
            }
        }

        update_option( 'do_last_broadcast', $payload );
        wp_send_json_success( array( 'message' => 'Broadcast sent.' ) );
    }

    public static function process_scheduled_notification( $payload ) {
        if ( ! is_array( $payload ) ) {
            return;
        }

        $payload['sent_at'] = current_time( 'mysql' );
        update_option( 'do_last_broadcast', $payload );
    }
}

function do_render_notifications_page() {
    if ( isset( $_POST['do_save_notification'] ) && check_admin_referer( 'do_notifications_nonce' ) ) {
        $payload = array(
            'message'     => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
            'type'        => sanitize_key( wp_unslash( $_POST['type'] ?? 'announcement' ) ),
            'schedule_at' => sanitize_text_field( wp_unslash( $_POST['schedule_at'] ?? '' ) ),
        );

        if ( ! empty( $payload['schedule_at'] ) && strtotime( $payload['schedule_at'] ) > time() ) {
            wp_schedule_single_event( strtotime( $payload['schedule_at'] ), DO_Notifications_Module::CRON_HOOK, array( $payload ) );
            echo '<div class="updated"><p>Notification scheduled.</p></div>';
        } else {
            update_option(
                'do_last_broadcast',
                array(
                    'message' => $payload['message'],
                    'type'    => $payload['type'],
                    'sent_at' => current_time( 'mysql' ),
                )
            );
            echo '<div class="updated"><p>Notification sent.</p></div>';
        }
    }

    $last = get_option( 'do_last_broadcast', array() );

    echo '<div class="wrap"><h1>Notifications</h1><form method="post">';
    wp_nonce_field( 'do_notifications_nonce' );
    echo '<p><label>Type <select name="type"><option value="masail">Masail</option><option value="announcement">Announcement</option><option value="donation">Donation Request</option></select></label></p>';
    echo '<p><label>Message<br><textarea name="message" rows="4" cols="80"></textarea></label></p>';
    echo '<p><label>Schedule (optional, YYYY-MM-DD HH:MM:SS)<br><input name="schedule_at" size="30"></label></p>';
    echo '<p><button class="button button-primary" name="do_save_notification" value="1">Send / Schedule</button></p>';
    echo '</form>';

    echo '<h2>Last Notification</h2>';
    echo '<p><strong>Type:</strong> ' . esc_html( $last['type'] ?? 'none' ) . '</p>';
    echo '<p><strong>Message:</strong> ' . esc_html( $last['message'] ?? 'None' ) . '</p>';
    echo '<p><strong>Sent At:</strong> ' . esc_html( $last['sent_at'] ?? '-' ) . '</p>';
    echo '</div>';
}

DO_Notifications_Module::init();
