<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$leads = get_posts(
    array(
        'post_type'      => 'lmp_lead',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    )
);

if ( ! empty( $leads ) ) {
    foreach ( $leads as $lead_id ) {
        wp_delete_post( (int) $lead_id, true );
    }
}

delete_option( 'lmp_options' );
delete_option( 'lmp_analytics' );
