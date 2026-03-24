<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$offers   = $offer_context['offers'];
$settings = $offer_context['settings'];
?>
<div id="cw-upsell-modal" class="cw-upsell-modal cw-anim-<?php echo esc_attr( $settings['animation_style'] ); ?>" style="--cw-bg:<?php echo esc_attr( $settings['background_color'] ); ?>;--cw-text:<?php echo esc_attr( $settings['text_color'] ); ?>;">
    <div class="cw-upsell-backdrop"></div>
    <div class="cw-upsell-content cw-layout-<?php echo esc_attr( $settings['layout'] ); ?>">
        <?php if ( ! empty( $settings['close_button'] ) ) : ?>
            <button class="cw-close" type="button" aria-label="Close">&times;</button>
        <?php endif; ?>

        <h3>Special Deal Just For You</h3>
        <div class="cw-upsell-items">
            <?php foreach ( $offers as $offer ) : ?>
                <?php
                $saving = max( 0, (float) $offer['price'] - (float) $offer['discount_price'] );
                ?>
                <div class="cw-upsell-item">
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
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
