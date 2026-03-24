<?php
/**
 * Core bootstrap class.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWTS_Core {
	/**
	 * Admin module.
	 *
	 * @var CWTS_Admin
	 */
	private $admin;

	/**
	 * Frontend module.
	 *
	 * @var CWTS_Frontend
	 */
	private $frontend;

	/**
	 * Ajax module.
	 *
	 * @var CWTS_Ajax
	 */
	private $ajax;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->admin    = new CWTS_Admin();
		$this->frontend = new CWTS_Frontend();
		$this->ajax     = new CWTS_Ajax();

		add_action( 'init', array( $this, 'load_textdomain' ) );
		CWTS_Logger::log( 'Plugin initialized.' );
	}

	/**
	 * Load text domain.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'cashwala-testimonial-slider', false, dirname( CWTS_BASENAME ) . '/languages' );
	}
}
