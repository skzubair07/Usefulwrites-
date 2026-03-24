<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Admin {
	const OPTION_KEY = 'cw_shop_settings';
	private $logger;

	public function __construct( CW_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_cw_clear_logs', array( $this, 'clear_logs' ) );
		add_action( 'admin_post_cw_export_leads', array( $this, 'export_leads' ) );
	}

	public static function set_default_options() {
		$defaults = self::defaults();
		$current  = get_option( self::OPTION_KEY, array() );
		update_option( self::OPTION_KEY, wp_parse_args( $current, $defaults ) );
	}

	public static function defaults() {
		return array(
			'razorpay_key_id'      => '',
			'razorpay_key_secret'  => '',
			'razorpay_webhook'     => '',
			'smtp_host'            => '',
			'smtp_port'            => '587',
			'smtp_user'            => '',
			'smtp_pass'            => '',
			'smtp_secure'          => 'tls',
			'affiliate_percent'    => '20',
			'cookie_days'          => '30',
			'min_withdraw'         => '500',
			'business_name'        => get_bloginfo( 'name' ),
			'from_email'           => get_option( 'admin_email' ),
		);
	}

	public static function get_settings() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public function register_menu() {
		add_menu_page( 'CashWala Shop', 'CashWala Shop', 'manage_options', 'cw-shop', array( $this, 'settings_page' ), 'dashicons-store', 59 );
		add_submenu_page( 'cw-shop', __( 'System Logs', 'cashwala-shop' ), __( 'System Logs', 'cashwala-shop' ), 'manage_options', 'cw-shop-logs', array( $this, 'logs_page' ) );
		add_submenu_page( 'cw-shop', __( 'Leads', 'cashwala-shop' ), __( 'Leads', 'cashwala-shop' ), 'manage_options', 'cw-shop-leads', array( $this, 'leads_page' ) );
	}

	public function register_settings() {
		register_setting( 'cw_shop_settings_group', self::OPTION_KEY, array( $this, 'sanitize_settings' ) );
	}

	public function sanitize_settings( $input ) {
		$input = (array) $input;
		return array(
			'razorpay_key_id'      => sanitize_text_field( $input['razorpay_key_id'] ?? '' ),
			'razorpay_key_secret'  => sanitize_text_field( $input['razorpay_key_secret'] ?? '' ),
			'razorpay_webhook'     => sanitize_text_field( $input['razorpay_webhook'] ?? '' ),
			'smtp_host'            => sanitize_text_field( $input['smtp_host'] ?? '' ),
			'smtp_port'            => sanitize_text_field( $input['smtp_port'] ?? '587' ),
			'smtp_user'            => sanitize_text_field( $input['smtp_user'] ?? '' ),
			'smtp_pass'            => sanitize_text_field( $input['smtp_pass'] ?? '' ),
			'smtp_secure'          => sanitize_text_field( $input['smtp_secure'] ?? 'tls' ),
			'affiliate_percent'    => sanitize_text_field( $input['affiliate_percent'] ?? '20' ),
			'cookie_days'          => sanitize_text_field( $input['cookie_days'] ?? '30' ),
			'min_withdraw'         => sanitize_text_field( $input['min_withdraw'] ?? '500' ),
			'business_name'        => sanitize_text_field( $input['business_name'] ?? get_bloginfo( 'name' ) ),
			'from_email'           => sanitize_email( $input['from_email'] ?? get_option( 'admin_email' ) ),
		);
	}

	public function settings_page() {
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'CashWala Shop Settings', 'cashwala-shop' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'cw_shop_settings_group' ); ?>
				<table class="form-table">
					<tr><th>Razorpay Key ID</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[razorpay_key_id]" value="<?php echo esc_attr( $settings['razorpay_key_id'] ); ?>" class="regular-text"></td></tr>
					<tr><th>Razorpay Key Secret</th><td><input type="password" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[razorpay_key_secret]" value="<?php echo esc_attr( $settings['razorpay_key_secret'] ); ?>" class="regular-text"></td></tr>
					<tr><th>Razorpay Webhook Secret</th><td><input type="password" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[razorpay_webhook]" value="<?php echo esc_attr( $settings['razorpay_webhook'] ); ?>" class="regular-text"></td></tr>
					<tr><th>SMTP Host</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_host]" value="<?php echo esc_attr( $settings['smtp_host'] ); ?>" class="regular-text"></td></tr>
					<tr><th>SMTP Port</th><td><input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_port]" value="<?php echo esc_attr( $settings['smtp_port'] ); ?>" class="small-text"></td></tr>
					<tr><th>SMTP User</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_user]" value="<?php echo esc_attr( $settings['smtp_user'] ); ?>" class="regular-text"></td></tr>
					<tr><th>SMTP Password</th><td><input type="password" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_pass]" value="<?php echo esc_attr( $settings['smtp_pass'] ); ?>" class="regular-text"></td></tr>
					<tr><th>SMTP Secure</th><td><select name="<?php echo esc_attr( self::OPTION_KEY ); ?>[smtp_secure]"><option value="tls" <?php selected( $settings['smtp_secure'], 'tls' ); ?>>TLS</option><option value="ssl" <?php selected( $settings['smtp_secure'], 'ssl' ); ?>>SSL</option><option value="" <?php selected( $settings['smtp_secure'], '' ); ?>>None</option></select></td></tr>
					<tr><th>Business Name</th><td><input type="text" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[business_name]" value="<?php echo esc_attr( $settings['business_name'] ); ?>" class="regular-text"></td></tr>
					<tr><th>From Email</th><td><input type="email" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[from_email]" value="<?php echo esc_attr( $settings['from_email'] ); ?>" class="regular-text"></td></tr>
					<tr><th>Affiliate Default %</th><td><input type="number" step="0.01" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[affiliate_percent]" value="<?php echo esc_attr( $settings['affiliate_percent'] ); ?>" class="small-text"></td></tr>
					<tr><th>Affiliate Cookie Days</th><td><input type="number" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[cookie_days]" value="<?php echo esc_attr( $settings['cookie_days'] ); ?>" class="small-text"></td></tr>
					<tr><th>Minimum Withdraw</th><td><input type="number" step="0.01" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[min_withdraw]" value="<?php echo esc_attr( $settings['min_withdraw'] ); ?>" class="small-text"></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function logs_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$logs = $this->logger->read_latest( 300 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'System Logs', 'cashwala-shop' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cw_clear_logs' ); ?>
				<input type="hidden" name="action" value="cw_clear_logs">
				<?php submit_button( __( 'Clear Logs', 'cashwala-shop' ), 'delete', 'submit', false ); ?>
			</form>
			<pre style="background:#0a0a0a;color:#e8e8e8;padding:15px;max-height:500px;overflow:auto;"><?php echo esc_html( implode( PHP_EOL, $logs ) ); ?></pre>
		</div>
		<?php
	}

	public function leads_page() {
		$rows = CW_Leads::get_leads( 200 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Captured Leads', 'cashwala-shop' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'cw_export_leads' ); ?>
				<input type="hidden" name="action" value="cw_export_leads">
				<?php submit_button( __( 'Export CSV', 'cashwala-shop' ), 'primary', 'submit', false ); ?>
			</form>
			<table class="widefat striped"><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Product</th><th>Created</th></tr></thead><tbody>
			<?php foreach ( $rows as $row ) : ?>
				<tr><td><?php echo esc_html( $row->id ); ?></td><td><?php echo esc_html( $row->name ); ?></td><td><?php echo esc_html( $row->email ); ?></td><td><?php echo esc_html( $row->phone ); ?></td><td><?php echo esc_html( get_the_title( $row->product_id ) ); ?></td><td><?php echo esc_html( $row->created_at ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
		<?php
	}

	public function clear_logs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed', 'cashwala-shop' ) );
		}
		check_admin_referer( 'cw_clear_logs' );
		$this->logger->clear();
		wp_safe_redirect( admin_url( 'admin.php?page=cw-shop-logs' ) );
		exit;
	}

	public function export_leads() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed', 'cashwala-shop' ) );
		}
		check_admin_referer( 'cw_export_leads' );
		$rows = CW_Leads::get_leads( 0 );
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=cw-leads-' . gmdate( 'Ymd-His' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'ID', 'Name', 'Email', 'Phone', 'Product ID', 'Created At' ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, array( $row->id, $row->name, $row->email, $row->phone, $row->product_id, $row->created_at ) );
		}
		fclose( $output );
		exit;
	}
}
