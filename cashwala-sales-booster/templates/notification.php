<?php
/**
 * Notification template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cw-sb-widget cw-sb-notification" data-cw-sb-widget="notification" hidden>
	<div class="cw-sb-notification-dot" aria-hidden="true"></div>
	<div class="cw-sb-notification-content">
		<div class="cw-sb-label"><?php esc_html_e( 'Live Sales', 'cashwala-sales-booster' ); ?></div>
		<p class="cw-sb-message"></p>
	</div>
</div>
