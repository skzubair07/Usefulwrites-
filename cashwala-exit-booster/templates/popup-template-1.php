<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
$settings = CW_EIB_Core::get_settings();
?>
<div id="cw-eib-overlay" class="cw-eib-overlay" aria-hidden="true">
    <div class="cw-eib-popup cw-eib-template-one" role="dialog" aria-modal="true" aria-labelledby="cw-eib-title">
        <button class="cw-eib-close" type="button" aria-label="Close popup">×</button>
        <h2 id="cw-eib-title"><?php echo esc_html( $settings['headline'] ); ?></h2>
        <p><?php echo esc_html( $settings['subtext'] ); ?></p>
        <div class="cw-eib-countdown" data-seconds="<?php echo esc_attr( (string) $settings['countdown_seconds'] ); ?>"></div>

        <form class="cw-eib-form" novalidate>
            <?php if ( ! empty( $settings['show_name'] ) ) : ?><input type="text" name="name" placeholder="Your name" autocomplete="name"><?php endif; ?>
            <?php if ( ! empty( $settings['show_email'] ) ) : ?><input type="email" name="email" placeholder="Email" autocomplete="email"><?php endif; ?>
            <?php if ( ! empty( $settings['show_phone'] ) ) : ?><input type="text" name="phone" placeholder="Phone" autocomplete="tel"><?php endif; ?>
            <button type="submit" class="cw-eib-submit"><?php echo esc_html( $settings['button_text'] ); ?></button>
        </form>

        <div class="cw-eib-coupon-wrap" hidden>
            <span class="cw-eib-coupon-code"><?php echo esc_html( $settings['coupon_code'] ); ?></span>
            <button class="cw-eib-copy" type="button">Copy Code</button>
        </div>

        <a class="cw-eib-wa-link" href="#" target="_blank" rel="noopener" hidden>Continue on WhatsApp</a>
        <div class="cw-eib-msg" role="status" aria-live="polite"></div>
    </div>
</div>
