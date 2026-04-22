<?php /** Payment settings template. */ ?>
<div class="wrap"><h1>Payment Settings</h1>
<form method="post"><?php wp_nonce_field( 'do_payment_settings_nonce' ); ?>
<p><label><input type="checkbox" name="manual_mode" value="1" <?php checked( $settings['manual_mode'], 1 ); ?>> Enable Manual UPI</label></p>
<p><label>UPI ID <input name="upi_id" value="<?php echo esc_attr( $settings['upi_id'] ); ?>"></label></p>
<p><label><input type="checkbox" name="razorpay_enabled" value="1" <?php checked( $settings['razorpay_enabled'], 1 ); ?>> Enable Razorpay Structure</label></p>
<p><label>Razorpay Key <input name="razorpay_key" value="<?php echo esc_attr( $settings['razorpay_key'] ); ?>"></label></p>
<p><label>Disclaimer<textarea name="disclaimer" rows="4" cols="80"><?php echo esc_textarea( $settings['disclaimer'] ); ?></textarea></label></p>
<p><button class="button button-primary" name="do_save_payment_settings" value="1">Save</button></p></form></div>
