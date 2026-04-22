<?php
/**
 * Prompt Control Panel for all major prompt types.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Prompt Control Panel under main menu.
 */
function do_register_prompt_control_menu() {
    add_submenu_page(
        'do-dashboard',
        __( 'Prompt Control Panel', 'deoband-online' ),
        __( 'Prompt Control Panel', 'deoband-online' ),
        'manage_options',
        'do-prompt-control',
        'do_render_prompt_control_page'
    );
}
add_action( 'admin_menu', 'do_register_prompt_control_menu' );

/**
 * Get default prompt map.
 */
function do_prompt_defaults() {
    return array(
        'do_ai_prompt'         => "Answer strictly based on Darul Uloom Deoband and Binori Town sources. Do not generate your own fatwa. If not found, say 'answer will be provided later'.",
        'do_translation_prompt'=> 'Translate into selected language in a simple, respectful Islamic tone. Do not mistranslate religious terms. Keep Arabic/Islamic words unchanged.',
        'do_search_prompt'     => 'Interpret user question intent and prioritize exact Islamic masail relevance before broad matches.',
        'do_moderation_prompt' => 'Reject abusive or misleading content. Keep Islamic respect and factual safety in responses.',
    );
}

/**
 * Render and save Prompt Control Panel.
 */
function do_render_prompt_control_page() {
    $defaults = do_prompt_defaults();

    if ( isset( $_POST['do_save_prompts'] ) && check_admin_referer( 'do_prompts_nonce' ) ) {
        foreach ( array_keys( $defaults ) as $key ) {
            update_option( $key, sanitize_textarea_field( wp_unslash( $_POST[ $key ] ?? $defaults[ $key ] ) ) );
        }
        echo '<div class="updated"><p>Prompts saved.</p></div>';
    }

    if ( isset( $_POST['do_reset_prompt'] ) && check_admin_referer( 'do_prompts_nonce' ) ) {
        $target = sanitize_key( wp_unslash( $_POST['do_reset_prompt'] ) );
        if ( isset( $defaults[ $target ] ) ) {
            update_option( $target, $defaults[ $target ] );
            echo '<div class="updated"><p>Prompt reset to default.</p></div>';
        }
    }

    $test_output = '';
    if ( isset( $_POST['do_test_prompt'] ) && check_admin_referer( 'do_prompts_nonce' ) ) {
        $test_type = sanitize_key( wp_unslash( $_POST['test_prompt_type'] ?? 'do_ai_prompt' ) );
        $sample    = sanitize_textarea_field( wp_unslash( $_POST['test_sample_text'] ?? '' ) );

        if ( 'do_translation_prompt' === $test_type && class_exists( 'DO_Language_Module' ) ) {
            $test_output = DO_Language_Module::translate_text( $sample, 'urdu' );
            if ( is_wp_error( $test_output ) ) {
                $test_output = $test_output->get_error_message();
            }
        } elseif ( 'do_ai_prompt' === $test_type && class_exists( 'DO_AI_Module' ) ) {
            $test_output = DO_AI_Module::generate_answer( $sample );
            if ( is_wp_error( $test_output ) ) {
                $test_output = $test_output->get_error_message();
            }
        } else {
            $test_output = ( get_option( $test_type, $defaults[ $test_type ] ?? '' ) ) . "\n\n" . $sample;
        }
    }

    $values = array();
    foreach ( $defaults as $key => $default ) {
        $values[ $key ] = get_option( $key, $default );
    }

    echo '<div class="wrap"><h1>Prompt Control Panel</h1><form method="post">';
    wp_nonce_field( 'do_prompts_nonce' );

    foreach ( $values as $key => $value ) {
        echo '<h3>' . esc_html( ucwords( str_replace( array( 'do_', '_prompt', '_' ), array( '', '', ' ' ), $key ) ) ) . '</h3>';
        echo '<textarea name="' . esc_attr( $key ) . '" rows="4" cols="110">' . esc_textarea( $value ) . '</textarea>';
        echo '<p><button class="button" name="do_reset_prompt" value="' . esc_attr( $key ) . '">Reset to Default</button></p>';
    }

    echo '<p><button class="button button-primary" name="do_save_prompts" value="1">Save All Prompts</button></p>';

    echo '<hr><h2>Test Prompt</h2>';
    echo '<p><label>Prompt Type <select name="test_prompt_type">';
    foreach ( array_keys( $values ) as $key ) {
        echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $key ) . '</option>';
    }
    echo '</select></label></p>';
    echo '<p><label>Sample Text<br><textarea name="test_sample_text" rows="3" cols="110"></textarea></label></p>';
    echo '<p><button class="button" name="do_test_prompt" value="1">Run Test</button></p>';

    if ( '' !== $test_output ) {
        echo '<h3>Test Output</h3><pre style="background:#fff;padding:12px;border:1px solid #ddd;">' . esc_html( $test_output ) . '</pre>';
    }

    echo '</form></div>';
}
