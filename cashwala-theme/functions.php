<?php
/**
 * Cashwala Theme functions.
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'cashwala_theme_setup' ) ) {
	/**
	 * Theme setup.
	 */
	function cashwala_theme_setup() {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

		register_nav_menus(
			array(
				'primary' => esc_html__( 'Primary Menu', 'cashwala-theme' ),
			)
		);
	}
}
add_action( 'after_setup_theme', 'cashwala_theme_setup' );

/**
 * Register assets.
 */
function cashwala_theme_assets() {
	$theme_version = wp_get_theme()->get( 'Version' );
	$options       = cashwala_get_options();
	$skin_enabled  = ! empty( $options['enable_skins'] );
	$skin          = $skin_enabled && ! empty( $options['selected_skin'] ) ? sanitize_key( $options['selected_skin'] ) : 'classic';
	$allowed_skins = array( 'classic', 'dark', 'neon', 'minimal', 'premium' );

	if ( ! in_array( $skin, $allowed_skins, true ) ) {
		$skin = 'classic';
	}

	wp_enqueue_style( 'cashwala-main-style', get_template_directory_uri() . '/assets/css/style.css', array(), $theme_version );
	wp_enqueue_style( 'cashwala-skin-style', get_template_directory_uri() . '/skins/' . $skin . '.css', array( 'cashwala-main-style' ), $theme_version );

	wp_enqueue_script( 'cashwala-main-js', get_template_directory_uri() . '/assets/js/main.js', array(), $theme_version, true );
	wp_localize_script(
		'cashwala-main-js',
		'cashwalaTheme',
		array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'cashwala_search_nonce' ),
			'darkModeEnabled'   => ! empty( $options['enable_dark_mode'] ),
			'whatsappEnabled'   => ! empty( $options['enable_whatsapp'] ),
			'searchPlaceholder' => esc_html__( 'Search plugins...', 'cashwala-theme' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'cashwala_theme_assets' );

/**
 * Add async decoding + lazy loading to post thumbnail HTML.
 *
 * @param string $html Image html.
 * @return string
 */
function cashwala_filter_post_thumbnail( $html ) {
	if ( false === strpos( $html, 'loading=' ) ) {
		$html = str_replace( '<img', '<img loading="lazy"', $html );
	}

	if ( false === strpos( $html, 'decoding=' ) ) {
		$html = str_replace( '<img', '<img decoding="async"', $html );
	}

	return $html;
}
add_filter( 'post_thumbnail_html', 'cashwala_filter_post_thumbnail' );

/**
 * Theme defaults.
 *
 * @return array<string,mixed>
 */
function cashwala_default_options() {
	return array(
		'website_name'             => get_bloginfo( 'name' ),
		'logo_url'                 => '',
		'whatsapp_number'          => '',
		'whatsapp_message'         => 'Hello, I am interested in your product.',
		'header_links'             => array(),
		'footer_links'             => array(),
		'selected_skin'            => 'classic',
		'enable_whatsapp'          => 1,
		'enable_dark_mode'         => 1,
		'enable_skins'             => 1,
	);
}

/**
 * Get merged options.
 *
 * @return array<string,mixed>
 */
function cashwala_get_options() {
	$saved = get_option( 'cashwala_theme_options', array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args( $saved, cashwala_default_options() );
}

/**
 * Register settings.
 */
function cashwala_register_settings() {
	register_setting(
		'cashwala_theme_options_group',
		'cashwala_theme_options',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'cashwala_sanitize_options',
			'default'           => cashwala_default_options(),
		)
	);
}
add_action( 'admin_init', 'cashwala_register_settings' );

/**
 * Sanitize options.
 *
 * @param array<string,mixed> $input Raw values.
 * @return array<string,mixed>
 */
function cashwala_sanitize_options( $input ) {
	$defaults = cashwala_default_options();
	$output   = array();

	$output['website_name']     = isset( $input['website_name'] ) ? sanitize_text_field( $input['website_name'] ) : $defaults['website_name'];
	$output['logo_url']         = isset( $input['logo_url'] ) ? esc_url_raw( $input['logo_url'] ) : '';
	$output['whatsapp_number']  = isset( $input['whatsapp_number'] ) ? preg_replace( '/[^0-9]/', '', (string) $input['whatsapp_number'] ) : '';
	$output['whatsapp_message'] = isset( $input['whatsapp_message'] ) ? sanitize_textarea_field( $input['whatsapp_message'] ) : $defaults['whatsapp_message'];

	$allowed_skins = array( 'classic', 'dark', 'neon', 'minimal', 'premium' );
	$skin_input    = isset( $input['selected_skin'] ) ? sanitize_key( $input['selected_skin'] ) : 'classic';
	$output['selected_skin'] = in_array( $skin_input, $allowed_skins, true ) ? $skin_input : 'classic';

	$output['enable_whatsapp']  = ! empty( $input['enable_whatsapp'] ) ? 1 : 0;
	$output['enable_dark_mode'] = ! empty( $input['enable_dark_mode'] ) ? 1 : 0;
	$output['enable_skins']     = ! empty( $input['enable_skins'] ) ? 1 : 0;

	$output['header_links'] = cashwala_sanitize_links_array( isset( $input['header_links'] ) ? $input['header_links'] : array() );
	$output['footer_links'] = cashwala_sanitize_links_array( isset( $input['footer_links'] ) ? $input['footer_links'] : array() );

	return wp_parse_args( $output, $defaults );
}

/**
 * Sanitize repeatable links.
 *
 * @param mixed $links Links input.
 * @return array<int,array<string,string>>
 */
function cashwala_sanitize_links_array( $links ) {
	$sanitized = array();

	if ( ! is_array( $links ) ) {
		return $sanitized;
	}

	foreach ( $links as $link ) {
		if ( ! is_array( $link ) ) {
			continue;
		}

		$text = isset( $link['text'] ) ? sanitize_text_field( $link['text'] ) : '';
		$url  = isset( $link['url'] ) ? esc_url_raw( $link['url'] ) : '';

		if ( '' === $text || '' === $url ) {
			continue;
		}

		$sanitized[] = array(
			'text' => $text,
			'url'  => $url,
		);
	}

	return $sanitized;
}

/**
 * Register admin menu.
 */
function cashwala_register_settings_page() {
	add_theme_page(
		esc_html__( 'Cashwala Settings', 'cashwala-theme' ),
		esc_html__( 'Cashwala Settings', 'cashwala-theme' ),
		'manage_options',
		'cashwala-settings',
		'cashwala_render_settings_page'
	);
}
add_action( 'admin_menu', 'cashwala_register_settings_page' );

/**
 * Enqueue settings page scripts.
 *
 * @param string $hook Hook suffix.
 */
function cashwala_admin_assets( $hook ) {
	if ( 'appearance_page_cashwala-settings' !== $hook ) {
		return;
	}

	wp_enqueue_script( 'cashwala-admin-links', get_template_directory_uri() . '/assets/js/main.js', array(), wp_get_theme()->get( 'Version' ), true );
}
add_action( 'admin_enqueue_scripts', 'cashwala_admin_assets' );

/**
 * Render settings page.
 */
function cashwala_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options = cashwala_get_options();
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Cashwala Settings', 'cashwala-theme' ); ?></h1>
		<form action="options.php" method="post">
			<?php settings_fields( 'cashwala_theme_options_group' ); ?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="cw_website_name"><?php esc_html_e( 'Website Name', 'cashwala-theme' ); ?></label></th>
					<td><input id="cw_website_name" name="cashwala_theme_options[website_name]" type="text" class="regular-text" value="<?php echo esc_attr( $options['website_name'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cw_logo_url"><?php esc_html_e( 'Logo URL', 'cashwala-theme' ); ?></label></th>
					<td><input id="cw_logo_url" name="cashwala_theme_options[logo_url]" type="url" class="regular-text" value="<?php echo esc_url( $options['logo_url'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cw_whatsapp_number"><?php esc_html_e( 'WhatsApp Number', 'cashwala-theme' ); ?></label></th>
					<td><input id="cw_whatsapp_number" name="cashwala_theme_options[whatsapp_number]" type="text" class="regular-text" value="<?php echo esc_attr( $options['whatsapp_number'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="cw_whatsapp_message"><?php esc_html_e( 'Default WhatsApp Message', 'cashwala-theme' ); ?></label></th>
					<td><textarea id="cw_whatsapp_message" name="cashwala_theme_options[whatsapp_message]" rows="4" class="large-text"><?php echo esc_textarea( $options['whatsapp_message'] ); ?></textarea></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Header Links', 'cashwala-theme' ); ?></th>
					<td><?php cashwala_render_links_repeatable( 'header_links', $options['header_links'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Footer Links', 'cashwala-theme' ); ?></th>
					<td><?php cashwala_render_links_repeatable( 'footer_links', $options['footer_links'] ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="cw_selected_skin"><?php esc_html_e( 'Select Theme Skin', 'cashwala-theme' ); ?></label></th>
					<td>
						<select id="cw_selected_skin" name="cashwala_theme_options[selected_skin]">
							<?php
							$skins = array(
								'classic' => 'Classic',
								'dark'    => 'Dark',
								'neon'    => 'Neon',
								'minimal' => 'Minimal',
								'premium' => 'Premium',
							);
							foreach ( $skins as $key => $label ) {
								printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $options['selected_skin'], $key, false ), esc_html( $label ) );
							}
							?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Features', 'cashwala-theme' ); ?></th>
					<td>
						<label><input type="checkbox" name="cashwala_theme_options[enable_whatsapp]" value="1" <?php checked( ! empty( $options['enable_whatsapp'] ) ); ?>> <?php esc_html_e( 'Enable WhatsApp button', 'cashwala-theme' ); ?></label><br>
						<label><input type="checkbox" name="cashwala_theme_options[enable_dark_mode]" value="1" <?php checked( ! empty( $options['enable_dark_mode'] ) ); ?>> <?php esc_html_e( 'Enable Dark Mode', 'cashwala-theme' ); ?></label><br>
						<label><input type="checkbox" name="cashwala_theme_options[enable_skins]" value="1" <?php checked( ! empty( $options['enable_skins'] ) ); ?>> <?php esc_html_e( 'Enable Skins', 'cashwala-theme' ); ?></label>
					</td>
				</tr>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Render repeatable links field.
 *
 * @param string                               $field_key Key.
 * @param array<int,array<string,string>>|mixed $values Links.
 */
function cashwala_render_links_repeatable( $field_key, $values ) {
	$values = is_array( $values ) ? $values : array();
	?>
	<div class="cashwala-repeatable" data-field-key="<?php echo esc_attr( $field_key ); ?>">
		<div class="cashwala-repeatable-list">
			<?php if ( empty( $values ) ) : ?>
				<?php cashwala_repeatable_row_template( $field_key, 0, '', '' ); ?>
			<?php else : ?>
				<?php foreach ( $values as $index => $link ) : ?>
					<?php
					$text = isset( $link['text'] ) ? $link['text'] : '';
					$url  = isset( $link['url'] ) ? $link['url'] : '';
					cashwala_repeatable_row_template( $field_key, (int) $index, $text, $url );
					?>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<button type="button" class="button cashwala-add-row"><?php esc_html_e( 'Add Link', 'cashwala-theme' ); ?></button>
	</div>
	<?php
}

/**
 * Repeatable row template.
 */
function cashwala_repeatable_row_template( $field_key, $index, $text, $url ) {
	?>
	<div class="cashwala-repeatable-row" style="margin-bottom:8px;display:flex;gap:8px;align-items:center;max-width:720px;">
		<input type="text" name="cashwala_theme_options[<?php echo esc_attr( $field_key ); ?>][<?php echo esc_attr( (string) $index ); ?>][text]" placeholder="<?php esc_attr_e( 'Text', 'cashwala-theme' ); ?>" value="<?php echo esc_attr( $text ); ?>" class="regular-text">
		<input type="url" name="cashwala_theme_options[<?php echo esc_attr( $field_key ); ?>][<?php echo esc_attr( (string) $index ); ?>][url]" placeholder="https://" value="<?php echo esc_url( $url ); ?>" class="regular-text">
		<button type="button" class="button-link-delete cashwala-remove-row"><?php esc_html_e( 'Remove', 'cashwala-theme' ); ?></button>
	</div>
	<?php
}

/**
 * AJAX live search.
 */
function cashwala_ajax_live_search() {
	check_ajax_referer( 'cashwala_search_nonce', 'nonce' );

	$term = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	if ( '' === $term ) {
		wp_send_json_success( '<p class="cashwala-no-result">' . esc_html__( 'No results found', 'cashwala-theme' ) . '</p>' );
	}

	$query = new WP_Query(
		array(
			'post_type'           => array( 'cw_book', 'cw_combo' ),
			'post_status'         => 'publish',
			'posts_per_page'      => 8,
			's'                   => $term,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	ob_start();

	if ( $query->have_posts() ) {
		echo '<ul class="cashwala-search-results-list">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$title   = get_the_title();
			$excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ? get_the_excerpt() : get_the_content() ), 18 );
			$title   = cashwala_highlight_term( $title, $term );
			$excerpt = cashwala_highlight_term( $excerpt, $term );
			echo '<li class="cashwala-search-item">';
			echo '<a href="' . esc_url( get_permalink() ) . '">';
			echo '<h3>' . wp_kses_post( $title ) . '</h3>';
			echo '<p>' . wp_kses_post( $excerpt ) . '</p>';
			echo '</a>';
			echo '</li>';
		}
		echo '</ul>';
	} else {
		echo '<p class="cashwala-no-result">' . esc_html__( 'No results found', 'cashwala-theme' ) . '</p>';
	}

	wp_reset_postdata();

	wp_send_json_success( ob_get_clean() );
}
add_action( 'wp_ajax_cashwala_live_search', 'cashwala_ajax_live_search' );
add_action( 'wp_ajax_nopriv_cashwala_live_search', 'cashwala_ajax_live_search' );

/**
 * Highlight matched term.
 *
 * @param string $text  String.
 * @param string $term  Search term.
 * @return string
 */
function cashwala_highlight_term( $text, $term ) {
	if ( '' === $term ) {
		return esc_html( $text );
	}

	$quoted = preg_quote( $term, '/' );
	$text   = esc_html( $text );

	return (string) preg_replace( '/(' . $quoted . ')/i', '<mark>$1</mark>', $text );
}

/**
 * Render list links.
 *
 * @param array<int,array<string,string>> $links Links.
 */
function cashwala_print_links( $links ) {
	if ( empty( $links ) || ! is_array( $links ) ) {
		return;
	}

	echo '<ul class="cashwala-link-list">';
	foreach ( $links as $link ) {
		if ( empty( $link['text'] ) || empty( $link['url'] ) ) {
			continue;
		}
		echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['text'] ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * WhatsApp floating button.
 */
function cashwala_whatsapp_button() {
	$options = cashwala_get_options();

	if ( empty( $options['enable_whatsapp'] ) || empty( $options['whatsapp_number'] ) ) {
		return;
	}

	$message = rawurlencode( (string) $options['whatsapp_message'] );
	$url     = 'https://wa.me/' . rawurlencode( $options['whatsapp_number'] );
	$url     = $message ? $url . '?text=' . $message : $url;

	echo '<a class="cashwala-whatsapp-float" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__( 'Chat on WhatsApp', 'cashwala-theme' ) . '">';
	echo esc_html__( 'WhatsApp', 'cashwala-theme' );
	echo '</a>';
}
add_action( 'wp_footer', 'cashwala_whatsapp_button' );
