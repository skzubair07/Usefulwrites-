<?php
/**
 * Database installer and schema manager.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Database {

    /**
     * Create all plugin tables.
     */
    public static function install() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $prefix          = $wpdb->prefix . 'do_';

        $tables = array();
        $tables[] = "CREATE TABLE {$prefix}masail (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            answer LONGTEXT NOT NULL,
            source_url VARCHAR(255) DEFAULT '',
            question_hash CHAR(32) DEFAULT '',
            keywords TEXT,
            category VARCHAR(120) DEFAULT '',
            likes BIGINT UNSIGNED DEFAULT 0,
            shares BIGINT UNSIGNED DEFAULT 0,
            views BIGINT UNSIGNED DEFAULT 0,
            ai_generated TINYINT(1) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY question_hash (question_hash)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}tokens (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            balance INT NOT NULL DEFAULT 0,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}subscriptions (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            plan_key VARCHAR(80) NOT NULL,
            limits_json LONGTEXT,
            started_at DATETIME NOT NULL,
            expires_at DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            PRIMARY KEY (id)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}affiliate_referrals (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            referrer_user_id BIGINT UNSIGNED NOT NULL,
            referred_user_id BIGINT UNSIGNED NOT NULL,
            commission_amount DECIMAL(10,2) DEFAULT 0,
            status VARCHAR(20) DEFAULT 'pending',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}complaints (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            subject VARCHAR(190) DEFAULT '',
            status VARCHAR(20) DEFAULT 'open',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}complaint_messages (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            complaint_id BIGINT UNSIGNED NOT NULL,
            sender_type VARCHAR(20) NOT NULL,
            message LONGTEXT NOT NULL,
            image_url VARCHAR(255) DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}payments (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(30) NOT NULL,
            reference VARCHAR(100) DEFAULT '',
            status VARCHAR(20) DEFAULT 'pending',
            meta_json LONGTEXT,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY reference (reference)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}payment_credits (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            transaction_id VARCHAR(120) NOT NULL,
            tokens INT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY transaction_id (transaction_id)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}translations (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            original_text LONGTEXT NOT NULL,
            translated_text LONGTEXT NOT NULL,
            language VARCHAR(30) NOT NULL,
            source_hash CHAR(32) NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY source_hash (source_hash)
        ) $charset_collate";

        $tables[] = "CREATE TABLE {$prefix}logs (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(30) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate";

        foreach ( $tables as $sql ) {
            dbDelta( $sql );
        }
    }

    /**
     * Create default options.
     */
    public static function seed_options() {
        add_option( 'do_general_settings', array() );
        add_option( 'do_api_settings', array() );
        add_option( 'do_token_settings', array( 'price_per_token' => 10, 'packages' => array() ) );
        add_option( 'do_subscription_settings', array() );
        add_option( 'do_affiliate_settings', array( 'commission_percent' => 10 ) );
        add_option( 'do_import_settings', array( 'sources' => array(), 'import_limit' => 10 ) );
        add_option( 'do_payment_settings', array() );
        add_option( 'do_notification_settings', array() );
        add_option( 'do_news_settings', array() );
        add_option( 'do_prayer_settings', array() );
        add_option( 'do_content_builder_blocks', array() );
        add_option( 'do_search_settings', array( 'question_weight' => 20, 'keyword_weight' => 10, 'answer_weight' => 5 ) );
        add_option( 'do_ai_settings', array( 'enabled' => 1, 'timeout' => 20, 'retry' => 2, 'primary' => 'grok', 'backup' => 'openai' ) );
        add_option( 'do_translation_settings', array( 'enabled' => 1, 'provider' => 'google', 'local_ai' => 0 ) );
        add_option( 'do_rate_limit_settings', array( 'max_per_minute' => 5 ) );
        add_option( 'do_trending_settings', array( 'enabled' => 1 ) );
    }
}
