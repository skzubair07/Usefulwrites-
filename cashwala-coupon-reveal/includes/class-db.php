<?php
if (! defined('ABSPATH')) {
    exit;
}

class CWCR_DB
{
    const DB_VERSION = '1.0.0';

    public static function activate()
    {
        self::create_tables();
        add_option('cwcr_db_version', self::DB_VERSION);

        if (! get_option('cwcr_settings')) {
            add_option('cwcr_settings', self::default_settings());
        }

        if (! get_option('cwcr_analytics')) {
            add_option('cwcr_analytics', [
                'views'       => 0,
                'reveals'     => 0,
                'conversions' => 0,
            ]);
        }
    }

    public static function maybe_upgrade()
    {
        if (get_option('cwcr_db_version') !== self::DB_VERSION) {
            self::create_tables();
            update_option('cwcr_db_version', self::DB_VERSION);
        }
    }

    public static function create_tables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cw_coupon_leads';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) DEFAULT '' NOT NULL,
            phone VARCHAR(50) DEFAULT '' NOT NULL,
            coupon VARCHAR(100) DEFAULT '' NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY email (email),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function insert_lead($email, $phone, $coupon)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'cw_coupon_leads';

        $result = $wpdb->insert(
            $table_name,
            [
                'email'      => sanitize_email($email),
                'phone'      => sanitize_text_field($phone),
                'coupon'     => sanitize_text_field($coupon),
                'created_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s']
        );

        if (false === $result) {
            CWCR_Logger::log('error', 'Lead insert failed', ['db_error' => $wpdb->last_error]);
            return false;
        }

        return true;
    }

    public static function default_settings()
    {
        return [
            'enabled'              => 1,
            'display_mode'         => 'popup',
            'coupon_codes'         => 'SAVE10',
            'dynamic_coupon'       => 0,
            'expiry_minutes'       => 15,
            'reveal_action'        => 'click',
            'trigger_type'         => 'page_load',
            'trigger_delay'        => 3,
            'trigger_scroll'       => 50,
            'trigger_exit_intent'  => 0,
            'lead_email'           => 1,
            'lead_phone'           => 0,
            'lead_required'        => 1,
            'bg_color'             => '#111827',
            'text_color'           => '#ffffff',
            'button_style'         => 'rounded',
            'animation_style'      => 'fade-up',
            'message_before'       => 'Unlock your exclusive deal by completing a quick action.',
            'message_after'        => 'Success! Your discount is ready. Copy and use at checkout.',
            'inline_title'         => 'Unlock Your Discount',
        ];
    }
}
