<?php
/**
 * Plugin Name: CashWala Broadcast Pro
 * Description: Lead management, email campaigns, WhatsApp broadcast, and marketing automation system.
 * Version: 1.0.0
 * Author: CashWala
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: cashwala-broadcast-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CWBP_VERSION', '1.0.0' );
define( 'CWBP_FILE', __FILE__ );
define( 'CWBP_DIR', plugin_dir_path( __FILE__ ) );
define( 'CWBP_URL', plugin_dir_url( __FILE__ ) );

require_once CWBP_DIR . 'includes/class-contacts.php';
require_once CWBP_DIR . 'includes/class-campaigns.php';
require_once CWBP_DIR . 'includes/class-automation.php';
require_once CWBP_DIR . 'includes/class-cron.php';
require_once CWBP_DIR . 'includes/class-analytics.php';
require_once CWBP_DIR . 'includes/class-ajax.php';
require_once CWBP_DIR . 'includes/class-admin.php';

function cwbp_table_contacts() {
    global $wpdb;
    return $wpdb->prefix . 'cwbp_contacts';
}

function cwbp_table_campaigns() {
    global $wpdb;
    return $wpdb->prefix . 'cwbp_campaigns';
}

function cwbp_table_email_queue() {
    global $wpdb;
    return $wpdb->prefix . 'cwbp_email_queue';
}

function cwbp_table_automations() {
    global $wpdb;
    return $wpdb->prefix . 'cwbp_automations';
}

function cwbp_table_automation_runs() {
    global $wpdb;
    return $wpdb->prefix . 'cwbp_automation_runs';
}

function cwbp_table_analytics() {
    global $wpdb;
    return $wpdb->prefix . 'cwbp_analytics';
}

function cwbp_activate() {
    global $wpdb;
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    $charset_collate = $wpdb->get_charset_collate();

    $sql_contacts = 'CREATE TABLE ' . cwbp_table_contacts() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        email VARCHAR(190) NOT NULL,
        phone VARCHAR(50) DEFAULT '' NOT NULL,
        source VARCHAR(100) DEFAULT 'manual' NOT NULL,
        status VARCHAR(20) DEFAULT 'lead' NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY email_unique (email),
        KEY status_idx (status),
        KEY source_idx (source),
        KEY created_idx (created_at)
    ) $charset_collate";

    $sql_campaigns = 'CREATE TABLE ' . cwbp_table_campaigns() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message LONGTEXT NOT NULL,
        audience VARCHAR(20) NOT NULL,
        send_mode VARCHAR(20) NOT NULL,
        scheduled_at DATETIME NULL,
        status VARCHAR(20) DEFAULT 'draft' NOT NULL,
        created_by BIGINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        sent_at DATETIME NULL,
        total_recipients INT UNSIGNED DEFAULT 0 NOT NULL,
        sent_count INT UNSIGNED DEFAULT 0 NOT NULL,
        fail_count INT UNSIGNED DEFAULT 0 NOT NULL,
        PRIMARY KEY  (id),
        KEY status_idx (status),
        KEY audience_idx (audience),
        KEY scheduled_idx (scheduled_at)
    ) $charset_collate";

    $sql_queue = 'CREATE TABLE ' . cwbp_table_email_queue() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT UNSIGNED NULL,
        contact_id BIGINT UNSIGNED NOT NULL,
        email VARCHAR(190) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message LONGTEXT NOT NULL,
        send_after DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT 'pending' NOT NULL,
        attempts TINYINT UNSIGNED DEFAULT 0 NOT NULL,
        max_attempts TINYINT UNSIGNED DEFAULT 3 NOT NULL,
        last_error TEXT NULL,
        sent_at DATETIME NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY status_send_idx (status, send_after),
        KEY campaign_idx (campaign_id),
        KEY contact_idx (contact_id)
    ) $charset_collate";

    $sql_automations = 'CREATE TABLE ' . cwbp_table_automations() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(190) NOT NULL,
        trigger_event VARCHAR(50) NOT NULL,
        steps LONGTEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'active' NOT NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY trigger_idx (trigger_event),
        KEY status_idx (status)
    ) $charset_collate";

    $sql_runs = 'CREATE TABLE ' . cwbp_table_automation_runs() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        automation_id BIGINT UNSIGNED NOT NULL,
        contact_id BIGINT UNSIGNED NOT NULL,
        step_index INT UNSIGNED DEFAULT 0 NOT NULL,
        execute_at DATETIME NOT NULL,
        status VARCHAR(20) DEFAULT 'pending' NOT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NULL,
        PRIMARY KEY  (id),
        KEY execute_idx (status, execute_at),
        KEY automation_idx (automation_id),
        KEY contact_idx (contact_id)
    ) $charset_collate";

    $sql_analytics = 'CREATE TABLE ' . cwbp_table_analytics() . " (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        campaign_id BIGINT UNSIGNED NULL,
        contact_id BIGINT UNSIGNED NULL,
        event_type VARCHAR(30) NOT NULL,
        meta_value VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY event_idx (event_type),
        KEY campaign_idx (campaign_id),
        KEY contact_idx (contact_id),
        KEY created_idx (created_at)
    ) $charset_collate";

    dbDelta( $sql_contacts );
    dbDelta( $sql_campaigns );
    dbDelta( $sql_queue );
    dbDelta( $sql_automations );
    dbDelta( $sql_runs );
    dbDelta( $sql_analytics );

    add_option( 'cwbp_db_version', CWBP_VERSION );

    CWBP_Cron::schedule();
}
register_activation_hook( __FILE__, 'cwbp_activate' );

function cwbp_deactivate() {
    CWBP_Cron::unschedule();
}
register_deactivation_hook( __FILE__, 'cwbp_deactivate' );

function cwbp_bootstrap() {
    CWBP_Contacts::init();
    CWBP_Campaigns::init();
    CWBP_Automation::init();
    CWBP_Cron::init();
    CWBP_Analytics::init();
    CWBP_Ajax::init();
    CWBP_Admin::init();
}
add_action( 'plugins_loaded', 'cwbp_bootstrap' );

/**
 * Public helper for external integrations.
 */
function cw_add_contact( $name, $email, $phone = '', $source = 'manual', $status = 'lead' ) {
    return CWBP_Contacts::add_contact( $name, $email, $phone, $source, $status );
}

add_action(
    'init',
    static function () {
        if ( isset( $_GET['cwbp_open'] ) ) {
            $queue_id = absint( wp_unslash( $_GET['cwbp_open'] ) );
            CWBP_Analytics::track_open( $queue_id );
            nocache_headers();
            header( 'Content-Type: image/gif' );
            echo base64_decode( 'R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==' );
            exit;
        }

        if ( isset( $_GET['cwbp_click'] ) ) {
            $queue_id = absint( wp_unslash( $_GET['cwbp_click'] ) );
            $url      = isset( $_GET['url'] ) ? esc_url_raw( wp_unslash( $_GET['url'] ) ) : home_url();
            CWBP_Analytics::track_click( $queue_id, $url );
            wp_safe_redirect( $url );
            exit;
        }
    }
);
