<?php
/**
 * Notifications render logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CashWala_SB_Notifications {

	/**
	 * Render notifications UI.
	 *
	 * @param array $settings Plugin settings.
	 * @return void
	 */
	public static function render( $settings ) {
		if ( empty( $settings['notifications_enabled'] ) ) {
			return;
		}

		$template = CW_SB_PATH . 'templates/notification.php';
		if ( file_exists( $template ) ) {
			require $template;
		}
	}
}
