<?php
/**
 * Plugin Name: CashWala Razorpay Quick Sell Pro
 * Description: Production-ready digital product selling plugin with Razorpay, secure delivery, email automation, analytics, and logs.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-quick-sell-pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CWQSP_VERSION', '1.0.0' );
define( 'CWQSP_FILE', __FILE__ );
define( 'CWQSP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CWQSP_URL', plugin_dir_url( __FILE__ ) );

require_once CWQSP_PATH . 'includes/class-cwqsp-logger.php';
require_once CWQSP_PATH . 'includes/class-cwqsp-admin.php';
require_once CWQSP_PATH . 'includes/class-cwqsp-cpt.php';
require_once CWQSP_PATH . 'includes/class-cwqsp-download.php';
require_once CWQSP_PATH . 'includes/class-cwqsp-payment.php';
require_once CWQSP_PATH . 'includes/class-cwqsp-frontend.php';

final class CWQSP_Plugin {
	/** @var CWQSP_Plugin */
	private static $instance = null;

	/** @var CWQSP_Logger */
	public $logger;

	/** @var CWQSP_Admin */
	public $admin;

	/** @var CWQSP_CPT */
	public $cpt;

	/** @var CWQSP_Download */
	public $download;

	/** @var CWQSP_Payment */
	public $payment;

	/** @var CWQSP_Frontend */
	public $frontend;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->logger   = new CWQSP_Logger();
		$this->admin    = new CWQSP_Admin( $this->logger );
		$this->cpt      = new CWQSP_CPT();
		$this->download = new CWQSP_Download( $this->logger );
		$this->payment  = new CWQSP_Payment( $this->logger, $this->download );
		$this->frontend = new CWQSP_Frontend();
	}

	public static function activate() {
		CWQSP_Logger::maybe_prepare_log_directory();
		CWQSP_Admin::set_default_options();
		CWQSP_Download::create_table();
		CWQSP_Payment::create_table();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'CWQSP_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CWQSP_Plugin', 'deactivate' ) );

CWQSP_Plugin::instance();
