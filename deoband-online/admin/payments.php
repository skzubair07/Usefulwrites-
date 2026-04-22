<?php
/**
 * Payment settings/admin handlers.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Payment settings page renderer.
 */
function do_render_payment_settings_page() {
    if ( isset( $_POST['do_save_payment_settings'] ) && check_admin_referer( 'do_payment_settings_nonce' ) ) {
        $settings = array(
            'disclaimer'       => wp_kses_post( wp_unslash( $_POST['disclaimer'] ?? '' ) ),
            'upi_id'           => sanitize_text_field( wp_unslash( $_POST['upi_id'] ?? '' ) ),
            'razorpay_key'     => sanitize_text_field( wp_unslash( $_POST['razorpay_key'] ?? '' ) ),
            'manual_mode'      => ! empty( $_POST['manual_mode'] ) ? 1 : 0,
            'razorpay_enabled' => ! empty( $_POST['razorpay_enabled'] ) ? 1 : 0,
        );
        update_option( 'do_payment_settings', $settings );
        echo '<div class="updated"><p>Payment settings saved.</p></div>';
    }

    $settings = wp_parse_args(
        get_option( 'do_payment_settings', array() ),
        array(
            'disclaimer'       => 'Please verify details before payment.',
            'upi_id'           => '',
            'razorpay_key'     => '',
            'manual_mode'      => 1,
            'razorpay_enabled' => 0,
        )
    );

    include DO_PLUGIN_DIR . 'templates/admin-payment-settings.php';
}
