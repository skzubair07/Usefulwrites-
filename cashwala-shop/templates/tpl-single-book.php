<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
while ( have_posts() ) :
	the_post();
	$product_id = get_the_ID();
	$sample     = get_post_meta( $product_id, '_cw_sample_url', true );
	$youtube    = get_post_meta( $product_id, '_cw_youtube_url', true );
	$gumroad    = get_post_meta( $product_id, '_cw_gumroad_url', true );
	?>
	<div class="cw-single container" style="max-width:900px;margin:30px auto;">
		<h1><?php the_title(); ?></h1>
		<div><?php the_post_thumbnail( 'large' ); ?></div>
		<div><?php the_content(); ?></div>
		<?php if ( ! empty( $sample ) ) : ?>
			<p><a href="<?php echo esc_url( $sample ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View Sample', 'cashwala-shop' ); ?></a></p>
		<?php endif; ?>
		<?php if ( ! empty( $youtube ) ) : ?>
			<p><a href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Watch Video', 'cashwala-shop' ); ?></a></p>
		<?php endif; ?>
		<?php echo do_shortcode( '[cw_buy_button id="' . absint( $product_id ) . '"]' ); ?>
		<?php if ( ! empty( $gumroad ) ) : ?>
			<p><?php esc_html_e( 'International buyers:', 'cashwala-shop' ); ?> <a href="<?php echo esc_url( $gumroad ); ?>" target="_blank" rel="noopener">Gumroad</a></p>
		<?php endif; ?>
	</div>
	<?php
endwhile;
get_footer();
