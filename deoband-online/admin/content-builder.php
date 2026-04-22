<?php
/**
 * Content Builder admin interface.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the content-builder page where admin can add flexible blocks.
 */
function do_render_content_builder_page() {
    if ( isset( $_POST['do_save_content_builder'] ) && check_admin_referer( 'do_content_builder_nonce' ) ) {
        $blocks = array();
        $types  = (array) ( $_POST['block_type'] ?? array() );
        $values = (array) ( $_POST['block_value'] ?? array() );

        foreach ( $types as $index => $type ) {
            $safe_type = sanitize_text_field( wp_unslash( $type ) );
            $value     = wp_kses_post( wp_unslash( $values[ $index ] ?? '' ) );
            if ( $safe_type && $value ) {
                $blocks[] = array(
                    'type'  => $safe_type,
                    'value' => $value,
                );
            }
        }

        update_option( 'do_content_builder_blocks', $blocks );
        echo '<div class="updated"><p>Content blocks saved.</p></div>';
    }

    $blocks = get_option( 'do_content_builder_blocks', array() );
    include DO_PLUGIN_DIR . 'templates/admin-content-builder.php';
}
