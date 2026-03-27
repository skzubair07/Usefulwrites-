<?php
/**
 * Cashwala admin settings (2-minute setup).
 *
 * @package Cashwala_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default setting values.
 *
 * @return array<string,mixed>
 */
function cashwala_get_default_settings() {
	return array(
		'logo_id'            => 0,
		'whatsapp_number'    => '',
		'footer_links'       => "Privacy Policy|#\nTerms|#",
		'global_skin'        => 'premium',
		'razorpay_key_id'    => '',
		'razorpay_key_secret'=> '',
	);
}

/**
 * Read settings.
 *
 * @return array<string,mixed>
 */
function cashwala_get_settings() {
	$saved    = get_option( 'cashwala_settings', array() );
	$defaults = cashwala_get_default_settings();
	$settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $defaults );

	/**
	 * Filter settings before use.
	 */
	return apply_filters( 'cashwala_theme_settings', $settings );
}

/**
 * Register theme option and fields.
 */
function cashwala_register_admin_settings() {
	register_setting(
		'cashwala_settings_group',
		'cashwala_settings',
		array(
			'type'              => 'array',
			'default'           => cashwala_get_default_settings(),
			'sanitize_callback' => 'cashwala_sanitize_settings',
		)
	);

	add_settings_section( 'cashwala_branding', esc_html__( 'Branding & Theme Skin', 'cashwala-theme' ), '__return_false', 'cashwala-settings' );
	add_settings_section( 'cashwala_sales', esc_html__( 'Sales & Payment', 'cashwala-theme' ), '__return_false', 'cashwala-settings' );

	add_settings_field( 'logo_id', esc_html__( 'Logo Upload', 'cashwala-theme' ), 'cashwala_field_logo', 'cashwala-settings', 'cashwala_branding' );
	add_settings_field( 'whatsapp_number', esc_html__( 'WhatsApp Number', 'cashwala-theme' ), 'cashwala_field_whatsapp', 'cashwala-settings', 'cashwala_sales' );
	add_settings_field( 'footer_links', esc_html__( 'Footer Links', 'cashwala-theme' ), 'cashwala_field_footer_links', 'cashwala-settings', 'cashwala_branding' );
	add_settings_field( 'global_skin', esc_html__( 'Global Skin', 'cashwala-theme' ), 'cashwala_field_skin', 'cashwala-settings', 'cashwala_branding' );
	add_settings_field( 'razorpay_key_id', esc_html__( 'Razorpay Key ID', 'cashwala-theme' ), 'cashwala_field_razorpay_id', 'cashwala-settings', 'cashwala_sales' );
	add_settings_field( 'razorpay_key_secret', esc_html__( 'Razorpay Key Secret', 'cashwala-theme' ), 'cashwala_field_razorpay_secret', 'cashwala-settings', 'cashwala_sales' );
}
add_action( 'admin_init', 'cashwala_register_admin_settings' );

/**
 * Add Appearance submenu page.
 */
function cashwala_register_settings_page() {
	add_theme_page(
		esc_html__( 'Cashwala Setup', 'cashwala-theme' ),
		esc_html__( 'Cashwala Setup', 'cashwala-theme' ),
		'manage_options',
		'cashwala-settings',
		'cashwala_render_settings_page'
	);
}
add_action( 'admin_menu', 'cashwala_register_settings_page' );

/**
 * Enqueue admin media scripts.
 *
 * @param string $hook Hook suffix.
 */
function cashwala_admin_settings_assets( $hook ) {
	if ( 'appearance_page_cashwala-settings' !== $hook ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'cashwala_admin_settings_assets' );

/**
 * Sanitize all settings.
 *
 * @param array<string,mixed> $input Raw input.
 * @return array<string,mixed>
 */
function cashwala_sanitize_settings( $input ) {
	$defaults = cashwala_get_default_settings();

	return array(
		'logo_id'             => isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0,
		'whatsapp_number'     => isset( $input['whatsapp_number'] ) ? preg_replace( '/[^0-9+]/', '', (string) $input['whatsapp_number'] ) : '',
		'footer_links'        => isset( $input['footer_links'] ) ? sanitize_textarea_field( (string) $input['footer_links'] ) : $defaults['footer_links'],
		'global_skin'         => isset( $input['global_skin'] ) && in_array( $input['global_skin'], array( 'premium', 'dark', 'neon', 'minimal', 'classic' ), true ) ? sanitize_key( $input['global_skin'] ) : $defaults['global_skin'],
		'razorpay_key_id'     => isset( $input['razorpay_key_id'] ) ? sanitize_text_field( (string) $input['razorpay_key_id'] ) : '',
		'razorpay_key_secret' => isset( $input['razorpay_key_secret'] ) ? sanitize_text_field( (string) $input['razorpay_key_secret'] ) : '',
	);
}

/**
 * Render admin page.
 */
function cashwala_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Cashwala Theme – 2 Minute Setup', 'cashwala-theme' ); ?></h1>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'cashwala_settings_group' );
			do_settings_sections( 'cashwala-settings' );
			submit_button( esc_html__( 'Save Cashwala Settings', 'cashwala-theme' ) );
			?>
		</form>
	</div>
	<?php
}

