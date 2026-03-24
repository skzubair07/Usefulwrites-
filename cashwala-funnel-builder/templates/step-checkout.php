<?php
if (! defined('ABSPATH')) {
    exit;
}
?>
<div class="cwfb-step-wrapper cwfb-step-checkout">
    <h3><?php echo esc_html__('Step 2: Checkout', 'cashwala-funnel-builder'); ?></h3>
    <?php if (! empty($next_url)) : ?>
        <a class="cwfb-next-step" href="<?php echo esc_url($next_url); ?>"><?php echo esc_html__('Complete Order', 'cashwala-funnel-builder'); ?></a>
    <?php endif; ?>
</div>
