<?php
/**
 * Affiliate tracking and commission module.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Affiliate_Module {

    public static function init() {
        add_action( 'user_register', array( __CLASS__, 'capture_referral_signup' ) );
        add_action( 'do_affiliate_commission_check', array( __CLASS__, 'process_token_purchase_commissions' ) );

        if ( ! wp_next_scheduled( 'do_affiliate_commission_check' ) ) {
            wp_schedule_event( time() + 300, 'hourly', 'do_affiliate_commission_check' );
        }
    }

    public static function capture_referral_signup( $user_id ) {
        if ( empty( $_COOKIE['do_referrer_id'] ) ) {
            return;
        }
        global $wpdb;
        $referrer = absint( $_COOKIE['do_referrer_id'] );
        if ( $referrer > 0 && $referrer !== $user_id ) {
            $wpdb->insert(
                $wpdb->prefix . 'do_affiliate_referrals',
                array(
                    'referrer_user_id'  => $referrer,
                    'referred_user_id'  => $user_id,
                    'commission_amount' => 0,
                    'status'            => 'pending',
                    'created_at'        => current_time( 'mysql' ),
                )
            );
        }
    }

    public static function process_token_purchase_commissions() {
        global $wpdb;
        $settings      = get_option( 'do_affiliate_settings', array( 'commission_percent' => 10 ) );
        $commission    = (float) ( $settings['commission_percent'] ?? 10 );
        $processed_map = get_option( 'do_affiliate_commission_refs', array() );

        $rows = $wpdb->get_results(
            "SELECT pc.user_id, pc.transaction_id, p.amount
             FROM {$wpdb->prefix}do_payment_credits pc
             LEFT JOIN {$wpdb->prefix}do_payments p ON p.reference = pc.transaction_id",
            ARRAY_A
        );

        foreach ( $rows as $row ) {
            $tx = sanitize_text_field( $row['transaction_id'] );
            if ( ! $tx || ! empty( $processed_map[ $tx ] ) ) {
                continue;
            }

            $signup_referral = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}do_affiliate_referrals WHERE referred_user_id = %d ORDER BY id ASC LIMIT 1",
                    absint( $row['user_id'] )
                ),
                ARRAY_A
            );

            if ( ! $signup_referral ) {
                continue;
            }

            $amount = (float) ( $row['amount'] ?? 0 );
            if ( $amount <= 0 ) {
                continue;
            }

            $commission_amount = round( $amount * ( $commission / 100 ), 2 );

            $wpdb->insert(
                $wpdb->prefix . 'do_affiliate_referrals',
                array(
                    'referrer_user_id'  => absint( $signup_referral['referrer_user_id'] ),
                    'referred_user_id'  => absint( $row['user_id'] ),
                    'commission_amount' => $commission_amount,
                    'status'            => 'pending',
                    'created_at'        => current_time( 'mysql' ),
                )
            );

            $processed_map[ $tx ] = 1;
        }

        update_option( 'do_affiliate_commission_refs', $processed_map );
    }
}

function do_render_affiliate_settings_page() {
    global $wpdb;

    if ( isset( $_POST['do_save_affiliate_settings'] ) && check_admin_referer( 'do_affiliate_settings_nonce' ) ) {
        $settings = array(
            'commission_percent' => min( 100, max( 0, floatval( $_POST['commission_percent'] ?? 10 ) ) ),
        );
        update_option( 'do_affiliate_settings', $settings );
        echo '<div class="updated"><p>Affiliate settings saved.</p></div>';
    }

    if ( isset( $_POST['affiliate_action'] ) && check_admin_referer( 'do_affiliate_settings_nonce' ) ) {
        $id     = absint( $_POST['referral_id'] ?? 0 );
        $action = sanitize_key( wp_unslash( $_POST['affiliate_action'] ) );
        if ( in_array( $action, array( 'approved', 'rejected' ), true ) ) {
            $wpdb->update( $wpdb->prefix . 'do_affiliate_referrals', array( 'status' => $action ), array( 'id' => $id ) );
        }
    }

    $settings = get_option( 'do_affiliate_settings', array( 'commission_percent' => 10 ) );
    $rows     = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'do_affiliate_referrals ORDER BY id DESC LIMIT 200', ARRAY_A );

    echo '<div class="wrap"><h1>Affiliate Settings</h1>';
    echo '<form method="post">';
    wp_nonce_field( 'do_affiliate_settings_nonce' );
    echo '<p><label>Commission Percentage <input type="number" step="0.01" max="100" min="0" name="commission_percent" value="' . esc_attr( $settings['commission_percent'] ) . '"></label></p>';
    echo '<p><button class="button button-primary" name="do_save_affiliate_settings" value="1">Save</button></p>';
    echo '</form>';

    echo '<h2>Referral Earnings</h2><table class="widefat"><thead><tr><th>ID</th><th>Referrer</th><th>Referred</th><th>Commission</th><th>Status</th><th>Action</th></tr></thead><tbody>';
    foreach ( $rows as $row ) {
        echo '<tr>';
        echo '<td>' . esc_html( $row['id'] ) . '</td>';
        echo '<td>' . esc_html( $row['referrer_user_id'] ) . '</td>';
        echo '<td>' . esc_html( $row['referred_user_id'] ) . '</td>';
        echo '<td>' . esc_html( $row['commission_amount'] ) . '</td>';
        echo '<td>' . esc_html( $row['status'] ) . '</td>';
        echo '<td><form method="post">';
        wp_nonce_field( 'do_affiliate_settings_nonce' );
        echo '<input type="hidden" name="referral_id" value="' . esc_attr( $row['id'] ) . '">';
        echo '<button class="button" name="affiliate_action" value="approved">Approve</button> ';
        echo '<button class="button" name="affiliate_action" value="rejected">Reject</button>';
        echo '</form></td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

DO_Affiliate_Module::init();
