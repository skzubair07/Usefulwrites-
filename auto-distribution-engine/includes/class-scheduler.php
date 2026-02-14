<?php

if (! defined('ABSPATH')) {
    exit;
}

class UADE_Scheduler
{
    const CRON_HOOK = 'uade_process_queue_event';

    /** @var UADE_Queue */
    private $queue;

    /** @var UADE_Platform_Router */
    private $router;

    public function __construct(UADE_Queue $queue, UADE_Platform_Router $router)
    {
        $this->queue  = $queue;
        $this->router = $router;

        add_filter('cron_schedules', [$this, 'add_cron_interval']);
        add_action(self::CRON_HOOK, [$this, 'process_queue']);

        self::schedule_cron();
    }

    public function add_cron_interval($schedules)
    {
        $schedules['uade_every_five_minutes'] = [
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display'  => __('Every 5 Minutes (UADE)', 'auto-distribution-engine'),
        ];

        return $schedules;
    }

    public static function schedule_cron()
    {
        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, 'uade_every_five_minutes', self::CRON_HOOK);
        }
    }

    public static function unschedule_cron()
    {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
        }
    }

    public function process_queue()
    {
        $settings = UADE_Settings_Page::get_settings();

        if (empty($settings['general']['enabled'])) {
            return;
        }

        $limit = max(1, (int) $settings['general']['daily_limit']);
        $items = $this->queue->get_due_items($limit);

        foreach ($items as $item) {
            $post = get_post((int) $item->post_id);
            if (! $post || 'publish' !== $post->post_status) {
                $this->queue->update_item((int) $item->id, [
                    'status' => 'failed',
                ]);
                continue;
            }

            $result = $this->router->distribute_post($post, $item);

            $new_status = $result['all_success'] ? 'completed' : 'failed';

            $this->queue->update_item((int) $item->id, [
                'status'          => $new_status,
                'attempts'        => (int) $item->attempts + 1,
                'platform_status' => wp_json_encode($result['status_map']),
                'platform_error'  => wp_json_encode($result['errors']),
            ]);
        }
    }
}
