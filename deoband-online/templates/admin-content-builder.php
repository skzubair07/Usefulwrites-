<?php /** Content builder template. */ ?>
<div class="wrap"><h1>Content Builder</h1>
<form method="post"><?php wp_nonce_field( 'do_content_builder_nonce' ); ?>
<div id="do-blocks">
<?php foreach ( $blocks as $i => $block ) : ?>
<p><select name="block_type[]"><option value="image" <?php selected( $block['type'], 'image' ); ?>>Image</option><option value="video" <?php selected( $block['type'], 'video' ); ?>>Video</option><option value="html" <?php selected( $block['type'], 'html' ); ?>>HTML</option><option value="script" <?php selected( $block['type'], 'script' ); ?>>Script</option></select>
<textarea name="block_value[]" rows="2" cols="80"><?php echo esc_textarea( $block['value'] ); ?></textarea></p>
<?php endforeach; ?>
<p><select name="block_type[]"><option value="image">Image</option><option value="video">Video</option><option value="html">HTML</option><option value="script">Script</option></select><textarea name="block_value[]" rows="2" cols="80"></textarea></p>
</div>
<p><button class="button button-primary" name="do_save_content_builder" value="1">Save</button></p></form></div>
