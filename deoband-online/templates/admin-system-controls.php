<?php /** System controls template. */ ?>
<div class="wrap"><h1>System Controls</h1>
<form method="post"><?php wp_nonce_field( 'do_system_controls_nonce' ); ?>
<h2>Search Weights</h2>
<p><label>Question Weight <input type="number" name="question_weight" value="<?php echo esc_attr( $search['question_weight'] ); ?>"></label></p>
<p><label>Keyword Weight <input type="number" name="keyword_weight" value="<?php echo esc_attr( $search['keyword_weight'] ); ?>"></label></p>
<p><label>Answer Weight <input type="number" name="answer_weight" value="<?php echo esc_attr( $search['answer_weight'] ); ?>"></label></p>
<h2>AI Controls</h2>
<p><label><input type="checkbox" name="ai_enabled" value="1" <?php checked( $ai['enabled'], 1 ); ?>> Enable AI</label></p>
<p><label>Primary Provider <select name="ai_primary"><option value="grok" <?php selected( $ai['primary'], 'grok' ); ?>>Grok</option><option value="openai" <?php selected( $ai['primary'], 'openai' ); ?>>OpenAI</option><option value="gemini" <?php selected( $ai['primary'], 'gemini' ); ?>>Gemini</option></select></label></p>
<p><label>Backup Provider <select name="ai_backup"><option value="openai" <?php selected( $ai['backup'], 'openai' ); ?>>OpenAI</option><option value="gemini" <?php selected( $ai['backup'], 'gemini' ); ?>>Gemini</option><option value="grok" <?php selected( $ai['backup'], 'grok' ); ?>>Grok</option></select></label></p>
<p><label>Timeout (seconds) <input type="number" name="ai_timeout" value="<?php echo esc_attr( $ai['timeout'] ); ?>"></label></p>
<p><label>Retry Count <input type="number" name="ai_retry" value="<?php echo esc_attr( $ai['retry'] ); ?>"></label></p>
<h2>Rate Limit</h2>
<p><label>Max questions per minute <input type="number" name="max_per_minute" value="<?php echo esc_attr( $rate['max_per_minute'] ); ?>"></label></p>
<h2>Trending</h2>
<p><label><input type="checkbox" name="trending_enabled" value="1" <?php checked( $trending['enabled'], 1 ); ?>> Enable trending calculations</label></p>
<p><button class="button button-primary" name="do_save_system_controls" value="1">Save Controls</button></p>
</form></div>
