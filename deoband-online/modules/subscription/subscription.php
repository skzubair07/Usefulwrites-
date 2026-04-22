<?php
/**
 * Subscription plans and limits module.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Subscription_Module {

    public static function get_active_plan( $user_id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . $wpdb->prefix . "do_subscriptions WHERE user_id=%d AND status='active' AND expires_at >= %s ORDER BY id DESC LIMIT 1",
                $user_id,
                current_time( 'mysql' )
            ),
            ARRAY_A
        );
    }
}

function do_render_subscription_settings_page() {
    if ( isset( $_POST['do_save_subscription_settings'] ) && check_admin_referer( 'do_subscription_settings_nonce' ) ) {
        $plans = array();
        foreach ( (array) ( $_POST['plan_key'] ?? array() ) as $idx => $key ) {
            $safe_key = sanitize_key( wp_unslash( $key ) );
            $plans[]  = array(
                'key'            => $safe_key,
                'label'          => sanitize_text_field( wp_unslash( $_POST['plan_label'][ $idx ] ?? '' ) ),
                'monthly_price'  => floatval( $_POST['plan_price'][ $idx ] ?? 0 ),
                'question_limit' => absint( $_POST['question_limit'][ $idx ] ?? 0 ),
            );
        }
        update_option( 'do_subscription_settings', array( 'plans' => $plans ) );
        echo '<div class="updated"><p>Subscription plans saved.</p></div>';
    }

    $settings = get_option( 'do_subscription_settings', array( 'plans' => array() ) );
    include DO_PLUGIN_DIR . 'templates/admin-subscription-settings.php';
}
