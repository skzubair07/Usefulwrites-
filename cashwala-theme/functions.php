<?php
/**
 * Cashwala theme engine.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_template_directory() . '/inc/admin-settings.php';

/**
 * Theme setup.
 */
function cashwala_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Primary Menu', 'cashwala-theme' ),
		)
	);
}
add_action( 'after_setup_theme', 'cashwala_setup' );

/**
 * Register all frontend assets.
 */
function cashwala_enqueue_assets() {
	$version = wp_get_theme()->get( 'Version' );
	$options = cashwala_get_settings();

	$skin          = ! empty( $options['global_skin'] ) ? sanitize_key( $options['global_skin'] ) : 'premium';
	$allowed_skins = array( 'premium', 'dark', 'neon', 'minimal', 'classic' );

	if ( ! in_array( $skin, $allowed_skins, true ) ) {
		$skin = 'premium';
	}

	wp_enqueue_style( 'cashwala-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', array(), null );
	wp_enqueue_style( 'cashwala-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css', array(), '6.5.2' );
	wp_enqueue_style( 'cashwala-style', get_template_directory_uri() . '/assets/css/style.css', array( 'cashwala-fonts', 'cashwala-fontawesome' ), $version );
	wp_enqueue_style( 'cashwala-skin', get_template_directory_uri() . '/skins/' . $skin . '.css', array( 'cashwala-style' ), $version );

	wp_enqueue_script( 'cashwala-theme-engine', get_template_directory_uri() . '/assets/js/theme-engine.js', array(), $version, true );
	$currency_symbol = function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ) : '₹';

	wp_localize_script(
		'cashwala-theme-engine',
		'cashwalaEngine',
		array(
			'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
			'nonce'           => wp_create_nonce( 'cashwala_nonce' ),
			'skinNonce'       => wp_create_nonce( 'cashwala_skin_nonce' ),
			'currencySymbol'  => $currency_symbol,
			'leadSuccessText' => esc_html__( 'Perfect! Redirecting you to secure checkout…', 'cashwala-theme' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'cashwala_enqueue_assets' );

/**
 * Register product post type.
 */
function cashwala_register_product_cpt() {
	$labels = array(
		'name'          => esc_html__( 'Cashwala Products', 'cashwala-theme' ),
		'singular_name' => esc_html__( 'Cashwala Product', 'cashwala-theme' ),
		'add_new_item'  => esc_html__( 'Add New Product', 'cashwala-theme' ),
		'edit_item'     => esc_html__( 'Edit Product', 'cashwala-theme' ),
	);

	register_post_type(
		'cw_product',
		array(
			'labels'        => $labels,
			'public'        => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-cart',
			'show_in_rest'  => true,
			'rewrite'       => array( 'slug' => 'products' ),
			'supports'      => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		)
	);
}
add_action( 'init', 'cashwala_register_product_cpt' );

/**
 * Register product meta box.
 */
function cashwala_register_product_meta_box() {
	add_meta_box( 'cashwala_product_meta', esc_html__( 'Product Commerce Data', 'cashwala-theme' ), 'cashwala_render_product_meta_box', 'cw_product', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'cashwala_register_product_meta_box' );

/**
 * Render meta box.
 *
 * @param WP_Post $post Product post.
 */
function cashwala_render_product_meta_box( $post ) {
	wp_nonce_field( 'cashwala_save_product_meta', 'cashwala_product_meta_nonce' );

	$price      = get_post_meta( $post->ID, '_cw_price', true );
	$sale_price = get_post_meta( $post->ID, '_cw_sale_price', true );
	$file_url   = get_post_meta( $post->ID, '_cw_file_url', true );
	$type       = get_post_meta( $post->ID, '_cw_product_type', true );
	$type       = $type ? $type : 'book';
	?>
	<p>
		<label for="cw_price"><strong><?php esc_html_e( 'Regular Price', 'cashwala-theme' ); ?></strong></label><br>
		<input type="number" min="0" step="0.01" id="cw_price" name="cw_price" value="<?php echo esc_attr( $price ); ?>" class="widefat">
	</p>
	<p>
		<label for="cw_sale_price"><strong><?php esc_html_e( 'Sale Price', 'cashwala-theme' ); ?></strong></label><br>
		<input type="number" min="0" step="0.01" id="cw_sale_price" name="cw_sale_price" value="<?php echo esc_attr( $sale_price ); ?>" class="widefat">
	</p>
	<p>
		<label for="cw_file_url"><strong><?php esc_html_e( 'Secure File URL', 'cashwala-theme' ); ?></strong></label><br>
		<input type="url" id="cw_file_url" name="cw_file_url" value="<?php echo esc_url( $file_url ); ?>" class="widefat" placeholder="https://">
	</p>
	<p>
		<label for="cw_product_type"><strong><?php esc_html_e( 'Type', 'cashwala-theme' ); ?></strong></label><br>
		<select id="cw_product_type" name="cw_product_type">
			<option value="book" <?php selected( $type, 'book' ); ?>><?php esc_html_e( 'Book', 'cashwala-theme' ); ?></option>
			<option value="combo" <?php selected( $type, 'combo' ); ?>><?php esc_html_e( 'Combo', 'cashwala-theme' ); ?></option>
		</select>
	</p>
	<?php
}

/**
 * Save product meta.
 *
 * @param int $post_id Post ID.
 */
function cashwala_save_product_meta( $post_id ) {
	if ( ! isset( $_POST['cashwala_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cashwala_product_meta_nonce'] ) ), 'cashwala_save_product_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$price      = isset( $_POST['cw_price'] ) ? (float) wp_unslash( $_POST['cw_price'] ) : 0;
	$sale_price = isset( $_POST['cw_sale_price'] ) ? (float) wp_unslash( $_POST['cw_sale_price'] ) : 0;
	$file_url   = isset( $_POST['cw_file_url'] ) ? esc_url_raw( wp_unslash( $_POST['cw_file_url'] ) ) : '';
	$type       = isset( $_POST['cw_product_type'] ) ? sanitize_key( wp_unslash( $_POST['cw_product_type'] ) ) : 'book';

	update_post_meta( $post_id, '_cw_price', $price );
	update_post_meta( $post_id, '_cw_sale_price', $sale_price );
	update_post_meta( $post_id, '_cw_file_url', $file_url );
	update_post_meta( $post_id, '_cw_product_type', in_array( $type, array( 'book', 'combo' ), true ) ? $type : 'book' );
}
add_action( 'save_post_cw_product', 'cashwala_save_product_meta' );

/**
 * AJAX live search.
 */
function cashwala_live_search_ajax() {
	check_ajax_referer( 'cashwala_nonce', 'nonce' );

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';

	$query = new WP_Query(
		array(
			'post_type'      => 'cw_product',
			'post_status'    => 'publish',
			's'              => $term,
			'posts_per_page' => 6,
			'no_found_rows'  => true,
		)
	);

	ob_start();

	if ( $query->have_posts() ) {
		echo '<div class="cw-live-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			echo '<a class="cw-live-item" href="' . esc_url( get_permalink() ) . '">';
			echo '<strong>' . esc_html( get_the_title() ) . '</strong>';
			echo '<span>' . esc_html( wp_trim_words( get_the_excerpt(), 10 ) ) . '</span>';
			echo '</a>';
		}
		echo '</div>';
	} else {
		echo '<p class="cw-live-empty">' . esc_html__( 'No products found.', 'cashwala-theme' ) . '</p>';
	}

	wp_reset_postdata();
	wp_send_json_success( ob_get_clean() );
}
add_action( 'wp_ajax_cashwala_live_search', 'cashwala_live_search_ajax' );
add_action( 'wp_ajax_nopriv_cashwala_live_search', 'cashwala_live_search_ajax' );

/**
 * Save skin preference from header switch.
 */
function cashwala_switch_skin_ajax() {
	check_ajax_referer( 'cashwala_skin_nonce', 'nonce' );

	$skin          = isset( $_POST['skin'] ) ? sanitize_key( wp_unslash( $_POST['skin'] ) ) : 'premium';
	$allowed_skins = array( 'premium', 'dark', 'neon', 'minimal', 'classic' );

	if ( ! in_array( $skin, $allowed_skins, true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid skin.' ) );
	}

	setcookie( 'cashwala_skin_preview', $skin, time() + WEEK_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
	wp_send_json_success( array( 'skin' => $skin ) );
}
add_action( 'wp_ajax_cashwala_switch_skin', 'cashwala_switch_skin_ajax' );
add_action( 'wp_ajax_nopriv_cashwala_switch_skin', 'cashwala_switch_skin_ajax' );

/**
 * Use cookie skin for real-time previews.
 *
 * @param array<string,mixed> $settings Existing settings.
 * @return array<string,mixed>
 */
function cashwala_apply_skin_preview( $settings ) {
	if ( empty( $_COOKIE['cashwala_skin_preview'] ) ) {
		return $settings;
	}

	$skin = sanitize_key( wp_unslash( $_COOKIE['cashwala_skin_preview'] ) );
	if ( in_array( $skin, array( 'premium', 'dark', 'neon', 'minimal', 'classic' ), true ) ) {
		$settings['global_skin'] = $skin;
	}

	return $settings;
}
add_filter( 'cashwala_theme_settings', 'cashwala_apply_skin_preview' );

/**
 * Create custom lead table.
 */
function cashwala_create_leads_table() {
	global $wpdb;
	$table_name      = $wpdb->prefix . 'cw_leads';
	$charset_collate = $wpdb->get_charset_collate();
	$sql             = "CREATE TABLE {$table_name} (
		id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		product_id BIGINT UNSIGNED NOT NULL,
		name VARCHAR(120) NOT NULL,
		email VARCHAR(180) NOT NULL,
		whatsapp VARCHAR(25) NOT NULL,
		razorpay_payment_id VARCHAR(120) DEFAULT '',
		created_at DATETIME NOT NULL,
		PRIMARY KEY (id),
		KEY product_id (product_id),
		KEY email (email)
	) {$charset_collate};";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
add_action( 'after_switch_theme', 'cashwala_create_leads_table' );

/**
 * Lead capture and checkout payload.
 */
function cashwala_capture_lead_ajax() {
	check_ajax_referer( 'cashwala_nonce', 'nonce' );

	$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$name       = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$whatsapp   = isset( $_POST['whatsapp'] ) ? preg_replace( '/[^0-9+]/', '', (string) wp_unslash( $_POST['whatsapp'] ) ) : '';

	if ( ! $product_id || ! $name || ! is_email( $email ) || ! $whatsapp ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Please complete all lead fields.', 'cashwala-theme' ) ) );
	}

	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'cw_leads',
		array(
			'product_id' => $product_id,
			'name'       => $name,
			'email'      => $email,
			'whatsapp'   => $whatsapp,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%s', '%s', '%s', '%s' )
	);

	$options     = cashwala_get_settings();
	$key_id      = ! empty( $options['razorpay_key_id'] ) ? $options['razorpay_key_id'] : '';
	$amount      = (float) get_post_meta( $product_id, '_cw_sale_price', true );
	$amount      = $amount > 0 ? $amount : (float) get_post_meta( $product_id, '_cw_price', true );
	$amount_paise = (int) round( $amount * 100 );

	$checkout_url = add_query_arg(
		array(
			'product_id' => $product_id,
			'lead_id'    => (int) $wpdb->insert_id,
			'amount'     => $amount_paise,
			'key_id'     => rawurlencode( $key_id ),
		),
		home_url( '/checkout/' )
	);

	wp_send_json_success(
		array(
			'checkout_url' => esc_url_raw( $checkout_url ),
			'message'      => esc_html__( 'Lead captured. Redirecting to Razorpay…', 'cashwala-theme' ),
		)
	);
}
add_action( 'wp_ajax_cashwala_capture_lead', 'cashwala_capture_lead_ajax' );
add_action( 'wp_ajax_nopriv_cashwala_capture_lead', 'cashwala_capture_lead_ajax' );

/**
 * Payment success: generate secure download token + send mail.
 */
function cashwala_payment_success_ajax() {
	check_ajax_referer( 'cashwala_nonce', 'nonce' );

	$lead_id             = isset( $_POST['lead_id'] ) ? absint( wp_unslash( $_POST['lead_id'] ) ) : 0;
	$payment_id          = isset( $_POST['razorpay_payment_id'] ) ? sanitize_text_field( wp_unslash( $_POST['razorpay_payment_id'] ) ) : '';
	$product_id          = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
	$secure_file_url_raw = get_post_meta( $product_id, '_cw_file_url', true );

	if ( ! $lead_id || ! $payment_id || ! $product_id || ! $secure_file_url_raw ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Invalid payment callback.', 'cashwala-theme' ) ) );
	}

	global $wpdb;
	$leads_table = $wpdb->prefix . 'cw_leads';
	$lead        = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$leads_table} WHERE id = %d", $lead_id ) );

	if ( ! $lead ) {
		wp_send_json_error( array( 'message' => esc_html__( 'Lead not found.', 'cashwala-theme' ) ) );
	}

	$wpdb->update(
		$leads_table,
		array( 'razorpay_payment_id' => $payment_id ),
		array( 'id' => $lead_id ),
		array( '%s' ),
		array( '%d' )
	);

	$token = wp_generate_password( 20, false, false );
	set_transient(
		'cashwala_download_' . $token,
		array(
			'product_id' => $product_id,
			'file_url'   => esc_url_raw( $secure_file_url_raw ),
		),
		HOUR_IN_SECONDS * 24
	);

	$download_url = add_query_arg(
		array(
			'cashwala_download' => $token,
		),
		home_url( '/' )
	);

	wp_mail(
		$lead->email,
		sprintf( 'Your download link: %s', get_the_title( $product_id ) ),
		sprintf( "Hi %s,\n\nThanks for your purchase.\nDownload here: %s\n\n- Team Cashwala", $lead->name, $download_url )
	);

	wp_send_json_success(
		array(
			'download_url' => esc_url_raw( $download_url ),
			'message'      => esc_html__( 'Payment verified. Download ready.', 'cashwala-theme' ),
		)
	);
}
add_action( 'wp_ajax_cashwala_payment_success', 'cashwala_payment_success_ajax' );
add_action( 'wp_ajax_nopriv_cashwala_payment_success', 'cashwala_payment_success_ajax' );

/**
 * Process secure token download.
 */
function cashwala_handle_secure_download() {
	if ( empty( $_GET['cashwala_download'] ) ) {
		return;
	}

	$token = sanitize_text_field( wp_unslash( $_GET['cashwala_download'] ) );
	$data  = get_transient( 'cashwala_download_' . $token );

	if ( empty( $data['file_url'] ) ) {
		wp_die( esc_html__( 'This download link is expired or invalid.', 'cashwala-theme' ) );
	}

	delete_transient( 'cashwala_download_' . $token );
	wp_safe_redirect( esc_url_raw( $data['file_url'] ) );
	exit;
}
add_action( 'template_redirect', 'cashwala_handle_secure_download' );
