<?php
/**
 * Token management module (1 token = 1 question) with payment verification.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Tokens_Module {

    public static function init() {
        add_action( 'wp_ajax_do_buy_tokens', array( __CLASS__, 'buy_tokens' ) );
    }

    public static function get_balance( $user_id ) {
        global $wpdb;
        return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT balance FROM ' . $wpdb->prefix . 'do_tokens WHERE user_id = %d', $user_id ) );
    }

    public static function add_tokens( $user_id, $amount ) {
        global $wpdb;
        $balance = self::get_balance( $user_id ) + (int) $amount;

        $wpdb->replace(
            $wpdb->prefix . 'do_tokens',
            array(
                'user_id'    => absint( $user_id ),
                'balance'    => $balance,
                'updated_at' => current_time( 'mysql' ),
            )
        );
    }

    public static function consume_token( $user_id ) {
        $balance = self::get_balance( $user_id );
        if ( $balance <= 0 ) {
            return false;
        }
        self::add_tokens( $user_id, -1 );
        return true;
    }

    /**
     * Credit tokens only after verified payment and unique transaction.
     */
    public static function buy_tokens() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array( 'message' => 'Login required.' ) );
        }

        global $wpdb;

        $user_id         = get_current_user_id();
        $package_tokens  = absint( $_POST['tokens'] ?? 0 );
        $transaction_id  = sanitize_text_field( wp_unslash( $_POST['transaction_id'] ?? '' ) );

        if ( $package_tokens <= 0 ) {
            wp_send_json_error( array( 'message' => 'Invalid package.' ) );
        }

        if ( ! preg_match( '/^[A-Za-z0-9\-_]{8,120}$/', $transaction_id ) ) {
            DO_Logger::log( 'PAYMENT', 'Invalid transaction id by user ' . $user_id );
            wp_send_json_error( array( 'message' => 'Invalid transaction ID.' ) );
        }

        $already_credited = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'do_payment_credits WHERE transaction_id = %s', $transaction_id ) );
        if ( $already_credited ) {
            DO_Logger::log( 'PAYMENT', 'Duplicate credit blocked for tx: ' . $transaction_id );
            wp_send_json_error( array( 'message' => 'This transaction was already used.' ) );
        }

        $payment = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}do_payments WHERE user_id = %d AND reference = %s AND status IN ('verified','completed') ORDER BY id DESC LIMIT 1",
                $user_id,
                $transaction_id
            ),
            ARRAY_A
        );

        if ( ! $payment ) {
            DO_Logger::log( 'PAYMENT', 'Payment verification failed for tx: ' . $transaction_id );
            wp_send_json_error( array( 'message' => 'Payment is not verified yet.' ) );
        }

        $wpdb->insert(
            $wpdb->prefix . 'do_payment_credits',
            array(
                'user_id'        => $user_id,
                'transaction_id' => $transaction_id,
                'tokens'         => $package_tokens,
                'created_at'     => current_time( 'mysql' ),
            )
        );

        self::add_tokens( $user_id, $package_tokens );
        DO_Logger::log( 'PAYMENT', 'Token credited. user=' . $user_id . ' tx=' . $transaction_id . ' tokens=' . $package_tokens );

        wp_send_json_success( array( 'balance' => self::get_balance( $user_id ) ) );
    }
}

function do_render_token_settings_page() {
    if ( isset( $_POST['do_save_token_settings'] ) && check_admin_referer( 'do_token_settings_nonce' ) ) {
        $packages = array();
        foreach ( (array) ( $_POST['package_tokens'] ?? array() ) as $i => $token_count ) {
            $tokens = absint( $token_count );
            $price  = floatval( $_POST['package_price'][ $i ] ?? 0 );
            if ( $tokens > 0 && $price > 0 ) {
                $packages[] = array( 'tokens' => $tokens, 'price' => $price );
            }
        }

        update_option(
            'do_token_settings',
            array(
                'price_per_token' => floatval( $_POST['price_per_token'] ?? 0 ),
                'packages'        => $packages,
            )
        );
        echo '<div class="updated"><p>Token settings updated.</p></div>';
    }

    $settings = get_option( 'do_token_settings', array( 'price_per_token' => 10, 'packages' => array() ) );
    include DO_PLUGIN_DIR . 'templates/admin-token-settings.php';
}

DO_Tokens_Module::init();
