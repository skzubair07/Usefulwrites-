<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$total_pages = (int) ceil( $context['total'] / $context['per_page'] );
?>
<div class="wrap cwlmp-wrap">
    <h2><?php esc_html_e( 'Licenses', 'cashwala-license-manager' ); ?></h2>

    <form method="get" class="cwlmp-filter-form">
        <input type="hidden" name="page" value="cwlmp-licenses" />
        <select name="status">
            <option value=""><?php esc_html_e( 'All Statuses', 'cashwala-license-manager' ); ?></option>
            <option value="active" <?php selected( 'active', $context['status'] ); ?>><?php esc_html_e( 'Active', 'cashwala-license-manager' ); ?></option>
            <option value="expired" <?php selected( 'expired', $context['status'] ); ?>><?php esc_html_e( 'Expired', 'cashwala-license-manager' ); ?></option>
            <option value="revoked" <?php selected( 'revoked', $context['status'] ); ?>><?php esc_html_e( 'Revoked', 'cashwala-license-manager' ); ?></option>
        </select>
        <input type="search" name="s" value="<?php echo esc_attr( $context['search'] ); ?>" placeholder="<?php esc_attr_e( 'Search by email, domain, key mask', 'cashwala-license-manager' ); ?>" />
        <button class="button"><?php esc_html_e( 'Filter', 'cashwala-license-manager' ); ?></button>
    </form>

    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead>
            <tr>
                <th><?php esc_html_e( 'ID', 'cashwala-license-manager' ); ?></th>
                <th><?php esc_html_e( 'Key', 'cashwala-license-manager' ); ?></th>
                <th><?php esc_html_e( 'Status', 'cashwala-license-manager' ); ?></th>
                <th><?php esc_html_e( 'Expiry', 'cashwala-license-manager' ); ?></th>
                <th><?php esc_html_e( 'Domain', 'cashwala-license-manager' ); ?></th>
                <th><?php esc_html_e( 'Assigned', 'cashwala-license-manager' ); ?></th>
                <th><?php esc_html_e( 'Action', 'cashwala-license-manager' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $context['licenses'] ) ) : ?>
                <tr><td colspan="7"><?php esc_html_e( 'No licenses found.', 'cashwala-license-manager' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $context['licenses'] as $license ) : ?>
                    <tr>
                        <td><?php echo esc_html( $license['id'] ); ?></td>
                        <td><code><?php echo esc_html( $license['key_mask'] ); ?></code></td>
                        <td><?php echo esc_html( ucfirst( $license['status'] ) ); ?></td>
                        <td><?php echo ! empty( $license['expiry_date'] ) ? esc_html( gmdate( 'Y-m-d', strtotime( $license['expiry_date'] ) ) ) : '—'; ?></td>
                        <td><?php echo ! empty( $license['bound_domain'] ) ? esc_html( $license['bound_domain'] ) : '—'; ?></td>
                        <td>
                            <?php echo ! empty( $license['assigned_email'] ) ? esc_html( $license['assigned_email'] ) : '—'; ?>
                            <?php if ( ! empty( $license['assigned_user_id'] ) ) : ?>
                                <br/><small><?php printf( esc_html__( 'User ID: %d', 'cashwala-license-manager' ), absint( $license['assigned_user_id'] ) ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <details>
                                <summary><?php esc_html_e( 'Edit', 'cashwala-license-manager' ); ?></summary>
                                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="cwlmp-inline-form">
                                    <input type="hidden" name="action" value="cwlmp_update_license" />
                                    <input type="hidden" name="license_id" value="<?php echo esc_attr( $license['id'] ); ?>" />
                                    <?php wp_nonce_field( 'cwlmp_update_license' ); ?>
                                    <p>
                                        <select name="status">
                                            <option value="active" <?php selected( $license['status'], 'active' ); ?>><?php esc_html_e( 'Active', 'cashwala-license-manager' ); ?></option>
                                            <option value="expired" <?php selected( $license['status'], 'expired' ); ?>><?php esc_html_e( 'Expired', 'cashwala-license-manager' ); ?></option>
                                            <option value="revoked" <?php selected( $license['status'], 'revoked' ); ?>><?php esc_html_e( 'Revoked', 'cashwala-license-manager' ); ?></option>
                                        </select>
                                    </p>
                                    <p><input type="date" name="expiry_date" value="<?php echo ! empty( $license['expiry_date'] ) ? esc_attr( gmdate( 'Y-m-d', strtotime( $license['expiry_date'] ) ) ) : ''; ?>" /></p>
                                    <p><input type="text" name="bound_domain" value="<?php echo esc_attr( $license['bound_domain'] ); ?>" placeholder="domain.com" /></p>
                                    <p><input type="email" name="assigned_email" value="<?php echo esc_attr( $license['assigned_email'] ); ?>" placeholder="email@example.com" /></p>
                                    <p><input type="number" min="0" name="assigned_user_id" value="<?php echo esc_attr( $license['assigned_user_id'] ); ?>" /></p>
                                    <p><input type="number" min="1" name="domain_limit" value="<?php echo esc_attr( $license['domain_limit'] ); ?>" /></p>
                                    <p><textarea name="notes" rows="2"><?php echo esc_textarea( $license['notes'] ); ?></textarea></p>
                                    <button class="button button-small button-primary"><?php esc_html_e( 'Save', 'cashwala-license-manager' ); ?></button>
                                </form>
                            </details>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav"><div class="tablenav-pages">
            <?php for ( $i = 1; $i <= $total_pages; $i++ ) : ?>
                <a class="button <?php echo $i === (int) $context['paged'] ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'paged' => $i ) ) ); ?>"><?php echo esc_html( $i ); ?></a>
            <?php endfor; ?>
        </div></div>
    <?php endif; ?>

    <h2><?php esc_html_e( 'Recent Validation Logs', 'cashwala-license-manager' ); ?></h2>
    <table class="wp-list-table widefat fixed striped table-view-list">
        <thead><tr><th><?php esc_html_e( 'Time', 'cashwala-license-manager' ); ?></th><th><?php esc_html_e( 'Result', 'cashwala-license-manager' ); ?></th><th><?php esc_html_e( 'Domain', 'cashwala-license-manager' ); ?></th><th><?php esc_html_e( 'Message', 'cashwala-license-manager' ); ?></th></tr></thead>
        <tbody>
        <?php foreach ( $context['logs'] as $log ) : ?>
            <tr>
                <td><?php echo esc_html( gmdate( 'Y-m-d H:i:s', strtotime( $log['created_at'] ) ) ); ?></td>
                <td><?php echo esc_html( ucfirst( $log['result'] ) ); ?></td>
                <td><?php echo esc_html( $log['domain'] ); ?></td>
                <td><?php echo esc_html( $log['message'] ); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
