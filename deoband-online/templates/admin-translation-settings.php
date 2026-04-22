<?php /** Translation settings template. */ ?>
<div class="wrap"><h1>Translation Settings</h1>
<form method="post"><?php wp_nonce_field( 'do_translation_settings_nonce' ); ?>
<p><label><input type="checkbox" name="enabled" value="1" <?php checked( $settings['enabled'], 1 ); ?>> Enable translation</label></p>
<p><label>Provider <select name="provider"><option value="google" <?php selected( $settings['provider'], 'google' ); ?>>Google</option><option value="grok" <?php selected( $settings['provider'], 'grok' ); ?>>Grok</option></select></label></p>
<p><label><input type="checkbox" name="local_ai" value="1" <?php checked( $settings['local_ai'], 1 ); ?>> Use local AI translation mode</label></p>
<p><button class="button button-primary" name="do_save_translation_settings" value="1">Save</button></p>
</form></div>
