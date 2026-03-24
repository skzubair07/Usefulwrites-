<?php
/**
 * Plugin Name: CashWala Shop
 * Description: Modular digital product sales system with Razorpay, secure delivery, affiliates, combos, leads and logging.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-shop
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CW_SHOP_VERSION', '1.0.0' );
define( 'CW_SHOP_FILE', __FILE__ );
define( 'CW_SHOP_PATH', plugin_dir_path( __FILE__ ) );
define( 'CW_SHOP_URL', plugin_dir_url( __FILE__ ) );

require_once CW_SHOP_PATH . 'includes/class-logger.php';
require_once CW_SHOP_PATH . 'includes/class-cpt.php';
require_once CW_SHOP_PATH . 'includes/class-admin.php';
require_once CW_SHOP_PATH . 'includes/class-payment.php';
require_once CW_SHOP_PATH . 'includes/class-download.php';
require_once CW_SHOP_PATH . 'includes/class-affiliate.php';
require_once CW_SHOP_PATH . 'includes/class-leads.php';
require_once CW_SHOP_PATH . 'includes/class-frontend.php';

final class CW_Shop {

	/** @var CW_Shop */
	private static $instance = null;

	/** @var CW_Logger */
	public $logger;

	/** @var CW_Admin */
	public $admin;

	/** @var CW_CPT */
	public $cpt;

	/** @var CW_Payment */
	public $payment;

	/** @var CW_Download */
	public $download;

	/** @var CW_Affiliate */
	public $affiliate;

	/** @var CW_Leads */
	public $leads;

	/** @var CW_Frontend */
	public $frontend;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->logger    = new CW_Logger();
		$this->cpt       = new CW_CPT( $this->logger );
		$this->admin     = new CW_Admin( $this->logger );
		$this->download  = new CW_Download( $this->logger );
		$this->affiliate = new CW_Affiliate( $this->logger );
		$this->leads     = new CW_Leads( $this->logger );
		$this->payment   = new CW_Payment( $this->logger, $this->download, $this->affiliate, $this->leads );
		$this->frontend  = new CW_Frontend( $this->logger );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'cashwala-shop', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public static function activate() {
		CW_Logger::maybe_prepare_log_directory();
		CW_Leads::create_table();
		CW_Affiliate::create_tables();
		CW_Payment::create_table();
		CW_Download::create_table();
		CW_Admin::set_default_options();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}

register_activation_hook( __FILE__, array( 'CW_Shop', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CW_Shop', 'deactivate' ) );

CW_Shop::instance();