/** Field callbacks. */
function cashwala_field_logo() {
	$settings = cashwala_get_settings();
	$logo_id  = absint( $settings['logo_id'] );
	$logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'thumbnail' ) : '';
	?>
	<input type="hidden" name="cashwala_settings[logo_id]" id="cashwala_logo_id" value="<?php echo esc_attr( (string) $logo_id ); ?>">
	<button type="button" class="button" id="cashwala_upload_logo"><?php esc_html_e( 'Upload / Select Logo', 'cashwala-theme' ); ?></button>
	<div style="margin-top:10px;"><img id="cashwala_logo_preview" src="<?php echo esc_url( $logo_url ); ?>" alt="" style="max-width:120px;height:auto;"></div>
	<script>
		document.addEventListener('DOMContentLoaded', function () {
			var button = document.getElementById('cashwala_upload_logo');
			var field = document.getElementById('cashwala_logo_id');
			var preview = document.getElementById('cashwala_logo_preview');
			if (!button || typeof wp === 'undefined' || !wp.media) return;
			button.addEventListener('click', function () {
				var frame = wp.media({ title: 'Select Logo', button: { text: 'Use Logo' }, multiple: false });
				frame.on('select', function () {
					var item = frame.state().get('selection').first().toJSON();
					field.value = item.id;
					preview.src = item.url;
				});
				frame.open();
			});
		});
	</script>
	<?php
}

function cashwala_field_whatsapp() {
	$settings = cashwala_get_settings();
	echo '<input type="text" class="regular-text" name="cashwala_settings[whatsapp_number]" value="' . esc_attr( (string) $settings['whatsapp_number'] ) . '" placeholder="+91XXXXXXXXXX">';
}

function cashwala_field_footer_links() {
	$settings = cashwala_get_settings();
	echo '<textarea name="cashwala_settings[footer_links]" class="large-text" rows="4" placeholder="Label|https://example.com">' . esc_textarea( (string) $settings['footer_links'] ) . '</textarea>';
	echo '<p class="description">One link per line in this format: Label|URL</p>';
}

function cashwala_field_skin() {
	$settings = cashwala_get_settings();
	$skins    = array( 'premium' => 'Premium', 'dark' => 'Dark', 'neon' => 'Neon', 'minimal' => 'Minimal', 'classic' => 'Classic' );
	echo '<select name="cashwala_settings[global_skin]">';
	foreach ( $skins as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $settings['global_skin'], $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
}

function cashwala_field_razorpay_id() {
	$settings = cashwala_get_settings();
	echo '<input type="text" class="regular-text" name="cashwala_settings[razorpay_key_id]" value="' . esc_attr( (string) $settings['razorpay_key_id'] ) . '">';
}

function cashwala_field_razorpay_secret() {
	$settings = cashwala_get_settings();
	echo '<input type="password" class="regular-text" name="cashwala_settings[razorpay_key_secret]" value="' . esc_attr( (string) $settings['razorpay_key_secret'] ) . '">';
}

/**
 * Helper: footer links as structured array.
 *
 * @return array<int,array<string,string>>
 */
function cashwala_get_footer_links_array() {
	$settings = cashwala_get_settings();
	$raw      = explode( "\n", (string) $settings['footer_links'] );
	$links    = array();

	foreach ( $raw as $row ) {
		$row = trim( $row );
		if ( '' === $row || false === strpos( $row, '|' ) ) {
			continue;
		}
		list($label, $url) = array_map( 'trim', explode( '|', $row, 2 ) );
		if ( '' === $label || '' === $url ) {
			continue;
		}
		$links[] = array(
			'label' => $label,
			'url'   => esc_url( $url ),
		);
	}

	return $links;
}
