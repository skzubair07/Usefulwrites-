<?php
if (! defined('ABSPATH')) {
    exit;
}

class CWCR_Logger
{
    const OPTION_KEY = 'cwcr_logs';
    const MAX_LOGS = 200;

    public static function activate()
    {
        if (! get_option(self::OPTION_KEY)) {
            add_option(self::OPTION_KEY, []);
        }
    }

    public static function log($level, $message, array $context = [])
    {
        $logs = get_option(self::OPTION_KEY, []);
        if (! is_array($logs)) {
            $logs = [];
        }

        $logs[] = [
            'time'    => current_time('mysql'),
            'level'   => sanitize_text_field($level),
            'message' => sanitize_text_field($message),
            'context' => wp_json_encode($context),
        ];

        if (count($logs) > self::MAX_LOGS) {
            $logs = array_slice($logs, -1 * self::MAX_LOGS);
        }

        update_option(self::OPTION_KEY, $logs, false);
    }

    public static function latest($limit = 50)
    {
        $logs = get_option(self::OPTION_KEY, []);
        if (! is_array($logs)) {
            return [];
        }
        return array_reverse(array_slice($logs, -1 * absint($limit)));
    }
}
