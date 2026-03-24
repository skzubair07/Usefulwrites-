<div class="wrap cwbp-wrap">
    <h1><?php esc_html_e( 'CashWala Broadcast Pro', 'cashwala-broadcast-pro' ); ?></h1>

    <div class="cwbp-stat-grid">
        <div class="cwbp-card"><h3><?php esc_html_e( 'Total Contacts', 'cashwala-broadcast-pro' ); ?></h3><p class="cwbp-stat"><?php echo esc_html( $stats['total_contacts'] ); ?></p></div>
        <div class="cwbp-card"><h3><?php esc_html_e( 'Total Campaigns', 'cashwala-broadcast-pro' ); ?></h3><p class="cwbp-stat"><?php echo esc_html( $stats['total_campaigns'] ); ?></p></div>
        <div class="cwbp-card"><h3><?php esc_html_e( 'Emails Sent', 'cashwala-broadcast-pro' ); ?></h3><p class="cwbp-stat"><?php echo esc_html( $stats['emails_sent'] ); ?></p></div>
        <div class="cwbp-card"><h3><?php esc_html_e( 'Total Opens', 'cashwala-broadcast-pro' ); ?></h3><p class="cwbp-stat"><?php echo esc_html( $stats['total_opens'] ); ?></p></div>
        <div class="cwbp-card"><h3><?php esc_html_e( 'Total Clicks', 'cashwala-broadcast-pro' ); ?></h3><p class="cwbp-stat"><?php echo esc_html( $stats['total_clicks'] ); ?></p></div>
    </div>

    <div class="cwbp-card">
        <h2><?php esc_html_e( 'Campaign Performance', 'cashwala-broadcast-pro' ); ?></h2>
        <table class="widefat striped">
            <thead><tr><th>Name</th><th>Status</th><th>Sent</th><th>Failed</th><th>Opens</th><th>Clicks</th></tr></thead>
            <tbody>
            <?php if ( $performance ) : ?>
                <?php foreach ( $performance as $row ) : ?>
                    <tr>
                        <td><?php echo esc_html( $row->name ); ?></td>
                        <td><?php echo esc_html( ucfirst( $row->status ) ); ?></td>
                        <td><?php echo esc_html( $row->sent_count ); ?></td>
                        <td><?php echo esc_html( $row->fail_count ); ?></td>
                        <td><?php echo esc_html( $row->opens ); ?></td>
                        <td><?php echo esc_html( $row->clicks ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="6"><?php esc_html_e( 'No data yet.', 'cashwala-broadcast-pro' ); ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
