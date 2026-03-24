<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'cwqsp_settings' );

global $wpdb;
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cwqsp_orders' );
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'cwqsp_download_tokens' );

$products = get_posts(
	array(
		'post_type'      => 'cw_product',
		'posts_per_page' => -1,
		'post_status'    => 'any',
		'fields'         => 'ids',
	)
);

if ( $products ) {
	foreach ( $products as $product_id ) {
		wp_delete_post( $product_id, true );
	}
}
