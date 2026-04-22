<?php
/**
 * Auto import module using WP-Cron and source-specific DOM parsing.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Cron_Import_Module {

    const HOOK = 'do_cron_import_run';

    public static function init() {
        add_action( self::HOOK, array( __CLASS__, 'run_import' ) );
        add_filter( 'cron_schedules', array( __CLASS__, 'add_schedule' ) );

        if ( ! wp_next_scheduled( self::HOOK ) ) {
            wp_schedule_event( time() + 120, 'do_every_30_minutes', self::HOOK );
        }
    }

    public static function add_schedule( $schedules ) {
        $schedules['do_every_30_minutes'] = array(
            'interval' => 1800,
            'display'  => 'Every 30 Minutes (Deoband Online)',
        );
        return $schedules;
    }

    public static function run_import() {
        $settings = get_option( 'do_import_settings', array() );
        $sources  = (array) ( $settings['sources'] ?? array() );
        $limit    = max( 1, absint( $settings['import_limit'] ?? 10 ) );

        foreach ( $sources as $source ) {
            $url = esc_url_raw( $source['url'] ?? '' );
            if ( ! $url ) {
                continue;
            }

            $response = wp_remote_get( $url, array( 'timeout' => 20 ) );
            if ( is_wp_error( $response ) ) {
                DO_Logger::log( 'IMPORT', 'Failed to fetch source: ' . $url . ' | ' . $response->get_error_message() );
                continue;
            }

            $html  = wp_remote_retrieve_body( $response );
            $items = self::parse_source( $url, $html );
            $items = array_slice( $items, 0, $limit );

            foreach ( $items as $item ) {
                self::store_imported_item( $item['question'], $item['answer'], $item['source_url'] );
            }
        }
    }

    private static function parse_source( $url, $html ) {
        if ( false !== stripos( $url, 'deoband' ) ) {
            return self::parse_deoband( $url, $html );
        }

        if ( false !== stripos( $url, 'binori' ) ) {
            return self::parse_binori( $url, $html );
        }

        return self::parse_generic( $url, $html );
    }

    private static function parse_deoband( $url, $html ) {
        return self::parse_by_xpath( $url, $html, '//article|//div[contains(@class,"post")]' );
    }

    private static function parse_binori( $url, $html ) {
        return self::parse_by_xpath( $url, $html, '//div[contains(@class,"entry")] | //article' );
    }

    private static function parse_generic( $url, $html ) {
        return self::parse_by_xpath( $url, $html, '//article|//div[contains(@class,"entry")]|//div[contains(@class,"post")]' );
    }

    private static function parse_by_xpath( $url, $html, $query ) {
        $items = array();

        if ( empty( $html ) ) {
            return $items;
        }

        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html );
        $xpath = new DOMXPath( $dom );
        $nodes = $xpath->query( $query );

        if ( ! $nodes ) {
            return $items;
        }

        foreach ( $nodes as $node ) {
            $heading = $xpath->query( './/h1|.//h2|.//h3', $node );
            $paras   = $xpath->query( './/p', $node );

            $question = $heading->length ? trim( $heading->item( 0 )->textContent ) : '';
            $answer   = '';

            foreach ( $paras as $p ) {
                $answer .= ' ' . trim( $p->textContent );
            }

            $answer = trim( preg_replace( '/\s+/', ' ', $answer ) );
            if ( strlen( $question ) < 8 || strlen( $answer ) < 15 ) {
                continue;
            }

            $items[] = array(
                'question'   => sanitize_text_field( $question ),
                'answer'     => wp_kses_post( $answer ),
                'source_url' => esc_url_raw( $url ),
            );
        }

        return $items;
    }

    private static function store_imported_item( $question, $answer, $source_url ) {
        global $wpdb;
        $hash = md5( $question );

        $exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $wpdb->prefix . 'do_masail WHERE question_hash = %s', $hash ) );
        if ( $exists ) {
            DO_Logger::log( 'IMPORT', 'Duplicate skipped: ' . $hash );
            return;
        }

        $inserted = $wpdb->insert(
            $wpdb->prefix . 'do_masail',
            array(
                'question'      => $question,
                'question_hash' => $hash,
                'answer'        => $answer,
                'source_url'    => $source_url,
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            )
        );

        if ( $inserted ) {
            DO_Logger::log( 'IMPORT', 'Import success: ' . $hash );
        } else {
            DO_Logger::log( 'IMPORT', 'Import failed: ' . $hash );
        }
    }
}

function do_render_import_settings_page() {
    if ( isset( $_POST['do_save_import_settings'] ) && check_admin_referer( 'do_import_settings_nonce' ) ) {
        $sources = array();
        foreach ( (array) ( $_POST['source_url'] ?? array() ) as $url ) {
            $safe = esc_url_raw( wp_unslash( $url ) );
            if ( $safe ) {
                $sources[] = array( 'url' => $safe );
            }
        }

        update_option(
            'do_import_settings',
            array(
                'sources'      => $sources,
                'import_limit' => max( 1, absint( $_POST['import_limit'] ?? 10 ) ),
            )
        );
        echo '<div class="updated"><p>Import settings saved.</p></div>';
    }

    $settings = get_option( 'do_import_settings', array( 'sources' => array(), 'import_limit' => 10 ) );
    include DO_PLUGIN_DIR . 'templates/admin-import-settings.php';
}

DO_Cron_Import_Module::init();
