<?php /** API settings template. */ ?>
<div class="wrap"><h1>API Settings</h1>
<form method="post"><?php wp_nonce_field( 'do_api_settings_nonce' ); ?>
<h2>AI Providers</h2>
<p><label>Grok API Key <input name="grok_api_key" value="<?php echo esc_attr( $settings['grok_api_key'] ); ?>" size="80"></label></p>
<p><label>Grok Model <input name="grok_model" value="<?php echo esc_attr( $settings['grok_model'] ); ?>"></label></p>
<p><label>OpenAI API Key <input name="openai_api_key" value="<?php echo esc_attr( $settings['openai_api_key'] ); ?>" size="80"></label></p>
<p><label>OpenAI API URL <input name="openai_api_url" value="<?php echo esc_attr( $settings['openai_api_url'] ); ?>" size="80"></label></p>
<p><label>OpenAI Model <input name="openai_model" value="<?php echo esc_attr( $settings['openai_model'] ); ?>"></label></p>
<p><label>Gemini API Key <input name="gemini_api_key" value="<?php echo esc_attr( $settings['gemini_api_key'] ); ?>" size="80"></label></p>
<p><label>Gemini Model <input name="gemini_model" value="<?php echo esc_attr( $settings['gemini_model'] ); ?>"></label></p>
<p><label>Google Translate Key <input name="google_translate_key" value="<?php echo esc_attr( $settings['google_translate_key'] ); ?>" size="80"></label></p>
<h2>Other APIs</h2>
<p><label>Prayer Time API URL <input name="prayer_api_url" value="<?php echo esc_attr( $settings['prayer_api_url'] ); ?>" size="80"></label></p>
<p><label>News RSS URL <input name="news_rss_url" value="<?php echo esc_attr( $settings['news_rss_url'] ); ?>" size="80"></label></p>
<p><button class="button button-primary" name="do_save_api_settings" value="1">Save</button></p></form></div>
