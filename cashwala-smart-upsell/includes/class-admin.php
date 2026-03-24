<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CW_Upsell_Admin {
    /** @var CW_Upsell_DB */
    private $db;

    /** @var CW_Upsell_Logger */
    private $logger;

    public function __construct( CW_Upsell_DB $db, CW_Upsell_Logger $logger ) {
        $this->db     = $db;
        $this->logger = $logger;

        add_action( 'admin_menu', array( $this, 'register_menu' ) );
        add_action( 'admin_init', array( $this, 'handle_post_actions' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    public function register_menu() {
        add_menu_page(
            __( 'CashWala Upsell', 'cashwala-smart-upsell' ),
            __( 'CashWala Upsell', 'cashwala-smart-upsell' ),
            'manage_options',
            'cw-upsell',
            array( $this, 'render_page' ),
            'dashicons-chart-line',
            56
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( false === strpos( $hook, 'cw-upsell' ) ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script( 'cw-upsell-admin-js', CW_UPSELL_URL . 'assets/js/script.js', array( 'jquery' ), CW_UPSELL_VERSION, true );
    }

    public function handle_post_actions() {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( empty( $_POST['cw_action'] ) ) {
            return;
        }

        check_admin_referer( 'cw_upsell_admin_action', 'cw_nonce' );

        $action = sanitize_key( wp_unslash( $_POST['cw_action'] ) );

        if ( 'save_settings' === $action ) {
            $settings = get_option( 'cw_upsell_settings', CW_Upsell_DB::default_settings() );

            $settings['enabled']              = isset( $_POST['enabled'] ) ? 1 : 0;
            $settings['trigger_event']        = sanitize_key( $_POST['trigger_event'] ?? 'buy_now' );
            $settings['display_type']         = sanitize_key( $_POST['display_type'] ?? 'popup' );
            $settings['animation_style']      = sanitize_key( $_POST['animation_style'] ?? 'fade' );
            $settings['delay']                = absint( $_POST['delay'] ?? 1000 );
            $settings['page_targeting']       = sanitize_text_field( $_POST['page_targeting'] ?? '' );
            $settings['device_targeting']     = sanitize_key( $_POST['device_targeting'] ?? 'all' );
            $settings['layout']               = sanitize_key( $_POST['layout'] ?? 'card' );
            $settings['background_color']     = sanitize_hex_color( $_POST['background_color'] ?? '#ffffff' );
            $settings['text_color']           = sanitize_hex_color( $_POST['text_color'] ?? '#1f2937' );
            $settings['button_style']         = sanitize_key( $_POST['button_style'] ?? 'rounded' );
            $settings['behavior']             = sanitize_key( $_POST['behavior'] ?? 'once' );
            $settings['close_button']         = isset( $_POST['close_button'] ) ? 1 : 0;
            $settings['show_repeat_visitors'] = isset( $_POST['show_repeat_visitors'] ) ? 1 : 0;

            update_option( 'cw_upsell_settings', $settings, false );
            $this->logger->log( 'Settings updated.' );
        }

        if ( 'save_offer' === $action ) {
            $offer_id = $this->db->save_offer( wp_unslash( $_POST ) );
            $this->logger->log( 'Offer saved.', array( 'offer_id' => $offer_id ) );
        }

        if ( 'delete_offer' === $action && ! empty( $_POST['id'] ) ) {
            $deleted = $this->db->delete_offer( absint( $_POST['id'] ) );
            $this->logger->log( 'Offer deleted.', array( 'offer_id' => absint( $_POST['id'] ), 'deleted' => $deleted ) );
        }

        if ( 'clear_logs' === $action ) {
            $this->logger->clear();
        }

        wp_safe_redirect( admin_url( 'admin.php?page=cw-upsell' ) );
        exit;
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings  = get_option( 'cw_upsell_settings', CW_Upsell_DB::default_settings() );
        $analytics = get_option( 'cw_upsell_analytics', array( 'views' => 0, 'accepts' => 0, 'skips' => 0 ) );
        $offers    = $this->db->get_offers();
        $logs      = $this->logger->get_logs();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'CashWala Smart Upsell Pro', 'cashwala-smart-upsell' ); ?></h1>

            <form method="post" style="background:#fff;padding:20px;margin:20px 0;border:1px solid #e5e7eb;">
                <?php wp_nonce_field( 'cw_upsell_admin_action', 'cw_nonce' ); ?>
                <input type="hidden" name="cw_action" value="save_settings" />

                <h2><?php esc_html_e( '1. General Settings', 'cashwala-smart-upsell' ); ?></h2>
                <p><label><input type="checkbox" name="enabled" <?php checked( ! empty( $settings['enabled'] ) ); ?> /> <?php esc_html_e( 'Enable upsell system', 'cashwala-smart-upsell' ); ?></label></p>
                <p>
                    <label><?php esc_html_e( 'Trigger event', 'cashwala-smart-upsell' ); ?></label>
                    <select name="trigger_event">
                        <option value="buy_now" <?php selected( $settings['trigger_event'], 'buy_now' ); ?>>Buy Now click</option>
                        <option value="add_to_cart" <?php selected( $settings['trigger_event'], 'add_to_cart' ); ?>>After Add to Cart</option>
                        <option value="checkout" <?php selected( $settings['trigger_event'], 'checkout' ); ?>>Checkout page</option>
                        <option value="custom_hook" <?php selected( $settings['trigger_event'], 'custom_hook' ); ?>>Custom Hook</option>
                    </select>
                </p>

                <h2><?php esc_html_e( '3. Display Settings', 'cashwala-smart-upsell' ); ?></h2>
                <p>
                    <label>Display type</label>
                    <select name="display_type">
                        <option value="popup" <?php selected( $settings['display_type'], 'popup' ); ?>>Popup</option>
                        <option value="inline" <?php selected( $settings['display_type'], 'inline' ); ?>>Inline</option>
                    </select>
                </p>
                <p>
                    <label>Animation style</label>
                    <select name="animation_style">
                        <option value="fade" <?php selected( $settings['animation_style'], 'fade' ); ?>>Fade</option>
                        <option value="slide" <?php selected( $settings['animation_style'], 'slide' ); ?>>Slide Up</option>
                        <option value="zoom" <?php selected( $settings['animation_style'], 'zoom' ); ?>>Zoom</option>
                    </select>
                </p>
                <p><label>Delay (ms) <input type="number" min="0" name="delay" value="<?php echo esc_attr( $settings['delay'] ); ?>" /></label></p>

                <h2><?php esc_html_e( '4. Targeting', 'cashwala-smart-upsell' ); ?></h2>
                <p><label>Page targeting IDs (comma separated): <input type="text" name="page_targeting" value="<?php echo esc_attr( $settings['page_targeting'] ); ?>" /></label></p>
                <p>
                    <label>Device targeting</label>
                    <select name="device_targeting">
                        <option value="all" <?php selected( $settings['device_targeting'], 'all' ); ?>>All</option>
                        <option value="desktop" <?php selected( $settings['device_targeting'], 'desktop' ); ?>>Desktop</option>
                        <option value="mobile" <?php selected( $settings['device_targeting'], 'mobile' ); ?>>Mobile</option>
                    </select>
                </p>

                <h2><?php esc_html_e( '5. Design Settings', 'cashwala-smart-upsell' ); ?></h2>
                <p><label>Background color <input type="color" name="background_color" value="<?php echo esc_attr( $settings['background_color'] ); ?>" /></label></p>
                <p><label>Text color <input type="color" name="text_color" value="<?php echo esc_attr( $settings['text_color'] ); ?>" /></label></p>
                <p>
                    <label>Button style</label>
                    <select name="button_style">
                        <option value="rounded" <?php selected( $settings['button_style'], 'rounded' ); ?>>Rounded</option>
                        <option value="square" <?php selected( $settings['button_style'], 'square' ); ?>>Square</option>
                        <option value="pill" <?php selected( $settings['button_style'], 'pill' ); ?>>Pill</option>
                    </select>
                </p>
                <p>
                    <label>Layout</label>
                    <select name="layout">
                        <option value="card" <?php selected( $settings['layout'], 'card' ); ?>>Card</option>
                        <option value="grid" <?php selected( $settings['layout'], 'grid' ); ?>>Grid</option>
                    </select>
                </p>

                <h2><?php esc_html_e( '6. Behavior', 'cashwala-smart-upsell' ); ?></h2>
                <p>
                    <select name="behavior">
                        <option value="once" <?php selected( $settings['behavior'], 'once' ); ?>>Show once per session</option>
                        <option value="repeat" <?php selected( $settings['behavior'], 'repeat' ); ?>>Allow repeats</option>
                    </select>
                </p>
                <p><label><input type="checkbox" name="close_button" <?php checked( ! empty( $settings['close_button'] ) ); ?> /> Close button</label></p>
                <p><label><input type="checkbox" name="show_repeat_visitors" <?php checked( ! empty( $settings['show_repeat_visitors'] ) ); ?> /> Show to repeat visitors</label></p>
                <p><button class="button button-primary"><?php esc_html_e( 'Save Settings', 'cashwala-smart-upsell' ); ?></button></p>
            </form>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <form method="post" style="background:#fff;padding:20px;border:1px solid #e5e7eb;">
                    <?php wp_nonce_field( 'cw_upsell_admin_action', 'cw_nonce' ); ?>
                    <input type="hidden" name="cw_action" value="save_offer" />
                    <h2><?php esc_html_e( '2. Upsell Products', 'cashwala-smart-upsell' ); ?></h2>
                    <p><input required type="text" name="title" placeholder="Product title" style="width:100%;" /></p>
                    <p><textarea required name="description" placeholder="Description" style="width:100%;"></textarea></p>
                    <p><input type="url" name="image" placeholder="Image URL" style="width:100%;" /></p>
                    <p><input type="url" name="link" placeholder="Offer link" style="width:100%;" /></p>
                    <p><input type="number" step="0.01" name="price" placeholder="Price" /></p>
                    <p><input type="number" step="0.01" name="discount_price" placeholder="Discounted price" /></p>
                    <p><button class="button button-primary">Save Offer</button></p>
                </form>

                <div style="background:#fff;padding:20px;border:1px solid #e5e7eb;">
                    <h2>Offers</h2>
                    <?php if ( empty( $offers ) ) : ?>
                        <p>No offers yet.</p>
                    <?php else : ?>
                        <table class="widefat striped">
                            <thead><tr><th>Title</th><th>Price</th><th>Discount</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php foreach ( $offers as $offer ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $offer['title'] ); ?></td>
                                    <td><?php echo esc_html( wc_price_fallback( $offer['price'] ) ); ?></td>
                                    <td><?php echo esc_html( wc_price_fallback( $offer['discount_price'] ) ); ?></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field( 'cw_upsell_admin_action', 'cw_nonce' ); ?>
                                            <input type="hidden" name="cw_action" value="delete_offer" />
                                            <input type="hidden" name="id" value="<?php echo esc_attr( $offer['id'] ); ?>" />
                                            <button class="button button-secondary" onclick="return confirm('Delete offer?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #e5e7eb;">
                <h2><?php esc_html_e( '7. Analytics', 'cashwala-smart-upsell' ); ?></h2>
                <p><strong>Views:</strong> <?php echo esc_html( (string) absint( $analytics['views'] ?? 0 ) ); ?></p>
                <p><strong>Accept clicks:</strong> <?php echo esc_html( (string) absint( $analytics['accepts'] ?? 0 ) ); ?></p>
                <p><strong>Skip clicks:</strong> <?php echo esc_html( (string) absint( $analytics['skips'] ?? 0 ) ); ?></p>
            </div>

            <div style="background:#fff;padding:20px;margin-top:20px;border:1px solid #e5e7eb;">
                <h2>Logs</h2>
                <form method="post" style="margin-bottom:10px;">
                    <?php wp_nonce_field( 'cw_upsell_admin_action', 'cw_nonce' ); ?>
                    <input type="hidden" name="cw_action" value="clear_logs" />
                    <button class="button">Clear logs</button>
                </form>
                <table class="widefat striped">
                    <thead><tr><th>Time</th><th>Level</th><th>Message</th><th>Context</th></tr></thead>
                    <tbody>
                    <?php if ( empty( $logs ) ) : ?>
                        <tr><td colspan="4">No logs available.</td></tr>
                    <?php else : ?>
                        <?php foreach ( $logs as $log ) : ?>
                            <tr>
                                <td><?php echo esc_html( $log['time'] ); ?></td>
                                <td><?php echo esc_html( strtoupper( $log['level'] ) ); ?></td>
                                <td><?php echo esc_html( $log['message'] ); ?></td>
                                <td><code><?php echo esc_html( $log['context'] ); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }
}

if ( ! function_exists( 'wc_price_fallback' ) ) {
    function wc_price_fallback( $amount ) {
        if ( function_exists( 'wc_price' ) ) {
            return wp_strip_all_tags( wc_price( (float) $amount ) );
        }

        return '$' . number_format( (float) $amount, 2 );
    }
}
