<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap cwlmp-wrap">
    <h1><?php esc_html_e( 'CashWala License Manager Pro', 'cashwala-license-manager' ); ?></h1>

    <div class="cwlmp-cards">
        <div class="cwlmp-card"><strong><?php echo esc_html( $context['stats']['total'] ); ?></strong><span><?php esc_html_e( 'Total', 'cashwala-license-manager' ); ?></span></div>
        <div class="cwlmp-card"><strong><?php echo esc_html( $context['stats']['active'] ); ?></strong><span><?php esc_html_e( 'Active', 'cashwala-license-manager' ); ?></span></div>
        <div class="cwlmp-card"><strong><?php echo esc_html( $context['stats']['expired'] ); ?></strong><span><?php esc_html_e( 'Expired', 'cashwala-license-manager' ); ?></span></div>
        <div class="cwlmp-card"><strong><?php echo esc_html( $context['stats']['revoked'] ); ?></strong><span><?php esc_html_e( 'Revoked', 'cashwala-license-manager' ); ?></span></div>
    </div>

    <p class="description">
        <?php
        printf(
            /* translators: 1: valid requests, 2: invalid requests */
            esc_html__( 'Last 7 days validations — Valid: %1$d | Invalid: %2$d | Expired: %3$d | Revoked: %4$d', 'cashwala-license-manager' ),
            absint( $context['vstats']['valid'] ),
            absint( $context['vstats']['invalid'] ),
            absint( $context['vstats']['expired'] ),
            absint( $context['vstats']['revoked'] )
        );
        ?>
    </p>

    <?php if ( ! empty( $context['generated'] ) && is_array( $context['generated'] ) ) : ?>
        <div class="notice notice-success">
            <p><strong><?php esc_html_e( 'Generated Keys (copy now, they are not stored in plain text):', 'cashwala-license-manager' ); ?></strong></p>
            <textarea readonly rows="5" class="large-text code"><?php echo esc_textarea( implode( PHP_EOL, $context['generated'] ) ); ?></textarea>
        </div>
    <?php endif; ?>

    <div class="cwlmp-grid">
        <div class="cwlmp-panel">
            <h2><?php esc_html_e( 'Generate Single License', 'cashwala-license-manager' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="cwlmp_generate_license" />
                <?php wp_nonce_field( 'cwlmp_generate_license' ); ?>
                <?php include CWLMP_PATH . 'templates/license-fields.php'; ?>
                <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Generate License', 'cashwala-license-manager' ); ?></button></p>
            </form>
        </div>

        <div class="cwlmp-panel">
            <h2><?php esc_html_e( 'Bulk Generate', 'cashwala-license-manager' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="cwlmp_bulk_generate_license" />
                <?php wp_nonce_field( 'cwlmp_bulk_generate_license' ); ?>
                <p>
                    <label for="bulk_count"><?php esc_html_e( 'Number of Keys', 'cashwala-license-manager' ); ?></label>
                    <input id="bulk_count" name="bulk_count" type="number" min="1" max="500" value="10" />
                </p>
                <?php include CWLMP_PATH . 'templates/license-fields.php'; ?>
                <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Bulk Generate Keys', 'cashwala-license-manager' ); ?></button></p>
            </form>
        </div>
    </div>

    <p>
        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=cwlmp_export_csv' ), 'cwlmp_export_csv' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Export Licenses CSV', 'cashwala-license-manager' ); ?></a>
    </p>
</div>
