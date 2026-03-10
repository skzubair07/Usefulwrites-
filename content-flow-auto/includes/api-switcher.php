<?php
/**
 * API failover switcher.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Attempts AI providers in configured priority order.
 *
 * @param string $title Article title.
 * @param array  $settings Plugin settings.
 * @return string|WP_Error
 */
function cfa_api_switcher( $title, $settings ) {
	$priority = isset( $settings['api_priority'] ) && is_array( $settings['api_priority'] ) ? $settings['api_priority'] : array();
	$priority = array_values( array_unique( array_map( 'sanitize_key', $priority ) ) );

	if ( empty( $priority ) ) {
		$priority = array( 'gemini', 'groq', 'cohere', 'huggingface', 'openai' );
	}

	$last_error = new WP_Error( 'cfa_no_provider', __( 'No API provider succeeded.', 'content-flow-auto' ) );

	foreach ( $priority as $provider ) {
		cfa_log( ucfirst( $provider ) . ' API request sent' );
		try {
			$result = cfa_generate_with_provider( $provider, $title, $settings );
			if ( is_wp_error( $result ) ) {
				$last_error = $result;
				cfa_log( ucfirst( $provider ) . ' failed: ' . $result->get_error_message() );
				continue;
			}

			if ( ! empty( $result ) ) {
				cfa_log( 'Article generated successfully via ' . ucfirst( $provider ) );
				return $result;
			}

			$last_error = new WP_Error( 'cfa_empty_response', __( 'Empty response from provider.', 'content-flow-auto' ) );
			cfa_log( ucfirst( $provider ) . ' returned empty response' );
		} catch ( Exception $e ) {
			$last_error = new WP_Error( 'cfa_exception', $e->getMessage() );
			cfa_log( ucfirst( $provider ) . ' exception: ' . $e->getMessage() );
		}

		cfa_log( 'Switched to next provider' );
	}

	return $last_error;
}
