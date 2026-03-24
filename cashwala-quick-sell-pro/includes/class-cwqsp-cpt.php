<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWQSP_CPT {
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( $this, 'meta_boxes' ) );
		add_action( 'save_post_cw_product', array( $this, 'save_product_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	public function enqueue_admin_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || 'cw_product' !== $screen->post_type ) {
			return;
		}
		wp_enqueue_media();
	}

	public function register_post_type() {
		register_post_type(
			'cw_product',
			array(
				'label'        => 'Products',
				'labels'       => array(
					'name'          => 'Products',
					'singular_name' => 'Product',
				),
				'public'       => true,
				'show_in_menu' => true,
				'menu_icon'    => 'dashicons-products',
				'supports'     => array( 'title', 'editor', 'excerpt' ),
				'show_in_rest' => true,
			)
		);
	}

	public function meta_boxes() {
		add_meta_box( 'cwqsp_product_meta', 'Product Settings', array( $this, 'render_meta' ), 'cw_product', 'normal', 'high' );
	}

	public function render_meta( $post ) {
		wp_nonce_field( 'cwqsp_save_product', 'cwqsp_product_nonce' );
		$price         = get_post_meta( $post->ID, '_cwqsp_price', true );
		$file_id       = absint( get_post_meta( $post->ID, '_cwqsp_file_id', true ) );
		$redirect_url  = get_post_meta( $post->ID, '_cwqsp_redirect_url', true );
		$whatsapp      = get_post_meta( $post->ID, '_cwqsp_whatsapp', true );
		$file_url      = $file_id ? wp_get_attachment_url( $file_id ) : '';
		?>
		<p><label><strong>Price (INR)</strong></label><br><input type="number" step="0.01" min="0" class="widefat" name="cwqsp_price" value="<?php echo esc_attr( $price ); ?>"></p>
		<p><label><strong>Digital File</strong></label><br>
			<input type="hidden" id="cwqsp_file_id" name="cwqsp_file_id" value="<?php echo esc_attr( $file_id ); ?>">
			<input type="text" id="cwqsp_file_url" class="widefat" value="<?php echo esc_attr( $file_url ); ?>" readonly>
			<button type="button" class="button" id="cwqsp_file_picker">Select File</button>
		</p>
		<p><label><strong>Redirect URL</strong></label><br><input type="url" class="widefat" name="cwqsp_redirect_url" value="<?php echo esc_url( $redirect_url ); ?>"></p>
		<p><label><strong>WhatsApp Number</strong></label><br><input type="text" class="widefat" name="cwqsp_whatsapp" placeholder="919999999999" value="<?php echo esc_attr( $whatsapp ); ?>"></p>
		<script>
		jQuery(function($){
			if (typeof wp === 'undefined' || typeof wp.media === 'undefined') return;
			$('#cwqsp_file_picker').on('click', function(e){
				e.preventDefault();
				var frame = wp.media({title: 'Select Product File', button: {text: 'Use this file'}, multiple: false});
				frame.on('select', function(){
					var file = frame.state().get('selection').first().toJSON();
					$('#cwqsp_file_id').val(file.id);
					$('#cwqsp_file_url').val(file.url);
				});
				frame.open();
			});
		});
		</script>
		<?php
	}

	public function save_product_meta( $post_id ) {
		if ( ! isset( $_POST['cwqsp_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cwqsp_product_nonce'] ) ), 'cwqsp_save_product' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_cwqsp_price', isset( $_POST['cwqsp_price'] ) ? floatval( wp_unslash( $_POST['cwqsp_price'] ) ) : 0 );
		update_post_meta( $post_id, '_cwqsp_file_id', isset( $_POST['cwqsp_file_id'] ) ? absint( wp_unslash( $_POST['cwqsp_file_id'] ) ) : 0 );
		update_post_meta( $post_id, '_cwqsp_redirect_url', isset( $_POST['cwqsp_redirect_url'] ) ? esc_url_raw( wp_unslash( $_POST['cwqsp_redirect_url'] ) ) : '' );
		update_post_meta( $post_id, '_cwqsp_whatsapp', isset( $_POST['cwqsp_whatsapp'] ) ? preg_replace( '/[^0-9]/', '', wp_unslash( $_POST['cwqsp_whatsapp'] ) ) : '' );
	}
}
