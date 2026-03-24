<?php
/**
 * Admin class.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWTS_Admin {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_post_cwts_save_testimonial', array( $this, 'handle_save_testimonial' ) );
		add_action( 'admin_post_cwts_delete_testimonial', array( $this, 'handle_delete_testimonial' ) );
		add_action( 'admin_post_cwts_save_settings', array( $this, 'handle_save_settings' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'CashWala Testimonials', 'cashwala-testimonial-slider' ),
			__( 'CashWala Testimonials', 'cashwala-testimonial-slider' ),
			'manage_options',
			'cwts-dashboard',
			array( $this, 'render_manage_page' ),
			'dashicons-format-quote',
			25
		);

		add_submenu_page(
			'cwts-dashboard',
			__( 'Manage Testimonials', 'cashwala-testimonial-slider' ),
			__( 'Manage Testimonials', 'cashwala-testimonial-slider' ),
			'manage_options',
			'cwts-dashboard',
			array( $this, 'render_manage_page' )
		);

		add_submenu_page(
			'cwts-dashboard',
			__( 'Slider Settings', 'cashwala-testimonial-slider' ),
			__( 'Slider Settings', 'cashwala-testimonial-slider' ),
			'manage_options',
			'cwts-slider-settings',
			array( $this, 'render_slider_settings' )
		);

		add_submenu_page(
			'cwts-dashboard',
			__( 'Display Settings', 'cashwala-testimonial-slider' ),
			__( 'Display Settings', 'cashwala-testimonial-slider' ),
			'manage_options',
			'cwts-display-settings',
			array( $this, 'render_display_settings' )
		);

		add_submenu_page(
			'cwts-dashboard',
			__( 'Design Settings', 'cashwala-testimonial-slider' ),
			__( 'Design Settings', 'cashwala-testimonial-slider' ),
			'manage_options',
			'cwts-design-settings',
			array( $this, 'render_design_settings' )
		);

		add_submenu_page(
			'cwts-dashboard',
			__( 'Error Logs', 'cashwala-testimonial-slider' ),
			__( 'Error Logs', 'cashwala-testimonial-slider' ),
			'manage_options',
			'cwts-error-logs',
			array( $this, 'render_logs_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook.
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'cwts' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
	}

	/**
	 * Render manage page.
	 *
	 * @return void
	 */
	public function render_manage_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$edit_id      = isset( $_GET['edit_id'] ) ? absint( $_GET['edit_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$testimonial  = $edit_id ? CWTS_DB::get_testimonial( $edit_id ) : null;
		$testimonials = CWTS_DB::get_testimonials( array( 'limit' => 200 ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Manage Testimonials', 'cashwala-testimonial-slider' ); ?></h1>
			<p><strong><?php esc_html_e( 'Shortcode:', 'cashwala-testimonial-slider' ); ?></strong> <code>[cw_testimonials]</code></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="background:#fff;padding:20px;border-radius:12px;max-width:900px;">
				<?php wp_nonce_field( 'cwts_save_testimonial_action', 'cwts_save_testimonial_nonce' ); ?>
				<input type="hidden" name="action" value="cwts_save_testimonial">
				<input type="hidden" name="id" value="<?php echo esc_attr( $testimonial['id'] ?? 0 ); ?>">
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="cwts-name"><?php esc_html_e( 'Name', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input required type="text" id="cwts-name" name="name" class="regular-text" value="<?php echo esc_attr( $testimonial['name'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cwts-photo"><?php esc_html_e( 'Photo URL', 'cashwala-testimonial-slider' ); ?></label></th>
						<td>
							<input type="url" id="cwts-photo" name="photo" class="regular-text" value="<?php echo esc_url( $testimonial['photo'] ?? '' ); ?>">
						</td>
					</tr>
					<tr>
						<th><label for="cwts-company"><?php esc_html_e( 'Company', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="text" id="cwts-company" name="company" class="regular-text" value="<?php echo esc_attr( $testimonial['company'] ?? '' ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cwts-rating"><?php esc_html_e( 'Rating', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="number" id="cwts-rating" name="rating" min="1" max="5" value="<?php echo esc_attr( $testimonial['rating'] ?? 5 ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cwts-text"><?php esc_html_e( 'Review Text', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><textarea required id="cwts-text" name="text" rows="5" class="large-text"><?php echo esc_textarea( $testimonial['text'] ?? '' ); ?></textarea></td>
					</tr>
				</table>
				<?php submit_button( $edit_id ? __( 'Update Testimonial', 'cashwala-testimonial-slider' ) : __( 'Add Testimonial', 'cashwala-testimonial-slider' ) ); ?>
			</form>

			<h2 style="margin-top:32px;"><?php esc_html_e( 'Existing Testimonials', 'cashwala-testimonial-slider' ); ?></h2>
			<table class="widefat striped">
				<thead>
				<tr>
					<th><?php esc_html_e( 'ID', 'cashwala-testimonial-slider' ); ?></th>
					<th><?php esc_html_e( 'Name', 'cashwala-testimonial-slider' ); ?></th>
					<th><?php esc_html_e( 'Company', 'cashwala-testimonial-slider' ); ?></th>
					<th><?php esc_html_e( 'Rating', 'cashwala-testimonial-slider' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'cashwala-testimonial-slider' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( empty( $testimonials ) ) : ?>
					<tr><td colspan="5"><?php esc_html_e( 'No testimonials yet.', 'cashwala-testimonial-slider' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $testimonials as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['id'] ); ?></td>
							<td><?php echo esc_html( $item['name'] ); ?></td>
							<td><?php echo esc_html( $item['company'] ); ?></td>
							<td><?php echo esc_html( str_repeat( '★', (int) $item['rating'] ) ); ?></td>
							<td>
								<a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=cwts-dashboard&edit_id=' . absint( $item['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'cashwala-testimonial-slider' ); ?></a>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-left:8px;">
									<?php wp_nonce_field( 'cwts_delete_testimonial_action_' . absint( $item['id'] ), 'cwts_delete_testimonial_nonce' ); ?>
									<input type="hidden" name="action" value="cwts_delete_testimonial">
									<input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ); ?>">
									<button type="submit" class="button button-link-delete" onclick="return confirm('<?php echo esc_js( __( 'Delete this testimonial?', 'cashwala-testimonial-slider' ) ); ?>');"><?php esc_html_e( 'Delete', 'cashwala-testimonial-slider' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render slider settings.
	 *
	 * @return void
	 */
	public function render_slider_settings() {
		$this->render_settings_page( 'slider' );
	}

	/**
	 * Render display settings.
	 *
	 * @return void
	 */
	public function render_display_settings() {
		$this->render_settings_page( 'display' );
	}

	/**
	 * Render design settings.
	 *
	 * @return void
	 */
	public function render_design_settings() {
		$this->render_settings_page( 'design' );
	}

	/**
	 * Generic settings page renderer.
	 *
	 * @param string $section Section.
	 * @return void
	 */
	private function render_settings_page( $section ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = $this->get_settings();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( ucwords( str_replace( '-', ' ', $section ) ) . ' ' . __( 'Settings', 'cashwala-testimonial-slider' ) ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="background:#fff;padding:20px;border-radius:12px;max-width:900px;">
				<?php wp_nonce_field( 'cwts_save_settings_action', 'cwts_save_settings_nonce' ); ?>
				<input type="hidden" name="action" value="cwts_save_settings">
				<input type="hidden" name="section" value="<?php echo esc_attr( $section ); ?>">
				<table class="form-table" role="presentation">
					<?php if ( 'slider' === $section ) : ?>
					<tr>
						<th><label for="cwts-autoplay-speed"><?php esc_html_e( 'Autoplay Speed (ms)', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="number" id="cwts-autoplay-speed" name="autoplay_speed" min="1000" step="100" value="<?php echo esc_attr( $settings['autoplay_speed'] ); ?>"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Loop', 'cashwala-testimonial-slider' ); ?></th>
						<td><label><input type="checkbox" name="loop" value="1" <?php checked( 1, (int) $settings['loop'] ); ?>> <?php esc_html_e( 'Enable loop', 'cashwala-testimonial-slider' ); ?></label></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Navigation', 'cashwala-testimonial-slider' ); ?></th>
						<td><label><input type="checkbox" name="navigation" value="1" <?php checked( 1, (int) $settings['navigation'] ); ?>> <?php esc_html_e( 'Show arrows', 'cashwala-testimonial-slider' ); ?></label></td>
					</tr>
					<?php endif; ?>

					<?php if ( 'display' === $section ) : ?>
					<tr>
						<th><label for="cwts-layout"><?php esc_html_e( 'Layout Type', 'cashwala-testimonial-slider' ); ?></label></th>
						<td>
							<select id="cwts-layout" name="layout">
								<option value="slider" <?php selected( 'slider', $settings['layout'] ); ?>><?php esc_html_e( 'Slider', 'cashwala-testimonial-slider' ); ?></option>
								<option value="grid" <?php selected( 'grid', $settings['layout'] ); ?>><?php esc_html_e( 'Grid', 'cashwala-testimonial-slider' ); ?></option>
								<option value="single" <?php selected( 'single', $settings['layout'] ); ?>><?php esc_html_e( 'Single', 'cashwala-testimonial-slider' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="cwts-items-per-row"><?php esc_html_e( 'Items Per Row', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="number" id="cwts-items-per-row" name="items_per_row" min="1" max="4" value="<?php echo esc_attr( $settings['items_per_row'] ); ?>"></td>
					</tr>
					<?php endif; ?>

					<?php if ( 'design' === $section ) : ?>
					<tr>
						<th><label for="cwts-bg-color"><?php esc_html_e( 'Background Color', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="text" id="cwts-bg-color" class="cwts-color-field" name="bg_color" value="<?php echo esc_attr( $settings['bg_color'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cwts-text-color"><?php esc_html_e( 'Text Color', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="text" id="cwts-text-color" class="cwts-color-field" name="text_color" value="<?php echo esc_attr( $settings['text_color'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cwts-star-color"><?php esc_html_e( 'Star Color', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="text" id="cwts-star-color" class="cwts-color-field" name="star_color" value="<?php echo esc_attr( $settings['star_color'] ); ?>"></td>
					</tr>
					<tr>
						<th><label for="cwts-card-style"><?php esc_html_e( 'Card Style', 'cashwala-testimonial-slider' ); ?></label></th>
						<td>
							<select id="cwts-card-style" name="card_style">
								<option value="premium" <?php selected( 'premium', $settings['card_style'] ); ?>><?php esc_html_e( 'Premium', 'cashwala-testimonial-slider' ); ?></option>
								<option value="minimal" <?php selected( 'minimal', $settings['card_style'] ); ?>><?php esc_html_e( 'Minimal', 'cashwala-testimonial-slider' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="cwts-border-radius"><?php esc_html_e( 'Border Radius (px)', 'cashwala-testimonial-slider' ); ?></label></th>
						<td><input type="number" id="cwts-border-radius" name="border_radius" min="0" max="40" value="<?php echo esc_attr( $settings['border_radius'] ); ?>"></td>
					</tr>
					<tr>
						<th><?php esc_html_e( 'Shadow', 'cashwala-testimonial-slider' ); ?></th>
						<td><label><input type="checkbox" name="shadow" value="1" <?php checked( 1, (int) $settings['shadow'] ); ?>> <?php esc_html_e( 'Enable drop shadow', 'cashwala-testimonial-slider' ); ?></label></td>
					</tr>
					<?php endif; ?>
				</table>
				<?php submit_button( __( 'Save Settings', 'cashwala-testimonial-slider' ) ); ?>
			</form>
		</div>
		<script>
		jQuery(function($){
			$('.cwts-color-field').wpColorPicker();
		});
		</script>
		<?php
	}

	/**
	 * Render logs page.
	 *
	 * @return void
	 */
	public function render_logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$logs = CWTS_Logger::read_logs();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Error Logs', 'cashwala-testimonial-slider' ); ?></h1>
			<pre style="background:#0f172a;color:#e2e8f0;padding:16px;border-radius:12px;max-height:500px;overflow:auto;line-height:1.5;"><?php echo esc_html( $logs ); ?></pre>
		</div>
		<?php
	}

	/**
	 * Save testimonial handler.
	 *
	 * @return void
	 */
	public function handle_save_testimonial() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'cashwala-testimonial-slider' ) );
		}

		check_admin_referer( 'cwts_save_testimonial_action', 'cwts_save_testimonial_nonce' );

		$id   = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		$data = array(
			'name'    => isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '',
			'photo'   => isset( $_POST['photo'] ) ? esc_url_raw( wp_unslash( $_POST['photo'] ) ) : '',
			'text'    => isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '',
			'rating'  => isset( $_POST['rating'] ) ? absint( wp_unslash( $_POST['rating'] ) ) : 5,
			'company' => isset( $_POST['company'] ) ? sanitize_text_field( wp_unslash( $_POST['company'] ) ) : '',
		);

		if ( empty( $data['name'] ) || empty( $data['text'] ) ) {
			CWTS_Logger::log( 'Failed save testimonial: required fields missing.', 'error' );
			wp_safe_redirect( admin_url( 'admin.php?page=cwts-dashboard&message=missing_fields' ) );
			exit;
		}

		if ( $id ) {
			CWTS_DB::update_testimonial( $id, $data );
		} else {
			CWTS_DB::insert_testimonial( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=cwts-dashboard&message=saved' ) );
		exit;
	}

	/**
	 * Delete testimonial handler.
	 *
	 * @return void
	 */
	public function handle_delete_testimonial() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'cashwala-testimonial-slider' ) );
		}

		$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
		check_admin_referer( 'cwts_delete_testimonial_action_' . $id, 'cwts_delete_testimonial_nonce' );

		if ( $id ) {
			CWTS_DB::delete_testimonial( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=cwts-dashboard&message=deleted' ) );
		exit;
	}

	/**
	 * Save settings handler.
	 *
	 * @return void
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized access.', 'cashwala-testimonial-slider' ) );
		}

		check_admin_referer( 'cwts_save_settings_action', 'cwts_save_settings_nonce' );
		$settings = $this->get_settings();
		$section  = isset( $_POST['section'] ) ? sanitize_key( wp_unslash( $_POST['section'] ) ) : '';

		if ( 'slider' === $section ) {
			$settings['autoplay_speed'] = isset( $_POST['autoplay_speed'] ) ? max( 1000, absint( wp_unslash( $_POST['autoplay_speed'] ) ) ) : 4000;
			$settings['loop']           = isset( $_POST['loop'] ) ? 1 : 0;
			$settings['navigation']     = isset( $_POST['navigation'] ) ? 1 : 0;
		}

		if ( 'display' === $section ) {
			$layout_options             = array( 'grid', 'slider', 'single' );
			$layout                     = isset( $_POST['layout'] ) ? sanitize_key( wp_unslash( $_POST['layout'] ) ) : 'slider';
			$settings['layout']         = in_array( $layout, $layout_options, true ) ? $layout : 'slider';
			$settings['items_per_row']  = isset( $_POST['items_per_row'] ) ? min( 4, max( 1, absint( wp_unslash( $_POST['items_per_row'] ) ) ) ) : 3;
		}

		if ( 'design' === $section ) {
			$card_style_options        = array( 'premium', 'minimal' );
			$settings['bg_color']      = isset( $_POST['bg_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['bg_color'] ) ) : '#ffffff';
			$settings['text_color']    = isset( $_POST['text_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['text_color'] ) ) : '#111827';
			$settings['star_color']    = isset( $_POST['star_color'] ) ? sanitize_hex_color( wp_unslash( $_POST['star_color'] ) ) : '#f59e0b';
			$card_style                = isset( $_POST['card_style'] ) ? sanitize_key( wp_unslash( $_POST['card_style'] ) ) : 'premium';
			$settings['card_style']    = in_array( $card_style, $card_style_options, true ) ? $card_style : 'premium';
			$settings['border_radius'] = isset( $_POST['border_radius'] ) ? min( 40, max( 0, absint( wp_unslash( $_POST['border_radius'] ) ) ) ) : 16;
			$settings['shadow']        = isset( $_POST['shadow'] ) ? 1 : 0;
		}

		update_option( 'cwts_settings', $settings );
		CWTS_Logger::log( 'Settings updated for section: ' . $section );

		wp_safe_redirect( admin_url( 'admin.php?page=cwts-' . $section . '-settings&message=settings_saved' ) );
		exit;
	}

	/**
	 * Get settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		$defaults = array(
			'autoplay_speed' => 4000,
			'loop'           => 1,
			'navigation'     => 1,
			'layout'         => 'slider',
			'items_per_row'  => 3,
			'bg_color'       => '#ffffff',
			'text_color'     => '#111827',
			'star_color'     => '#f59e0b',
			'card_style'     => 'premium',
			'border_radius'  => 16,
			'shadow'         => 1,
		);

		$saved = get_option( 'cwts_settings', array() );
		return wp_parse_args( $saved, $defaults );
	}
}
