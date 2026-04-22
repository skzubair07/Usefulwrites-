<?php
/**
 * Generic settings pages and sanitization callbacks.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load prompt admin panel file from admin layer.
require_once DO_PLUGIN_DIR . 'admin/prompts.php';

/**
 * Render API/settings page with translation provider controls.
 */
function do_render_api_settings_page() {
    if ( isset( $_POST['do_save_api_settings'] ) && check_admin_referer( 'do_api_settings_nonce' ) ) {
        $settings = array(
            'grok_api_key'         => sanitize_text_field( wp_unslash( $_POST['grok_api_key'] ?? '' ) ),
            'grok_model'           => sanitize_text_field( wp_unslash( $_POST['grok_model'] ?? 'grok-beta' ) ),
            'openai_api_key'       => sanitize_text_field( wp_unslash( $_POST['openai_api_key'] ?? '' ) ),
            'openai_api_url'       => esc_url_raw( wp_unslash( $_POST['openai_api_url'] ?? 'https://api.openai.com/v1/chat/completions' ) ),
            'openai_model'         => sanitize_text_field( wp_unslash( $_POST['openai_model'] ?? 'gpt-4o-mini' ) ),
            'gemini_api_key'       => sanitize_text_field( wp_unslash( $_POST['gemini_api_key'] ?? '' ) ),
            'gemini_model'         => sanitize_text_field( wp_unslash( $_POST['gemini_model'] ?? 'gemini-1.5-flash' ) ),
            'google_translate_key' => sanitize_text_field( wp_unslash( $_POST['google_translate_key'] ?? '' ) ),
            'prayer_api_url'       => esc_url_raw( wp_unslash( $_POST['prayer_api_url'] ?? '' ) ),
            'news_rss_url'         => esc_url_raw( wp_unslash( $_POST['news_rss_url'] ?? '' ) ),
        );
        update_option( 'do_api_settings', $settings );

        // Keep backward compatibility and sync with global prompt options.
        $prompt = sanitize_textarea_field( wp_unslash( $_POST['answer_prompt'] ?? get_option( 'do_ai_prompt', '' ) ) );
        update_option( 'do_ai_prompt', $prompt );

        update_option(
            'do_translation_provider_settings',
            array(
                'enabled'      => ! empty( $_POST['translation_enabled'] ) ? 1 : 0,
                'use_google'   => ! empty( $_POST['translation_use_google'] ) ? 1 : 0,
                'use_ai'       => ! empty( $_POST['translation_use_ai'] ) ? 1 : 0,
                'use_local_ai' => ! empty( $_POST['translation_use_local_ai'] ) ? 1 : 0,
            )
        );

        echo '<div class="updated"><p>API settings saved.</p></div>';
    }

    $settings = wp_parse_args( get_option( 'do_api_settings', array() ), array(
        'grok_api_key'         => '',
        'grok_model'           => 'grok-beta',
        'openai_api_key'       => '',
        'openai_api_url'       => 'https://api.openai.com/v1/chat/completions',
        'openai_model'         => 'gpt-4o-mini',
        'gemini_api_key'       => '',
        'gemini_model'         => 'gemini-1.5-flash',
        'google_translate_key' => '',
        'prayer_api_url'       => '',
        'news_rss_url'         => '',
    ) );

    $prompt      = get_option( 'do_ai_prompt', "Answer strictly based on Darul Uloom Deoband and Binori Town sources. Do not generate your own fatwa. If not found, say 'answer will be provided later'." );
    $tr_provider = get_option( 'do_translation_provider_settings', array( 'enabled' => 1, 'use_google' => 1, 'use_ai' => 1, 'use_local_ai' => 0 ) );

    echo '<div class="wrap"><h1>API Settings</h1><form method="post">';
    wp_nonce_field( 'do_api_settings_nonce' );

    echo '<h2>AI Providers</h2>';
    echo '<p><label>Grok API Key <input name="grok_api_key" value="' . esc_attr( $settings['grok_api_key'] ) . '" size="80"></label></p>';
    echo '<p><label>Grok Model <input name="grok_model" value="' . esc_attr( $settings['grok_model'] ) . '"></label></p>';
    echo '<p><label>OpenAI API Key <input name="openai_api_key" value="' . esc_attr( $settings['openai_api_key'] ) . '" size="80"></label></p>';
    echo '<p><label>OpenAI API URL <input name="openai_api_url" value="' . esc_attr( $settings['openai_api_url'] ) . '" size="80"></label></p>';
    echo '<p><label>OpenAI Model <input name="openai_model" value="' . esc_attr( $settings['openai_model'] ) . '"></label></p>';
    echo '<p><label>Gemini API Key <input name="gemini_api_key" value="' . esc_attr( $settings['gemini_api_key'] ) . '" size="80"></label></p>';
    echo '<p><label>Gemini Model <input name="gemini_model" value="' . esc_attr( $settings['gemini_model'] ) . '"></label></p>';

    echo '<h2>Translation Providers</h2>';
    echo '<p><label><input type="checkbox" name="translation_enabled" value="1" ' . checked( $tr_provider['enabled'] ?? 0, 1, false ) . '> Enable Translation</label></p>';
    echo '<p><label><input type="checkbox" name="translation_use_google" value="1" ' . checked( $tr_provider['use_google'] ?? 0, 1, false ) . '> Use Google Translate</label></p>';
    echo '<p><label><input type="checkbox" name="translation_use_ai" value="1" ' . checked( $tr_provider['use_ai'] ?? 0, 1, false ) . '> Use AI Translation</label></p>';
    echo '<p><label><input type="checkbox" name="translation_use_local_ai" value="1" ' . checked( $tr_provider['use_local_ai'] ?? 0, 1, false ) . '> Use Local AI (disabled by default)</label></p>';
    echo '<p><label>Google Translate API Key <input name="google_translate_key" value="' . esc_attr( $settings['google_translate_key'] ) . '" size="80"></label></p>';

    echo '<h2>AI Answer Prompt (synced with Prompt Panel)</h2>';
    echo '<p><textarea name="answer_prompt" rows="4" cols="100">' . esc_textarea( $prompt ) . '</textarea></p>';

    echo '<h2>Other APIs</h2>';
    echo '<p><label>Prayer Time API URL <input name="prayer_api_url" value="' . esc_attr( $settings['prayer_api_url'] ) . '" size="80"></label></p>';
    echo '<p><label>News RSS URL <input name="news_rss_url" value="' . esc_attr( $settings['news_rss_url'] ) . '" size="80"></label></p>';
    echo '<p><button class="button button-primary" name="do_save_api_settings" value="1">Save</button></p>';
    echo '</form></div>';
}

