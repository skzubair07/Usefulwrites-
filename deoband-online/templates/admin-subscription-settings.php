<?php /** Subscription settings template. */ ?>
<div class="wrap"><h1>Subscription Plans</h1><form method="post"><?php wp_nonce_field( 'do_subscription_settings_nonce' ); ?>
<?php foreach ( $settings['plans'] as $plan ) : ?>
<p><input name="plan_key[]" value="<?php echo esc_attr( $plan['key'] ); ?>" placeholder="key"> <input name="plan_label[]" value="<?php echo esc_attr( $plan['label'] ); ?>" placeholder="label"> <input type="number" step="0.01" name="plan_price[]" value="<?php echo esc_attr( $plan['monthly_price'] ); ?>"> <input type="number" name="question_limit[]" value="<?php echo esc_attr( $plan['question_limit'] ); ?>"></p>
<?php endforeach; ?>
<p><input name="plan_key[]" placeholder="key"> <input name="plan_label[]" placeholder="label"> <input type="number" step="0.01" name="plan_price[]" placeholder="Monthly Price"> <input type="number" name="question_limit[]" placeholder="Question Limit"></p>
<p><button class="button button-primary" name="do_save_subscription_settings" value="1">Save</button></p></form></div>
