<?php
/**
 * Bulk generator admin page.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle article generation form.
 *
 * @return array
 */
function cfa_handle_generation_submission() {
	$result = array(
		'created' => 0,
		'failed'  => 0,
		'errors'  => array(),
	);

	if ( ! isset( $_POST['cfa_generate_articles'] ) ) {
		return $result;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		$result['errors'][] = __( 'Insufficient permissions.', 'content-flow-auto' );
		return $result;
	}

	check_admin_referer( 'cfa_generate_articles_action', 'cfa_generate_articles_nonce' );

	$raw_titles = isset( $_POST['cfa_titles'] ) ? wp_unslash( $_POST['cfa_titles'] ) : '';
	$lines      = preg_split( '/\r\n|\r|\n/', (string) $raw_titles );
	$titles     = array_filter(
		array_map(
			'sanitize_text_field',
			array_map( 'trim', (array) $lines )
		)
	);

	if ( empty( $titles ) ) {
		$result['errors'][] = __( 'Please enter at least one title.', 'content-flow-auto' );
		return $result;
	}

	$settings = get_option( 'cfa_settings', array() );

	foreach ( $titles as $title ) {
		$content = cfa_api_switcher( $title, $settings );
		if ( is_wp_error( $content ) ) {
			$result['failed']++;
			$result['errors'][] = sprintf( __( '"%s" failed: %s', 'content-flow-auto' ), $title, $content->get_error_message() );
			cfa_log( 'Generation failed for "' . $title . '": ' . $content->get_error_message() );
			continue;
		}

		$post_id = cfa_create_generated_post( $title, $content, $settings );
		if ( is_wp_error( $post_id ) ) {
			$result['failed']++;
			$result['errors'][] = sprintf( __( '"%s" post creation failed: %s', 'content-flow-auto' ), $title, $post_id->get_error_message() );
			continue;
		}

		$result['created']++;
	}

	return $result;
}

/**
 * Render bulk generator page.
 *
 * @return void
 */
function cfa_render_bulk_generator_page() {
	$submission_result = cfa_handle_generation_submission();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Generate Articles', 'content-flow-auto' ); ?></h1>
		<p><?php echo esc_html__( 'Enter one article title per line and click generate.', 'content-flow-auto' ); ?></p>

		<?php if ( $submission_result['created'] > 0 ) : ?>
			<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( '%d draft posts created successfully.', 'content-flow-auto' ), $submission_result['created'] ) ); ?></p></div>
		<?php endif; ?>

		<?php if ( $submission_result['failed'] > 0 || ! empty( $submission_result['errors'] ) ) : ?>
			<div class="notice notice-error"><p><?php echo esc_html__( 'Some items failed.', 'content-flow-auto' ); ?></p>
				<ul>
					<?php foreach ( $submission_result['errors'] as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<form method="post">
			<?php wp_nonce_field( 'cfa_generate_articles_action', 'cfa_generate_articles_nonce' ); ?>
			<textarea name="cfa_titles" rows="12" class="large-text" placeholder="AI future of jobs
Best AI tools 2026
How AI will change business"></textarea>
			<p>
				<button type="submit" name="cfa_generate_articles" class="button button-primary"><?php echo esc_html__( 'Generate Draft Articles', 'content-flow-auto' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}
