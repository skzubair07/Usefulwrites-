<?php
if (!defined('ABSPATH')) {
    exit;
}

class CWFB_Logger {
    public static function log($message, $context = array()) {
        $upload_dir = wp_upload_dir();
        $log_dir    = trailingslashit($upload_dir['basedir']) . 'cashwala-funnel-builder-logs';

        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }

        $log_file  = trailingslashit($log_dir) . 'cwfb.log';
        $timestamp = current_time('mysql');
        $entry     = sprintf('[%s] %s %s', $timestamp, wp_json_encode($context), sanitize_text_field($message));

        file_put_contents($log_file, $entry . PHP_EOL, FILE_APPEND | LOCK_EX); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    }

    public static function get_logs($limit = 100) {
        $upload_dir = wp_upload_dir();
        $log_file   = trailingslashit($upload_dir['basedir']) . 'cashwala-funnel-builder-logs/cwfb.log';

        if (!file_exists($log_file)) {
            return array();
        }

        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file
        if (!is_array($lines)) {
            return array();
        }

        return array_slice(array_reverse($lines), 0, absint($limit));
    }
}
