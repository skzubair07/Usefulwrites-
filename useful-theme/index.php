<?php
/**
 * Main template for Useful Theme.
 *
 * @package Useful_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$query = new WP_Query(
	array(
		'post_type'      => 'useful_product',
		'posts_per_page' => 12,
		'meta_key'       => '_useful_price',
		'orderby'        => 'meta_value_num',
		'order'          => 'DESC',
	)
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<main class="useful-shell">
	<section class="hero">
		<p class="hero__kicker"><?php echo esc_html__( 'Digital Product Store', 'useful-theme' ); ?></p>
		<h1><?php bloginfo( 'name' ); ?></h1>
		<p><?php echo esc_html__( 'Premium assets, plugins, and tools crafted for creators who move fast.', 'useful-theme' ); ?></p>
	</section>

	<?php if ( $query->have_posts() ) : ?>
		<section class="bento-grid" aria-label="<?php echo esc_attr__( 'Product listing', 'useful-theme' ); ?>">
			<?php
			$index = 0;
			while ( $query->have_posts() ) :
				$query->the_post();
				$index++;
				$price    = get_post_meta( get_the_ID(), '_useful_price', true );
				$badge    = get_post_meta( get_the_ID(), '_useful_badge', true );
				$featured = 1 === $index;

				if ( $featured && empty( $badge ) ) {
					$badge = 'Top Deal';
				}
				?>
				<article class="product-card <?php echo $featured ? 'is-featured' : ''; ?>" data-product-title="<?php echo esc_attr( get_the_title() ); ?>" data-product-price="<?php echo esc_attr( is_numeric( $price ) ? number_format_i18n( (float) $price, 2 ) : '0.00' ); ?>">
					<?php if ( has_post_thumbnail() ) : ?>
						<div class="product-media">
							<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy', 'decoding' => 'async' ) ); ?>
						</div>
					<?php endif; ?>
					<div class="product-body">
						<?php if ( ! empty( $badge ) ) : ?>
							<span class="product-badge"><?php echo esc_html( $badge ); ?></span>
						<?php endif; ?>
						<h2 class="product-title"><?php the_title(); ?></h2>
						<p class="product-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20, '...' ) ); ?></p>
						<div class="product-footer">
							<strong class="product-price">₹<?php echo esc_html( is_numeric( $price ) ? number_format_i18n( (float) $price, 2 ) : '0.00' ); ?></strong>
							<button class="quick-buy" type="button" data-open-modal="1"><?php echo esc_html__( 'Quick Buy', 'useful-theme' ); ?></button>
						</div>
					</div>
				</article>
			<?php endwhile; ?>
		</section>
		<?php wp_reset_postdata(); ?>
	<?php else : ?>
		<section class="empty-state">
			<h2><?php echo esc_html__( 'No products yet.', 'useful-theme' ); ?></h2>
			<p><?php echo esc_html__( 'Create your first Useful Product from the admin dashboard.', 'useful-theme' ); ?></p>
		</section>
	<?php endif; ?>
</main>

<div class="qb-modal" id="quick-buy-modal" aria-hidden="true">
	<div class="qb-modal__overlay" data-close-modal="1"></div>
	<div class="qb-modal__sheet" role="dialog" aria-modal="true" aria-labelledby="qb-title">
		<button type="button" class="qb-modal__close" data-close-modal="1" aria-label="<?php echo esc_attr__( 'Close', 'useful-theme' ); ?>">×</button>
		<p class="qb-modal__eyebrow"><?php echo esc_html__( 'You are buying', 'useful-theme' ); ?></p>
		<h3 id="qb-title" class="qb-modal__title"></h3>
		<p class="qb-modal__price" id="qb-price"></p>
		<a href="#" class="qb-modal__cta" id="qb-buy-now"><?php echo esc_html__( 'Buy Now', 'useful-theme' ); ?></a>
		<a href="#" class="qb-modal__wa" id="qb-whatsapp" target="_blank" rel="noopener noreferrer"><?php echo esc_html__( 'Buy via WhatsApp', 'useful-theme' ); ?></a>
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>
