<?php
/**
 * Counter and stock template.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cw-sb-widget-stack" data-cw-sb-widget="counter" hidden>
	<div class="cw-sb-widget cw-sb-counter">
		<span class="cw-sb-icon" aria-hidden="true">👀</span>
		<span class="cw-sb-counter-text"><?php esc_html_e( '27 people are viewing this page', 'cashwala-sales-booster' ); ?></span>
	</div>
	<div class="cw-sb-widget cw-sb-stock" data-cw-sb-widget="stock">
		<span class="cw-sb-icon" aria-hidden="true">⚡</span>
		<span class="cw-sb-stock-text"><?php esc_html_e( 'Only 5 copies left', 'cashwala-sales-booster' ); ?></span>
	</div>
	<ul class="cw-sb-badges" data-cw-sb-widget="badges"></ul>
</div>
