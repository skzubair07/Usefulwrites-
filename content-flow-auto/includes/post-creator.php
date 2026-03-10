<?php
/**
 * Post creation helpers.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Create post draft from generated content.
 *
 * @param string $title Article title.
 * @param string $content Generated content.
 * @param array  $settings Plugin settings.
 * @return int|WP_Error
 */
function cfa_create_generated_post( $title, $content, $settings ) {
	$post_status = isset( $settings['post_status'] ) ? sanitize_key( $settings['post_status'] ) : 'draft';
	if ( ! in_array( $post_status, array( 'draft', 'pending', 'publish', 'private' ), true ) ) {
		$post_status = 'draft';
	}

	$category = isset( $settings['default_category'] ) ? absint( $settings['default_category'] ) : 0;
	$postarr  = array(
		'post_title'   => sanitize_text_field( $title ),
		'post_content' => wp_kses_post( $content ),
		'post_status'  => $post_status,
		'post_type'    => 'post',
	);

	if ( $category > 0 ) {
		$postarr['post_category'] = array( $category );
	}

	$post_id = wp_insert_post( $postarr, true );

	if ( is_wp_error( $post_id ) ) {
		cfa_log( 'Post creation failed: ' . $post_id->get_error_message() );
	} else {
		update_post_meta( $post_id, '_cfa_generated', '1' );
		cfa_log( 'Post created with ID ' . $post_id );
	}

	return $post_id;
}
