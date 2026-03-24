<?php
/**
 * Admin page renderer.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSB_Admin {
    /**
     * Init hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_post_wsb_reset_clicks', array( __CLASS__, 'handle_reset_clicks' ) );
    }

    /**
     * Register plugin admin menu.
     */
    public static function add_menu() {
        add_menu_page(
            __( 'WA Booster', 'whatsapp-sales-booster' ),
            __( 'WA Booster', 'whatsapp-sales-booster' ),
            'manage_options',
            'wsb-settings',
            array( __CLASS__, 'render_settings_page' ),
            'dashicons-format-chat',
            58
        );
    }

    /**
     * Render settings page.
     */
    public static function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings     = WSB_Settings::get_settings();
        $total_clicks = absint( get_option( WSB_Settings::CLICK_OPTION_KEY, 0 ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WhatsApp Sales Booster Pro', 'whatsapp-sales-booster' ); ?></h1>
            <p><?php esc_html_e( 'Configure your WhatsApp conversion widget and popup campaign.', 'whatsapp-sales-booster' ); ?></p>

            <div style="max-width:900px;background:#fff;border:1px solid #e2e4e7;padding:20px;border-radius:10px;">
                <form method="post" action="options.php">
                    <?php settings_fields( 'wsb_settings_group' ); ?>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="wsb-phone-number"><?php esc_html_e( 'WhatsApp Number', 'whatsapp-sales-booster' ); ?></label></th>
                                <td>
                                    <input id="wsb-phone-number" type="text" class="regular-text" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[phone_number]" value="<?php echo esc_attr( (string) $settings['phone_number'] ); ?>" placeholder="15551234567" />
                                    <p class="description"><?php esc_html_e( 'Include country code. Digits only, without + or spaces.', 'whatsapp-sales-booster' ); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wsb-prefilled-message"><?php esc_html_e( 'Prefilled Message', 'whatsapp-sales-booster' ); ?></label></th>
                                <td>
                                    <textarea id="wsb-prefilled-message" class="large-text" rows="4" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[prefilled_message]"><?php echo esc_textarea( (string) $settings['prefilled_message'] ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wsb-cta-text"><?php esc_html_e( 'CTA Button Text', 'whatsapp-sales-booster' ); ?></label></th>
                                <td>
                                    <input id="wsb-cta-text" type="text" class="regular-text" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[cta_text]" value="<?php echo esc_attr( (string) $settings['cta_text'] ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e( 'Popup Enable', 'whatsapp-sales-booster' ); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[popup_enabled]" value="1" <?php checked( '1', (string) $settings['popup_enabled'] ); ?> />
                                        <?php esc_html_e( 'Enable delayed popup', 'whatsapp-sales-booster' ); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wsb-popup-delay"><?php esc_html_e( 'Popup Delay (seconds)', 'whatsapp-sales-booster' ); ?></label></th>
                                <td>
                                    <input id="wsb-popup-delay" type="number" min="0" class="small-text" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[popup_delay]" value="<?php echo esc_attr( (string) $settings['popup_delay'] ); ?>" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wsb-popup-text"><?php esc_html_e( 'Popup Text', 'whatsapp-sales-booster' ); ?></label></th>
                                <td>
                                    <textarea id="wsb-popup-text" class="large-text" rows="3" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[popup_text]"><?php echo esc_textarea( (string) $settings['popup_text'] ); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="wsb-device-visibility"><?php esc_html_e( 'Device Visibility', 'whatsapp-sales-booster' ); ?></label></th>
                                <td>
                                    <select id="wsb-device-visibility" name="<?php echo esc_attr( WSB_Settings::OPTION_KEY ); ?>[device_visibility]">
                                        <option value="both" <?php selected( 'both', (string) $settings['device_visibility'] ); ?>><?php esc_html_e( 'Show on both', 'whatsapp-sales-booster' ); ?></option>
                                        <option value="mobile" <?php selected( 'mobile', (string) $settings['device_visibility'] ); ?>><?php esc_html_e( 'Mobile only', 'whatsapp-sales-booster' ); ?></option>
                                        <option value="desktop" <?php selected( 'desktop', (string) $settings['device_visibility'] ); ?>><?php esc_html_e( 'Desktop only', 'whatsapp-sales-booster' ); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Save Settings', 'whatsapp-sales-booster' ) ); ?>
                </form>
            </div>

            <div style="margin-top:20px;max-width:900px;background:#fff;border:1px solid #e2e4e7;padding:20px;border-radius:10px;">
                <h2><?php esc_html_e( 'Click Tracking', 'whatsapp-sales-booster' ); ?></h2>
                <p><strong><?php esc_html_e( 'Total WhatsApp Clicks:', 'whatsapp-sales-booster' ); ?></strong> <?php echo esc_html( (string) $total_clicks ); ?></p>

                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="wsb_reset_clicks" />
                    <?php wp_nonce_field( 'wsb_reset_clicks_action', 'wsb_reset_clicks_nonce' ); ?>
                    <?php submit_button( __( 'Reset Click Counter', 'whatsapp-sales-booster' ), 'secondary', 'submit', false ); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Reset click counter.
     */
    public static function handle_reset_clicks() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized request.', 'whatsapp-sales-booster' ) );
        }

        check_admin_referer( 'wsb_reset_clicks_action', 'wsb_reset_clicks_nonce' );

        update_option( WSB_Settings::CLICK_OPTION_KEY, 0 );

        wp_safe_redirect( admin_url( 'admin.php?page=wsb-settings&wsb_reset=1' ) );
        exit;
    }
}
