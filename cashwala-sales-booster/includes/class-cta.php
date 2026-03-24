<?php
/**
 * Sticky CTA renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CashWala_SB_CTA {

	/**
	 * Render CTA bar.
	 *
	 * @param array $settings Plugin settings.
	 * @return void
	 */
	public static function render( $settings ) {
		if ( empty( $settings['cta_enabled'] ) ) {
			return;
		}

		$template = CW_SB_PATH . 'templates/cta-bar.php';
		if ( file_exists( $template ) ) {
			require $template;
		}
	}
}
