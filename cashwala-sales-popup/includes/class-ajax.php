<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Sales_Popup_Ajax {
    /**
     * @var CW_Sales_Popup_DB
     */
    private $db;

    /**
     * @var CW_Sales_Popup_Logger
     */
    private $logger;

    public function __construct( $db, $logger ) {
        $this->db     = $db;
        $this->logger = $logger;

        add_action( 'wp_ajax_cw_sales_popup_get', array( $this, 'get_popup' ) );
        add_action( 'wp_ajax_nopriv_cw_sales_popup_get', array( $this, 'get_popup' ) );

        add_action( 'wp_ajax_cw_sales_popup_click', array( $this, 'track_click' ) );
        add_action( 'wp_ajax_nopriv_cw_sales_popup_click', array( $this, 'track_click' ) );
    }

    public function get_popup() {
        check_ajax_referer( 'cw_sales_popup_nonce', 'nonce' );

        $settings = get_option( 'cw_sales_popup_settings', CW_Sales_Popup_DB::default_settings() );
        if ( empty( $settings['enabled'] ) ) {
            wp_send_json_error( array( 'message' => 'Disabled' ), 403 );
        }

        $mode      = sanitize_key( $settings['data_mode'] ?? 'hybrid' );
        $manual    = $this->db->get_entries();
        $entry     = array();
        $use_random = 'random' === $mode || ( 'hybrid' === $mode && wp_rand( 0, 1 ) );

        if ( ! $use_random && ! empty( $manual ) ) {
            $entry = $manual[ array_rand( $manual ) ];
        } else {
            $entry = $this->get_random_entry();
            if ( 'manual' === $mode && ! empty( $manual ) ) {
                $entry = $manual[ array_rand( $manual ) ];
            }
        }

        if ( empty( $entry ) ) {
            $this->logger->log( 'warning', 'No popup entry available for response.' );
            wp_send_json_error( array( 'message' => 'No data available' ), 404 );
        }

        $analytics                = get_option( 'cw_sales_popup_analytics', array( 'impressions' => 0, 'clicks' => 0 ) );
        $analytics['impressions'] = absint( $analytics['impressions'] ) + 1;
        update_option( 'cw_sales_popup_analytics', $analytics, false );

        $payload = array(
            'name'        => sanitize_text_field( $entry['name'] ?? '' ),
            'city'        => sanitize_text_field( $entry['city'] ?? '' ),
            'product'     => sanitize_text_field( $entry['product'] ?? '' ),
            'link'        => esc_url_raw( $entry['link'] ?? '' ),
            'template'    => sanitize_key( $settings['template'] ?? 'template-1' ),
            'html'        => $this->render_popup( $entry, $settings ),
            'cta_enabled' => ! empty( $settings['cta_enabled'] ),
            'cta_text'    => sanitize_text_field( $settings['cta_text'] ?? 'View Product' ),
        );

        wp_send_json_success( $payload );
    }

    public function track_click() {
        check_ajax_referer( 'cw_sales_popup_nonce', 'nonce' );

        $analytics           = get_option( 'cw_sales_popup_analytics', array( 'impressions' => 0, 'clicks' => 0 ) );
        $analytics['clicks'] = absint( $analytics['clicks'] ) + 1;
        update_option( 'cw_sales_popup_analytics', $analytics, false );

        wp_send_json_success( array( 'clicks' => $analytics['clicks'] ) );
    }

    private function get_random_entry() {
        $names    = array( 'Rahul', 'Anita', 'Vikram', 'Pooja', 'Aarav', 'Neha', 'Karan', 'Simran' );
        $cities   = array( 'Delhi', 'Mumbai', 'Bangalore', 'Pune', 'Jaipur', 'Hyderabad', 'Ahmedabad' );
        $products = array( 'WhatsApp Plugin', 'SEO Booster Toolkit', 'Lead Magnet Pack', 'AI Content Pro', 'Growth Analytics Suite' );

        return array(
            'name'    => $names[ array_rand( $names ) ],
            'city'    => $cities[ array_rand( $cities ) ],
            'product' => $products[ array_rand( $products ) ],
            'link'    => home_url( '/' ),
        );
    }

    private function render_popup( $entry, $settings ) {
        $template = 'template-2' === ( $settings['template'] ?? 'template-1' )
            ? CW_SALES_POPUP_PATH . 'templates/popup-template-2.php'
            : CW_SALES_POPUP_PATH . 'templates/popup-template-1.php';

        if ( ! file_exists( $template ) ) {
            return '';
        }

        $entry    = array_map( 'sanitize_text_field', $entry );
        $link     = esc_url( $entry['link'] ?? '' );
        $cta_text = sanitize_text_field( $settings['cta_text'] ?? 'View Product' );
        $avatar   = esc_url( $settings['avatar_url'] ?? '' );
        $show_cta = ! empty( $settings['cta_enabled'] );

        ob_start();
        include $template;
        return ob_get_clean();
    }
}
