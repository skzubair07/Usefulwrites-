<?php
/**
 * Database class.
 *
 * @package CashWala_Testimonial_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWTS_DB {
	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'cw_testimonials';
	}

	/**
	 * Run plugin activation routines.
	 *
	 * @return void
	 */
	public static function activate() {
		global $wpdb;
		$table_name      = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(190) NOT NULL,
			photo VARCHAR(255) NOT NULL DEFAULT '',
			text TEXT NOT NULL,
			rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
			company VARCHAR(190) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id)
		) {$charset_collate};";

		dbDelta( $sql );

		if ( empty( get_option( 'cwts_settings' ) ) ) {
			$default_settings = array(
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
			add_option( 'cwts_settings', $default_settings );
		}

		CWTS_Logger::log( 'Database table created or updated.' );
	}

	/**
	 * Get testimonials.
	 *
	 * @param array $args Query args.
	 * @return array
	 */
	public static function get_testimonials( $args = array() ) {
		global $wpdb;
		$table_name = self::table_name();
		$defaults   = array(
			'ids'   => array(),
			'limit' => 10,
		);
		$args       = wp_parse_args( $args, $defaults );
		$limit      = absint( $args['limit'] );
		$limit      = $limit > 0 ? $limit : 10;

		$where  = '1=1';
		$params = array();

		if ( ! empty( $args['ids'] ) && is_array( $args['ids'] ) ) {
			$ids         = array_filter( array_map( 'absint', $args['ids'] ) );
			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
			if ( ! empty( $ids ) ) {
				$where    .= " AND id IN ({$placeholders})";
				$params    = array_merge( $params, $ids );
			}
		}

		$params[] = $limit;
		$query    = $wpdb->prepare( "SELECT * FROM {$table_name} WHERE {$where} ORDER BY id DESC LIMIT %d", $params );

		$results = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Get one testimonial.
	 *
	 * @param int $id ID.
	 * @return array|null
	 */
	public static function get_testimonial( $id ) {
		global $wpdb;
		$table_name = self::table_name();
		$id         = absint( $id );

		if ( ! $id ) {
			return null;
		}

		$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $result ?: null;
	}

	/**
	 * Insert testimonial.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert_testimonial( $data ) {
		global $wpdb;
		$table_name = self::table_name();

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name'       => sanitize_text_field( $data['name'] ),
				'photo'      => esc_url_raw( $data['photo'] ),
				'text'       => sanitize_textarea_field( $data['text'] ),
				'rating'     => min( 5, max( 1, absint( $data['rating'] ) ) ),
				'company'    => sanitize_text_field( $data['company'] ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%d', '%s', '%s' )
		);

		if ( false === $inserted ) {
			CWTS_Logger::log( 'Insert failed: ' . $wpdb->last_error, 'error' );
			return false;
		}

		CWTS_Logger::log( 'Testimonial inserted. ID: ' . $wpdb->insert_id );
		return (int) $wpdb->insert_id;
	}

	/**
	 * Update testimonial.
	 *
	 * @param int   $id ID.
	 * @param array $data Data.
	 * @return bool
	 */
	public static function update_testimonial( $id, $data ) {
		global $wpdb;
		$table_name = self::table_name();
		$id         = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$updated = $wpdb->update(
			$table_name,
			array(
				'name'    => sanitize_text_field( $data['name'] ),
				'photo'   => esc_url_raw( $data['photo'] ),
				'text'    => sanitize_textarea_field( $data['text'] ),
				'rating'  => min( 5, max( 1, absint( $data['rating'] ) ) ),
				'company' => sanitize_text_field( $data['company'] ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			CWTS_Logger::log( 'Update failed: ' . $wpdb->last_error, 'error' );
			return false;
		}

		CWTS_Logger::log( 'Testimonial updated. ID: ' . $id );
		return true;
	}

	/**
	 * Delete testimonial.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public static function delete_testimonial( $id ) {
		global $wpdb;
		$table_name = self::table_name();
		$id         = absint( $id );

		if ( ! $id ) {
			return false;
		}

		$deleted = $wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );
		if ( false === $deleted ) {
			CWTS_Logger::log( 'Delete failed: ' . $wpdb->last_error, 'error' );
			return false;
		}

		CWTS_Logger::log( 'Testimonial deleted. ID: ' . $id );
		return true;
	}
}
