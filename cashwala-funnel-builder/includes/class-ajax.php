<?php
if (!defined('ABSPATH')) {
    exit;
}

class CWFB_Ajax {
    private $stats_table;

    public function __construct() {
        global $wpdb;
        $this->stats_table = $wpdb->prefix . 'cw_funnel_stats';

        add_action('wp_ajax_cwfb_track_visit', array($this, 'track_visit'));
        add_action('wp_ajax_nopriv_cwfb_track_visit', array($this, 'track_visit'));
        add_action('wp_ajax_cwfb_track_conversion', array($this, 'track_conversion'));
        add_action('wp_ajax_nopriv_cwfb_track_conversion', array($this, 'track_conversion'));
    }

    public function track_visit() {
        $payload = $this->validate_request();
        if (is_wp_error($payload)) {
            wp_send_json_error(array('message' => $payload->get_error_message()), 400);
        }

        $ok = $this->increment_stat($payload['funnel_id'], $payload['step'], 'visits');
        if (!$ok) {
            CWFB_Logger::log('Visit tracking failed', $payload);
            wp_send_json_error(array('message' => __('Tracking failed', 'cashwala-funnel-builder')), 500);
        }

        wp_send_json_success(array('tracked' => true));
    }

    public function track_conversion() {
        $payload = $this->validate_request();
        if (is_wp_error($payload)) {
            wp_send_json_error(array('message' => $payload->get_error_message()), 400);
        }

        $ok = $this->increment_stat($payload['funnel_id'], $payload['step'], 'conversions');
        if (!$ok) {
            CWFB_Logger::log('Conversion tracking failed', $payload);
            wp_send_json_error(array('message' => __('Tracking failed', 'cashwala-funnel-builder')), 500);
        }

        wp_send_json_success(array('tracked' => true));
    }

    private function validate_request() {
        check_ajax_referer('cwfb_track_nonce', 'nonce');

        $funnel_id = isset($_POST['funnel_id']) ? absint($_POST['funnel_id']) : 0;
        $step      = isset($_POST['step']) ? sanitize_key(wp_unslash($_POST['step'])) : '';

        if ($funnel_id < 1 || !in_array($step, array('landing', 'checkout', 'thankyou'), true)) {
            return new WP_Error('invalid_payload', __('Invalid tracking payload', 'cashwala-funnel-builder'));
        }

        return array(
            'funnel_id' => $funnel_id,
            'step'      => $step,
        );
    }

    private function increment_stat($funnel_id, $step, $column) {
        if (!in_array($column, array('visits', 'conversions'), true)) {
            return false;
        }

        global $wpdb;
        $sql = $wpdb->prepare(
            "INSERT INTO {$this->stats_table} (funnel_id, step, visits, conversions)
             VALUES (%d, %s, %d, %d)
             ON DUPLICATE KEY UPDATE {$column} = {$column} + 1",
            $funnel_id,
            $step,
            'visits' === $column ? 1 : 0,
            'conversions' === $column ? 1 : 0
        );

        $result = $wpdb->query($sql); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        return false !== $result;
    }
}
