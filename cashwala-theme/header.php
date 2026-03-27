<?php
/**
 * Header template.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$options = cashwala_get_options();
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
	<div class="container site-header-inner">
		<div class="site-branding">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-brand-link" rel="home">
				<?php if ( ! empty( $options['logo_url'] ) ) : ?>
					<img src="<?php echo esc_url( $options['logo_url'] ); ?>" alt="<?php echo esc_attr( $options['website_name'] ); ?>" class="site-logo" loading="lazy" decoding="async">
				<?php else : ?>
					<span class="site-title"><?php echo esc_html( $options['website_name'] ); ?></span>
				<?php endif; ?>
			</a>
		</div>

		<nav class="header-nav" aria-label="<?php esc_attr_e( 'Header links', 'cashwala-theme' ); ?>">
			<?php cashwala_print_links( $options['header_links'] ); ?>
		</nav>

		<?php if ( ! empty( $options['enable_dark_mode'] ) ) : ?>
			<button class="dark-mode-toggle" type="button" data-dark-toggle aria-label="<?php esc_attr_e( 'Toggle dark mode', 'cashwala-theme' ); ?>">
				<?php esc_html_e( 'Dark', 'cashwala-theme' ); ?>
			</button>
		<?php endif; ?>
	</div>
</header>
<main id="primary" class="site-main">
