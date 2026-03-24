<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_CPT {
	private $logger;

	public function __construct( CW_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'init', array( $this, 'register_cpt' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_cw_book', array( $this, 'save_meta' ) );
	}

	public function register_cpt() {
		register_post_type(
			'cw_book',
			array(
				'label'           => __( 'Plugins', 'cashwala-shop' ),
				'labels'          => array(
					'name'          => __( 'Plugins', 'cashwala-shop' ),
					'singular_name' => __( 'Plugin', 'cashwala-shop' ),
				),
				'public'          => true,
				'show_in_menu'    => true,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'     => true,
				'rewrite'         => array( 'slug' => 'plugin' ),
				'menu_icon'       => 'dashicons-cart',
				'show_in_rest'    => true,
			)
		);

		register_post_type(
			'cw_combo',
			array(
				'label'           => __( 'Combos', 'cashwala-shop' ),
				'public'          => true,
				'show_in_menu'    => true,
				'supports'        => array( 'title', 'editor', 'thumbnail' ),
				'has_archive'     => true,
				'rewrite'         => array( 'slug' => 'combo' ),
				'menu_icon'       => 'dashicons-products',
				'show_in_rest'    => true,
			)
		);
	}

	public function add_meta_boxes() {
		add_meta_box( 'cw_book_meta', __( 'Book Details', 'cashwala-shop' ), array( $this, 'render_book_meta' ), 'cw_book', 'normal', 'default' );
		add_meta_box( 'cw_combo_meta', __( 'Combo Details', 'cashwala-shop' ), array( $this, 'render_combo_meta' ), 'cw_combo', 'normal', 'default' );
	}

	public function render_book_meta( $post ) {
		wp_nonce_field( 'cw_save_book_meta', 'cw_book_meta_nonce' );
		$fields = $this->get_book_meta( $post->ID );
		?>
		<p><label><?php esc_html_e( 'Price (INR)', 'cashwala-shop' ); ?></label><br><input type="number" step="0.01" min="0" name="cw_price" value="<?php echo esc_attr( $fields['price'] ); ?>" class="widefat"></p>
		<p><label><?php esc_html_e( 'PDF File URL', 'cashwala-shop' ); ?></label><br><input type="url" name="cw_pdf_url" value="<?php echo esc_url( $fields['pdf_url'] ); ?>" class="widefat"></p>
		<p><label><?php esc_html_e( 'Sample Link', 'cashwala-shop' ); ?></label><br><input type="url" name="cw_sample_url" value="<?php echo esc_url( $fields['sample_url'] ); ?>" class="widefat"></p>
		<p><label><?php esc_html_e( 'YouTube URL', 'cashwala-shop' ); ?></label><br><input type="url" name="cw_youtube_url" value="<?php echo esc_url( $fields['youtube_url'] ); ?>" class="widefat"></p>
		<p><label><?php esc_html_e( 'Gumroad URL', 'cashwala-shop' ); ?></label><br><input type="url" name="cw_gumroad_url" value="<?php echo esc_url( $fields['gumroad_url'] ); ?>" class="widefat"></p>
		<p><label><?php esc_html_e( 'Affiliate Commission Override (%)', 'cashwala-shop' ); ?></label><br><input type="number" step="0.01" min="0" max="100" name="cw_affiliate_override" value="<?php echo esc_attr( $fields['affiliate_override'] ); ?>" class="widefat"></p>
		<?php
	}

	public function render_combo_meta( $post ) {
		wp_nonce_field( 'cw_save_combo_meta', 'cw_combo_meta_nonce' );
		$selected = (array) get_post_meta( $post->ID, '_cw_combo_products', true );
		$price    = get_post_meta( $post->ID, '_cw_combo_price', true );
		$books    = get_posts(
			array(
				'post_type'      => 'cw_book',
				'posts_per_page' => -1,
				'post_status'    => 'publish',
			)
		);
		?>
		<p><label><?php esc_html_e( 'Combo Price (INR)', 'cashwala-shop' ); ?></label><br><input type="number" step="0.01" min="0" name="cw_combo_price" value="<?php echo esc_attr( $price ); ?>" class="widefat"></p>
		<p><label><?php esc_html_e( 'Select Products', 'cashwala-shop' ); ?></label></p>
		<select name="cw_combo_products[]" multiple size="8" class="widefat">
			<?php foreach ( $books as $book ) : ?>
				<option value="<?php echo esc_attr( $book->ID ); ?>" <?php selected( in_array( (string) $book->ID, array_map( 'strval', $selected ), true ) ); ?>><?php echo esc_html( $book->post_title ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function save_meta( $post_id ) {
		if ( ! isset( $_POST['cw_book_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cw_book_meta_nonce'] ) ), 'cw_save_book_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		update_post_meta( $post_id, '_cw_price', isset( $_POST['cw_price'] ) ? floatval( wp_unslash( $_POST['cw_price'] ) ) : 0 );
		update_post_meta( $post_id, '_cw_pdf_url', isset( $_POST['cw_pdf_url'] ) ? esc_url_raw( wp_unslash( $_POST['cw_pdf_url'] ) ) : '' );
		update_post_meta( $post_id, '_cw_sample_url', isset( $_POST['cw_sample_url'] ) ? esc_url_raw( wp_unslash( $_POST['cw_sample_url'] ) ) : '' );
		update_post_meta( $post_id, '_cw_youtube_url', isset( $_POST['cw_youtube_url'] ) ? esc_url_raw( wp_unslash( $_POST['cw_youtube_url'] ) ) : '' );
		update_post_meta( $post_id, '_cw_gumroad_url', isset( $_POST['cw_gumroad_url'] ) ? esc_url_raw( wp_unslash( $_POST['cw_gumroad_url'] ) ) : '' );
		update_post_meta( $post_id, '_cw_affiliate_override', isset( $_POST['cw_affiliate_override'] ) ? floatval( wp_unslash( $_POST['cw_affiliate_override'] ) ) : '' );
	}

	public function get_book_meta( $post_id ) {
		return array(
			'price'              => get_post_meta( $post_id, '_cw_price', true ),
			'pdf_url'            => get_post_meta( $post_id, '_cw_pdf_url', true ),
			'sample_url'         => get_post_meta( $post_id, '_cw_sample_url', true ),
			'youtube_url'        => get_post_meta( $post_id, '_cw_youtube_url', true ),
			'gumroad_url'        => get_post_meta( $post_id, '_cw_gumroad_url', true ),
			'affiliate_override' => get_post_meta( $post_id, '_cw_affiliate_override', true ),
		);
	}
}

add_action(
	'save_post_cw_combo',
	function( $post_id ) {
		if ( ! isset( $_POST['cw_combo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cw_combo_meta_nonce'] ) ), 'cw_save_combo_meta' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$products = isset( $_POST['cw_combo_products'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['cw_combo_products'] ) ) : array();
		update_post_meta( $post_id, '_cw_combo_products', $products );
		update_post_meta( $post_id, '_cw_combo_price', isset( $_POST['cw_combo_price'] ) ? floatval( wp_unslash( $_POST['cw_combo_price'] ) ) : 0 );
	}
);
