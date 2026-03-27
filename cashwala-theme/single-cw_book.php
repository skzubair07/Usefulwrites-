<?php
/**
 * Single product template for cw_book.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="container single-product-wrap">
	<?php
	while ( have_posts() ) :
		the_post();
		$price = get_post_meta( get_the_ID(), '_cw_price', true );
		?>
		<article <?php post_class( 'single-product' ); ?>>
			<h1 class="single-product-title"><?php the_title(); ?></h1>
			<?php if ( has_post_thumbnail() ) : ?>
				<div class="single-product-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>
			<div class="single-product-content">
				<?php the_content(); ?>
			</div>
			<p class="single-product-price"><?php echo '' !== $price ? esc_html( $price ) : esc_html__( 'Contact for price', 'cashwala-theme' ); ?></p>
			<div class="single-product-buy"><?php echo do_shortcode( '[cw_buy_button]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</article>
	<?php endwhile; ?>
</section>
<?php
get_footer();
