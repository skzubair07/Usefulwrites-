<?php
/**
 * Smart search module with weighted scoring and fuzzy matching.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_Search_Module {

    public static function init() {
        add_action( 'wp_ajax_do_smart_search', array( __CLASS__, 'smart_search' ) );
        add_action( 'wp_ajax_nopriv_do_smart_search', array( __CLASS__, 'smart_search' ) );
    }

    public static function smart_search() {
        check_ajax_referer( 'do_ajax_nonce', 'nonce' );
        global $wpdb;

        $query = sanitize_text_field( wp_unslash( $_REQUEST['query'] ?? '' ) );
        if ( '' === $query ) {
            wp_send_json_success( array() );
        }

        if ( is_user_logged_in() && class_exists( 'DO_ForYou_Module' ) ) {
            DO_ForYou_Module::track_search( get_current_user_id(), $query );
        }

        $tokens = self::tokenize_query( $query );
        if ( empty( $tokens ) ) {
            wp_send_json_success( array() );
        }

        $rows     = $wpdb->get_results( 'SELECT * FROM ' . $wpdb->prefix . 'do_masail ORDER BY created_at DESC LIMIT 400', ARRAY_A );
        $weights  = self::get_weights();
        $scored   = array();

        foreach ( $rows as $row ) {
            $score = self::calculate_score( $tokens, $row, $weights );
            if ( $score > 0 ) {
                $row['rank_score'] = $score;
                $scored[]          = $row;
            }
        }

        usort(
            $scored,
            static function ( $a, $b ) {
                return $b['rank_score'] <=> $a['rank_score'];
            }
        );

        wp_send_json_success( array_slice( $scored, 0, 50 ) );
    }

    private static function tokenize_query( $query ) {
        $stopwords = array( 'kya', 'hai', 'ka', 'ke', 'ki', 'the', 'is', 'a', 'an', 'of', 'to' );
        $words     = preg_split( '/\s+/u', strtolower( $query ) );
        $clean     = array();

        foreach ( $words as $word ) {
            $word = preg_replace( '/[^\p{L}\p{N}]/u', '', $word );
            if ( '' === $word || in_array( $word, $stopwords, true ) ) {
                continue;
            }
            $clean[] = $word;
        }

        return array_values( array_unique( $clean ) );
    }

    private static function calculate_score( $tokens, $row, $weights ) {
        $score = 0;

        $question = strtolower( (string) ( $row['question'] ?? '' ) );
        $keywords = strtolower( (string) ( $row['keywords'] ?? '' ) );
        $category = strtolower( (string) ( $row['category'] ?? '' ) );
        $answer   = strtolower( (string) ( $row['answer'] ?? '' ) );

        foreach ( $tokens as $token ) {
            if ( preg_match( '/\b' . preg_quote( $token, '/' ) . '\b/u', $question ) ) {
                $score += $weights['question'];
            }

            if ( false !== strpos( $keywords, $token ) || false !== strpos( $category, $token ) ) {
                $score += $weights['keyword'];
            }

            if ( false !== strpos( $answer, $token ) ) {
                $score += $weights['answer'];
            }

            $score += self::fuzzy_bonus( $token, $question, $keywords, $category, $answer );
        }

        return $score;
    }

    private static function fuzzy_bonus( $token, $question, $keywords, $category, $answer ) {
        $bonus = 0;
        $pool  = array_merge(
            preg_split( '/\s+/u', $question ),
            preg_split( '/\s+/u', $keywords ),
            preg_split( '/\s+/u', $category ),
            preg_split( '/\s+/u', substr( $answer, 0, 300 ) )
        );

        foreach ( $pool as $candidate ) {
            $candidate = preg_replace( '/[^\p{L}\p{N}]/u', '', strtolower( (string) $candidate ) );
            if ( strlen( $candidate ) < 3 ) {
                continue;
            }

            similar_text( $token, $candidate, $percent );
            if ( $percent >= 80 ) {
                $bonus += 2;
                break;
            }
        }

        return $bonus;
    }

    private static function get_weights() {
        $settings = get_option( 'do_search_settings', array() );
        return array(
            'question' => absint( $settings['question_weight'] ?? 20 ),
            'keyword'  => absint( $settings['keyword_weight'] ?? 10 ),
            'answer'   => absint( $settings['answer_weight'] ?? 5 ),
        );
    }
}

DO_Search_Module::init();
