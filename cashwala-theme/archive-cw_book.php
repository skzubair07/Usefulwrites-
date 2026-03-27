<?php
/**
 * Archive for cw_book.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="container archive-products-wrap">
	<header class="section-header">
		<h1><?php post_type_archive_title(); ?></h1>
	</header>
	<div class="product-grid">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<?php $price = get_post_meta( get_the_ID(), '_cw_price', true ); ?>
				<article <?php post_class( 'product-card' ); ?>>
					<a href="<?php the_permalink(); ?>" class="product-card-link">
						<h2 class="product-title"><?php the_title(); ?></h2>
						<p class="product-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ? get_the_excerpt() : get_the_content() ), 20 ) ); ?></p>
					</a>
					<p class="product-price"><?php echo '' !== $price ? esc_html( $price ) : esc_html__( 'Contact for price', 'cashwala-theme' ); ?></p>
					<div class="product-buy"><?php echo do_shortcode( '[cw_buy_button]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				</article>
			<?php endwhile; ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No products found.', 'cashwala-theme' ); ?></p>
		<?php endif; ?>
	</div>
	<?php the_posts_pagination(); ?>
</section>
<?php
get_footer();
