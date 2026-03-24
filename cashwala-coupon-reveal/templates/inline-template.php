<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="cwcr-inline cwcr-<?php echo esc_attr($settings['animation_style']); ?> cwcr-btn-<?php echo esc_attr($settings['button_style']); ?>" style="--cwcr-bg: <?php echo esc_attr($settings['bg_color']); ?>; --cwcr-text: <?php echo esc_attr($settings['text_color']); ?>;">
    <h3><?php echo esc_html($settings['inline_title']); ?></h3>
    <p class="cwcr-before-text"><?php echo esc_html($settings['message_before']); ?></p>
    <form class="cwcr-lead-form">
        <?php if (! empty($settings['lead_email'])) : ?>
            <input type="email" name="email" placeholder="<?php esc_attr_e('Enter your email', 'cashwala-coupon-reveal'); ?>" <?php echo ! empty($settings['lead_required']) ? 'required' : ''; ?> />
        <?php endif; ?>
        <?php if (! empty($settings['lead_phone'])) : ?>
            <input type="tel" name="phone" placeholder="<?php esc_attr_e('Enter your phone', 'cashwala-coupon-reveal'); ?>" <?php echo ! empty($settings['lead_required']) ? 'required' : ''; ?> />
        <?php endif; ?>
        <button type="submit" class="cwcr-cta"><?php esc_html_e('Unlock Your Discount', 'cashwala-coupon-reveal'); ?></button>
    </form>
    <button type="button" class="cwcr-reveal-btn"><?php esc_html_e('Reveal Coupon', 'cashwala-coupon-reveal'); ?></button>
    <div class="cwcr-reveal-result" hidden>
        <p class="cwcr-after-text"></p>
        <div class="cwcr-coupon-wrap">
            <code class="cwcr-coupon-code"></code>
            <button type="button" class="cwcr-copy-btn"><?php esc_html_e('Copy', 'cashwala-coupon-reveal'); ?></button>
        </div>
        <p class="cwcr-copy-msg" aria-live="polite"></p>
        <p class="cwcr-countdown"></p>
    </div>
</div>
