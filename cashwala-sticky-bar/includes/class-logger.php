<?php

if (!defined('ABSPATH')) {
    exit;
}

class CW_SB_Logger {
    public static function log($message, $context = array()) {
        $logs = get_option(CW_SB_LOG_KEY, array());

        $entry = array(
            'time' => current_time('mysql'),
            'message' => sanitize_text_field($message),
            'context' => wp_json_encode($context),
        );

        $logs[] = $entry;

        if (count($logs) > 50) {
            $logs = array_slice($logs, -50);
        }

        update_option(CW_SB_LOG_KEY, $logs, false);
    }

    public static function get_logs() {
        $logs = get_option(CW_SB_LOG_KEY, array());
        return is_array($logs) ? array_reverse($logs) : array();
    }

    public static function clear() {
        update_option(CW_SB_LOG_KEY, array(), false);
    }
}
