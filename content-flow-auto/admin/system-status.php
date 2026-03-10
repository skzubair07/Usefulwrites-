<?php
/**
 * System status page.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Count generated posts today.
 *
 * @return int
 */
function cfa_get_generated_posts_today_count() {
	$query = new WP_Query(
		array(
			'post_type'      => 'post',
			'post_status'    => array( 'draft', 'pending', 'publish', 'private' ),
			'date_query'     => array(
				array(
					'after'     => gmdate( 'Y-m-d 00:00:00', strtotime( current_time( 'mysql' ) ) ),
					'inclusive' => true,
				),
			),
			'meta_key'       => '_cfa_generated',
			'meta_value'     => '1',
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);

	return (int) $query->found_posts;
}

/**
 * API status table values.
 *
 * @return array
 */
function cfa_get_api_statuses() {
	$settings  = cfa_get_settings();
	$providers = cfa_get_provider_labels();
	$statuses  = array();

	foreach ( $providers as $key => $label ) {
		$field            = $key . '_api_key';
		if ( 'huggingface' === $key ) {
			$field = 'huggingface_api_key';
		}
		$statuses[ $label ] = ! empty( $settings[ $field ] ) ? __( 'Configured', 'content-flow-auto' ) : __( 'Missing key', 'content-flow-auto' );
	}

	return $statuses;
}

/**
 * Render status page.
 */
function cfa_render_system_status_page() {
	$api_statuses = cfa_get_api_statuses();
	$logs         = cfa_get_logs();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'System Status', 'content-flow-auto' ); ?></h1>

		<h2><?php echo esc_html__( 'API Connection Status', 'content-flow-auto' ); ?></h2>
		<table class="widefat striped">
			<thead><tr><th><?php echo esc_html__( 'Provider', 'content-flow-auto' ); ?></th><th><?php echo esc_html__( 'Status', 'content-flow-auto' ); ?></th></tr></thead>
			<tbody>
				<?php foreach ( $api_statuses as $provider => $status ) : ?>
					<tr><td><?php echo esc_html( $provider ); ?></td><td><?php echo esc_html( $status ); ?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<h2><?php echo esc_html__( 'Environment', 'content-flow-auto' ); ?></h2>
		<ul>
			<li><?php echo esc_html__( 'Generated posts today:', 'content-flow-auto' ) . ' ' . esc_html( (string) cfa_get_generated_posts_today_count() ); ?></li>
			<li><?php echo esc_html__( 'Queue size:', 'content-flow-auto' ) . ' ' . esc_html__( '0 (synchronous generation)', 'content-flow-auto' ); ?></li>
			<li><?php echo esc_html__( 'WordPress version:', 'content-flow-auto' ) . ' ' . esc_html( get_bloginfo( 'version' ) ); ?></li>
			<li><?php echo esc_html__( 'PHP version:', 'content-flow-auto' ) . ' ' . esc_html( PHP_VERSION ); ?></li>
			<li><?php echo esc_html__( 'Memory limit:', 'content-flow-auto' ) . ' ' . esc_html( wp_convert_hr_to_bytes( WP_MEMORY_LIMIT ) . ' bytes (' . WP_MEMORY_LIMIT . ')' ); ?></li>
		</ul>

		<h2><?php echo esc_html__( 'Error Log', 'content-flow-auto' ); ?></h2>
		<textarea class="large-text code" rows="18" readonly><?php echo esc_textarea( $logs ); ?></textarea>
	</div>
	<?php
}
