<?php
/**
 * Uninstall routine for CashWala Sales Booster.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cw_sb_settings' );
delete_option( 'cw_sb_analytics' );
