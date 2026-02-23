<?php
/**
 * Structured debug logger for AstraFlow AI Core.
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

class Astra_Debugger {
    private const OPTION_KEY = 'astraflow_ai_core_debug_logs';
    private const LOG_LIMIT = 50;

    /**
     * @param array<string,mixed> $entry
     */
    public function log(array $entry): void {
        $logs = $this->get_logs();

        $logs[] = array(
            'timestamp' => current_time('mysql', true),
            'event'     => $entry['event'] ?? 'unknown',
            'context'   => $entry['context'] ?? array(),
        );

        if (count($logs) > self::LOG_LIMIT) {
            $logs = array_slice($logs, -self::LOG_LIMIT);
        }

        update_option(self::OPTION_KEY, $logs, false);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function get_logs(): array {
        $logs = get_option(self::OPTION_KEY, array());
        return is_array($logs) ? $logs : array();
    }
}
