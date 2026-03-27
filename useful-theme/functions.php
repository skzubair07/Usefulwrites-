<?php
/**
 * Useful Theme functions and definitions.
 *
 * @package Useful_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme setup.
 */
function useful_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);
}
add_action( 'after_setup_theme', 'useful_theme_setup' );

/**
 * Register CPT for products.
 */
function useful_register_product_cpt() {
	$labels = array(
		'name'               => esc_html__( 'Useful Products', 'useful-theme' ),
		'singular_name'      => esc_html__( 'Useful Product', 'useful-theme' ),
		'add_new'            => esc_html__( 'Add New', 'useful-theme' ),
		'add_new_item'       => esc_html__( 'Add New Product', 'useful-theme' ),
		'edit_item'          => esc_html__( 'Edit Product', 'useful-theme' ),
		'new_item'           => esc_html__( 'New Product', 'useful-theme' ),
		'view_item'          => esc_html__( 'View Product', 'useful-theme' ),
		'search_items'       => esc_html__( 'Search Products', 'useful-theme' ),
		'not_found'          => esc_html__( 'No products found', 'useful-theme' ),
		'not_found_in_trash' => esc_html__( 'No products found in Trash', 'useful-theme' ),
		'menu_name'          => esc_html__( 'Useful Products', 'useful-theme' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'has_archive'        => true,
		'rewrite'            => array( 'slug' => 'products' ),
		'supports'           => array( 'title', 'editor', 'thumbnail' ),
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-cart',
	);

	register_post_type( 'useful_product', $args );
}
add_action( 'init', 'useful_register_product_cpt' );

/**
 * Add meta box for product data.
 */
function useful_add_product_meta_box() {
	add_meta_box(
		'useful_product_meta',
		esc_html__( 'Product Details', 'useful-theme' ),
		'useful_render_product_meta_box',
		'useful_product',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'useful_add_product_meta_box' );

/**
 * Render product meta box.
 *
 * @param WP_Post $post Current post.
 */
function useful_render_product_meta_box( $post ) {
	$price = get_post_meta( $post->ID, '_useful_price', true );
	$badge = get_post_meta( $post->ID, '_useful_badge', true );

	wp_nonce_field( 'useful_save_product_meta', 'useful_product_meta_nonce' );
	?>
	<p>
		<label for="useful_price"><strong><?php echo esc_html__( 'Price (₹)', 'useful-theme' ); ?></strong></label><br>
		<input type="number" min="0" step="0.01" id="useful_price" name="useful_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%;max-width:280px;">
	</p>
	<p>
		<label for="useful_badge"><strong><?php echo esc_html__( 'Badge', 'useful-theme' ); ?></strong></label><br>
		<input type="text" id="useful_badge" name="useful_badge" value="<?php echo esc_attr( $badge ); ?>" style="width:100%;max-width:320px;" placeholder="Top Deal">
	</p>
	<?php
}

/**
 * Save product meta data.
 *
 * @param int $post_id Post ID.
 */
function useful_save_product_meta( $post_id ) {
	if ( ! isset( $_POST['useful_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['useful_product_meta_nonce'] ) ), 'useful_save_product_meta' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['useful_price'] ) ) {
		$price = sanitize_text_field( wp_unslash( $_POST['useful_price'] ) );
		update_post_meta( $post_id, '_useful_price', $price );
	}

	if ( isset( $_POST['useful_badge'] ) ) {
		$badge = sanitize_text_field( wp_unslash( $_POST['useful_badge'] ) );
		update_post_meta( $post_id, '_useful_badge', $badge );
	}
}
add_action( 'save_post_useful_product', 'useful_save_product_meta' );

/**
 * Add admin menu page.
 */
function useful_add_theme_admin_menu() {
	add_theme_page(
		esc_html__( 'Useful Theme Settings', 'useful-theme' ),
		esc_html__( 'Useful Theme', 'useful-theme' ),
		'manage_options',
		'useful-theme-settings',
		'useful_render_theme_settings_page'
	);
}
add_action( 'admin_menu', 'useful_add_theme_admin_menu' );

/**
 * Render admin settings page.
 */
function useful_render_theme_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$allowed_skins = array( 'premium', 'neon', 'minimal', 'classic', 'retro' );
	$message       = '';

	if ( isset( $_POST['useful_theme_submit'] ) ) {
		if ( ! isset( $_POST['useful_theme_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['useful_theme_nonce'] ) ), 'useful_theme_save_settings' ) ) {
			$message = esc_html__( 'Security check failed. Please try again.', 'useful-theme' );
		} else {
			$skin = isset( $_POST['useful_skin'] ) ? sanitize_text_field( wp_unslash( $_POST['useful_skin'] ) ) : 'premium';
			if ( ! in_array( $skin, $allowed_skins, true ) ) {
				$skin = 'premium';
			}
			update_option( 'useful_skin', $skin );
			$message = esc_html__( 'Settings saved.', 'useful-theme' );
		}
	}

	$current_skin = get_option( 'useful_skin', 'premium' );
	if ( ! in_array( $current_skin, $allowed_skins, true ) ) {
		$current_skin = 'premium';
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Useful Theme Settings', 'useful-theme' ); ?></h1>
		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="">
			<?php wp_nonce_field( 'useful_theme_save_settings', 'useful_theme_nonce' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="useful_skin"><?php echo esc_html__( 'Select Skin', 'useful-theme' ); ?></label></th>
					<td>
						<select id="useful_skin" name="useful_skin">
							<option value="premium" <?php selected( $current_skin, 'premium' ); ?>><?php echo esc_html__( 'Premium', 'useful-theme' ); ?></option>
							<option value="neon" <?php selected( $current_skin, 'neon' ); ?>><?php echo esc_html__( 'Neon', 'useful-theme' ); ?></option>
							<option value="minimal" <?php selected( $current_skin, 'minimal' ); ?>><?php echo esc_html__( 'Minimal', 'useful-theme' ); ?></option>
							<option value="classic" <?php selected( $current_skin, 'classic' ); ?>><?php echo esc_html__( 'Classic', 'useful-theme' ); ?></option>
							<option value="retro" <?php selected( $current_skin, 'retro' ); ?>><?php echo esc_html__( 'Retro', 'useful-theme' ); ?></option>
						</select>
					</td>
				</tr>
			</table>
			<?php submit_button( esc_html__( 'Save Changes', 'useful-theme' ), 'primary', 'useful_theme_submit' ); ?>
		</form>
	</div>
	<?php
}

/**
 * Enqueue scripts and styles.
 */
function useful_enqueue_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'useful-theme-style',
		get_stylesheet_uri(),
		array(),
		$theme_version
	);

	wp_enqueue_script(
		'useful-theme-engine',
		get_template_directory_uri() . '/assets/js/engine.js',
		array(),
		$theme_version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'useful_enqueue_assets' );

/**
 * Add defer attribute to theme JS.
 *
 * @param string $tag    Script tag.
 * @param string $handle Script handle.
 * @param string $src    Script src.
 * @return string
 */
function useful_add_defer_to_engine( $tag, $handle, $src ) {
	if ( 'useful-theme-engine' !== $handle ) {
		return $tag;
	}

	return '<script src="' . esc_url( $src ) . '" defer></script>';
}
add_filter( 'script_loader_tag', 'useful_add_defer_to_engine', 10, 3 );

/**
 * Inline dynamic skin variables.
 */
function useful_add_skin_variables() {
	$skins = array(
		'premium' => array(
			'--bg'            => '#f5f5f7',
			'--surface'       => '#ffffff',
			'--text'          => '#101218',
			'--muted'         => '#5a6474',
			'--accent'        => '#4f46e5',
			'--radius'        => '24px',
			'--shadow'        => '0 30px 80px rgba(16, 18, 24, 0.08), 0 10px 30px rgba(16, 18, 24, 0.06)',
			'--border'        => '1px solid rgba(79, 70, 229, 0.08)',
			'--font-primary'  => 'Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
		),
		'neon'    => array(
			'--bg'            => '#0d1117',
			'--surface'       => '#111827',
			'--text'          => '#e5f3ff',
			'--muted'         => '#a8c2df',
			'--accent'        => '#00f6ff',
			'--radius'        => '16px',
			'--shadow'        => '0 0 0 1px rgba(0, 246, 255, 0.6), 0 0 32px rgba(255, 0, 204, 0.35)',
			'--border'        => '1px solid rgba(0, 246, 255, 0.7)',
			'--font-primary'  => 'Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
		),
		'minimal' => array(
			'--bg'            => '#ffffff',
			'--surface'       => '#ffffff',
			'--text'          => '#121212',
			'--muted'         => '#555555',
			'--accent'        => '#1d4ed8',
			'--radius'        => '0px',
			'--shadow'        => 'none',
			'--border'        => '1px solid #dedede',
			'--font-primary'  => '"Playfair Display", Georgia, "Times New Roman", serif',
		),
		'classic' => array(
			'--bg'            => '#f8fafc',
			'--surface'       => '#ffffff',
			'--text'          => '#0f172a',
			'--muted'         => '#475569',
			'--accent'        => '#1d4ed8',
			'--radius'        => '8px',
			'--shadow'        => '0 10px 30px rgba(15, 23, 42, 0.08)',
			'--border'        => '1px solid rgba(29, 78, 216, 0.15)',
			'--font-primary'  => 'Inter, system-ui, -apple-system, Segoe UI, Roboto, sans-serif',
		),
		'retro'   => array(
			'--bg'            => '#ffd9c9',
			'--surface'       => '#ffe9df',
			'--text'          => '#1a1a1a',
			'--muted'         => '#343434',
			'--accent'        => '#ff5d3a',
			'--radius'        => '6px',
			'--shadow'        => '6px 6px 0 #000000',
			'--border'        => '2px solid #000000',
			'--font-primary'  => '"Courier New", Courier, monospace',
		),
	);

	$current_skin = get_option( 'useful_skin', 'premium' );
	if ( ! isset( $skins[ $current_skin ] ) ) {
		$current_skin = 'premium';
	}

	$css = ':root {';
	foreach ( $skins[ $current_skin ] as $key => $value ) {
		$css .= sprintf( '%1$s:%2$s;', esc_html( $key ), esc_html( $value ) );
	}
	$css .= '}';

	if ( 'neon' === $current_skin ) {
		$css .= 'body{ text-shadow:0 0 10px rgba(0,246,255,0.2);}';
	}

	wp_add_inline_style( 'useful-theme-style', $css );
}
add_action( 'wp_enqueue_scripts', 'useful_add_skin_variables', 20 );

/**
 * Disable unnecessary frontend bloat.
 */
function useful_remove_wp_bloat() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	remove_action( 'rest_api_init', 'wp_oembed_register_route' );
}
add_action( 'init', 'useful_remove_wp_bloat' );
