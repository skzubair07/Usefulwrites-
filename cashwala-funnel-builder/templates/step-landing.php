<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="cwfb-step-wrapper cwfb-step-landing">
    <h3><?php echo esc_html__('Step 1: Landing', 'cashwala-funnel-builder'); ?></h3>
    <?php if (! empty($next_url)) : ?>
        <a class="cwfb-next-step" href="<?php echo esc_url($next_url); ?>"><?php echo esc_html__('Continue to Checkout', 'cashwala-funnel-builder'); ?></a>
    <?php endif; ?>
</div>
