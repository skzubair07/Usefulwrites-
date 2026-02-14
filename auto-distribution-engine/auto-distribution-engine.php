<?php
/**
 * Plugin Name: Universal Auto Distribution Engine
 * Description: Automatically distribute WordPress posts to multiple social platforms after publishing.
 * Version: 1.0.0
 * Author: Usefulwrites
 * Text Domain: auto-distribution-engine
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! defined('UADE_VERSION')) {
    define('UADE_VERSION', '1.0.0');
}

if (! defined('UADE_PLUGIN_FILE')) {
    define('UADE_PLUGIN_FILE', __FILE__);
}

if (! defined('UADE_PLUGIN_DIR')) {
    define('UADE_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (! defined('UADE_PLUGIN_URL')) {
    define('UADE_PLUGIN_URL', plugin_dir_url(__FILE__));
}

require_once UADE_PLUGIN_DIR . 'includes/class-queue.php';
require_once UADE_PLUGIN_DIR . 'includes/class-scheduler.php';
require_once UADE_PLUGIN_DIR . 'includes/class-platform-router.php';
require_once UADE_PLUGIN_DIR . 'admin/settings-page.php';
require_once UADE_PLUGIN_DIR . 'admin/queue-page.php';

final class UADE_Plugin
{
    /** @var UADE_Plugin|null */
    private static $instance = null;

    /** @var UADE_Queue */
    private $queue;

    /** @var UADE_Scheduler */
    private $scheduler;

    /** @var UADE_Platform_Router */
    private $router;

    public static function instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->queue     = new UADE_Queue();
        $this->router    = new UADE_Platform_Router();
        $this->scheduler = new UADE_Scheduler($this->queue, $this->router);

        add_action('init', [$this, 'register_post_status']);
        add_action('save_post_post', [$this, 'handle_post_save'], 20, 3);
        add_action('admin_init', [$this, 'register_settings']);

        new UADE_Settings_Page($this->queue, $this->router);
        new UADE_Queue_Page($this->queue, $this->router);
    }

    public function register_post_status()
    {
        register_post_status('uade_pending_approval', [
            'label'                     => _x('Pending Distribution Approval', 'post status', 'auto-distribution-engine'),
            'public'                    => false,
            'internal'                  => true,
            'protected'                 => true,
            'show_in_admin_status_list' => false,
            'show_in_admin_all_list'    => false,
        ]);
    }

    public function register_settings()
    {
        register_setting('uade_settings_group', UADE_Settings_Page::OPTION_KEY, [
            'type'              => 'array',
            'sanitize_callback' => [UADE_Settings_Page::class, 'sanitize_settings'],
            'default'           => UADE_Settings_Page::default_settings(),
        ]);
    }

    public function handle_post_save($post_id, $post, $update)
    {
        if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
            return;
        }

        if ('publish' !== get_post_status($post_id)) {
            return;
        }

        if ('post' !== $post->post_type) {
            return;
        }

        if ('auto-draft' === $post->post_status) {
            return;
        }

        $settings = UADE_Settings_Page::get_settings();
        if (empty($settings['general']['enabled'])) {
            return;
        }

        if ($this->queue->has_post($post_id)) {
            return;
        }

        $this->queue->enqueue_post($post_id, ! empty($settings['general']['manual_approval']));
    }

    public static function activate()
    {
        $queue = new UADE_Queue();
        $queue->create_table();

        UADE_Scheduler::schedule_cron();
    }

    public static function deactivate()
    {
        UADE_Scheduler::unschedule_cron();
    }
}

register_activation_hook(__FILE__, ['UADE_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['UADE_Plugin', 'deactivate']);

UADE_Plugin::instance();
