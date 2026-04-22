<?php /** Import settings template. */ ?>
<div class="wrap"><h1>Import Settings</h1><form method="post"><?php wp_nonce_field( 'do_import_settings_nonce' ); ?>
<p><label>Import limit per run <input type="number" min="1" name="import_limit" value="<?php echo esc_attr( $settings['import_limit'] ?? 10 ); ?>"></label></p>
<?php foreach ( $settings['sources'] as $source ) : ?>
<p><input name="source_url[]" size="90" value="<?php echo esc_attr( $source['url'] ); ?>"></p>
<?php endforeach; ?>
<p><input name="source_url[]" size="90" placeholder="https://source-site.example/feed"></p>
<p><button class="button button-primary" name="do_save_import_settings" value="1">Save</button></p></form></div>
