<div class="wrap cwbp-wrap">
    <h1><?php esc_html_e( 'Contacts', 'cashwala-broadcast-pro' ); ?></h1>

    <div class="cwbp-card">
        <form method="get" class="cwbp-inline-form">
            <input type="hidden" name="page" value="cwbp_contacts" />
            <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email, phone', 'cashwala-broadcast-pro' ); ?>" />
            <select name="status">
                <option value=""><?php esc_html_e( 'All', 'cashwala-broadcast-pro' ); ?></option>
                <option value="lead" <?php selected( $status, 'lead' ); ?>><?php esc_html_e( 'Leads', 'cashwala-broadcast-pro' ); ?></option>
                <option value="buyer" <?php selected( $status, 'buyer' ); ?>><?php esc_html_e( 'Buyers', 'cashwala-broadcast-pro' ); ?></option>
            </select>
            <button class="button button-primary"><?php esc_html_e( 'Filter', 'cashwala-broadcast-pro' ); ?></button>
        </form>

        <div class="cwbp-actions">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=cwbp_export_contacts' ) ); ?>">
                <?php wp_nonce_field( 'cwbp_export_contacts' ); ?>
                <input type="hidden" name="status" value="<?php echo esc_attr( $status ); ?>" />
                <button class="button"><?php esc_html_e( 'Export CSV', 'cashwala-broadcast-pro' ); ?></button>
            </form>
        </div>

        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=cwbp_delete_contacts' ) ); ?>">
            <?php wp_nonce_field( 'cwbp_delete_contacts' ); ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th><input type="checkbox" id="cwbp-select-all" /></th>
                    <th><?php esc_html_e( 'Name', 'cashwala-broadcast-pro' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'cashwala-broadcast-pro' ); ?></th>
                    <th><?php esc_html_e( 'Phone', 'cashwala-broadcast-pro' ); ?></th>
                    <th><?php esc_html_e( 'Source', 'cashwala-broadcast-pro' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'cashwala-broadcast-pro' ); ?></th>
                    <th><?php esc_html_e( 'Created', 'cashwala-broadcast-pro' ); ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if ( ! empty( $contacts ) ) : ?>
                    <?php foreach ( $contacts as $contact ) : ?>
                        <tr>
                            <td><input type="checkbox" name="contact_ids[]" value="<?php echo esc_attr( $contact->id ); ?>" /></td>
                            <td><?php echo esc_html( $contact->name ); ?></td>
                            <td><?php echo esc_html( $contact->email ); ?></td>
                            <td><?php echo esc_html( $contact->phone ); ?></td>
                            <td><?php echo esc_html( $contact->source ); ?></td>
                            <td><span class="cwbp-badge cwbp-<?php echo esc_attr( $contact->status ); ?>"><?php echo esc_html( ucfirst( $contact->status ) ); ?></span></td>
                            <td><?php echo esc_html( $contact->created_at ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7"><?php esc_html_e( 'No contacts found.', 'cashwala-broadcast-pro' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <p><button class="button button-secondary" type="submit"><?php esc_html_e( 'Delete Selected', 'cashwala-broadcast-pro' ); ?></button></p>
        </form>
    </div>
</div>
