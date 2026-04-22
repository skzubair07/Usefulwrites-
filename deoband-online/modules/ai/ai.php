<?php
/**
 * AI answer module with primary/backup integrations and logs.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_AI_Module {

    /**
     * Default controlled answer prompt.
     */
    const DEFAULT_ANSWER_PROMPT = "Answer strictly based on Darul Uloom Deoband and Binori Town sources. Do not generate your own fatwa. If not found, say 'answer will be provided later'.";

    public static function init() {
        add_action( 'wp_ajax_do_ai_answer', array( __CLASS__, 'handle_ai_answer' ) );
        add_action( 'wp_ajax_nopriv_do_ai_answer', array( __CLASS__, 'handle_ai_answer' ) );
    }

    /**
     * Generate AI answer from AJAX request.
     */
    public static function handle_ai_answer() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );

        $rate = DO_Rate_Limit::check( 'ask_question', self::rate_limit_max(), 60 );
        if ( is_wp_error( $rate ) ) {
            wp_send_json_error( array( 'message' => $rate->get_error_message() ), 429 );
        }

        $question = sanitize_textarea_field( wp_unslash( $_POST['question'] ?? '' ) );
        if ( '' === $question ) {
            wp_send_json_error( array( 'message' => 'Question required.' ) );
        }

        $global = get_option( 'do_global_controls', array() );
        if ( ! empty( $global['disable_ai'] ) ) {
            wp_send_json_error( array( 'message' => 'AI is currently disabled by admin.' ) );
        }

        if ( ! empty( $global['force_manual_answers'] ) ) {
            wp_send_json_success( array( 'answer' => 'answer will be provided later' ) );
        }

        $answer = self::generate_answer( $question );
        if ( is_wp_error( $answer ) ) {
            DO_Logger::log( 'AI', 'AI failure: ' . $answer->get_error_message() );
            wp_send_json_error( array( 'message' => $answer->get_error_message() ) );
        }

        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'do_masail',
            array(
                'question'      => $question,
                'question_hash' => md5( $question ),
                'answer'        => $answer,
                'ai_generated'  => 1,
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            )
        );

        DO_Logger::log( 'AI', 'AI success for question hash: ' . md5( $question ) );
        wp_send_json_success( array( 'answer' => $answer ) );
    }

    /**
     * Generate an answer using configured providers.
     */
    public static function generate_answer( $prompt, $for_translation = false ) {
        $api_settings = get_option( 'do_api_settings', array() );
        $ai_settings  = get_option( 'do_ai_settings', array( 'enabled' => 1, 'timeout' => 20, 'retry' => 2, 'primary' => 'grok', 'backup' => 'openai' ) );

        if ( empty( $ai_settings['enabled'] ) ) {
            return new WP_Error( 'ai_disabled', 'AI is disabled by administrator.' );
        }

        $final_prompt = self::build_prompt( $prompt, $for_translation );

        $timeout  = max( 5, (int) ( $ai_settings['timeout'] ?? 20 ) );
        $retries  = max( 1, (int) ( $ai_settings['retry'] ?? 2 ) );
        $primary  = sanitize_key( $ai_settings['primary'] ?? 'grok' );
        $backup   = sanitize_key( $ai_settings['backup'] ?? 'openai' );

        $primary_result = self::call_provider( $primary, $api_settings, $final_prompt, $timeout, $retries, $for_translation );
        if ( ! is_wp_error( $primary_result ) ) {
            DO_Logger::log( 'AI', 'Primary provider success: ' . $primary );
            return $primary_result;
        }

        DO_Logger::log( 'AI', 'Primary failed (' . $primary . '): ' . $primary_result->get_error_message() );

        $backup_result = self::call_provider( $backup, $api_settings, $final_prompt, $timeout, $retries, $for_translation );
        if ( ! is_wp_error( $backup_result ) ) {
            DO_Logger::log( 'AI', 'Fallback used successfully: ' . $backup );
            return $backup_result;
        }

        DO_Logger::log( 'AI', 'Fallback failed (' . $backup . '): ' . $backup_result->get_error_message() );
        return $backup_result;
    }

    /**
     * Build final prompt from admin-controlled prompt and user message.
     */
    private static function build_prompt( $user_prompt, $for_translation ) {
        if ( $for_translation ) {
            return $user_prompt;
        }

        $ai_settings = get_option( 'do_ai_settings', array() );
        $base_prompt = trim( (string) ( $ai_settings['answer_prompt'] ?? self::DEFAULT_ANSWER_PROMPT ) );
        if ( '' === $base_prompt ) {
            $base_prompt = self::DEFAULT_ANSWER_PROMPT;
        }

        return $base_prompt . "\n\nUser Question:\n" . $user_prompt;
    }

    private static function call_provider( $provider, $api_settings, $prompt, $timeout, $retries, $for_translation ) {
        $last_error = new WP_Error( 'ai_failed', 'Unknown AI failure.' );

        for ( $attempt = 1; $attempt <= $retries; $attempt++ ) {
            if ( 'grok' === $provider ) {
                $result = self::call_openai_compatible(
                    'https://api.x.ai/v1/chat/completions',
                    sanitize_text_field( $api_settings['grok_api_key'] ?? '' ),
                    sanitize_text_field( $api_settings['grok_model'] ?? 'grok-beta' ),
                    $prompt,
                    $timeout,
                    $for_translation
                );
            } elseif ( 'gemini' === $provider ) {
                $result = self::call_gemini(
                    sanitize_text_field( $api_settings['gemini_api_key'] ?? '' ),
                    sanitize_text_field( $api_settings['gemini_model'] ?? 'gemini-1.5-flash' ),
                    $prompt,
                    $timeout
                );
            } else {
                $result = self::call_openai_compatible(
                    sanitize_text_field( $api_settings['openai_api_url'] ?? 'https://api.openai.com/v1/chat/completions' ),
                    sanitize_text_field( $api_settings['openai_api_key'] ?? '' ),
                    sanitize_text_field( $api_settings['openai_model'] ?? 'gpt-4o-mini' ),
                    $prompt,
                    $timeout,
                    $for_translation
                );
            }

            if ( ! is_wp_error( $result ) ) {
                return $result;
            }

            $last_error = $result;
        }

        return $last_error;
    }

    private static function call_openai_compatible( $url, $api_key, $model, $prompt, $timeout, $for_translation ) {
        if ( ! $api_key || ! $url ) {
            return new WP_Error( 'missing_key', 'AI provider API key missing.' );
        }

        $system = $for_translation ? 'You are a precise translation assistant.' : 'You are a qualified Islamic Q&A assistant with clear references.';

        $response = wp_remote_post(
            $url,
            array(
                'timeout' => $timeout,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                    'Content-Type'  => 'application/json',
                ),
                'body'    => wp_json_encode(
                    array(
                        'model'    => $model,
                        'messages' => array(
                            array( 'role' => 'system', 'content' => $system ),
                            array( 'role' => 'user', 'content' => $prompt ),
                        ),
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_Error( 'provider_error', 'Provider HTTP error ' . $code );
        }

        $content = $json['choices'][0]['message']['content'] ?? '';
        if ( ! $content ) {
            return new WP_Error( 'empty_response', 'AI response empty.' );
        }

        return wp_kses_post( $content );
    }

    private static function call_gemini( $api_key, $model, $prompt, $timeout ) {
        if ( ! $api_key ) {
            return new WP_Error( 'missing_key', 'Gemini API key missing.' );
        }

        $url = sprintf( 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s', rawurlencode( $model ), rawurlencode( $api_key ) );

        $response = wp_remote_post(
            $url,
            array(
                'timeout' => $timeout,
                'headers' => array( 'Content-Type' => 'application/json' ),
                'body'    => wp_json_encode(
                    array(
                        'contents' => array(
                            array(
                                'parts' => array(
                                    array( 'text' => $prompt ),
                                ),
                            ),
                        ),
                    )
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $json = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code >= 400 ) {
            return new WP_Error( 'gemini_error', 'Gemini HTTP error ' . $code );
        }

        $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
        if ( ! $text ) {
            return new WP_Error( 'empty_response', 'Gemini response empty.' );
        }

        return wp_kses_post( $text );
    }

    private static function rate_limit_max() {
        $settings = get_option( 'do_rate_limit_settings', array( 'max_per_minute' => 5 ) );
        return max( 1, absint( $settings['max_per_minute'] ?? 5 ) );
    }
}

DO_AI_Module::init();
