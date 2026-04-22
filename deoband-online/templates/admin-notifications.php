<?php /** Notifications admin template. */ ?>
<div class="wrap"><h1>Notifications</h1>
<p>Last broadcast: <?php echo esc_html( $last['message'] ?? 'None' ); ?></p>
<p>Send broadcasts using AJAX action <code>do_send_broadcast</code>.</p></div>
