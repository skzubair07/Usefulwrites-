<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_EIB_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_menu(): void {
        add_menu_page(
            esc_html__( 'CashWala Exit Booster', 'cashwala-exit-booster' ),
            esc_html__( 'CashWala Exit Booster', 'cashwala-exit-booster' ),
            'manage_options',
            'cw-eib-settings',
            array( $this, 'render_settings_page' ),
            'dashicons-warning',
            59
        );

        add_submenu_page(
            'cw-eib-settings',
            esc_html__( 'Error Logs', 'cashwala-exit-booster' ),
            esc_html__( 'Error Logs', 'cashwala-exit-booster' ),
            'manage_options',
            'cw-eib-logs',
            array( $this, 'render_logs_page' )
        );
    }

    public function register_settings(): void {
        register_setting( 'cw_eib_settings_group', 'cw_eib_settings', array( $this, 'sanitize_settings' ) );
    }

    public function sanitize_settings( $input ): array {
        $defaults = CW_EIB_Core::instance()->default_settings();
        $data     = wp_parse_args( is_array( $input ) ? $input : array(), $defaults );

        $data['enabled']                 = empty( $data['enabled'] ) ? 0 : 1;
        $data['trigger_type']            = in_array( $data['trigger_type'], array( 'exit', 'delay', 'scroll' ), true ) ? $data['trigger_type'] : 'exit';
        $data['delay_seconds']           = max( 0, absint( $data['delay_seconds'] ) );
        $data['scroll_percent']          = min( 100, max( 1, absint( $data['scroll_percent'] ) ) );
        $data['popup_variant']           = in_array( $data['popup_variant'], array( 'discount', 'lead', 'whatsapp' ), true ) ? $data['popup_variant'] : 'discount';
        $data['headline']                = sanitize_text_field( $data['headline'] );
        $data['subtext']                 = sanitize_textarea_field( $data['subtext'] );
        $data['button_text']             = sanitize_text_field( $data['button_text'] );
        $data['coupon_code']             = sanitize_text_field( $data['coupon_code'] );
        $data['countdown_seconds']       = max( 0, absint( $data['countdown_seconds'] ) );
        $data['show_name']               = empty( $data['show_name'] ) ? 0 : 1;
        $data['show_email']              = empty( $data['show_email'] ) ? 0 : 1;
        $data['show_phone']              = empty( $data['show_phone'] ) ? 0 : 1;
        $data['required_validation']     = in_array( $data['required_validation'], array( 'email', 'phone', 'none' ), true ) ? $data['required_validation'] : 'email';
        $data['whatsapp_number']         = preg_replace( '/[^0-9+]/', '', (string) $data['whatsapp_number'] );
        $data['whatsapp_message']        = sanitize_text_field( $data['whatsapp_message'] );
        $data['bg_color']                = sanitize_hex_color( $data['bg_color'] ) ?: $defaults['bg_color'];
        $data['button_color']            = sanitize_hex_color( $data['button_color'] ) ?: $defaults['button_color'];
        $data['border_radius']           = max( 0, absint( $data['border_radius'] ) );
        $data['font_size']               = max( 12, absint( $data['font_size'] ) );
        $data['target_devices']          = in_array( $data['target_devices'], array( 'all', 'mobile', 'desktop' ), true ) ? $data['target_devices'] : 'all';
        $data['frequency']               = in_array( $data['frequency'], array( 'session_once', 'always' ), true ) ? $data['frequency'] : 'session_once';
        $data['template']                = in_array( $data['template'], array( 'template_1', 'template_2' ), true ) ? $data['template'] : 'template_1';
        $data['test_mode']               = empty( $data['test_mode'] ) ? 0 : 1;
        $data['cookie_duration_minutes'] = max( 1, absint( $data['cookie_duration_minutes'] ) );
        $data['inactivity_seconds']      = max( 3, absint( $data['inactivity_seconds'] ) );

        $targets = array();
        if ( ! empty( $data['target_posts'] ) ) {
            $raw = is_array( $data['target_posts'] ) ? $data['target_posts'] : explode( ',', (string) $data['target_posts'] );
            foreach ( $raw as $item ) {
                $id = absint( $item );
                if ( $id > 0 ) {
                    $targets[] = $id;
                }
            }
        }

        $data['target_posts'] = array_values( array_unique( $targets ) );

        return $data;
    }

    public function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings  = CW_EIB_Core::get_settings();
        $analytics = get_option( 'cw_eib_analytics', array( 'views' => 0, 'clicks' => 0, 'conversions' => 0 ) );
        ?>
        <div class="wrap cw-eib-admin-wrap">
            <h1><?php echo esc_html__( 'CashWala Exit Booster', 'cashwala-exit-booster' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'cw_eib_settings_group' ); ?>

                <h2><?php echo esc_html__( 'General Settings', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th><?php echo esc_html__( 'Enable Plugin', 'cashwala-exit-booster' ); ?></th><td><label><input type="checkbox" name="cw_eib_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php echo esc_html__( 'Enabled', 'cashwala-exit-booster' ); ?></label></td></tr>
                    <tr><th><?php echo esc_html__( 'Trigger Type', 'cashwala-exit-booster' ); ?></th><td><select name="cw_eib_settings[trigger_type]"><option value="exit" <?php selected( $settings['trigger_type'], 'exit' ); ?>>Exit Intent</option><option value="delay" <?php selected( $settings['trigger_type'], 'delay' ); ?>>Delay</option><option value="scroll" <?php selected( $settings['trigger_type'], 'scroll' ); ?>>Scroll %</option></select></td></tr>
                    <tr><th><?php echo esc_html__( 'Delay (seconds)', 'cashwala-exit-booster' ); ?></th><td><input type="number" min="0" name="cw_eib_settings[delay_seconds]" value="<?php echo esc_attr( $settings['delay_seconds'] ); ?>"></td></tr>
                    <tr><th><?php echo esc_html__( 'Scroll %', 'cashwala-exit-booster' ); ?></th><td><input type="number" min="1" max="100" name="cw_eib_settings[scroll_percent]" value="<?php echo esc_attr( $settings['scroll_percent'] ); ?>"></td></tr>
                </table>

                <h2><?php echo esc_html__( 'Popup Content', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Variant</th><td><select name="cw_eib_settings[popup_variant]"><option value="discount" <?php selected( $settings['popup_variant'], 'discount' ); ?>>Discount popup</option><option value="lead" <?php selected( $settings['popup_variant'], 'lead' ); ?>>Lead capture popup</option><option value="whatsapp" <?php selected( $settings['popup_variant'], 'whatsapp' ); ?>>WhatsApp redirect popup</option></select></td></tr>
                    <tr><th>Template</th><td><select name="cw_eib_settings[template]"><option value="template_1" <?php selected( $settings['template'], 'template_1' ); ?>>Template 1</option><option value="template_2" <?php selected( $settings['template'], 'template_2' ); ?>>Template 2</option></select></td></tr>
                    <tr><th>Headline</th><td><input class="regular-text" type="text" name="cw_eib_settings[headline]" value="<?php echo esc_attr( $settings['headline'] ); ?>"></td></tr>
                    <tr><th>Subtext</th><td><textarea class="large-text" name="cw_eib_settings[subtext]"><?php echo esc_textarea( $settings['subtext'] ); ?></textarea></td></tr>
                    <tr><th>Button text</th><td><input class="regular-text" type="text" name="cw_eib_settings[button_text]" value="<?php echo esc_attr( $settings['button_text'] ); ?>"></td></tr>
                    <tr><th>Coupon code</th><td><input class="regular-text" type="text" name="cw_eib_settings[coupon_code]" value="<?php echo esc_attr( $settings['coupon_code'] ); ?>"></td></tr>
                    <tr><th>Countdown (seconds)</th><td><input type="number" min="0" name="cw_eib_settings[countdown_seconds]" value="<?php echo esc_attr( $settings['countdown_seconds'] ); ?>"></td></tr>
                </table>

                <h2><?php echo esc_html__( 'Lead Settings', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Fields</th><td><label><input type="checkbox" name="cw_eib_settings[show_name]" value="1" <?php checked( ! empty( $settings['show_name'] ) ); ?>>Name</label> <label><input type="checkbox" name="cw_eib_settings[show_email]" value="1" <?php checked( ! empty( $settings['show_email'] ) ); ?>>Email</label> <label><input type="checkbox" name="cw_eib_settings[show_phone]" value="1" <?php checked( ! empty( $settings['show_phone'] ) ); ?>>Phone</label></td></tr>
                    <tr><th>Validation</th><td><select name="cw_eib_settings[required_validation]"><option value="email" <?php selected( $settings['required_validation'], 'email' ); ?>>Require Email</option><option value="phone" <?php selected( $settings['required_validation'], 'phone' ); ?>>Require Phone</option><option value="none" <?php selected( $settings['required_validation'], 'none' ); ?>>No requirement</option></select></td></tr>
                </table>

                <h2><?php echo esc_html__( 'WhatsApp Settings', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Number</th><td><input class="regular-text" type="text" name="cw_eib_settings[whatsapp_number]" value="<?php echo esc_attr( $settings['whatsapp_number'] ); ?>"></td></tr>
                    <tr><th>Message</th><td><input class="regular-text" type="text" name="cw_eib_settings[whatsapp_message]" value="<?php echo esc_attr( $settings['whatsapp_message'] ); ?>"></td></tr>
                </table>

                <h2><?php echo esc_html__( 'Design Settings', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Background color</th><td><input type="color" name="cw_eib_settings[bg_color]" value="<?php echo esc_attr( $settings['bg_color'] ); ?>"></td></tr>
                    <tr><th>Button color</th><td><input type="color" name="cw_eib_settings[button_color]" value="<?php echo esc_attr( $settings['button_color'] ); ?>"></td></tr>
                    <tr><th>Border radius</th><td><input type="number" min="0" name="cw_eib_settings[border_radius]" value="<?php echo esc_attr( $settings['border_radius'] ); ?>"></td></tr>
                    <tr><th>Font size</th><td><input type="number" min="12" name="cw_eib_settings[font_size]" value="<?php echo esc_attr( $settings['font_size'] ); ?>"></td></tr>
                </table>

                <h2><?php echo esc_html__( 'Targeting', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Post/Page IDs</th><td><input class="regular-text" type="text" name="cw_eib_settings[target_posts]" value="<?php echo esc_attr( implode( ',', (array) $settings['target_posts'] ) ); ?>"><p class="description">Comma-separated IDs</p></td></tr>
                    <tr><th>Device</th><td><select name="cw_eib_settings[target_devices]"><option value="all" <?php selected( $settings['target_devices'], 'all' ); ?>>All</option><option value="mobile" <?php selected( $settings['target_devices'], 'mobile' ); ?>>Mobile only</option><option value="desktop" <?php selected( $settings['target_devices'], 'desktop' ); ?>>Desktop only</option></select></td></tr>
                    <tr><th>Frequency</th><td><select name="cw_eib_settings[frequency]"><option value="session_once" <?php selected( $settings['frequency'], 'session_once' ); ?>>Show once per session</option><option value="always" <?php selected( $settings['frequency'], 'always' ); ?>>Always</option></select></td></tr>
                    <tr><th>Cookie minutes</th><td><input type="number" min="1" name="cw_eib_settings[cookie_duration_minutes]" value="<?php echo esc_attr( $settings['cookie_duration_minutes'] ); ?>"></td></tr>
                    <tr><th>Inactivity seconds</th><td><input type="number" min="3" name="cw_eib_settings[inactivity_seconds]" value="<?php echo esc_attr( $settings['inactivity_seconds'] ); ?>"></td></tr>
                </table>

                <h2><?php echo esc_html__( 'Analytics', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Views</th><td><?php echo esc_html( (string) ( $analytics['views'] ?? 0 ) ); ?></td></tr>
                    <tr><th>Clicks</th><td><?php echo esc_html( (string) ( $analytics['clicks'] ?? 0 ) ); ?></td></tr>
                    <tr><th>Conversions</th><td><?php echo esc_html( (string) ( $analytics['conversions'] ?? 0 ) ); ?></td></tr>
                </table>

                <h2><?php echo esc_html__( 'Test Mode', 'cashwala-exit-booster' ); ?></h2>
                <table class="form-table">
                    <tr><th>Enable test mode</th><td><label><input type="checkbox" name="cw_eib_settings[test_mode]" value="1" <?php checked( ! empty( $settings['test_mode'] ) ); ?>> Always allow test button to trigger popup.</label></td></tr>
                    <tr><th>Show Test Popup</th><td><button type="button" class="button button-primary" id="cw-eib-show-test-popup">Show Test Popup</button><p class="description">Save settings first, then use this button on frontend while logged in.</p></td></tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            (function () {
                const btn = document.getElementById('cw-eib-show-test-popup');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    alert('Open any frontend page while logged in and append ?cw_eib_test=1');
                });
            })();
        </script>
        <?php
    }

    public function render_logs_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $logs = CW_EIB_Logger::get_last_errors( 50 );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Error Logs', 'cashwala-exit-booster' ); ?></h1>
            <p><?php esc_html_e( 'Showing last 50 error lines.', 'cashwala-exit-booster' ); ?></p>
            <div style="background:#fff;border:1px solid #ccd0d4;padding:12px;max-height:600px;overflow:auto;font-family:monospace;">
                <?php if ( empty( $logs ) ) : ?>
                    <p><?php esc_html_e( 'No errors logged yet.', 'cashwala-exit-booster' ); ?></p>
                <?php else : ?>
                    <?php foreach ( $logs as $line ) : ?>
                        <div><?php echo esc_html( $line ); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
