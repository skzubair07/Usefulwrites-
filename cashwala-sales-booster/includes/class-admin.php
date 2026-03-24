<?php
/**
 * Admin settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CashWala_SB_Admin {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register menu.
	 *
	 * @return void
	 */
	public static function register_menu() {
		add_menu_page(
			__( 'Sales Booster', 'cashwala-sales-booster' ),
			__( 'Sales Booster', 'cashwala-sales-booster' ),
			'manage_options',
			'cw-sales-booster',
			array( __CLASS__, 'render_page' ),
			'dashicons-chart-line',
			57
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'cw_sb_settings_group',
			'cw_sb_settings',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 * @return void
	 */
	public static function enqueue_assets( $hook ) {
		if ( 'toplevel_page_cw-sales-booster' !== $hook ) {
			return;
		}

		wp_enqueue_style( 'cw-sb-style', CW_SB_URL . 'assets/css/style.css', array(), CW_SB_VERSION );
		wp_enqueue_script( 'cw-sb-script', CW_SB_URL . 'assets/js/script.js', array(), CW_SB_VERSION, true );
	}

	/**
	 * Sanitize settings fields.
	 *
	 * @param array $input Input values.
	 * @return array
	 */
	public static function sanitize_settings( $input ) {
		$defaults = CashWala_Sales_Booster::default_settings();
		$output   = array();

		$checkbox_fields = array(
			'notifications_enabled',
			'timer_enabled',
			'counter_enabled',
			'low_stock_enabled',
			'low_stock_autodec',
			'trust_badges_enabled',
			'cta_enabled',
			'trigger_exit_intent',
		);

		foreach ( $defaults as $key => $value ) {
			if ( in_array( $key, $checkbox_fields, true ) ) {
				$output[ $key ] = isset( $input[ $key ] ) ? 1 : 0;
				continue;
			}

			switch ( $key ) {
				case 'notifications_interval':
				case 'notifications_duration':
				case 'timer_duration':
				case 'counter_min':
				case 'counter_max':
				case 'counter_refresh':
				case 'low_stock_static':
				case 'low_stock_min':
				case 'low_stock_max':
				case 'trigger_delay':
				case 'trigger_scroll':
					$output[ $key ] = isset( $input[ $key ] ) ? max( 1, absint( $input[ $key ] ) ) : $value;
					break;
				case 'timer_type':
					$output[ $key ] = ( isset( $input[ $key ] ) && in_array( $input[ $key ], array( 'evergreen', 'fixed' ), true ) ) ? $input[ $key ] : $value;
					break;
				case 'low_stock_mode':
					$output[ $key ] = ( isset( $input[ $key ] ) && in_array( $input[ $key ], array( 'static', 'dynamic' ), true ) ) ? $input[ $key ] : $value;
					break;
				case 'cta_action':
					$output[ $key ] = ( isset( $input[ $key ] ) && in_array( $input[ $key ], array( 'redirect', 'scroll' ), true ) ) ? $input[ $key ] : $value;
					break;
				case 'cta_link':
					$output[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( $input[ $key ] ) : $value;
					break;
				case 'fixed_end_datetime':
					$output[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( wp_unslash( $input[ $key ] ) ) : $value;
					break;
				case 'primary_color':
				case 'accent_color':
				case 'text_color':
				case 'background_color':
					$output[ $key ] = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : $value;
					if ( empty( $output[ $key ] ) ) {
						$output[ $key ] = $value;
					}
					break;
				default:
					$output[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( wp_unslash( $input[ $key ] ) ) : $value;
					break;
			}
		}

		if ( $output['counter_min'] > $output['counter_max'] ) {
			$output['counter_max'] = $output['counter_min'];
		}

		if ( $output['low_stock_min'] > $output['low_stock_max'] ) {
			$output['low_stock_max'] = $output['low_stock_min'];
		}

		return $output;
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = wp_parse_args( get_option( 'cw_sb_settings', array() ), CashWala_Sales_Booster::default_settings() );
		$analytics = wp_parse_args( get_option( 'cw_sb_analytics', array() ), array( 'impressions' => 0, 'clicks' => 0 ) );
		$conv      = $analytics['impressions'] > 0 ? round( ( $analytics['clicks'] / $analytics['impressions'] ) * 100, 2 ) : 0;
		$tab       = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'notifications';
		$tabs      = array(
			'notifications' => __( 'Notifications', 'cashwala-sales-booster' ),
			'timer'         => __( 'Timer', 'cashwala-sales-booster' ),
			'counter'       => __( 'Counter', 'cashwala-sales-booster' ),
			'cta'           => __( 'CTA', 'cashwala-sales-booster' ),
			'design'        => __( 'Design', 'cashwala-sales-booster' ),
			'analytics'     => __( 'Analytics', 'cashwala-sales-booster' ),
		);
		?>
		<div class="wrap cw-sb-admin-wrap">
			<h1><?php esc_html_e( 'CashWala Sales Booster', 'cashwala-sales-booster' ); ?></h1>
			<p><?php esc_html_e( 'Premium urgency and trust widgets for higher digital product conversions.', 'cashwala-sales-booster' ); ?></p>

			<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Sales Booster Tabs', 'cashwala-sales-booster' ); ?>">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<?php $class = $tab === $key ? 'nav-tab nav-tab-active' : 'nav-tab'; ?>
					<a class="<?php echo esc_attr( $class ); ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=cw-sales-booster&tab=' . $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</nav>

			<form method="post" action="options.php" class="cw-sb-admin-card">
				<?php settings_fields( 'cw_sb_settings_group' ); ?>
				<?php self::render_tab_content( $tab, $settings, $analytics, $conv ); ?>
				<?php if ( 'analytics' !== $tab ) : ?>
					<?php submit_button( __( 'Save Settings', 'cashwala-sales-booster' ) ); ?>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render tab section fields.
	 *
	 * @param string $tab Active tab.
	 * @param array  $settings Plugin settings.
	 * @param array  $analytics Analytics metrics.
	 * @param float  $conv Conversion rate.
	 * @return void
	 */
	private static function render_tab_content( $tab, $settings, $analytics, $conv ) {
		switch ( $tab ) {
			case 'timer':
				?>
				<h2><?php esc_html_e( 'Countdown Timer', 'cashwala-sales-booster' ); ?></h2>
				<label><input type="checkbox" name="cw_sb_settings[timer_enabled]" value="1" <?php checked( $settings['timer_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable Timer', 'cashwala-sales-booster' ); ?></label>
				<p>
					<label><?php esc_html_e( 'Timer Type', 'cashwala-sales-booster' ); ?></label>
					<select name="cw_sb_settings[timer_type]">
						<option value="evergreen" <?php selected( $settings['timer_type'], 'evergreen' ); ?>><?php esc_html_e( 'Evergreen', 'cashwala-sales-booster' ); ?></option>
						<option value="fixed" <?php selected( $settings['timer_type'], 'fixed' ); ?>><?php esc_html_e( 'Fixed', 'cashwala-sales-booster' ); ?></option>
					</select>
				</p>
				<p><label><?php esc_html_e( 'Evergreen Duration (seconds)', 'cashwala-sales-booster' ); ?></label><input type="number" min="60" name="cw_sb_settings[timer_duration]" value="<?php echo esc_attr( $settings['timer_duration'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Fixed End DateTime (UTC)', 'cashwala-sales-booster' ); ?></label><input type="datetime-local" name="cw_sb_settings[fixed_end_datetime]" value="<?php echo esc_attr( $settings['fixed_end_datetime'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Time Delay Trigger (seconds)', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" name="cw_sb_settings[trigger_delay]" value="<?php echo esc_attr( $settings['trigger_delay'] ); ?>" /></p>
				<?php
				break;
			case 'counter':
				?>
				<h2><?php esc_html_e( 'Visitor Counter & Stock', 'cashwala-sales-booster' ); ?></h2>
				<label><input type="checkbox" name="cw_sb_settings[counter_enabled]" value="1" <?php checked( $settings['counter_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable Visitor Counter', 'cashwala-sales-booster' ); ?></label>
				<p><label><?php esc_html_e( 'Counter Min', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" name="cw_sb_settings[counter_min]" value="<?php echo esc_attr( $settings['counter_min'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Counter Max', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" name="cw_sb_settings[counter_max]" value="<?php echo esc_attr( $settings['counter_max'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Refresh Interval (seconds)', 'cashwala-sales-booster' ); ?></label><input type="number" min="3" name="cw_sb_settings[counter_refresh]" value="<?php echo esc_attr( $settings['counter_refresh'] ); ?>" /></p>
				<hr />
				<label><input type="checkbox" name="cw_sb_settings[low_stock_enabled]" value="1" <?php checked( $settings['low_stock_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable Low Stock Alert', 'cashwala-sales-booster' ); ?></label>
				<p>
					<label><?php esc_html_e( 'Stock Mode', 'cashwala-sales-booster' ); ?></label>
					<select name="cw_sb_settings[low_stock_mode]">
						<option value="dynamic" <?php selected( $settings['low_stock_mode'], 'dynamic' ); ?>><?php esc_html_e( 'Dynamic', 'cashwala-sales-booster' ); ?></option>
						<option value="static" <?php selected( $settings['low_stock_mode'], 'static' ); ?>><?php esc_html_e( 'Static', 'cashwala-sales-booster' ); ?></option>
					</select>
				</p>
				<p><label><?php esc_html_e( 'Static Stock Value', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" name="cw_sb_settings[low_stock_static]" value="<?php echo esc_attr( $settings['low_stock_static'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Dynamic Min', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" name="cw_sb_settings[low_stock_min]" value="<?php echo esc_attr( $settings['low_stock_min'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Dynamic Max', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" name="cw_sb_settings[low_stock_max]" value="<?php echo esc_attr( $settings['low_stock_max'] ); ?>" /></p>
				<label><input type="checkbox" name="cw_sb_settings[low_stock_autodec]" value="1" <?php checked( $settings['low_stock_autodec'], 1 ); ?> /> <?php esc_html_e( 'Auto Decrease Stock in Session', 'cashwala-sales-booster' ); ?></label>
				<p><label><?php esc_html_e( 'Scroll Trigger (%)', 'cashwala-sales-booster' ); ?></label><input type="number" min="1" max="95" name="cw_sb_settings[trigger_scroll]" value="<?php echo esc_attr( $settings['trigger_scroll'] ); ?>" /></p>
				<?php
				break;
			case 'cta':
				?>
				<h2><?php esc_html_e( 'Sticky CTA', 'cashwala-sales-booster' ); ?></h2>
				<label><input type="checkbox" name="cw_sb_settings[cta_enabled]" value="1" <?php checked( $settings['cta_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable Sticky CTA Bar', 'cashwala-sales-booster' ); ?></label>
				<p><label><?php esc_html_e( 'CTA Text', 'cashwala-sales-booster' ); ?></label><textarea name="cw_sb_settings[cta_text]" rows="2"><?php echo esc_textarea( $settings['cta_text'] ); ?></textarea></p>
				<p><label><?php esc_html_e( 'CTA Button Text', 'cashwala-sales-booster' ); ?></label><input type="text" name="cw_sb_settings[cta_button_text]" value="<?php echo esc_attr( $settings['cta_button_text'] ); ?>" /></p>
				<p>
					<label><?php esc_html_e( 'Button Action', 'cashwala-sales-booster' ); ?></label>
					<select name="cw_sb_settings[cta_action]">
						<option value="redirect" <?php selected( $settings['cta_action'], 'redirect' ); ?>><?php esc_html_e( 'Redirect', 'cashwala-sales-booster' ); ?></option>
						<option value="scroll" <?php selected( $settings['cta_action'], 'scroll' ); ?>><?php esc_html_e( 'Scroll To Element', 'cashwala-sales-booster' ); ?></option>
					</select>
				</p>
				<p><label><?php esc_html_e( 'Redirect Link', 'cashwala-sales-booster' ); ?></label><input type="url" name="cw_sb_settings[cta_link]" value="<?php echo esc_attr( $settings['cta_link'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Scroll Target Selector', 'cashwala-sales-booster' ); ?></label><input type="text" name="cw_sb_settings[cta_scroll_target]" value="<?php echo esc_attr( $settings['cta_scroll_target'] ); ?>" /></p>
				<label><input type="checkbox" name="cw_sb_settings[trigger_exit_intent]" value="1" <?php checked( $settings['trigger_exit_intent'], 1 ); ?> /> <?php esc_html_e( 'Enable Exit Intent Trigger', 'cashwala-sales-booster' ); ?></label>
				<?php
				break;
			case 'design':
				?>
				<h2><?php esc_html_e( 'Design & Trust Badges', 'cashwala-sales-booster' ); ?></h2>
				<label><input type="checkbox" name="cw_sb_settings[trust_badges_enabled]" value="1" <?php checked( $settings['trust_badges_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable Trust Badges', 'cashwala-sales-booster' ); ?></label>
				<p><label><?php esc_html_e( 'Trust Badge Lines', 'cashwala-sales-booster' ); ?></label><textarea name="cw_sb_settings[trust_badges]" rows="4"><?php echo esc_textarea( $settings['trust_badges'] ); ?></textarea></p>
				<p><label><?php esc_html_e( 'Primary Color', 'cashwala-sales-booster' ); ?></label><input type="color" name="cw_sb_settings[primary_color]" value="<?php echo esc_attr( $settings['primary_color'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Accent Color', 'cashwala-sales-booster' ); ?></label><input type="color" name="cw_sb_settings[accent_color]" value="<?php echo esc_attr( $settings['accent_color'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Text Color', 'cashwala-sales-booster' ); ?></label><input type="color" name="cw_sb_settings[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Background Color', 'cashwala-sales-booster' ); ?></label><input type="color" name="cw_sb_settings[background_color]" value="<?php echo esc_attr( $settings['background_color'] ); ?>" /></p>
				<?php
				break;
			case 'analytics':
				?>
				<h2><?php esc_html_e( 'Performance Analytics', 'cashwala-sales-booster' ); ?></h2>
				<div class="cw-sb-kpi-grid">
					<div class="cw-sb-kpi"><span><?php esc_html_e( 'Impressions', 'cashwala-sales-booster' ); ?></span><strong><?php echo esc_html( number_format_i18n( $analytics['impressions'] ) ); ?></strong></div>
					<div class="cw-sb-kpi"><span><?php esc_html_e( 'Clicks', 'cashwala-sales-booster' ); ?></span><strong><?php echo esc_html( number_format_i18n( $analytics['clicks'] ) ); ?></strong></div>
					<div class="cw-sb-kpi"><span><?php esc_html_e( 'Conversion %', 'cashwala-sales-booster' ); ?></span><strong><?php echo esc_html( $conv ); ?>%</strong></div>
				</div>
				<?php
				break;
			case 'notifications':
			default:
				?>
				<h2><?php esc_html_e( 'Live Sales Notifications', 'cashwala-sales-booster' ); ?></h2>
				<label><input type="checkbox" name="cw_sb_settings[notifications_enabled]" value="1" <?php checked( $settings['notifications_enabled'], 1 ); ?> /> <?php esc_html_e( 'Enable Floating Sales Notifications', 'cashwala-sales-booster' ); ?></label>
				<p><label><?php esc_html_e( 'Names (one per line)', 'cashwala-sales-booster' ); ?></label><textarea rows="6" name="cw_sb_settings[names]"><?php echo esc_textarea( $settings['names'] ); ?></textarea></p>
				<p><label><?php esc_html_e( 'Cities (one per line)', 'cashwala-sales-booster' ); ?></label><textarea rows="6" name="cw_sb_settings[cities]"><?php echo esc_textarea( $settings['cities'] ); ?></textarea></p>
				<p><label><?php esc_html_e( 'Products (one per line)', 'cashwala-sales-booster' ); ?></label><textarea rows="6" name="cw_sb_settings[products]"><?php echo esc_textarea( $settings['products'] ); ?></textarea></p>
				<p><label><?php esc_html_e( 'Message Variations (one per line)', 'cashwala-sales-booster' ); ?></label><textarea rows="4" name="cw_sb_settings[message_variations]"><?php echo esc_textarea( $settings['message_variations'] ); ?></textarea></p>
				<p><label><?php esc_html_e( 'Loop Interval (seconds)', 'cashwala-sales-booster' ); ?></label><input type="number" min="3" name="cw_sb_settings[notifications_interval]" value="<?php echo esc_attr( $settings['notifications_interval'] ); ?>" /></p>
				<p><label><?php esc_html_e( 'Visible Duration (seconds)', 'cashwala-sales-booster' ); ?></label><input type="number" min="2" name="cw_sb_settings[notifications_duration]" value="<?php echo esc_attr( $settings['notifications_duration'] ); ?>" /></p>
				<?php
				break;
		}
	}
}
