<?php
/**
 * Front-end output and behavior.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WSB_Frontend {
    /**
     * Init hooks.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( __CLASS__, 'render_widget' ) );

        add_action( 'wp_ajax_wsb_track_click', array( __CLASS__, 'track_click' ) );
        add_action( 'wp_ajax_nopriv_wsb_track_click', array( __CLASS__, 'track_click' ) );
    }

    /**
     * Register/enqueue assets.
     */
    public static function enqueue_assets() {
        $settings = WSB_Settings::get_settings();

        if ( empty( $settings['phone_number'] ) ) {
            return;
        }

        wp_enqueue_style(
            'wsb-style',
            WSB_PLUGIN_URL . 'assets/css/style.css',
            array(),
            WSB_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'wsb-script',
            WSB_PLUGIN_URL . 'assets/js/script.js',
            array( 'jquery' ),
            WSB_PLUGIN_VERSION,
            true
        );

        $whatsapp_url = self::get_whatsapp_url( (string) $settings['phone_number'], (string) $settings['prefilled_message'] );

        wp_localize_script(
            'wsb-script',
            'wsbData',
            array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'wsb_track_click_nonce' ),
                'popupEnabled' => '1' === (string) $settings['popup_enabled'],
                'popupDelay'   => absint( $settings['popup_delay'] ),
                'whatsappUrl'  => esc_url_raw( $whatsapp_url ),
            )
        );
    }

    /**
     * Render floating button and popup.
     */
    public static function render_widget() {
        $settings = WSB_Settings::get_settings();

        if ( empty( $settings['phone_number'] ) ) {
            return;
        }

        $cta_text     = ! empty( $settings['cta_text'] ) ? (string) $settings['cta_text'] : __( 'Chat Now', 'whatsapp-sales-booster' );
        $popup_text   = ! empty( $settings['popup_text'] ) ? (string) $settings['popup_text'] : __( 'Need quick help? Our team is online on WhatsApp.', 'whatsapp-sales-booster' );
        $device_class = 'wsb-show-both';

        if ( 'mobile' === (string) $settings['device_visibility'] ) {
            $device_class = 'wsb-show-mobile';
        } elseif ( 'desktop' === (string) $settings['device_visibility'] ) {
            $device_class = 'wsb-show-desktop';
        }

        $whatsapp_url = self::get_whatsapp_url( (string) $settings['phone_number'], (string) $settings['prefilled_message'] );
        ?>
        <div class="wsb-wrapper <?php echo esc_attr( $device_class ); ?>" data-popup-enabled="<?php echo esc_attr( (string) $settings['popup_enabled'] ); ?>" data-popup-delay="<?php echo esc_attr( (string) absint( $settings['popup_delay'] ) ); ?>">
            <div class="wsb-popup" id="wsb-popup" aria-hidden="true">
                <button class="wsb-popup-close" type="button" aria-label="<?php esc_attr_e( 'Close popup', 'whatsapp-sales-booster' ); ?>">×</button>
                <p class="wsb-popup-text"><?php echo esc_html( $popup_text ); ?></p>
                <a class="wsb-popup-btn wsb-track-click" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $whatsapp_url ); ?>">
                    <span class="wsb-icon" aria-hidden="true">💬</span>
                    <span><?php echo esc_html( $cta_text ); ?></span>
                </a>
            </div>

            <a class="wsb-float-btn wsb-track-click" target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $whatsapp_url ); ?>" aria-label="<?php esc_attr_e( 'Chat on WhatsApp', 'whatsapp-sales-booster' ); ?>">
                <span class="wsb-icon" aria-hidden="true">💬</span>
                <span class="wsb-btn-text"><?php echo esc_html( $cta_text ); ?></span>
            </a>
        </div>
        <?php
    }

    /**
     * Build wa.me URL.
     *
     * @param string $phone_number WhatsApp number.
     * @param string $message Prefilled message.
     * @return string
     */
    private static function get_whatsapp_url( $phone_number, $message ) {
        $sanitized_phone = preg_replace( '/[^0-9]/', '', $phone_number );
        $encoded_message = rawurlencode( $message );

        return 'https://wa.me/' . $sanitized_phone . '?text=' . $encoded_message;
    }

    /**
     * AJAX click tracker.
     */
    public static function track_click() {
        check_ajax_referer( 'wsb_track_click_nonce', 'nonce' );

        $current_clicks = absint( get_option( WSB_Settings::CLICK_OPTION_KEY, 0 ) );
        $updated_clicks = $current_clicks + 1;

        update_option( WSB_Settings::CLICK_OPTION_KEY, $updated_clicks );

        wp_send_json_success(
            array(
                'total_clicks' => $updated_clicks,
            )
        );
    }
}