/**
 * Render System Controls page (feature on/off + existing controls).
 */
function do_render_system_controls_page() {
    if ( isset( $_POST['do_save_system_controls'] ) && check_admin_referer( 'do_system_controls_nonce' ) ) {
        update_option(
            'do_search_settings',
            array(
                'question_weight' => absint( $_POST['question_weight'] ?? 20 ),
                'keyword_weight'  => absint( $_POST['keyword_weight'] ?? 10 ),
                'answer_weight'   => absint( $_POST['answer_weight'] ?? 5 ),
            )
        );

        update_option(
            'do_ai_settings',
            array_merge(
                get_option( 'do_ai_settings', array() ),
                array(
                    'enabled' => ! empty( $_POST['ai_enabled'] ) ? 1 : 0,
                    'timeout' => max( 5, absint( $_POST['ai_timeout'] ?? 20 ) ),
                    'retry'   => max( 1, absint( $_POST['ai_retry'] ?? 2 ) ),
                    'primary' => sanitize_key( wp_unslash( $_POST['ai_primary'] ?? 'grok' ) ),
                    'backup'  => sanitize_key( wp_unslash( $_POST['ai_backup'] ?? 'openai' ) ),
                )
            )
        );

        update_option( 'do_rate_limit_settings', array( 'max_per_minute' => max( 1, absint( $_POST['max_per_minute'] ?? 5 ) ) ) );
        update_option( 'do_trending_settings', array( 'enabled' => ! empty( $_POST['trending_enabled'] ) ? 1 : 0 ) );

        update_option(
            'do_feature_toggles',
            array(
                'ai'            => ! empty( $_POST['toggle_ai'] ) ? 1 : 0,
                'translation'   => ! empty( $_POST['toggle_translation'] ) ? 1 : 0,
                'import'        => ! empty( $_POST['toggle_import'] ) ? 1 : 0,
                'trending'      => ! empty( $_POST['toggle_trending'] ) ? 1 : 0,
                'foryou'        => ! empty( $_POST['toggle_foryou'] ) ? 1 : 0,
                'notifications' => ! empty( $_POST['toggle_notifications'] ) ? 1 : 0,
                'affiliate'     => ! empty( $_POST['toggle_affiliate'] ) ? 1 : 0,
                'tokens'        => ! empty( $_POST['toggle_tokens'] ) ? 1 : 0,
            )
        );

        update_option(
            'do_global_controls',
            array(
                'disable_ai'           => ! empty( $_POST['disable_ai'] ) ? 1 : 0,
                'disable_import'       => ! empty( $_POST['disable_import'] ) ? 1 : 0,
                'disable_translation'  => ! empty( $_POST['disable_translation'] ) ? 1 : 0,
                'force_manual_answers' => ! empty( $_POST['force_manual_answers'] ) ? 1 : 0,
            )
        );

        echo '<div class="updated"><p>System controls saved.</p></div>';
    }

    $search   = get_option( 'do_search_settings', array( 'question_weight' => 20, 'keyword_weight' => 10, 'answer_weight' => 5 ) );
    $ai       = get_option( 'do_ai_settings', array( 'enabled' => 1, 'timeout' => 20, 'retry' => 2, 'primary' => 'grok', 'backup' => 'openai' ) );
    $rate     = get_option( 'do_rate_limit_settings', array( 'max_per_minute' => 5 ) );
    $trending = get_option( 'do_trending_settings', array( 'enabled' => 1 ) );
    $global   = get_option( 'do_global_controls', array() );
    $toggles  = get_option( 'do_feature_toggles', array( 'ai' => 1, 'translation' => 1, 'import' => 1, 'trending' => 1, 'foryou' => 1, 'notifications' => 1, 'affiliate' => 1, 'tokens' => 1 ) );

    echo '<div class="wrap"><h1>System Controls</h1><form method="post">';
    wp_nonce_field( 'do_system_controls_nonce' );
    echo '<h2>Feature ON/OFF Controls</h2>';
    echo '<p><label><input type="checkbox" name="toggle_ai" value="1" ' . checked( $toggles['ai'] ?? 0, 1, false ) . '> AI System</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_translation" value="1" ' . checked( $toggles['translation'] ?? 0, 1, false ) . '> Translation System</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_import" value="1" ' . checked( $toggles['import'] ?? 0, 1, false ) . '> Import System</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_trending" value="1" ' . checked( $toggles['trending'] ?? 0, 1, false ) . '> Trending</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_foryou" value="1" ' . checked( $toggles['foryou'] ?? 0, 1, false ) . '> For You Feed</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_notifications" value="1" ' . checked( $toggles['notifications'] ?? 0, 1, false ) . '> Notifications</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_affiliate" value="1" ' . checked( $toggles['affiliate'] ?? 0, 1, false ) . '> Affiliate</label></p>';
    echo '<p><label><input type="checkbox" name="toggle_tokens" value="1" ' . checked( $toggles['tokens'] ?? 0, 1, false ) . '> Tokens</label></p>';

    echo '<h2>Search Weights</h2>';
    echo '<p><label>Question Weight <input type="number" name="question_weight" value="' . esc_attr( $search['question_weight'] ) . '"></label></p>';
    echo '<p><label>Keyword Weight <input type="number" name="keyword_weight" value="' . esc_attr( $search['keyword_weight'] ) . '"></label></p>';
    echo '<p><label>Answer Weight <input type="number" name="answer_weight" value="' . esc_attr( $search['answer_weight'] ) . '"></label></p>';

    echo '<h2>AI Controls</h2>';
    echo '<p><label><input type="checkbox" name="ai_enabled" value="1" ' . checked( $ai['enabled'] ?? 0, 1, false ) . '> Enable AI</label></p>';
    echo '<p><label>Primary <input name="ai_primary" value="' . esc_attr( $ai['primary'] ?? 'grok' ) . '"></label></p>';
    echo '<p><label>Backup <input name="ai_backup" value="' . esc_attr( $ai['backup'] ?? 'openai' ) . '"></label></p>';
    echo '<p><label>Timeout <input type="number" name="ai_timeout" value="' . esc_attr( $ai['timeout'] ?? 20 ) . '"></label></p>';
    echo '<p><label>Retry <input type="number" name="ai_retry" value="' . esc_attr( $ai['retry'] ?? 2 ) . '"></label></p>';

    echo '<h2>Rate Limit</h2>';
    echo '<p><label>Max questions per minute <input type="number" name="max_per_minute" value="' . esc_attr( $rate['max_per_minute'] ) . '"></label></p>';

    echo '<h2>Global Force Controls</h2>';
    echo '<p><label><input type="checkbox" name="disable_ai" value="1" ' . checked( $global['disable_ai'] ?? 0, 1, false ) . '> Disable AI</label></p>';
    echo '<p><label><input type="checkbox" name="disable_import" value="1" ' . checked( $global['disable_import'] ?? 0, 1, false ) . '> Disable Import</label></p>';
    echo '<p><label><input type="checkbox" name="disable_translation" value="1" ' . checked( $global['disable_translation'] ?? 0, 1, false ) . '> Disable Translation</label></p>';
    echo '<p><label><input type="checkbox" name="force_manual_answers" value="1" ' . checked( $global['force_manual_answers'] ?? 0, 1, false ) . '> Force manual answers</label></p>';

    echo '<p><button class="button button-primary" name="do_save_system_controls" value="1">Save Controls</button></p>';
    echo '</form></div>';
}
