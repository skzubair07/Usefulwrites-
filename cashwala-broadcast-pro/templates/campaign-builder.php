<div class="wrap cwbp-wrap">
    <h1><?php esc_html_e( 'Campaign Builder', 'cashwala-broadcast-pro' ); ?></h1>

    <div class="cwbp-grid">
        <div class="cwbp-card">
            <h2><?php esc_html_e( 'Create Campaign', 'cashwala-broadcast-pro' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=cwbp_save_campaign' ) ); ?>">
                <?php wp_nonce_field( 'cwbp_save_campaign' ); ?>
                <p><input class="regular-text" type="text" name="name" placeholder="Campaign name" required /></p>
                <p><input class="large-text" type="text" name="subject" placeholder="Subject" required /></p>
                <p><textarea class="large-text" rows="8" name="message" placeholder="HTML message. Use {name} smart tag." required></textarea></p>
                <p>
                    <label><?php esc_html_e( 'Audience', 'cashwala-broadcast-pro' ); ?></label>
                    <select name="audience">
                        <option value="all"><?php esc_html_e( 'All users', 'cashwala-broadcast-pro' ); ?></option>
                        <option value="buyers"><?php esc_html_e( 'Only buyers', 'cashwala-broadcast-pro' ); ?></option>
                        <option value="leads"><?php esc_html_e( 'Only leads', 'cashwala-broadcast-pro' ); ?></option>
                    </select>
                </p>
                <p>
                    <label><input type="radio" name="send_mode" value="send_now" checked /> <?php esc_html_e( 'Send now', 'cashwala-broadcast-pro' ); ?></label>
                    <label><input type="radio" name="send_mode" value="schedule" /> <?php esc_html_e( 'Schedule later', 'cashwala-broadcast-pro' ); ?></label>
                </p>
                <p><input type="datetime-local" name="scheduled_at" /></p>
                <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Queue Campaign', 'cashwala-broadcast-pro' ); ?></button></p>
            </form>
        </div>

        <div class="cwbp-card">
            <h2><?php esc_html_e( 'Campaign History', 'cashwala-broadcast-pro' ); ?></h2>
            <table class="widefat striped">
                <thead><tr><th>Name</th><th>Status</th><th>Recipients</th><th>Sent</th><th>Failed</th></tr></thead>
                <tbody>
                <?php if ( $campaigns ) : ?>
                    <?php foreach ( $campaigns as $campaign ) : ?>
                        <tr>
                            <td><?php echo esc_html( $campaign->name ); ?></td>
                            <td><?php echo esc_html( ucfirst( $campaign->status ) ); ?></td>
                            <td><?php echo esc_html( $campaign->total_recipients ); ?></td>
                            <td><?php echo esc_html( $campaign->sent_count ); ?></td>
                            <td><?php echo esc_html( $campaign->fail_count ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="5"><?php esc_html_e( 'No campaigns yet.', 'cashwala-broadcast-pro' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
