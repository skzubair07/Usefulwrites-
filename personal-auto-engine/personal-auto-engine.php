<?php
/**
 * Plugin Name: Personal Auto Distribution Engine
 * Description: Personal automated content distribution with AI integration and diagnostic tools.
 * Version: 1.0.0
 * Author: Usefulwrites
 * Text Domain: personal-auto-engine
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PADE_VERSION', '1.0.0');
define('PADE_PLUGIN_FILE', __FILE__);
define('PADE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PADE_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once PADE_PLUGIN_DIR . 'includes/class-database.php';
require_once PADE_PLUGIN_DIR . 'includes/class-logger.php';
require_once PADE_PLUGIN_DIR . 'includes/class-settings.php';
require_once PADE_PLUGIN_DIR . 'includes/class-ai-engine.php';
require_once PADE_PLUGIN_DIR . 'includes/class-router.php';
require_once PADE_PLUGIN_DIR . 'includes/class-queue.php';
require_once PADE_PLUGIN_DIR . 'includes/class-scheduler.php';

require_once PADE_PLUGIN_DIR . 'platforms/class-telegram.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-pinterest.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-facebook.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-twitter.php';
require_once PADE_PLUGIN_DIR . 'platforms/class-linkedin.php';

require_once PADE_PLUGIN_DIR . 'admin/page-settings.php';
require_once PADE_PLUGIN_DIR . 'admin/page-control-center.php';
require_once PADE_PLUGIN_DIR . 'admin/page-queue.php';

final class PADE_Plugin {
    private static ?PADE_Plugin $instance = null;

    private PADE_Database $database;
    private PADE_Settings $settings;
    private PADE_Logger $logger;
    private PADE_AI_Engine $ai_engine;
    private PADE_Router $router;
    private PADE_Queue $queue;

    public static function instance(): PADE_Plugin {
        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->database = new PADE_Database();
        $this->settings = new PADE_Settings();
        $this->logger = new PADE_Logger($this->database);
        $this->ai_engine = new PADE_AI_Engine($this->settings, $this->logger);
        $this->router = new PADE_Router($this->settings, $this->logger);
        $this->queue = new PADE_Queue($this->database, $this->settings, $this->ai_engine, $this->router, $this->logger);

        new PADE_Admin_Settings_Page($this->settings, $this->database, $this->logger, $this->queue, $this->router);
        new PADE_Admin_Control_Center_Page($this->database, $this->logger, $this->queue, $this->ai_engine, $this->router);
        new PADE_Admin_Queue_Page($this->database, $this->queue);

        add_action('publish_post', [$this->queue, 'handle_publish'], 10, 2);
        add_action('init', ['PADE_Scheduler', 'register_hooks']);
    }

    public static function activate(): void {
        $database = new PADE_Database();
        $database->create_tables();
        PADE_Scheduler::schedule();
    }

    public static function deactivate(): void {
        PADE_Scheduler::unschedule();
    }
}

register_activation_hook(__FILE__, ['PADE_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['PADE_Plugin', 'deactivate']);

PADE_Plugin::instance();
