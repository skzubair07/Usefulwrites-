<?php
/**
 * Frontend class.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWTS_Frontend {
	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'cw_testimonials', array( $this, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register assets.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style( 'cwts-style', CWTS_PLUGIN_URL . 'assets/css/style.css', array(), CWTS_VERSION );
		wp_register_script( 'cwts-script', CWTS_PLUGIN_URL . 'assets/js/script.js', array(), CWTS_VERSION, true );
	}

	/**
	 * Render shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$settings = $this->get_settings();
		$atts     = shortcode_atts(
			array(
				'layout'   => $settings['layout'],
				'limit'    => 10,
				'ids'      => '',
				'template' => '1',
			),
			$atts,
			'cw_testimonials'
		);

		$ids = ! empty( $atts['ids'] ) ? array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) ) : array();
		$items = CWTS_DB::get_testimonials(
			array(
				'limit' => absint( $atts['limit'] ),
				'ids'   => $ids,
			)
		);

		if ( empty( $items ) ) {
			return '<div class="cwts-empty">' . esc_html__( 'No testimonials found.', 'cashwala-testimonial-slider' ) . '</div>';
		}

		wp_enqueue_style( 'cwts-style' );
		wp_enqueue_script( 'cwts-script' );

		$container_id = 'cwts-' . wp_generate_uuid4();
		wp_localize_script(
			'cwts-script',
			'cwtsData',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cwts_nonce' ),
			)
		);

		$config = array(
			'layout'        => sanitize_key( $atts['layout'] ),
			'autoplaySpeed' => (int) $settings['autoplay_speed'],
			'loop'          => (int) $settings['loop'],
			'navigation'    => (int) $settings['navigation'],
			'itemsPerRow'   => (int) $settings['items_per_row'],
			'borderRadius'  => (int) $settings['border_radius'],
			'cardStyle'     => sanitize_key( $settings['card_style'] ),
			'shadow'        => (int) $settings['shadow'],
		);

		$styles = sprintf(
			'--cwts-bg:%1$s;--cwts-text:%2$s;--cwts-star:%3$s;--cwts-radius:%4$spx;--cwts-cols:%5$s;',
			esc_attr( $settings['bg_color'] ),
			esc_attr( $settings['text_color'] ),
			esc_attr( $settings['star_color'] ),
			esc_attr( $settings['border_radius'] ),
			esc_attr( $settings['items_per_row'] )
		);

		ob_start();
		?>
		<div id="<?php echo esc_attr( $container_id ); ?>" class="cwts-wrapper" data-cwts-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>" style="<?php echo esc_attr( $styles ); ?>">
			<?php
			$template = '2' === (string) $atts['template'] ? 'slider-template-2.php' : 'slider-template-1.php';
			include CWTS_PLUGIN_DIR . 'templates/' . $template;
			?>
		</div>
		<?php
		return ob_get_clean();
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
