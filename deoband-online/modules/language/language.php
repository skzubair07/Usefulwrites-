<?php
/**
 * Multi-language translation module.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Language_Module {

    const DEFAULT_TRANSLATION_PROMPT = 'Translate into selected language in a simple, respectful Islamic tone. Do not mistranslate religious terms. Keep Arabic/Islamic words unchanged.';

    public static function init() {
        add_action( 'init', array( __CLASS__, 'capture_language_preference' ) );
        add_action( 'wp_ajax_do_translate_text', array( __CLASS__, 'ajax_translate' ) );
        add_action( 'wp_ajax_nopriv_do_translate_text', array( __CLASS__, 'ajax_translate' ) );
    }

    public static function supported_languages() {
        return array( 'hindi', 'english', 'urdu' );
    }

    public static function capture_language_preference() {
        if ( empty( $_REQUEST['do_lang'] ) ) {
            return;
        }

        $language = sanitize_key( wp_unslash( $_REQUEST['do_lang'] ) );
        if ( ! in_array( $language, self::supported_languages(), true ) ) {
            return;
        }

        if ( is_user_logged_in() ) {
            update_user_meta( get_current_user_id(), 'do_language', $language );
        }

        setcookie( 'do_lang', $language, time() + MONTH_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
        $_COOKIE['do_lang'] = $language;
    }

    public static function get_current_language() {
        $lang = is_user_logged_in() ? get_user_meta( get_current_user_id(), 'do_language', true ) : '';
        if ( ! $lang && ! empty( $_COOKIE['do_lang'] ) ) {
            $lang = sanitize_key( wp_unslash( $_COOKIE['do_lang'] ) );
        }
        return in_array( $lang, self::supported_languages(), true ) ? $lang : 'english';
    }

    public static function ajax_translate() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        $text     = sanitize_textarea_field( wp_unslash( $_POST['text'] ?? '' ) );
        $language = sanitize_key( wp_unslash( $_POST['language'] ?? self::get_current_language() ) );

        $translated = self::translate_text( $text, $language );
        if ( is_wp_error( $translated ) ) {
            wp_send_json_error( array( 'message' => $translated->get_error_message() ) );
        }

        wp_send_json_success( array( 'translated' => $translated ) );
    }

    /**
     * Translate text with cache + provider fallback.
     */
    public static function translate_text( $text, $language ) {
        global $wpdb;

        $global = get_option( 'do_global_controls', array() );
        $prov   = get_option( 'do_translation_provider_settings', array( 'enabled' => 1, 'use_google' => 1, 'use_ai' => 1, 'use_local_ai' => 0 ) );

        if ( empty( $prov['enabled'] ) || ! empty( $global['disable_translation'] ) ) {
            return $text;
        }

        $language = sanitize_key( $language );
        if ( ! in_array( $language, self::supported_languages(), true ) ) {
            return new WP_Error( 'invalid_language', 'Unsupported language selected.' );
        }

        $prepared = self::protect_glossary_terms( $text );
        $hash     = md5( $prepared['text'] . $language );

        $cached = $wpdb->get_var( $wpdb->prepare( 'SELECT translated_text FROM ' . $wpdb->prefix . 'do_translations WHERE source_hash = %s', $hash ) );
        if ( $cached ) {
            return self::restore_glossary_terms( $cached, $prepared['map'] );
        }

        $translated = new WP_Error( 'no_provider', 'No translation provider configured.' );

        // Priority: Local -> AI -> Google.
        if ( ! empty( $prov['use_local_ai'] ) ) {
            $translated = self::local_translate( $prepared['text'], $language );
        }

        if ( is_wp_error( $translated ) && ! empty( $prov['use_ai'] ) ) {
            $translated = self::ai_translate( $prepared['text'], $language );
        }

        if ( is_wp_error( $translated ) && ! empty( $prov['use_google'] ) ) {
            $translated = self::google_translate( $prepared['text'], $language );
        }

        if ( is_wp_error( $translated ) ) {
            return 'Iska jawab verify kiya ja raha hai';
        }

        $translated = self::restore_glossary_terms( $translated, $prepared['map'] );

        $wpdb->insert(
            $wpdb->prefix . 'do_translations',
            array(
                'original_text'   => $text,
                'translated_text' => $translated,
                'language'        => $language,
                'source_hash'     => $hash,
                'created_at'      => current_time( 'mysql' ),
            )
        );

        return $translated;
    }

    /**
     * Protect glossary terms from translation.
     */
    private static function protect_glossary_terms( $text ) {
        $default_words = array( 'Namaz', 'Roza', 'Zakat', 'Nikah', 'Mehr' );
        $custom        = get_option( 'do_translation_glossary', array() );
        $glossary      = array_filter( array_unique( array_merge( $default_words, (array) $custom ) ) );

        $map = array();
        foreach ( $glossary as $index => $term ) {
            $token         = '__ISLAMIC_TERM_' . $index . '__';
            $map[ $token ] = $term;
            $text          = str_replace( $term, $token, $text );
        }

        return array( 'text' => $text, 'map' => $map );
    }

    private static function restore_glossary_terms( $text, $map ) {
        foreach ( $map as $token => $term ) {
            $text = str_replace( $token, $term, $text );
        }
        return $text;
    }

    /**
     * Build translation prompt from global prompt option.
     */
    private static function build_translation_prompt( $text, $language ) {
        $prompt = get_option( 'do_translation_prompt', self::DEFAULT_TRANSLATION_PROMPT );
        if ( '' === trim( (string) $prompt ) ) {
            $prompt = self::DEFAULT_TRANSLATION_PROMPT;
        }

        return $prompt . "\n\nTarget Language: {$language}\nText: {$text}";
    }

    private static function local_translate( $text, $language ) {
        // Future-ready placeholder: currently delegates to AI engine.
        return DO_AI_Module::generate_answer( self::build_translation_prompt( $text, $language ), true );
    }

    private static function ai_translate( $text, $language ) {
        return DO_AI_Module::generate_answer( self::build_translation_prompt( $text, $language ), true );
    }

    private static function google_translate( $text, $language ) {
        $api = get_option( 'do_api_settings', array() );
        $key = sanitize_text_field( $api['google_translate_key'] ?? '' );
        if ( ! $key ) {
            return new WP_Error( 'missing_key', 'Google Translate API key missing.' );
        }

        $response = wp_remote_post(
            add_query_arg( 'key', rawurlencode( $key ), 'https://translation.googleapis.com/language/translate/v2' ),
            array(
                'timeout' => 20,
                'body'    => array(
                    'q'      => $text,
                    'target' => self::language_code( $language ),
                    'format' => 'text',
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $data = json_decode( wp_remote_retrieve_body( $response ), true );
        $text = $data['data']['translations'][0]['translatedText'] ?? '';

        return $text ? wp_kses_post( $text ) : new WP_Error( 'translate_failed', 'Google translation failed.' );
    }

    private static function language_code( $language ) {
        $map = array(
            'english' => 'en',
            'hindi'   => 'hi',
            'urdu'    => 'ur',
        );
        return $map[ $language ] ?? 'en';
    }
}

/**
 * Translation settings page with glossary management.
 */
function do_render_translation_settings_page() {
    if ( isset( $_POST['do_save_translation_settings'] ) && check_admin_referer( 'do_translation_settings_nonce' ) ) {
        $glossary = array_filter( array_map( 'trim', explode( "\n", sanitize_textarea_field( wp_unslash( $_POST['glossary_words'] ?? '' ) ) ) ) );
        update_option( 'do_translation_glossary', $glossary );

        if ( isset( $_POST['reset_translation_prompt'] ) ) {
            update_option( 'do_translation_prompt', DO_Language_Module::DEFAULT_TRANSLATION_PROMPT );
        } else {
            update_option( 'do_translation_prompt', sanitize_textarea_field( wp_unslash( $_POST['translation_prompt'] ?? DO_Language_Module::DEFAULT_TRANSLATION_PROMPT ) ) );
        }

        echo '<div class="updated"><p>Translation settings saved.</p></div>';
    }

    $glossary = (array) get_option( 'do_translation_glossary', array() );
    $prompt   = get_option( 'do_translation_prompt', DO_Language_Module::DEFAULT_TRANSLATION_PROMPT );

    echo '<div class="wrap"><h1>Translation Settings</h1><form method="post">';
    wp_nonce_field( 'do_translation_settings_nonce' );
    echo '<p><label>Translation Prompt<br><textarea name="translation_prompt" rows="4" cols="110">' . esc_textarea( $prompt ) . '</textarea></label></p>';
    echo '<p><button class="button" name="reset_translation_prompt" value="1">Reset Prompt</button></p>';

    echo '<h3>Glossary (Do not translate these words)</h3>';
    echo '<p><textarea name="glossary_words" rows="6" cols="60" placeholder="One word per line">' . esc_textarea( implode( "\n", $glossary ) ) . '</textarea></p>';

    echo '<p><button class="button button-primary" name="do_save_translation_settings" value="1">Save</button></p>';
    echo '</form></div>';
}

DO_Language_Module::init();
