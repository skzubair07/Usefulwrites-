<?php
/**
 * Settings page and registration.
 *
 * @package ContentFlowAuto
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register settings.
 *
 * @return void
 */
function cfa_register_settings() {
	register_setting(
		'cfa_settings_group',
		'cfa_settings',
		array(
			'sanitize_callback' => 'cfa_sanitize_settings',
		)
	);

	add_settings_section( 'cfa_api_section', __( 'API Configuration', 'content-flow-auto' ), '__return_false', 'content-flow-auto-settings' );
	add_settings_section( 'cfa_generation_section', __( 'Generation Defaults', 'content-flow-auto' ), '__return_false', 'content-flow-auto-settings' );

	$fields = array(
		'gemini_api_key'      => 'Gemini API Key',
		'groq_api_key'        => 'Groq API Key',
		'cohere_api_key'      => 'Cohere API Key',
		'huggingface_api_key' => 'HuggingFace Token',
		'openai_api_key'      => 'OpenAI API Key',
	);

	foreach ( $fields as $field => $label ) {
		add_settings_field( $field, $label, 'cfa_render_text_field', 'content-flow-auto-settings', 'cfa_api_section', array( 'field' => $field ) );
	}

	add_settings_field( 'primary_api', __( 'Primary API', 'content-flow-auto' ), 'cfa_render_priority_field', 'content-flow-auto-settings', 'cfa_generation_section', array( 'index' => 0 ) );
	add_settings_field( 'secondary_api', __( 'Secondary API', 'content-flow-auto' ), 'cfa_render_priority_field', 'content-flow-auto-settings', 'cfa_generation_section', array( 'index' => 1 ) );
	add_settings_field( 'third_api', __( 'Third API', 'content-flow-auto' ), 'cfa_render_priority_field', 'content-flow-auto-settings', 'cfa_generation_section', array( 'index' => 2 ) );
	add_settings_field( 'fourth_api', __( 'Fourth API', 'content-flow-auto' ), 'cfa_render_priority_field', 'content-flow-auto-settings', 'cfa_generation_section', array( 'index' => 3 ) );
	add_settings_field( 'fifth_api', __( 'Fifth API', 'content-flow-auto' ), 'cfa_render_priority_field', 'content-flow-auto-settings', 'cfa_generation_section', array( 'index' => 4 ) );

	add_settings_field( 'article_length', __( 'Article Length (words)', 'content-flow-auto' ), 'cfa_render_number_field', 'content-flow-auto-settings', 'cfa_generation_section', array( 'field' => 'article_length', 'min' => 300, 'max' => 3000 ) );
	add_settings_field( 'default_category', __( 'Default Category', 'content-flow-auto' ), 'cfa_render_category_field', 'content-flow-auto-settings', 'cfa_generation_section' );
	add_settings_field( 'post_status', __( 'Post Status', 'content-flow-auto' ), 'cfa_render_status_field', 'content-flow-auto-settings', 'cfa_generation_section' );
}
add_action( 'admin_init', 'cfa_register_settings' );

function cfa_get_settings() {
	$defaults = array(
		'gemini_api_key'      => '',
		'groq_api_key'        => '',
		'cohere_api_key'      => '',
		'huggingface_api_key' => '',
		'openai_api_key'      => '',
		'api_priority'        => array( 'gemini', 'groq', 'cohere', 'huggingface', 'openai' ),
		'article_length'      => 1000,
		'default_category'    => 0,
		'post_status'         => 'draft',
	);
	return wp_parse_args( get_option( 'cfa_settings', array() ), $defaults );
}

function cfa_sanitize_settings( $input ) {
	$clean = cfa_get_settings();

	$keys = array( 'gemini_api_key', 'groq_api_key', 'cohere_api_key', 'huggingface_api_key', 'openai_api_key' );
	foreach ( $keys as $key ) {
		$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
	}

	$priority = array();
	for ( $i = 0; $i < 5; $i++ ) {
		$field = 'priority_' . $i;
		if ( isset( $input[ $field ] ) ) {
			$priority[] = sanitize_key( $input[ $field ] );
		}
	}
	$priority             = array_values( array_unique( array_filter( $priority ) ) );
	$clean['api_priority'] = $priority;

	$clean['article_length']   = isset( $input['article_length'] ) ? max( 300, absint( $input['article_length'] ) ) : 1000;
	$clean['default_category'] = isset( $input['default_category'] ) ? absint( $input['default_category'] ) : 0;
	$clean['post_status']      = isset( $input['post_status'] ) ? sanitize_key( $input['post_status'] ) : 'draft';

	return $clean;
}

function cfa_render_text_field( $args ) {
	$settings = cfa_get_settings();
	$field    = $args['field'];
	?>
	<input type="password" class="regular-text" name="cfa_settings[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $settings[ $field ] ?? '' ); ?>" autocomplete="off" />
	<?php
}

function cfa_render_priority_field( $args ) {
	$settings  = cfa_get_settings();
	$providers = cfa_get_provider_labels();
	$index     = (int) $args['index'];
	$current   = $settings['api_priority'][ $index ] ?? '';
	?>
	<select name="cfa_settings[priority_<?php echo esc_attr( (string) $index ); ?>]">
		<?php foreach ( $providers as $value => $label ) : ?>
			<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

function cfa_render_number_field( $args ) {
	$settings = cfa_get_settings();
	$field    = $args['field'];
	?>
	<input type="number" name="cfa_settings[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( (string) $settings[ $field ] ); ?>" min="<?php echo esc_attr( (string) $args['min'] ); ?>" max="<?php echo esc_attr( (string) $args['max'] ); ?>" />
	<?php
}

function cfa_render_category_field() {
	$settings = cfa_get_settings();
	wp_dropdown_categories(
		array(
			'name'             => 'cfa_settings[default_category]',
			'hide_empty'       => false,
			'show_option_none' => __( 'None', 'content-flow-auto' ),
			'selected'         => (int) $settings['default_category'],
		)
	);
}

function cfa_render_status_field() {
	$settings = cfa_get_settings();
	$statuses = array(
		'draft'   => __( 'Draft', 'content-flow-auto' ),
		'pending' => __( 'Pending Review', 'content-flow-auto' ),
		'publish' => __( 'Publish', 'content-flow-auto' ),
		'private' => __( 'Private', 'content-flow-auto' ),
	);
	?>
	<select name="cfa_settings[post_status]">
		<?php foreach ( $statuses as $key => $label ) : ?>
			<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['post_status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
		<?php endforeach; ?>
	</select>
	<?php
}

function cfa_get_provider_labels() {
	return array(
		'gemini'      => 'Gemini',
		'groq'        => 'Groq',
		'cohere'      => 'Cohere',
		'huggingface' => 'HuggingFace',
		'openai'      => 'OpenAI',
	);
}

function cfa_render_settings_page() {
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'Content Flow Settings', 'content-flow-auto' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'cfa_settings_group' );
			do_settings_sections( 'content-flow-auto-settings' );
			submit_button();
			?>
		</form>
	</div>
	<?php
}
