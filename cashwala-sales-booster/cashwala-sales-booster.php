<?php
/**
 * Plugin Name: CashWala Sales Booster
 * Plugin URI: https://cashwala.example.com/
 * Description: Conversion-focused urgency, scarcity, and trust widgets for digital product sales.
 * Version: 1.0.0
 * Author: CashWala
 * Text Domain: cashwala-sales-booster
 * Requires at least: 6.2
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CW_SB_VERSION', '1.0.0' );
define( 'CW_SB_FILE', __FILE__ );
define( 'CW_SB_PATH', plugin_dir_path( __FILE__ ) );
define( 'CW_SB_URL', plugin_dir_url( __FILE__ ) );

require_once CW_SB_PATH . 'includes/class-admin.php';
require_once CW_SB_PATH . 'includes/class-notifications.php';
require_once CW_SB_PATH . 'includes/class-timer.php';
require_once CW_SB_PATH . 'includes/class-counter.php';
require_once CW_SB_PATH . 'includes/class-cta.php';
require_once CW_SB_PATH . 'includes/class-analytics.php';

final class CashWala_Sales_Booster {

	/**
	 * Singleton instance.
	 *
	 * @var CashWala_Sales_Booster
	 */
	private static $instance;

	/**
	 * Plugin settings.
	 *
	 * @var array
	 */
	private $settings = array();

	/**
	 * Get singleton instance.
	 *
	 * @return CashWala_Sales_Booster
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->settings = $this->get_settings();

		register_activation_hook( CW_SB_FILE, array( __CLASS__, 'activate' ) );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render_frontend_components' ) );

		CashWala_SB_Admin::init();
		CashWala_SB_Analytics::init();
	}

	/**
	 * Activation defaults.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! get_option( 'cw_sb_settings' ) ) {
			update_option( 'cw_sb_settings', self::default_settings() );
		}

		if ( ! get_option( 'cw_sb_analytics' ) ) {
			update_option(
				'cw_sb_analytics',
				array(
					'impressions' => 0,
					'clicks'      => 0,
				)
			);
		}
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function default_settings() {
		return array(
			'notifications_enabled' => 1,
			'notifications_interval' => 8,
			'notifications_duration' => 4,
			'names'                  => "Rahul\nPriya\nAmit\nNeha\nArjun\nSneha",
			'cities'                 => "Mumbai\nDelhi\nBengaluru\nPune\nAhmedabad\nHyderabad",
			'products'               => "WhatsApp Plugin\nSEO Toolkit\nMarketing Bundle",
			'message_variations'     => "just bought\nnew order received for\nsomeone bought",
			'timer_enabled'          => 1,
			'timer_type'             => 'evergreen',
			'timer_duration'         => 600,
			'fixed_end_datetime'     => gmdate( 'Y-m-d\TH:i', strtotime( '+7 days' ) ),
			'counter_enabled'        => 1,
			'counter_min'            => 15,
			'counter_max'            => 42,
			'counter_refresh'        => 12,
			'low_stock_enabled'      => 1,
			'low_stock_mode'         => 'dynamic',
			'low_stock_static'       => 7,
			'low_stock_min'          => 3,
			'low_stock_max'          => 11,
			'low_stock_autodec'      => 1,
			'trust_badges_enabled'   => 1,
			'trust_badges'           => "Instant Download\nVerified Product\n1000+ Customers",
			'cta_enabled'            => 1,
			'cta_text'               => 'Get This Plugin Now - ₹99',
			'cta_button_text'        => 'Buy Now',
			'cta_link'               => '#',
			'cta_action'             => 'redirect',
			'cta_scroll_target'      => '#buy-now',
			'trigger_delay'          => 3,
			'trigger_scroll'         => 25,
			'trigger_exit_intent'    => 1,
			'primary_color'          => '#0f172a',
			'accent_color'           => '#16a34a',
			'text_color'             => '#ffffff',
			'background_color'       => '#111827',
		);
	}

	/**
	 * Get settings with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( 'cw_sb_settings', array() );
		return wp_parse_args( $settings, self::default_settings() );
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'cashwala-sales-booster', false, dirname( plugin_basename( CW_SB_FILE ) ) . '/languages/' );
	}

	/**
	 * Enqueue front-end assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() ) {
			return;
		}

		wp_enqueue_style( 'cw-sb-style', CW_SB_URL . 'assets/css/style.css', array(), CW_SB_VERSION );
		wp_enqueue_script( 'cw-sb-script', CW_SB_URL . 'assets/js/script.js', array(), CW_SB_VERSION, true );

		$payload = array(
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'cw_sb_track_event' ),
			'settings'  => $this->settings,
			'homeUrl'   => home_url( '/' ),
			'pageId'    => get_queried_object_id(),
			'isMobile'  => wp_is_mobile(),
		);

		wp_localize_script( 'cw-sb-script', 'CashWalaSB', $payload );
	}

	/**
	 * Render all enabled components.
	 *
	 * @return void
	 */
	public function render_frontend_components() {
		if ( is_admin() ) {
			return;
		}

		echo '<div id="cw-sb-root" class="cw-sb-root" aria-live="polite">';

		CashWala_SB_Notifications::render( $this->settings );
		CashWala_SB_Timer::render( $this->settings );
		CashWala_SB_Counter::render( $this->settings );
		CashWala_SB_CTA::render( $this->settings );

		echo '</div>';
	}
}

CashWala_Sales_Booster::instance();
