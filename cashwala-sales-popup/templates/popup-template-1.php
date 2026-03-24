<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$initial = strtoupper( substr( $entry['name'] ?? 'U', 0, 1 ) );
?>
<div class="cw-sales-popup">
    <div class="cw-sales-popup__avatar">
        <?php if ( ! empty( $avatar ) ) : ?>
            <img src="<?php echo esc_url( $avatar ); ?>" alt="<?php esc_attr_e( 'Buyer avatar', 'cashwala-sales-popup' ); ?>">
        <?php else : ?>
            <?php echo esc_html( $initial ); ?>
        <?php endif; ?>
    </div>
    <div>
        <p class="cw-sales-popup__text">
            <?php echo esc_html( $entry['name'] ); ?> <?php esc_html_e( 'from', 'cashwala-sales-popup' ); ?> <?php echo esc_html( $entry['city'] ); ?>
            <?php esc_html_e( 'just purchased', 'cashwala-sales-popup' ); ?>
            <span class="cw-sales-popup__product"><?php echo esc_html( $entry['product'] ); ?></span>
        </p>
        <?php if ( $show_cta && ! empty( $link ) ) : ?>
            <a class="cw-sales-popup__cta" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $cta_text ); ?></a>
        <?php endif; ?>
    </div>
</div>
