<?php
/**
 * Slider template 2.
 *
 * @var array $items
 * @var array $config
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cwts-layout cwts-layout-<?php echo esc_attr( $config['layout'] ); ?> cwts-template-alt <?php echo (int) $config['shadow'] ? 'cwts-has-shadow' : ''; ?>">
	<div class="cwts-track-wrap">
		<div class="cwts-track">
			<?php foreach ( $items as $item ) : ?>
				<article class="cwts-card">
					<p class="cwts-quote-mark">“</p>
					<p class="cwts-text"><?php echo esc_html( $item['text'] ); ?></p>
					<div class="cwts-stars" aria-label="<?php echo esc_attr( $item['rating'] ); ?> out of 5">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<span class="cwts-star <?php echo $i <= (int) $item['rating'] ? 'is-active' : ''; ?>">★</span>
						<?php endfor; ?>
					</div>
					<div class="cwts-card-foot">
						<?php if ( ! empty( $item['photo'] ) ) : ?>
							<img loading="lazy" src="<?php echo esc_url( $item['photo'] ); ?>" alt="<?php echo esc_attr( $item['name'] ); ?>" class="cwts-avatar">
						<?php endif; ?>
						<div>
							<h3 class="cwts-name"><?php echo esc_html( $item['name'] ); ?></h3>
							<?php if ( ! empty( $item['company'] ) ) : ?>
								<p class="cwts-company"><?php echo esc_html( $item['company'] ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="cwts-controls">
		<button type="button" class="cwts-nav cwts-prev" aria-label="<?php esc_attr_e( 'Previous testimonial', 'cashwala-testimonial-slider' ); ?>">←</button>
		<div class="cwts-dots"></div>
		<button type="button" class="cwts-nav cwts-next" aria-label="<?php esc_attr_e( 'Next testimonial', 'cashwala-testimonial-slider' ); ?>">→</button>
	</div>
</div>
