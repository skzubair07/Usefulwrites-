<?php
/**
 * Timer component render logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CashWala_SB_Timer {

	/**
	 * Render timer.
	 *
	 * @param array $settings Plugin settings.
	 * @return void
	 */
	public static function render( $settings ) {
		if ( empty( $settings['timer_enabled'] ) ) {
			return;
		}

		$template = CW_SB_PATH . 'templates/timer.php';
		if ( file_exists( $template ) ) {
			require $template;
		}
	}
}
