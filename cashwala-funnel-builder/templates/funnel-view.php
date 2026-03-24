<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="cwfb-funnel-view" data-funnel-id="<?php echo isset($funnel['id']) ? esc_attr($funnel['id']) : 0; ?>">
    <h3><?php echo isset($funnel['name']) ? esc_html($funnel['name']) : ''; ?></h3>
</div>
