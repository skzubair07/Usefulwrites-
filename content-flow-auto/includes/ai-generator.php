<?php
/**
 * AI generator provider clients.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build prompt.
 *
 * @param string $title Article title.
 * @param int    $length Article length.
 * @return string
 */
function cfa_build_prompt( $title, $length = 1000 ) {
	$word_count = absint( $length );
	if ( $word_count < 300 ) {
		$word_count = 1000;
	}

	return "Write a {$word_count} word SEO friendly article about: {$title}\n\nRequirements:\n\nUse H2 and H3 headings\nMake it readable and informative\nAvoid plagiarism\nWrite in natural human style";
}

/**
 * Dispatch generation to a provider.
 *
 * @param string $provider Provider key.
 * @param string $title Article title.
 * @param array  $settings Settings array.
 * @return string|WP_Error
 */
function cfa_generate_with_provider( $provider, $title, $settings ) {
	$prompt = cfa_build_prompt( $title, isset( $settings['article_length'] ) ? (int) $settings['article_length'] : 1000 );

	switch ( $provider ) {
		case 'gemini':
			return cfa_call_gemini( $prompt, (string) ( $settings['gemini_api_key'] ?? '' ) );
		case 'groq':
			return cfa_call_groq( $prompt, (string) ( $settings['groq_api_key'] ?? '' ) );
		case 'cohere':
			return cfa_call_cohere( $prompt, (string) ( $settings['cohere_api_key'] ?? '' ) );
		case 'huggingface':
			return cfa_call_huggingface( $prompt, (string) ( $settings['huggingface_api_key'] ?? '' ) );
		case 'openai':
			return cfa_call_openai( $prompt, (string) ( $settings['openai_api_key'] ?? '' ) );
		default:
			return new WP_Error( 'cfa_invalid_provider', __( 'Invalid AI provider.', 'content-flow-auto' ) );
	}
}

/**
 * Perform wp_remote_post request.
 */
function cfa_remote_json_post( $url, $headers, $body ) {
	$response = wp_remote_post(
		$url,
		array(
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
			'timeout' => 60,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	$raw  = wp_remote_retrieve_body( $response );
	$json = json_decode( $raw, true );

	if ( $code < 200 || $code >= 300 ) {
		return new WP_Error( 'cfa_api_error', sprintf( 'HTTP %d: %s', (int) $code, is_string( $raw ) ? $raw : 'Unknown error' ) );
	}

	return is_array( $json ) ? $json : new WP_Error( 'cfa_invalid_json', __( 'Invalid API response JSON.', 'content-flow-auto' ) );
}

function cfa_call_gemini( $prompt, $api_key ) {
	if ( empty( $api_key ) ) {
		return new WP_Error( 'cfa_missing_key', 'Gemini API key missing.' );
	}

	$url  = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . rawurlencode( $api_key );
	$data = cfa_remote_json_post(
		$url,
		array( 'Content-Type' => 'application/json' ),
		array( 'contents' => array( array( 'parts' => array( array( 'text' => $prompt ) ) ) ) )
	);

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	return $data['candidates'][0]['content']['parts'][0]['text'] ?? new WP_Error( 'cfa_parse', 'Gemini response parse failed.' );
}

function cfa_call_groq( $prompt, $api_key ) {
	if ( empty( $api_key ) ) {
		return new WP_Error( 'cfa_missing_key', 'Groq API key missing.' );
	}

	$data = cfa_remote_json_post(
		'https://api.groq.com/openai/v1/chat/completions',
		array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		array(
			'model'    => 'llama-3.1-8b-instant',
			'messages' => array(
				array( 'role' => 'system', 'content' => 'You are an expert SEO writer.' ),
				array( 'role' => 'user', 'content' => $prompt ),
			),
		)
	);

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	return $data['choices'][0]['message']['content'] ?? new WP_Error( 'cfa_parse', 'Groq response parse failed.' );
}

function cfa_call_cohere( $prompt, $api_key ) {
	if ( empty( $api_key ) ) {
		return new WP_Error( 'cfa_missing_key', 'Cohere API key missing.' );
	}

	$data = cfa_remote_json_post(
		'https://api.cohere.com/v2/chat',
		array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		array(
			'model'    => 'command-r-plus',
			'messages' => array(
				array( 'role' => 'user', 'content' => $prompt ),
			),
		)
	);

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	return $data['message']['content'][0]['text'] ?? new WP_Error( 'cfa_parse', 'Cohere response parse failed.' );
}

function cfa_call_huggingface( $prompt, $api_key ) {
	if ( empty( $api_key ) ) {
		return new WP_Error( 'cfa_missing_key', 'HuggingFace API key missing.' );
	}

	$data = cfa_remote_json_post(
		'https://api-inference.huggingface.co/models/google/flan-t5-xxl',
		array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		array( 'inputs' => $prompt )
	);

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( isset( $data[0]['generated_text'] ) ) {
		return $data[0]['generated_text'];
	}

	return new WP_Error( 'cfa_parse', 'HuggingFace response parse failed.' );
}

function cfa_call_openai( $prompt, $api_key ) {
	if ( empty( $api_key ) ) {
		return new WP_Error( 'cfa_missing_key', 'OpenAI API key missing.' );
	}

	$data = cfa_remote_json_post(
		'https://api.openai.com/v1/chat/completions',
		array(
			'Content-Type'  => 'application/json',
			'Authorization' => 'Bearer ' . $api_key,
		),
		array(
			'model'    => 'gpt-4o-mini',
			'messages' => array(
				array( 'role' => 'system', 'content' => 'You are an expert SEO writer.' ),
				array( 'role' => 'user', 'content' => $prompt ),
			),
		)
	);

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	return $data['choices'][0]['message']['content'] ?? new WP_Error( 'cfa_parse', 'OpenAI response parse failed.' );
}
