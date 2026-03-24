<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Upsell_Frontend {
    /** @var CW_Upsell_DB */
    private $db;

    /** @var CW_Upsell_Logger */
    private $logger;

    public function __construct( CW_Upsell_DB $db, CW_Upsell_Logger $logger ) {
        $this->db     = $db;
        $this->logger = $logger;

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_offer' ) );
        add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_buy_now_marker' ) );
        add_action( 'woocommerce_checkout_before_customer_details', array( $this, 'checkout_trigger' ) );
        add_action( 'cw_upsell_after_form_submit', array( $this, 'custom_hook_trigger' ) );
    }

    public function enqueue_assets() {
        $settings = get_option( 'cw_upsell_settings', CW_Upsell_DB::default_settings() );
        if ( empty( $settings['enabled'] ) || ! $this->should_render() ) {
            return;
        }

        wp_enqueue_style( 'cw-upsell-style', CW_UPSELL_URL . 'assets/css/style.css', array(), CW_UPSELL_VERSION );
        wp_enqueue_script( 'cw-upsell-script', CW_UPSELL_URL . 'assets/js/script.js', array(), CW_UPSELL_VERSION, true );

        wp_localize_script(
            'cw-upsell-script',
            'cwUpsellData',
            array(
                'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'cw_upsell_nonce' ),
                'displayType'  => $settings['display_type'],
                'triggerEvent' => $settings['trigger_event'],
                'delay'        => absint( $settings['delay'] ),
                'animation'    => $settings['animation_style'],
                'behavior'     => $settings['behavior'],
                'closeButton'  => ! empty( $settings['close_button'] ),
            )
        );
    }

    private function should_render() {
        $settings = get_option( 'cw_upsell_settings', CW_Upsell_DB::default_settings() );

        if ( 'mobile' === $settings['device_targeting'] && ! wp_is_mobile() ) {
            return false;
        }

        if ( 'desktop' === $settings['device_targeting'] && wp_is_mobile() ) {
            return false;
        }

        if ( ! empty( $settings['page_targeting'] ) ) {
            $ids = array_filter( array_map( 'absint', explode( ',', $settings['page_targeting'] ) ) );
            if ( ! empty( $ids ) && ! is_page( $ids ) && ! is_product() ) {
                return false;
            }
        }

        if ( empty( $settings['show_repeat_visitors'] ) && isset( $_COOKIE['cw_upsell_visitor'] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            return false;
        }

        if ( ! isset( $_COOKIE['cw_upsell_visitor'] ) ) {
            setcookie( 'cw_upsell_visitor', '1', time() + MONTH_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
        }

        return true;
    }

    public function render_offer() {
        $settings = get_option( 'cw_upsell_settings', CW_Upsell_DB::default_settings() );
        $offers   = $this->db->get_offers();

        if ( empty( $settings['enabled'] ) || empty( $offers ) || ! $this->should_render() ) {
            return;
        }

        $offer_context = array(
            'offers'   => $offers,
            'settings' => $settings,
        );

        if ( 'inline' === $settings['display_type'] ) {
            include CW_UPSELL_PATH . 'templates/upsell-inline.php';
        } else {
            include CW_UPSELL_PATH . 'templates/upsell-popup.php';
        }
    }

    public function add_buy_now_marker() {
        echo '<span class="cw-upsell-buy-now-marker" data-cw-buy-now="1"></span>';
    }

    public function checkout_trigger() {
        echo '<span class="cw-upsell-checkout-trigger" data-cw-checkout="1"></span>';
    }

    public function custom_hook_trigger() {
        echo '<span class="cw-upsell-custom-trigger" data-cw-custom-hook="1"></span>';
    }
}
