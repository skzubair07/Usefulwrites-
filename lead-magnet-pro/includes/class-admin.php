<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LMP_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    public static function add_menu() {
        add_menu_page(
            esc_html__( 'Lead Magnet Pro', 'lead-magnet-pro' ),
            esc_html__( 'Lead Magnet Pro', 'lead-magnet-pro' ),
            'manage_options',
            'lead-magnet-pro',
            array( __CLASS__, 'render_admin_page' ),
            'dashicons-megaphone',
            58
        );
    }

    public static function register_settings() {
        register_setting(
            'lmp_settings_group',
            'lmp_options',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( __CLASS__, 'sanitize_options' ),
                'default'           => lmp_get_options(),
            )
        );
    }

    public static function sanitize_options( $input ) {
        $defaults = lmp_get_options();
        $sanitized = array();

        $checkboxes = array(
            'enabled', 'show_once_session', 'show_name', 'show_email', 'show_phone', 'required_name', 'required_email', 'required_phone',
        );

        foreach ( $checkboxes as $field ) {
            $sanitized[ $field ] = ! empty( $input[ $field ] ) ? 1 : 0;
        }

        $sanitized['trigger_type']       = in_array( $input['trigger_type'] ?? '', array( 'time_delay', 'exit_intent', 'scroll' ), true ) ? $input['trigger_type'] : $defaults['trigger_type'];
        $sanitized['redirect_mode']      = in_array( $input['redirect_mode'] ?? '', array( 'none', 'url', 'whatsapp' ), true ) ? $input['redirect_mode'] : 'none';
        $sanitized['delay_seconds']      = max( 0, absint( $input['delay_seconds'] ?? $defaults['delay_seconds'] ) );
        $sanitized['scroll_percent']     = min( 100, max( 1, absint( $input['scroll_percent'] ?? $defaults['scroll_percent'] ) ) );
        $sanitized['title']              = sanitize_text_field( $input['title'] ?? $defaults['title'] );
        $sanitized['description']        = sanitize_text_field( $input['description'] ?? $defaults['description'] );
        $sanitized['submit_button_text'] = sanitize_text_field( $input['submit_button_text'] ?? $defaults['submit_button_text'] );
        $sanitized['success_message']    = sanitize_text_field( $input['success_message'] ?? $defaults['success_message'] );
        $sanitized['redirect_url']       = esc_url_raw( $input['redirect_url'] ?? '' );
        $sanitized['whatsapp_number']    = preg_replace( '/\D+/', '', (string) ( $input['whatsapp_number'] ?? $defaults['whatsapp_number'] ) );
        $sanitized['whatsapp_message']   = sanitize_text_field( $input['whatsapp_message'] ?? $defaults['whatsapp_message'] );
        $sanitized['privacy_note']       = sanitize_text_field( $input['privacy_note'] ?? $defaults['privacy_note'] );
        $sanitized['button_bg_color']    = sanitize_hex_color( $input['button_bg_color'] ?? $defaults['button_bg_color'] ) ?: $defaults['button_bg_color'];
        $sanitized['button_text_color']  = sanitize_hex_color( $input['button_text_color'] ?? $defaults['button_text_color'] ) ?: $defaults['button_text_color'];
        $sanitized['popup_background']   = sanitize_hex_color( $input['popup_background'] ?? $defaults['popup_background'] ) ?: $defaults['popup_background'];
        $sanitized['popup_text_color']   = sanitize_hex_color( $input['popup_text_color'] ?? $defaults['popup_text_color'] ) ?: $defaults['popup_text_color'];
        $opacity                         = (float) ( $input['overlay_opacity'] ?? $defaults['overlay_opacity'] );
        $sanitized['overlay_opacity']    = (string) min( 1, max( 0.1, $opacity ) );

        return $sanitized;
    }

    public static function render_admin_page() {
        $tab     = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
        $options = lmp_get_options();
        $tabs    = array(
            'general'   => esc_html__( 'General', 'lead-magnet-pro' ),
            'design'    => esc_html__( 'Design', 'lead-magnet-pro' ),
            'form'      => esc_html__( 'Form', 'lead-magnet-pro' ),
            'behavior'  => esc_html__( 'Behavior', 'lead-magnet-pro' ),
            'leads'     => esc_html__( 'Leads', 'lead-magnet-pro' ),
            'analytics' => esc_html__( 'Analytics', 'lead-magnet-pro' ),
        );
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'Lead Magnet Pro – Visitor to Lead Converter', 'lead-magnet-pro' ); ?></h1>
            <nav class="nav-tab-wrapper" style="margin-bottom:18px;">
                <?php foreach ( $tabs as $key => $label ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=lead-magnet-pro&tab=' . $key ) ); ?>" class="nav-tab <?php echo $tab === $key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <?php if ( in_array( $tab, array( 'general', 'design', 'form', 'behavior' ), true ) ) : ?>
                <form method="post" action="options.php">
                    <?php settings_fields( 'lmp_settings_group' ); ?>
                    <table class="form-table" role="presentation">
                        <?php self::render_tab_fields( $tab, $options ); ?>
                    </table>
                    <?php submit_button( esc_html__( 'Save Settings', 'lead-magnet-pro' ) ); ?>
                </form>
            <?php elseif ( 'leads' === $tab ) : ?>
                <?php self::render_leads_tab(); ?>
            <?php elseif ( 'analytics' === $tab ) : ?>
                <?php self::render_analytics_tab(); ?>
            <?php endif; ?>
        </div>
        <?php
    }

    private static function render_tab_fields( $tab, $options ) {
        if ( 'general' === $tab ) {
            self::checkbox_field( $options, 'enabled', 'Enable Popup' );
            self::select_field( $options, 'trigger_type', 'Trigger Type', array( 'time_delay' => 'Time Delay', 'exit_intent' => 'Exit Intent (Desktop)', 'scroll' => 'Scroll Percentage' ) );
            self::text_field( $options, 'title', 'Popup Title' );
            self::textarea_field( $options, 'description', 'Popup Description' );
            self::textarea_field( $options, 'privacy_note', 'Privacy Note' );
            self::text_field( $options, 'success_message', 'Success Message' );
            self::select_field( $options, 'redirect_mode', 'Redirect After Submit', array( 'none' => 'None (show success message)', 'url' => 'Redirect URL', 'whatsapp' => 'WhatsApp redirect (dynamic message)' ) );
            self::text_field( $options, 'redirect_url', 'Redirect URL' );
            self::text_field( $options, 'whatsapp_number', 'WhatsApp Number (with country code)' );
            self::textarea_field( $options, 'whatsapp_message', 'WhatsApp Message Template' );
        }

        if ( 'form' === $tab ) {
            self::checkbox_field( $options, 'show_name', 'Show Name Field' );
            self::checkbox_field( $options, 'show_email', 'Show Email Field' );
            self::checkbox_field( $options, 'show_phone', 'Show Phone Field' );
            self::checkbox_field( $options, 'required_name', 'Name Required' );
            self::checkbox_field( $options, 'required_email', 'Email Required' );
            self::checkbox_field( $options, 'required_phone', 'Phone Required' );
            self::text_field( $options, 'submit_button_text', 'Submit Button Text' );
        }

        if ( 'behavior' === $tab ) {
            self::number_field( $options, 'delay_seconds', 'Popup Delay (seconds)', 0, 999 );
            self::number_field( $options, 'scroll_percent', 'Scroll Trigger (%)', 1, 100 );
            self::checkbox_field( $options, 'show_once_session', 'Show Once Per Session (localStorage)' );
        }

        if ( 'design' === $tab ) {
            self::color_field( $options, 'popup_background', 'Popup Background Color' );
            self::color_field( $options, 'popup_text_color', 'Popup Text Color' );
            self::color_field( $options, 'button_bg_color', 'CTA Button Background' );
            self::color_field( $options, 'button_text_color', 'CTA Button Text Color' );
            self::text_field( $options, 'overlay_opacity', 'Overlay Opacity (0.1 to 1)' );
        }
    }

    private static function render_leads_tab() {
        $leads = get_posts(
            array(
                'post_type'      => 'lmp_lead',
                'post_status'    => 'publish',
                'posts_per_page' => 50,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );
        ?>
        <h2><?php echo esc_html__( 'Recent Leads', 'lead-magnet-pro' ); ?></h2>
        <p>
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'edit.php?post_type=lmp_lead' ) ); ?>">
                <?php echo esc_html__( 'Open Full Leads Table', 'lead-magnet-pro' ); ?>
            </a>
        </p>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="lmp_export_leads" />
            <?php wp_nonce_field( 'lmp_export_leads_nonce' ); ?>
            <?php submit_button( esc_html__( 'Export Leads CSV', 'lead-magnet-pro' ), 'secondary', 'submit', false ); ?>
        </form>

        <table class="widefat striped" style="margin-top:12px;">
            <thead>
            <tr>
                <th><?php echo esc_html__( 'Name', 'lead-magnet-pro' ); ?></th>
                <th><?php echo esc_html__( 'Email', 'lead-magnet-pro' ); ?></th>
                <th><?php echo esc_html__( 'Phone', 'lead-magnet-pro' ); ?></th>
                <th><?php echo esc_html__( 'Source Page', 'lead-magnet-pro' ); ?></th>
                <th><?php echo esc_html__( 'Time', 'lead-magnet-pro' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ( empty( $leads ) ) : ?>
                <tr><td colspan="5"><?php echo esc_html__( 'No leads yet.', 'lead-magnet-pro' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $leads as $lead ) : ?>
                    <tr>
                        <td><?php echo esc_html( get_post_meta( $lead->ID, '_lmp_name', true ) ); ?></td>
                        <td><?php echo esc_html( get_post_meta( $lead->ID, '_lmp_email', true ) ); ?></td>
                        <td><?php echo esc_html( get_post_meta( $lead->ID, '_lmp_phone', true ) ); ?></td>
                        <td>
                            <?php $url = get_post_meta( $lead->ID, '_lmp_page_url', true ); ?>
                            <?php if ( $url ) : ?>
                                <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url ); ?></a>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html( get_post_meta( $lead->ID, '_lmp_time', true ) ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private static function render_analytics_tab() {
        $stats = LMP_Analytics::get_stats();
        ?>
        <h2><?php echo esc_html__( 'Analytics Dashboard', 'lead-magnet-pro' ); ?></h2>
        <div style="display:grid;grid-template-columns:repeat(3,minmax(140px,1fr));gap:16px;max-width:850px;">
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:8px;">
                <p style="margin:0;color:#646970;"><?php echo esc_html__( 'Total Views', 'lead-magnet-pro' ); ?></p>
                <h3 style="margin:6px 0 0;"><?php echo esc_html( number_format_i18n( $stats['impressions'] ) ); ?></h3>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:8px;">
                <p style="margin:0;color:#646970;"><?php echo esc_html__( 'Total Leads', 'lead-magnet-pro' ); ?></p>
                <h3 style="margin:6px 0 0;"><?php echo esc_html( number_format_i18n( $stats['conversions'] ) ); ?></h3>
            </div>
            <div style="background:#fff;padding:16px;border:1px solid #dcdcde;border-radius:8px;">
                <p style="margin:0;color:#646970;"><?php echo esc_html__( 'Conversion Rate', 'lead-magnet-pro' ); ?></p>
                <h3 style="margin:6px 0 0;"><?php echo esc_html( $stats['rate'] ); ?>%</h3>
            </div>
        </div>
        <?php
    }

    private static function text_field( $options, $key, $label ) {
        ?>
        <tr>
            <th scope="row"><label for="lmp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><input name="lmp_options[<?php echo esc_attr( $key ); ?>]" id="lmp_<?php echo esc_attr( $key ); ?>" type="text" class="regular-text" value="<?php echo esc_attr( $options[ $key ] ?? '' ); ?>"></td>
        </tr>
        <?php
    }

    private static function number_field( $options, $key, $label, $min = 0, $max = 9999 ) {
        ?>
        <tr>
            <th scope="row"><label for="lmp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><input name="lmp_options[<?php echo esc_attr( $key ); ?>]" id="lmp_<?php echo esc_attr( $key ); ?>" type="number" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( $max ); ?>" class="small-text" value="<?php echo esc_attr( $options[ $key ] ?? '' ); ?>"></td>
        </tr>
        <?php
    }

    private static function textarea_field( $options, $key, $label ) {
        ?>
        <tr>
            <th scope="row"><label for="lmp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><textarea name="lmp_options[<?php echo esc_attr( $key ); ?>]" id="lmp_<?php echo esc_attr( $key ); ?>" rows="3" class="large-text"><?php echo esc_textarea( $options[ $key ] ?? '' ); ?></textarea></td>
        </tr>
        <?php
    }

    private static function select_field( $options, $key, $label, $choices ) {
        ?>
        <tr>
            <th scope="row"><label for="lmp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
                <select name="lmp_options[<?php echo esc_attr( $key ); ?>]" id="lmp_<?php echo esc_attr( $key ); ?>">
                    <?php foreach ( $choices as $value => $text ) : ?>
                        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $options[ $key ] ?? '', $value ); ?>><?php echo esc_html( $text ); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    private static function checkbox_field( $options, $key, $label ) {
        ?>
        <tr>
            <th scope="row"><?php echo esc_html( $label ); ?></th>
            <td>
                <label>
                    <input type="checkbox" name="lmp_options[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $options[ $key ] ) ); ?>>
                    <?php echo esc_html__( 'Yes', 'lead-magnet-pro' ); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    private static function color_field( $options, $key, $label ) {
        ?>
        <tr>
            <th scope="row"><label for="lmp_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><input name="lmp_options[<?php echo esc_attr( $key ); ?>]" id="lmp_<?php echo esc_attr( $key ); ?>" type="color" value="<?php echo esc_attr( $options[ $key ] ?? '' ); ?>"></td>
        </tr>
        <?php
    }
}
