<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$offers   = $offer_context['offers'];
$settings = $offer_context['settings'];
?>
<section id="cw-upsell-inline" class="cw-upsell-inline cw-layout-<?php echo esc_attr( $settings['layout'] ); ?>" style="--cw-bg:<?php echo esc_attr( $settings['background_color'] ); ?>;--cw-text:<?php echo esc_attr( $settings['text_color'] ); ?>;">
    <h3>Upgrade Your Order & Save More</h3>
    <div class="cw-upsell-items">
        <?php foreach ( $offers as $offer ) : ?>
            <?php $saving = max( 0, (float) $offer['price'] - (float) $offer['discount_price'] ); ?>
            <article class="cw-upsell-item">
                <?php if ( ! empty( $offer['image'] ) ) : ?>
                    <img src="<?php echo esc_url( $offer['image'] ); ?>" alt="<?php echo esc_attr( $offer['title'] ); ?>" />
                <?php endif; ?>
                <h4><?php echo esc_html( $offer['title'] ); ?></h4>
                <p><?php echo esc_html( $offer['description'] ); ?></p>
                <div class="cw-pricing">
                    <span class="cw-old-price"><?php echo esc_html( wc_price_fallback( $offer['price'] ) ); ?></span>
                    <span class="cw-discount-price"><?php echo esc_html( wc_price_fallback( $offer['discount_price'] ) ); ?></span>
                    <span class="cw-savings">Save <?php echo esc_html( wc_price_fallback( $saving ) ); ?></span>
                </div>
                <div class="cw-actions">
                    <a class="cw-btn cw-accept cw-<?php echo esc_attr( $settings['button_style'] ); ?>" data-cw-track="accept" href="<?php echo esc_url( $offer['link'] ); ?>">Accept Offer</a>
                    <button class="cw-btn cw-skip cw-<?php echo esc_attr( $settings['button_style'] ); ?>" data-cw-track="skip" type="button">Skip Offer</button>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
