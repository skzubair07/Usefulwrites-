<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CW_Affiliate {
	private $logger;

	public function __construct( CW_Logger $logger ) {
		$this->logger = $logger;
		add_action( 'init', array( $this, 'track_referral' ) );
		add_shortcode( 'cw_affiliate_dashboard', array( $this, 'dashboard_shortcode' ) );
		add_action( 'wp_ajax_cw_withdraw_request', array( $this, 'withdraw_request' ) );
		add_action( 'admin_post_cw_affiliate_status', array( $this, 'update_withdraw_status' ) );
		add_action( 'admin_menu', array( $this, 'register_withdraw_admin' ) );
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$comm    = $wpdb->prefix . 'cw_affiliate_commissions';
		$wd      = $wpdb->prefix . 'cw_affiliate_withdrawals';
		dbDelta( "CREATE TABLE {$comm} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			order_id BIGINT UNSIGNED NOT NULL,
			amount DECIMAL(12,2) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id)
		) {$charset};" );
		dbDelta( "CREATE TABLE {$wd} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			amount DECIMAL(12,2) NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'requested',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY user_id (user_id)
		) {$charset};" );
	}

	public function track_referral() {
		if ( empty( $_GET['cw_ref'] ) ) {
			return;
		}
		$user_id = absint( wp_unslash( $_GET['cw_ref'] ) );
		if ( $user_id <= 0 || ! get_user_by( 'id', $user_id ) ) {
			return;
		}
		$settings = CW_Admin::get_settings();
		$days     = max( 1, absint( $settings['cookie_days'] ) );
		setcookie( 'cw_ref', (string) $user_id, time() + ( DAY_IN_SECONDS * $days ), COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
		$_COOKIE['cw_ref'] = (string) $user_id;
	}

	public static function get_referrer_id() {
		return isset( $_COOKIE['cw_ref'] ) ? absint( wp_unslash( $_COOKIE['cw_ref'] ) ) : 0;
	}

	public static function get_commission_percent( $product_id ) {
		$override = get_post_meta( $product_id, '_cw_affiliate_override', true );
		if ( '' !== $override && is_numeric( $override ) ) {
			return floatval( $override );
		}
		$settings = CW_Admin::get_settings();
		return floatval( $settings['affiliate_percent'] );
	}

	public function add_commission( $user_id, $order_id, $amount ) {
		global $wpdb;
		$table = $wpdb->prefix . 'cw_affiliate_commissions';
		$wpdb->insert(
			$table,
			array(
				'user_id'    => absint( $user_id ),
				'order_id'   => absint( $order_id ),
				'amount'     => floatval( $amount ),
				'status'     => 'pending',
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%f', '%s', '%s' )
		);
	}

	public static function get_balance( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'cw_affiliate_commissions';
		return floatval( $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE user_id=%d AND status='approved'", absint( $user_id ) ) ) );
	}

	public static function get_requested_amount( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'cw_affiliate_withdrawals';
		return floatval( $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM {$table} WHERE user_id=%d AND status='requested'", absint( $user_id ) ) ) );
	}

	public function dashboard_shortcode() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please login to view affiliate dashboard.', 'cashwala-shop' ) . '</p>';
		}
		$user_id  = get_current_user_id();
		$ref_link = add_query_arg( 'cw_ref', $user_id, home_url() );
		$balance  = self::get_balance( $user_id );
		$pending  = self::get_requested_amount( $user_id );
		ob_start();
		?>
		<div class="cw-affiliate-box">
			<p><strong><?php esc_html_e( 'Referral Link:', 'cashwala-shop' ); ?></strong> <code><?php echo esc_url( $ref_link ); ?></code></p>
			<p><strong><?php esc_html_e( 'Approved Balance:', 'cashwala-shop' ); ?></strong> ₹<?php echo esc_html( number_format_i18n( $balance, 2 ) ); ?></p>
			<p><strong><?php esc_html_e( 'Pending Withdraw Requests:', 'cashwala-shop' ); ?></strong> ₹<?php echo esc_html( number_format_i18n( $pending, 2 ) ); ?></p>
			<form class="cw-withdraw-form">
				<input type="number" step="0.01" name="amount" required min="1" placeholder="Amount">
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'cw_front_nonce' ) ); ?>">
				<button type="submit"><?php esc_html_e( 'Request Withdraw', 'cashwala-shop' ); ?></button>
			</form>
			<div class="cw-withdraw-response"></div>
		</div>
		<?php
		return ob_get_clean();
	}

	public function withdraw_request() {
		check_ajax_referer( 'cw_front_nonce', 'nonce' );
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Login required.', 'cashwala-shop' ) ) );
		}
		$user_id  = get_current_user_id();
		$amount   = isset( $_POST['amount'] ) ? floatval( wp_unslash( $_POST['amount'] ) ) : 0;
		$settings = CW_Admin::get_settings();
		$min      = floatval( $settings['min_withdraw'] );
		$balance  = self::get_balance( $user_id ) - self::get_requested_amount( $user_id );

		if ( $amount < $min || $amount > $balance ) {
			wp_send_json_error( array( 'message' => __( 'Invalid withdraw amount.', 'cashwala-shop' ) ) );
		}
		global $wpdb;
		$wpdb->insert(
			$wpdb->prefix . 'cw_affiliate_withdrawals',
			array(
				'user_id'    => $user_id,
				'amount'     => $amount,
				'status'     => 'requested',
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%f', '%s', '%s' )
		);
		wp_send_json_success( array( 'message' => __( 'Withdraw request submitted.', 'cashwala-shop' ) ) );
	}

	public function register_withdraw_admin() {
		add_submenu_page( 'cw-shop', __( 'Affiliate Withdrawals', 'cashwala-shop' ), __( 'Affiliate Withdrawals', 'cashwala-shop' ), 'manage_options', 'cw-aff-withdraw', array( $this, 'withdraw_page' ) );
	}

	public function withdraw_page() {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}cw_affiliate_withdrawals ORDER BY id DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		echo '<div class="wrap"><h1>Affiliate Withdrawals</h1><table class="widefat"><thead><tr><th>ID</th><th>User</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->id ) . '</td><td>' . esc_html( $row->user_id ) . '</td><td>₹' . esc_html( $row->amount ) . '</td><td>' . esc_html( $row->status ) . '</td><td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'cw_affiliate_status' );
			echo '<input type="hidden" name="action" value="cw_affiliate_status"><input type="hidden" name="withdraw_id" value="' . esc_attr( $row->id ) . '">';
			echo '<select name="status"><option value="approved">Approve</option><option value="rejected">Reject</option><option value="paid">Mark Paid</option></select> <button class="button">Update</button></form>';
			echo '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public function update_withdraw_status() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Not allowed', 'cashwala-shop' ) );
		}
		check_admin_referer( 'cw_affiliate_status' );
		$id     = isset( $_POST['withdraw_id'] ) ? absint( wp_unslash( $_POST['withdraw_id'] ) ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'requested';
		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'cw_affiliate_withdrawals', array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=cw-aff-withdraw' ) );
		exit;
	}
}
