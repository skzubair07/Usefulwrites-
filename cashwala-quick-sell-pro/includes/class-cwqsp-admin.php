<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWQSP_Admin {
	const OPTION_KEY = 'cwqsp_settings';

	/** @var CWQSP_Logger */
	private $logger;

	public function __construct( CWQSP_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'phpmailer_init', array( $this, 'configure_smtp' ) );
		add_action( 'admin_post_cwqsp_clear_logs', array( $this, 'clear_logs' ) );
	}

	public static function defaults() {
		return array(
			'razorpay_key_id'       => '',
			'razorpay_key_secret'   => '',
			'razorpay_webhook'      => '',
			'smtp_host'             => '',
			'smtp_port'             => '587',
			'smtp_user'             => '',
			'smtp_password'         => '',
			'smtp_secure'           => 'tls',
			'smtp_from_email'       => get_option( 'admin_email' ),
			'smtp_from_name'        => get_bloginfo( 'name' ),
			'download_expiry_hours' => '24',
			'redirect_mode'         => 'thankyou',
		);
	}

	public static function set_default_options() {
		update_option( self::OPTION_KEY, wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() ) );
	}

	public static function get_settings() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public function register_menu() {
		add_menu_page( 'Quick Sell Pro', 'Quick Sell Pro', 'manage_options', 'cwqsp-dashboard', array( $this, 'render_page' ), 'dashicons-cart', 56 );
		add_submenu_page( 'cwqsp-dashboard', 'Quick Sell Pro', 'Dashboard', 'manage_options', 'cwqsp-dashboard', array( $this, 'render_page' ) );
		add_submenu_page( 'cwqsp-dashboard', 'Products', 'Products', 'manage_options', 'edit.php?post_type=cw_product' );
	}

	public function register_settings() {
		register_setting( 'cwqsp_settings_group', self::OPTION_KEY, array( $this, 'sanitize' ) );
	}

	public function sanitize( $input ) {
		$input = (array) $input;
		return array(
			'razorpay_key_id'       => sanitize_text_field( $input['razorpay_key_id'] ?? '' ),
			'razorpay_key_secret'   => sanitize_text_field( $input['razorpay_key_secret'] ?? '' ),
			'razorpay_webhook'      => sanitize_text_field( $input['razorpay_webhook'] ?? '' ),
			'smtp_host'             => sanitize_text_field( $input['smtp_host'] ?? '' ),
			'smtp_port'             => sanitize_text_field( $input['smtp_port'] ?? '587' ),
			'smtp_user'             => sanitize_text_field( $input['smtp_user'] ?? '' ),
			'smtp_password'         => sanitize_text_field( $input['smtp_password'] ?? '' ),
			'smtp_secure'           => sanitize_text_field( $input['smtp_secure'] ?? 'tls' ),
			'smtp_from_email'       => sanitize_email( $input['smtp_from_email'] ?? get_option( 'admin_email' ) ),
			'smtp_from_name'        => sanitize_text_field( $input['smtp_from_name'] ?? get_bloginfo( 'name' ) ),
			'download_expiry_hours' => (string) max( 1, absint( $input['download_expiry_hours'] ?? 24 ) ),
			'redirect_mode'         => in_array( $input['redirect_mode'] ?? 'thankyou', array( 'thankyou', 'product_url' ), true ) ? $input['redirect_mode'] : 'thankyou',
		);
	}

	public function configure_smtp( $phpmailer ) {
		$settings = self::get_settings();
		if ( empty( $settings['smtp_host'] ) ) {
			return;
		}
		$phpmailer->isSMTP();
		$phpmailer->Host       = $settings['smtp_host'];
		$phpmailer->Port       = absint( $settings['smtp_port'] );
		$phpmailer->SMTPAuth   = true;
		$phpmailer->Username   = $settings['smtp_user'];
		$phpmailer->Password   = $settings['smtp_password'];
		$phpmailer->SMTPSecure = $settings['smtp_secure'];
		$phpmailer->From       = $settings['smtp_from_email'];
		$phpmailer->FromName   = $settings['smtp_from_name'];
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::get_settings();
		$tab      = sanitize_key( $_GET['tab'] ?? 'razorpay' );
		$tabs     = array(
			'razorpay'  => 'Razorpay',
			'email'     => 'Email',
			'settings'  => 'Settings',
			'analytics' => 'Analytics',
			'logs'      => 'Logs',
		);
		?>
		<div class="wrap">
			<h1>Quick Sell Pro</h1>
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $tabs as $key => $label ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=cwqsp-dashboard&tab=' . $key ) ); ?>" class="nav-tab <?php echo esc_attr( $tab === $key ? 'nav-tab-active' : '' ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</h2>

			<?php if ( in_array( $tab, array( 'razorpay', 'email', 'settings' ), true ) ) : ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'cwqsp_settings_group' ); ?>
				<table class="form-table">
					<?php if ( 'razorpay' === $tab ) : ?>
						<tr><th>Key ID</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[razorpay_key_id]" value="<?php echo esc_attr( $settings['razorpay_key_id'] ); ?>"></td></tr>
						<tr><th>Secret</th><td><input type="password" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[razorpay_key_secret]" value="<?php echo esc_attr( $settings['razorpay_key_secret'] ); ?>"></td></tr>
						<tr><th>Webhook Secret</th><td><input type="password" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[razorpay_webhook]" value="<?php echo esc_attr( $settings['razorpay_webhook'] ); ?>"></td></tr>
					<?php elseif ( 'email' === $tab ) : ?>
						<tr><th>SMTP Host</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_host]" value="<?php echo esc_attr( $settings['smtp_host'] ); ?>"></td></tr>
						<tr><th>Port</th><td><input type="number" class="small-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_port]" value="<?php echo esc_attr( $settings['smtp_port'] ); ?>"></td></tr>
						<tr><th>Username</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_user]" value="<?php echo esc_attr( $settings['smtp_user'] ); ?>"></td></tr>
						<tr><th>Password</th><td><input type="password" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_password]" value="<?php echo esc_attr( $settings['smtp_password'] ); ?>"></td></tr>
						<tr><th>Secure</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_secure]"><option value="tls" <?php selected( $settings['smtp_secure'], 'tls' ); ?>>TLS</option><option value="ssl" <?php selected( $settings['smtp_secure'], 'ssl' ); ?>>SSL</option><option value="" <?php selected( $settings['smtp_secure'], '' ); ?>>None</option></select></td></tr>
						<tr><th>From Email</th><td><input type="email" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_from_email]" value="<?php echo esc_attr( $settings['smtp_from_email'] ); ?>"></td></tr>
						<tr><th>From Name</th><td><input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_from_name]" value="<?php echo esc_attr( $settings['smtp_from_name'] ); ?>"></td></tr>
					<?php else : ?>
						<tr><th>Download Expiry (hours)</th><td><input type="number" class="small-text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[download_expiry_hours]" value="<?php echo esc_attr( $settings['download_expiry_hours'] ); ?>"></td></tr>
						<tr><th>Redirect After Payment</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[redirect_mode]"><option value="thankyou" <?php selected( $settings['redirect_mode'], 'thankyou' ); ?>>Thank You Page</option><option value="product_url" <?php selected( $settings['redirect_mode'], 'product_url' ); ?>>Product Redirect URL</option></select></td></tr>
					<?php endif; ?>
				</table>
				<?php submit_button( 'Save Settings' ); ?>
			</form>
			<?php elseif ( 'analytics' === $tab ) : ?>
				<?php $this->render_analytics(); ?>
			<?php else : ?>
				<?php $this->render_logs(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	private function render_analytics() {
		global $wpdb;
		$table = CWQSP_Payment::table_name();
		$sales = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='paid'" );
		$rev   = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount), 0) FROM {$table} WHERE status='paid'" );
		?>
		<div class="card" style="max-width:700px;padding:16px;">
			<h2>Analytics</h2>
			<p><strong>Total Sales:</strong> <?php echo esc_html( $sales ); ?></p>
			<p><strong>Total Revenue:</strong> ₹<?php echo esc_html( number_format_i18n( $rev, 2 ) ); ?></p>
		</div>
		<?php
	}

	private function render_logs() {
		$logs = $this->logger->read_latest( 250 );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'cwqsp_clear_logs' ); ?>
			<input type="hidden" name="action" value="cwqsp_clear_logs">
			<?php submit_button( 'Clear Logs', 'delete', 'submit', false ); ?>
		</form>
		<pre style="background:#111;color:#eee;padding:12px;max-width:100%;overflow:auto;"><?php echo esc_html( implode( PHP_EOL, $logs ) ); ?></pre>
		<?php
	}

	public function clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'cwqsp_clear_logs' );
		$this->logger->clear();
		wp_safe_redirect( admin_url( 'admin.php?page=cwqsp-dashboard&tab=logs' ) );
		exit;
	}
}
