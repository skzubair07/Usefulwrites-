<?php
/**
 * Plugin Name: Lead Magnet Pro – Visitor to Lead Converter
 * Plugin URI: https://example.com/lead-magnet-pro
 * Description: High-converting popup lead capture plugin with WhatsApp-first flows, lead storage, and analytics.
 * Version: 1.0.0
 * Author: Usefulwrites
 * Text Domain: lead-magnet-pro
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'LMP_VERSION', '1.0.0' );
define( 'LMP_PLUGIN_FILE', __FILE__ );
define( 'LMP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'LMP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

$files = array(
    'includes/class-leads.php',
    'includes/class-analytics.php',
    'includes/class-ajax.php',
    'includes/class-popup.php',
    'includes/class-admin.php',
);

foreach ( $files as $file ) {
    $path = LMP_PLUGIN_PATH . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

/**
 * Get plugin options merged with defaults.
 *
 * @return array<string,mixed>
 */
function lmp_get_options() {
    $defaults = array(
        'enabled'                => 1,
        'trigger_type'           => 'time_delay',
        'delay_seconds'          => 5,
        'scroll_percent'         => 50,
        'show_once_session'      => 1,
        'title'                  => 'Get Exclusive Offers on WhatsApp',
        'description'            => 'Join now and receive curated deals, updates, and growth tips directly on WhatsApp.',
        'submit_button_text'     => 'Get Instant Access',
        'success_message'        => 'Thanks! We have received your details.',
        'show_name'              => 1,
        'show_email'             => 1,
        'show_phone'             => 1,
        'required_name'          => 1,
        'required_email'         => 0,
        'required_phone'         => 1,
        'redirect_mode'          => 'none',
        'redirect_url'           => '',
        'whatsapp_number'        => '919999999999',
        'whatsapp_message'       => 'Hi, I just signed up on {site_name} from {page_url}.',
        'privacy_note'           => 'We respect your privacy. No spam, ever.',
        'button_bg_color'        => '#16a34a',
        'button_text_color'      => '#ffffff',
        'popup_background'       => '#111827',
        'popup_text_color'       => '#f9fafb',
        'overlay_opacity'        => '0.70',
    );

    $stored = get_option( 'lmp_options', array() );

    return wp_parse_args( $stored, $defaults );
}

/**
 * Activate plugin.
 */
function lmp_activate_plugin() {
    if ( ! get_option( 'lmp_options' ) ) {
        update_option( 'lmp_options', lmp_get_options() );
    }

    if ( class_exists( 'LMP_Leads' ) ) {
        LMP_Leads::register_post_type();
    }

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'lmp_activate_plugin' );

/**
 * Deactivate plugin.
 */
function lmp_deactivate_plugin() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'lmp_deactivate_plugin' );

/**
 * Bootstrap plugin classes.
 */
function lmp_bootstrap() {
    if ( class_exists( 'LMP_Leads' ) ) {
        LMP_Leads::init();
    }

    if ( class_exists( 'LMP_Analytics' ) ) {
        LMP_Analytics::init();
    }

    if ( class_exists( 'LMP_Ajax' ) ) {
        LMP_Ajax::init();
    }

    if ( class_exists( 'LMP_Popup' ) ) {
        LMP_Popup::init();
    }

    if ( is_admin() && class_exists( 'LMP_Admin' ) ) {
        LMP_Admin::init();
    }
}
add_action( 'plugins_loaded', 'lmp_bootstrap' );
