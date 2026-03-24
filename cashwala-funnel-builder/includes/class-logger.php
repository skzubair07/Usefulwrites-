<?php

if (! defined('ABSPATH')) {
    exit;
}

class CWFB_Logger
{
    const OPTION_KEY = 'cwfb_logs';
    const MAX_LOGS = 200;

    public static function log($level, $message, array $context = array())
    {
        $logs = get_option(self::OPTION_KEY, array());
        if (! is_array($logs)) {
            $logs = array();
        }

        $logs[] = array(
            'time'    => current_time('mysql'),
            'level'   => sanitize_text_field($level),
            'message' => sanitize_text_field($message),
            'context' => wp_json_encode($context),
        );

        if (count($logs) > self::MAX_LOGS) {
            $logs = array_slice($logs, -1 * self::MAX_LOGS);
        }

        update_option(self::OPTION_KEY, $logs, false);
    }

    public static function get_logs()
    {
        $logs = get_option(self::OPTION_KEY, array());
        return is_array($logs) ? array_reverse($logs) : array();
    }

    public static function clear_logs()
    {
        delete_option(self::OPTION_KEY);
    }
}
