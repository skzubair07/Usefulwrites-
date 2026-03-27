<?php
/**
 * Header template.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = cashwala_get_settings();
$logo_url = ! empty( $settings['logo_id'] ) ? wp_get_attachment_image_url( (int) $settings['logo_id'], 'full' ) : '';
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="cw-header">
	<div class="cw-header__inner container">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="cw-brand" rel="home">
			<?php if ( $logo_url ) : ?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" class="cw-brand__logo">
			<?php else : ?>
				<span class="cw-brand__text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<div class="cw-search" data-live-search>
			<label class="screen-reader-text" for="cw-live-search"><?php esc_html_e( 'Search products', 'cashwala-theme' ); ?></label>
			<input id="cw-live-search" type="search" placeholder="<?php esc_attr_e( 'Search e-books, plugins, combos…', 'cashwala-theme' ); ?>" data-live-search-input>
			<div class="cw-search-results" data-live-search-results></div>
		</div>

		<div class="cw-skin-switcher" role="group" aria-label="<?php esc_attr_e( 'Skin switcher', 'cashwala-theme' ); ?>">
			<button type="button" class="cw-skin-btn" data-skin="premium"><?php esc_html_e( 'Light', 'cashwala-theme' ); ?></button>
			<button type="button" class="cw-skin-btn" data-skin="dark"><?php esc_html_e( 'Dark', 'cashwala-theme' ); ?></button>
			<button type="button" class="cw-skin-btn" data-skin="neon"><?php esc_html_e( 'Neon', 'cashwala-theme' ); ?></button>
		</div>
	</div>
</header>
<main class="cw-main">
