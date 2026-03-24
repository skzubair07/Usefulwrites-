<?php
/**
 * Counter and stock component renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CashWala_SB_Counter {

	/**
	 * Render counter and stock alert.
	 *
	 * @param array $settings Plugin settings.
	 * @return void
	 */
	public static function render( $settings ) {
		if ( empty( $settings['counter_enabled'] ) && empty( $settings['low_stock_enabled'] ) ) {
			return;
		}

		$template = CW_SB_PATH . 'templates/counter.php';
		if ( file_exists( $template ) ) {
			require $template;
		}
	}
}
