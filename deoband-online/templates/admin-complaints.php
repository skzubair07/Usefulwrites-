<?php /** Complaint manager template. */ ?>
<div class="wrap"><h1>Complaint Manager</h1>
<table class="widefat"><thead><tr><th>ID</th><th>User</th><th>Subject</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php foreach ( $complaints as $c ) : ?><tr><td><?php echo esc_html( $c['id'] ); ?></td><td><?php echo esc_html( $c['user_id'] ); ?></td><td><?php echo esc_html( $c['subject'] ); ?></td><td><?php echo esc_html( $c['status'] ); ?></td><td><?php echo esc_html( $c['created_at'] ); ?></td></tr><?php endforeach; ?>
</tbody></table></div>
