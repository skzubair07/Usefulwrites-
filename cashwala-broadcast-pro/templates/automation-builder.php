<div class="wrap cwbp-wrap">
    <h1><?php esc_html_e( 'Automation Builder', 'cashwala-broadcast-pro' ); ?></h1>
    <div class="cwbp-grid">
        <div class="cwbp-card">
            <h2><?php esc_html_e( 'Create Follow-up Flow', 'cashwala-broadcast-pro' ); ?></h2>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php?action=cwbp_save_automation' ) ); ?>">
                <?php wp_nonce_field( 'cwbp_save_automation' ); ?>
                <p><input type="text" class="regular-text" name="name" placeholder="Automation name" required /></p>
                <p>
                    <select name="trigger_event">
                        <option value="new_lead"><?php esc_html_e( 'Trigger: New Lead', 'cashwala-broadcast-pro' ); ?></option>
                        <option value="new_purchase"><?php esc_html_e( 'Trigger: New Purchase', 'cashwala-broadcast-pro' ); ?></option>
                    </select>
                </p>
                <?php for ( $i = 0; $i < 3; $i++ ) : ?>
                    <div class="cwbp-step">
                        <h4><?php echo esc_html( 'Step ' . ( $i + 1 ) ); ?></h4>
                        <input type="number" min="0" name="step_delay[]" placeholder="Delay days (0/1/3...)" />
                        <input type="text" class="large-text" name="step_subject[]" placeholder="Subject" />
                        <textarea class="large-text" rows="4" name="step_message[]" placeholder="Message with {name}"></textarea>
                    </div>
                <?php endfor; ?>
                <p><button class="button button-primary" type="submit"><?php esc_html_e( 'Save Automation', 'cashwala-broadcast-pro' ); ?></button></p>
            </form>
        </div>

        <div class="cwbp-card">
            <h2><?php esc_html_e( 'Saved Automations', 'cashwala-broadcast-pro' ); ?></h2>
            <table class="widefat striped">
                <thead><tr><th>Name</th><th>Trigger</th><th>Status</th><th>Steps</th></tr></thead>
                <tbody>
                <?php if ( $automations ) : ?>
                    <?php foreach ( $automations as $automation ) : ?>
                        <?php $steps = json_decode( $automation->steps, true ); ?>
                        <tr>
                            <td><?php echo esc_html( $automation->name ); ?></td>
                            <td><?php echo esc_html( $automation->trigger_event ); ?></td>
                            <td><?php echo esc_html( ucfirst( $automation->status ) ); ?></td>
                            <td><?php echo esc_html( is_array( $steps ) ? count( $steps ) : 0 ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4"><?php esc_html_e( 'No automations configured.', 'cashwala-broadcast-pro' ); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
