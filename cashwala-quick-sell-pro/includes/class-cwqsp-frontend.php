<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWQSP_Frontend {
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_shortcode( 'cwqsp_buy_button', array( $this, 'buy_button_shortcode' ) );
		add_shortcode( 'cwqsp_product_card', array( $this, 'product_card_shortcode' ) );
		add_action( 'template_redirect', array( $this, 'render_thankyou_screen' ) );
	}

	public function enqueue() {
		wp_enqueue_style( 'cwqsp-frontend', CWQSP_URL . 'assets/css/frontend.css', array(), CWQSP_VERSION );
		wp_enqueue_script( 'cwqsp-frontend', CWQSP_URL . 'assets/js/frontend.js', array( 'jquery' ), CWQSP_VERSION, true );
		wp_localize_script(
			'cwqsp-frontend',
			'cwqspData',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'cwqsp_nonce' ),
				'currency'     => 'INR',
				'failedText'   => 'Payment failed. Please retry.',
				'checkoutText' => 'Starting secure checkout...',
			)
		);
		wp_enqueue_script( 'cwqsp-razorpay', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, true );
	}

	public function buy_button_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => get_the_ID() ), $atts, 'cwqsp_buy_button' );
		$id   = absint( $atts['id'] );
		if ( ! $id || 'cw_product' !== get_post_type( $id ) ) {
			return '';
		}

		$price = (float) get_post_meta( $id, '_cwqsp_price', true );
		ob_start();
		?>
		<div class="cwqsp-wrap" data-product-id="<?php echo esc_attr( $id ); ?>">
			<div class="cwqsp-form">
				<input type="text" class="cwqsp-name" placeholder="Your name" required>
				<input type="email" class="cwqsp-email" placeholder="Your email" required>
				<input type="text" class="cwqsp-phone" placeholder="Your phone" required>
				<button type="button" class="cwqsp-buy-btn">Buy Now ₹<?php echo esc_html( number_format_i18n( $price, 2 ) ); ?></button>
			</div>
			<div class="cwqsp-loader" aria-hidden="true"></div>
			<div class="cwqsp-message"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function product_card_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'id' => get_the_ID() ), $atts, 'cwqsp_product_card' );
		$id   = absint( $atts['id'] );
		if ( ! $id ) {
			return '';
		}
		return '<div class="cwqsp-product-card"><h3>' . esc_html( get_the_title( $id ) ) . '</h3><div>' . wp_kses_post( wpautop( get_post_field( 'post_content', $id ) ) ) . '</div>' . do_shortcode( '[cwqsp_buy_button id="' . $id . '"]' ) . '</div>';
	}

	public function render_thankyou_screen() {
		if ( empty( $_GET['cwqsp_thankyou'] ) || empty( $_GET['order'] ) ) {
			return;
		}

		global $wpdb;
		$order_id = sanitize_text_field( wp_unslash( $_GET['order'] ) );
		$order    = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . CWQSP_Payment::table_name() . ' WHERE razorpay_order_id=%s', $order_id ) );
		if ( ! $order || 'paid' !== $order->status ) {
			wp_die( 'Order not found or unpaid.' );
		}

		$product_name = get_the_title( $order->product_id );
		$download     = esc_url( $order->download_link );
		$status_msg   = 'Payment successful. Your download is ready.';

		include CWQSP_PATH . 'templates/thank-you.php';
		exit;
	}
}
