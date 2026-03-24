<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<p>
    <label><?php esc_html_e( 'Status', 'cashwala-license-manager' ); ?></label>
    <select name="status">
        <option value="active"><?php esc_html_e( 'Active', 'cashwala-license-manager' ); ?></option>
        <option value="expired"><?php esc_html_e( 'Expired', 'cashwala-license-manager' ); ?></option>
        <option value="revoked"><?php esc_html_e( 'Revoked', 'cashwala-license-manager' ); ?></option>
    </select>
</p>
<p>
    <label><?php esc_html_e( 'Expiry Date', 'cashwala-license-manager' ); ?></label>
    <input name="expiry_date" type="date" />
</p>
<p>
    <label><?php esc_html_e( 'Domain Limit', 'cashwala-license-manager' ); ?></label>
    <input name="domain_limit" type="number" min="1" value="1" />
</p>
<p>
    <label><?php esc_html_e( 'Assign Email', 'cashwala-license-manager' ); ?></label>
    <input name="assigned_email" type="email" class="regular-text" />
</p>
<p>
    <label><?php esc_html_e( 'Assign User ID', 'cashwala-license-manager' ); ?></label>
    <input name="assigned_user_id" type="number" min="0" class="small-text" />
</p>
<p>
    <label><?php esc_html_e( 'Notes', 'cashwala-license-manager' ); ?></label>
    <textarea name="notes" rows="3" class="large-text"></textarea>
</p>
