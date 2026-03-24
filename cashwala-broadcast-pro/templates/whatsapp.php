<div class="wrap cwbp-wrap">
    <h1><?php esc_html_e( 'WhatsApp Broadcast', 'cashwala-broadcast-pro' ); ?></h1>

    <div class="cwbp-card">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=cwbp_send_whatsapp' ) ); ?>">
            <?php wp_nonce_field( 'cwbp_send_whatsapp' ); ?>
            <p>
                <select name="wa_audience">
                    <option value="all"><?php esc_html_e( 'All users', 'cashwala-broadcast-pro' ); ?></option>
                    <option value="buyers"><?php esc_html_e( 'Only buyers', 'cashwala-broadcast-pro' ); ?></option>
                    <option value="leads"><?php esc_html_e( 'Only leads', 'cashwala-broadcast-pro' ); ?></option>
                </select>
            </p>
            <p><textarea name="wa_message" rows="5" class="large-text" placeholder="Message with {name}" required></textarea></p>
            <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Generate Broadcast Links', 'cashwala-broadcast-pro' ); ?></button></p>
        </form>
    </div>

    <?php if ( ! empty( $links ) ) : ?>
        <div class="cwbp-card">
            <h2><?php esc_html_e( 'Generated Links', 'cashwala-broadcast-pro' ); ?></h2>
            <p><?php esc_html_e( 'Click any link below to open WhatsApp with pre-filled message.', 'cashwala-broadcast-pro' ); ?></p>
            <ol>
                <?php foreach ( $links as $link ) : ?>
                    <li><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link ); ?></a></li>
                <?php endforeach; ?>
            </ol>
        </div>
    <?php endif; ?>
</div>
