<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div id="cw-sticky-bar" class="cw-sticky-bar cw-template-1 cw-position-<?php echo esc_attr($this->settings['position']); ?>" style="<?php echo esc_attr($style_vars); ?>" aria-live="polite">
    <div class="cw-sticky-inner">
        <div class="cw-message-wrap">
            <strong class="cw-message" data-role="cw-message"><?php echo esc_html($this->settings['messages'][0] ?? ''); ?></strong>
            <?php if (!empty($this->settings['promo_text'])) : ?>
                <span class="cw-promo"><?php echo esc_html($this->settings['promo_text']); ?></span>
            <?php endif; ?>
            <?php if ($this->settings['countdown_enabled']) : ?>
                <span class="cw-countdown" data-role="cw-countdown"></span>
            <?php endif; ?>
        </div>
        <div class="cw-actions">
            <?php foreach ($this->settings['buttons'] as $button) : ?>
                <a class="cw-btn cw-btn-<?php echo esc_attr($button['style']); ?>" data-role="cw-track-click" data-button-text="<?php echo esc_attr($button['text']); ?>" href="<?php echo esc_url(CW_SB_Frontend::build_button_url($button)); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($button['text']); ?></a>
            <?php endforeach; ?>
            <?php if ($this->settings['close_enabled']) : ?>
                <button type="button" class="cw-close" data-role="cw-close" aria-label="Close">×</button>
            <?php endif; ?>
        </div>
    </div>
</div>
