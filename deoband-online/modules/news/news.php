<?php
/**
 * News module using RSS feeds from admin settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_News_Module {

    public static function get_news_items( $limit = 10 ) {
        include_once ABSPATH . WPINC . '/feed.php';
        $api_settings = get_option( 'do_api_settings', array() );
        $feed_url     = esc_url_raw( $api_settings['news_rss_url'] ?? '' );

        if ( ! $feed_url ) {
            return array();
        }

        $feed = fetch_feed( $feed_url );
        if ( is_wp_error( $feed ) ) {
            return array();
        }

        $items = $feed->get_items( 0, absint( $limit ) );
        $data  = array();

        foreach ( $items as $item ) {
            $data[] = array(
                'title' => sanitize_text_field( $item->get_title() ),
                'link'  => esc_url_raw( $item->get_link() ),
                'date'  => sanitize_text_field( $item->get_date( 'Y-m-d H:i:s' ) ),
            );
        }

        return $data;
    }
}

function do_render_news_settings_page() {
    echo '<div class="wrap"><h1>News System</h1><p>Set RSS feed URL from API Settings page.</p></div>';
}
