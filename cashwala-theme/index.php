<?php
/**
 * Product bento grid.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$products = new WP_Query(
	array(
		'post_type'      => 'cw_product',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
	)
);
?>
<section class="container cw-catalog">
	<div class="cw-catalog__head">
		<h1><?php esc_html_e( 'Digital Products That Print Cash', 'cashwala-theme' ); ?></h1>
		<p><?php esc_html_e( 'Fast checkout, instant access, zero friction.', 'cashwala-theme' ); ?></p>
	</div>

	<?php if ( $products->have_posts() ) : ?>
		<div class="cw-bento-grid">
			<?php
			$index = 0;
			while ( $products->have_posts() ) :
				$products->the_post();
				$product_id  = get_the_ID();
				$price       = (float) get_post_meta( $product_id, '_cw_price', true );
				$sale_price  = (float) get_post_meta( $product_id, '_cw_sale_price', true );
				$display     = $sale_price > 0 ? $sale_price : $price;
				$product_type = get_post_meta( $product_id, '_cw_product_type', true );
				?>
				<article <?php post_class( 'cw-card ' . ( 0 === $index ? 'cw-card--featured' : '' ) ); ?>>
					<span class="cw-proof"><?php esc_html_e( 'Used by 500+ people', 'cashwala-theme' ); ?></span>
					<p class="cw-type"><?php echo esc_html( ucfirst( $product_type ? $product_type : 'book' ) ); ?></p>
					<h2 class="cw-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<p class="cw-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
					<div class="cw-price-wrap">
						<?php if ( $sale_price > 0 ) : ?>
							<del><?php echo esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $price ) ) : '₹' . number_format_i18n( $price, 2 ) ); ?></del>
						<?php endif; ?>
						<strong><?php echo esc_html( function_exists( 'wc_price' ) ? wp_strip_all_tags( wc_price( $display ) ) : '₹' . number_format_i18n( $display, 2 ) ); ?></strong>
					</div>
					<button class="cw-buy-btn" data-product-id="<?php echo esc_attr( (string) $product_id ); ?>" data-product-title="<?php echo esc_attr( get_the_title() ); ?>">
						<?php esc_html_e( 'Buy Now', 'cashwala-theme' ); ?>
					</button>
				</article>
				<?php
				$index++;
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	<?php else : ?>
		<p><?php esc_html_e( 'No products yet. Add your first cw_product from dashboard.', 'cashwala-theme' ); ?></p>
	<?php endif; ?>
</section>

<div class="cw-modal" id="cw-lead-modal" aria-hidden="true">
	<div class="cw-modal__box">
		<button type="button" class="cw-modal__close" data-close-modal>&times;</button>
		<h3><?php esc_html_e( 'Enter details to get your download link', 'cashwala-theme' ); ?></h3>
		<form id="cw-lead-form">
			<input type="hidden" name="product_id" id="cw-product-id">
			<input type="text" name="name" placeholder="Your Name" required>
			<input type="email" name="email" placeholder="Your Email" required>
			<input type="tel" name="whatsapp" placeholder="WhatsApp Number" required>
			<button type="submit" class="cw-submit"><?php esc_html_e( 'Continue to Payment', 'cashwala-theme' ); ?></button>
		</form>
		<div id="cw-lead-status" class="cw-lead-status"></div>
	</div>
</div>
<?php
get_footer();
