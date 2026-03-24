<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Frontend {
	private $logger;

	public function __construct( CW_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'cw_buy_button', array( $this, 'buy_button_shortcode' ) );
		add_filter( 'single_template', array( $this, 'single_template' ) );
		add_action( 'wp_footer', array( $this, 'render_lead_popup' ) );
		add_action( 'wp', array( $this, 'handle_flash_error' ) );
	}

	public function enqueue_assets() {
		wp_enqueue_style( 'cw-shop-style', CW_SHOP_URL . 'assets/css/cw-style.css', array(), CW_SHOP_VERSION );
		wp_enqueue_script( 'cw-shop-front', CW_SHOP_URL . 'assets/js/cw-front.js', array( 'jquery' ), CW_SHOP_VERSION, true );
		wp_localize_script(
			'cw-shop-front',
			'cwShop',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'cw_front_nonce' ),
				'currency'  => 'INR',
				'message'   => __( 'Something went wrong. Please contact support.', 'cashwala-shop' ),
			)
		);
		wp_enqueue_script( 'razorpay-checkout', 'https://checkout.razorpay.com/v1/checkout.js', array(), null, true );
	}

	public function single_template( $template ) {
		if ( is_singular( 'cw_book' ) || is_singular( 'cw_combo' ) ) {
			$custom = CW_SHOP_PATH . 'templates/tpl-single-book.php';
			if ( file_exists( $custom ) ) {
				return $custom;
			}
		}
		return $template;
	}

	public function buy_button_shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'id' => get_the_ID(),
			),
			$atts,
			'cw_buy_button'
		);
		$product_id = absint( $atts['id'] );
		if ( ! $product_id ) {
			return '';
		}
		$price = ( 'cw_combo' === get_post_type( $product_id ) ) ? get_post_meta( $product_id, '_cw_combo_price', true ) : get_post_meta( $product_id, '_cw_price', true );

		ob_start();
		?>
		<div class="cw-buy-wrap" data-product-id="<?php echo esc_attr( $product_id ); ?>" data-price="<?php echo esc_attr( $price ); ?>">
			<button class="cw-open-lead button button-primary"><?php esc_html_e( 'Buy Now', 'cashwala-shop' ); ?> ₹<?php echo esc_html( number_format_i18n( (float) $price, 2 ) ); ?></button>
			<div class="cw-payment-response"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function render_lead_popup() {
		?>
		<div id="cw-lead-modal" style="display:none;">
			<div class="cw-modal-content">
				<button class="cw-close" type="button">×</button>
				<h3><?php esc_html_e( 'Before you checkout', 'cashwala-shop' ); ?></h3>
				<form id="cw-lead-form">
					<input type="text" name="name" placeholder="Name" required>
					<input type="email" name="email" placeholder="Email" required>
					<input type="text" name="phone" placeholder="Phone" required>
					<input type="hidden" name="product_id" value="">
					<button type="submit"><?php esc_html_e( 'Proceed to Payment', 'cashwala-shop' ); ?></button>
				</form>
				<div class="cw-lead-response"></div>
			</div>
		</div>
		<?php
	}

	public function handle_flash_error() {
		$message = $this->logger->pop_friendly_error();
		if ( ! empty( $message ) ) {
			add_action(
				'wp_footer',
				function() use ( $message ) {
					echo '<div class="cw-toast-error">' . esc_html( $message ) . '</div>';
				}
			);
		}
	}
}
