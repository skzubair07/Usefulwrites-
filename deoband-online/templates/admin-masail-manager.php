<?php /** Masail manager template. */ ?>
<div class="wrap"><h1>Masail / Questions Manager</h1>
<p>Add or edit entries via AJAX endpoint <code>do_save_masail</code>.</p>
<table class="widefat"><thead><tr><th>ID</th><th>Question</th><th>Category</th><th>Keywords</th><th>Source</th></tr></thead><tbody>
<?php foreach ( $items as $row ) : ?><tr><td><?php echo esc_html( $row['id'] ); ?></td><td><?php echo esc_html( $row['question'] ); ?></td><td><?php echo esc_html( $row['category'] ); ?></td><td><?php echo esc_html( $row['keywords'] ); ?></td><td><?php echo esc_url( $row['source_url'] ); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
