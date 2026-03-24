<?php

if (! defined('ABSPATH')) {
    exit;
}

class CWFB_DB
{
    const DB_VERSION = '1.0.0';

    public static function create_tables()
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $funnels_table = $wpdb->prefix . 'cw_funnels';
        $stats_table = $wpdb->prefix . 'cw_funnel_stats';

        $sql_funnels = "CREATE TABLE {$funnels_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(191) NOT NULL,
            landing_page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            checkout_page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            thankyou_page_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            status VARCHAR(20) NOT NULL DEFAULT 'inactive',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY status (status)
        ) {$charset_collate};";

        $sql_stats = "CREATE TABLE {$stats_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            funnel_id BIGINT(20) UNSIGNED NOT NULL,
            step VARCHAR(20) NOT NULL,
            visits BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            conversions BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY funnel_step (funnel_id, step)
        ) {$charset_collate};";

        dbDelta($sql_funnels);
        dbDelta($sql_stats);

        update_option('cwfb_db_version', self::DB_VERSION, false);
    }

    public static function get_funnels($only_active = false)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnels';
        if ($only_active) {
            return $wpdb->get_results("SELECT * FROM {$table} WHERE status = 'active' ORDER BY id DESC", ARRAY_A);
        }
        return $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);
    }

    public static function get_funnel($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnels';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", absint($id)), ARRAY_A);
    }

    public static function save_funnel(array $data, $id = 0)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnels';

        $payload = array(
            'name'             => sanitize_text_field($data['name'] ?? ''),
            'landing_page_id'  => absint($data['landing_page_id'] ?? 0),
            'checkout_page_id' => absint($data['checkout_page_id'] ?? 0),
            'thankyou_page_id' => absint($data['thankyou_page_id'] ?? 0),
            'status'           => (($data['status'] ?? 'inactive') === 'active') ? 'active' : 'inactive',
        );

        if (empty($payload['name'])) {
            return new WP_Error('cwfb_empty_name', __('Funnel name is required.', 'cashwala-funnel-builder'));
        }

        if ($id > 0) {
            $updated = $wpdb->update($table, $payload, array('id' => absint($id)), array('%s', '%d', '%d', '%d', '%s'), array('%d'));
            if ($updated === false) {
                CWFB_Logger::log('error', 'Failed to update funnel', array('id' => $id, 'db_error' => $wpdb->last_error));
                return new WP_Error('cwfb_save_failed', __('Unable to update funnel.', 'cashwala-funnel-builder'));
            }
            self::ensure_stats_rows(absint($id));
            return absint($id);
        }

        $payload['created_at'] = current_time('mysql');
        $inserted = $wpdb->insert($table, $payload, array('%s', '%d', '%d', '%d', '%s', '%s'));
        if (! $inserted) {
            CWFB_Logger::log('error', 'Failed to insert funnel', array('db_error' => $wpdb->last_error));
            return new WP_Error('cwfb_insert_failed', __('Unable to create funnel.', 'cashwala-funnel-builder'));
        }

        $funnel_id = (int) $wpdb->insert_id;
        self::ensure_stats_rows($funnel_id);
        return $funnel_id;
    }

    public static function delete_funnel($id)
    {
        global $wpdb;
        $funnels = $wpdb->prefix . 'cw_funnels';
        $stats = $wpdb->prefix . 'cw_funnel_stats';

        $wpdb->delete($stats, array('funnel_id' => absint($id)), array('%d'));
        $deleted = $wpdb->delete($funnels, array('id' => absint($id)), array('%d'));

        if ($deleted === false) {
            CWFB_Logger::log('error', 'Failed to delete funnel', array('id' => $id, 'db_error' => $wpdb->last_error));
            return false;
        }
        return true;
    }

    public static function ensure_stats_rows($funnel_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnel_stats';
        $steps = array('landing', 'checkout', 'thankyou');

        foreach ($steps as $step) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE funnel_id = %d AND step = %s", $funnel_id, $step));
            if (! $exists) {
                $wpdb->insert($table, array(
                    'funnel_id'    => absint($funnel_id),
                    'step'         => $step,
                    'visits'       => 0,
                    'conversions'  => 0,
                ), array('%d', '%s', '%d', '%d'));
            }
        }
    }

    public static function increment_visit($funnel_id, $step)
    {
        self::increment_counter($funnel_id, $step, 'visits');
    }

    public static function increment_conversion($funnel_id, $step)
    {
        self::increment_counter($funnel_id, $step, 'conversions');
    }

    private static function increment_counter($funnel_id, $step, $column)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnel_stats';
        $funnel_id = absint($funnel_id);
        $step = sanitize_key($step);
        if (! in_array($step, array('landing', 'checkout', 'thankyou'), true)) {
            return;
        }

        self::ensure_stats_rows($funnel_id);
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET {$column} = {$column} + 1 WHERE funnel_id = %d AND step = %s", $funnel_id, $step));
    }

    public static function get_stats($funnel_id = 0)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnel_stats';

        if ($funnel_id > 0) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE funnel_id = %d", absint($funnel_id)), ARRAY_A);
        }

        return $wpdb->get_results("SELECT * FROM {$table}", ARRAY_A);
    }

    public static function get_funnel_by_page($page_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'cw_funnels';
        $page_id = absint($page_id);

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} WHERE status = 'active' AND (landing_page_id = %d OR checkout_page_id = %d OR thankyou_page_id = %d) LIMIT 1",
            $page_id,
            $page_id,
            $page_id
        ), ARRAY_A);
    }
}
