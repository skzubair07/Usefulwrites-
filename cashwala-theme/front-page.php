<?php
/**
 * Front page template.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>
<section class="home-search-wrap" data-sticky-search-wrap>
	<div class="container">
		<div class="home-search-inner">
			<label class="screen-reader-text" for="cashwala-live-search"><?php esc_html_e( 'Search products', 'cashwala-theme' ); ?></label>
			<input type="search" id="cashwala-live-search" class="cashwala-live-search-input" placeholder="<?php esc_attr_e( 'Search plugins...', 'cashwala-theme' ); ?>" autocomplete="off" data-live-search-input>
			<div class="cashwala-search-loader" data-search-loader hidden></div>
		</div>
		<div class="cashwala-live-search-results" data-live-search-results aria-live="polite"></div>
	</div>
</section>

<section class="home-products">
	<div class="container">
		<header class="section-header">
			<h1><?php esc_html_e( 'Digital Products', 'cashwala-theme' ); ?></h1>
		</header>
		<div class="product-grid">
			<?php
			$products = new WP_Query(
				array(
					'post_type'           => array( 'cw_book', 'cw_combo' ),
					'post_status'         => 'publish',
					'posts_per_page'      => 9,
					'ignore_sticky_posts' => true,
					'no_found_rows'       => true,
				)
			);

			if ( $products->have_posts() ) :
				while ( $products->have_posts() ) :
					$products->the_post();
					$price = get_post_meta( get_the_ID(), '_cw_price', true );
					?>
					<article <?php post_class( 'product-card' ); ?>>
						<a href="<?php the_permalink(); ?>" class="product-card-link">
							<?php if ( has_post_thumbnail() ) : ?>
								<div class="product-thumb"><?php the_post_thumbnail( 'medium' ); ?></div>
							<?php endif; ?>
							<h2 class="product-title"><?php the_title(); ?></h2>
							<p class="product-desc"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_excerpt() ? get_the_excerpt() : get_the_content() ), 20 ) ); ?></p>
						</a>
						<p class="product-price">
							<?php echo '' !== $price ? esc_html( $price ) : esc_html__( 'Contact for price', 'cashwala-theme' ); ?>
						</p>
						<div class="product-buy"><?php echo do_shortcode( '[cw_buy_button]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					</article>
					<?php
				endwhile;
				wp_reset_postdata();
			else :
				?>
				<p><?php esc_html_e( 'No products found.', 'cashwala-theme' ); ?></p>
				<?php
			endif;
			?>
		</div>
	</div>
</section>
<?php
get_footer();
