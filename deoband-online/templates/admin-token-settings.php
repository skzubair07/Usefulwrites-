<?php /** Token settings template. */ ?>
<div class="wrap"><h1>Token Settings</h1><form method="post"><?php wp_nonce_field( 'do_token_settings_nonce' ); ?>
<p><label>Price Per Token <input type="number" step="0.01" name="price_per_token" value="<?php echo esc_attr( $settings['price_per_token'] ); ?>"></label></p>
<?php foreach ( $settings['packages'] as $p ) : ?>
<p><input name="package_tokens[]" type="number" value="<?php echo esc_attr( $p['tokens'] ); ?>"> tokens / <input name="package_price[]" type="number" step="0.01" value="<?php echo esc_attr( $p['price'] ); ?>"> price</p>
<?php endforeach; ?>
<p><input name="package_tokens[]" type="number" placeholder="Tokens"> / <input name="package_price[]" type="number" step="0.01" placeholder="Price"></p>
<p><button class="button button-primary" name="do_save_token_settings" value="1">Save</button></p></form></div>
