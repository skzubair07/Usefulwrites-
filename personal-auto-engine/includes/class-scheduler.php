<?php

if (! defined('ABSPATH')) {
    exit;
}

class PADE_Scheduler {
    public static function register_hooks(): void {
        add_filter('cron_schedules', [self::class, 'add_interval']);
    }

    public static function add_interval(array $schedules): array {
        $schedules['pade_five_minutes'] = [
            'interval' => 300,
            'display'  => __('Every 5 Minutes (PADE)', 'personal-auto-engine'),
        ];

        return $schedules;
    }

    public static function schedule(): void {
        if (! wp_next_scheduled('pade_process_queue_event')) {
            wp_schedule_event(time() + 120, 'hourly', 'pade_process_queue_event');
        }
    }

    public static function unschedule(): void {
        $timestamp = wp_next_scheduled('pade_process_queue_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'pade_process_queue_event');
        }
    }
}
