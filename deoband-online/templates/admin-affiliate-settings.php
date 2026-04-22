<?php /** Affiliate settings template. */ ?>
<div class="wrap"><h1>Affiliate Settings</h1><form method="post"><?php wp_nonce_field( 'do_affiliate_settings_nonce' ); ?>
<p><label>Commission Percentage <input type="number" step="0.01" max="100" min="0" name="commission_percent" value="<?php echo esc_attr( $settings['commission_percent'] ); ?>"></label></p>
<p><button class="button button-primary" name="do_save_affiliate_settings" value="1">Save</button></p></form></div>
