<?php
/**
 * Settings helper class.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSB_Settings {
    const OPTION_KEY       = 'wsb_settings';
    const CLICK_OPTION_KEY = 'wsb_total_clicks';

    /**
     * Init settings hooks.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
    }

    /**
     * Get default settings.
     *
     * @return array<string, mixed>
     */
    public static function get_defaults() {
        return array(
            'phone_number'      => '',
            'prefilled_message' => __( 'Hi! I need more information about your offer.', 'whatsapp-sales-booster' ),
            'cta_text'          => __( 'Chat Now', 'whatsapp-sales-booster' ),
            'popup_enabled'     => '1',
            'popup_delay'       => 5,
            'popup_text'        => __( 'Need quick help? Our team is online on WhatsApp.', 'whatsapp-sales-booster' ),
            'device_visibility' => 'both',
        );
    }

    /**
     * Register WP setting.
     */
    public static function register_settings() {
        register_setting(
            'wsb_settings_group',
            self::OPTION_KEY,
            array(
                'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
                'default'           => self::get_defaults(),
            )
        );
    }

    /**
     * Sanitize settings.
     *
     * @param array<string, mixed> $input Input values.
     * @return array<string, mixed>
     */
    public static function sanitize_settings( $input ) {
        $defaults = self::get_defaults();
        $input    = is_array( $input ) ? $input : array();

        $output = array();

        $output['phone_number']      = isset( $input['phone_number'] ) ? preg_replace( '/[^0-9]/', '', (string) $input['phone_number'] ) : $defaults['phone_number'];
        $output['prefilled_message'] = isset( $input['prefilled_message'] ) ? sanitize_textarea_field( wp_unslash( (string) $input['prefilled_message'] ) ) : $defaults['prefilled_message'];
        $output['cta_text']          = isset( $input['cta_text'] ) ? sanitize_text_field( wp_unslash( (string) $input['cta_text'] ) ) : $defaults['cta_text'];
        $output['popup_enabled']     = isset( $input['popup_enabled'] ) && '1' === (string) $input['popup_enabled'] ? '1' : '0';
        $output['popup_delay']       = isset( $input['popup_delay'] ) ? max( 0, absint( $input['popup_delay'] ) ) : (int) $defaults['popup_delay'];
        $output['popup_text']        = isset( $input['popup_text'] ) ? sanitize_textarea_field( wp_unslash( (string) $input['popup_text'] ) ) : $defaults['popup_text'];

        $visibility = isset( $input['device_visibility'] ) ? sanitize_key( (string) $input['device_visibility'] ) : $defaults['device_visibility'];
        if ( ! in_array( $visibility, array( 'both', 'mobile', 'desktop' ), true ) ) {
            $visibility = $defaults['device_visibility'];
        }
        $output['device_visibility'] = $visibility;

        return $output;
    }

    /**
     * Get merged plugin settings.
     *
     * @return array<string, mixed>
     */
    public static function get_settings() {
        $saved = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        return wp_parse_args( $saved, self::get_defaults() );
    }
}
