<?php
/**
 * REST API module exposing selected data.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class DO_API_Module {

    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            'deoband-online/v1',
            '/masail',
            array(
                'methods'             => 'GET',
                'callback'            => array( __CLASS__, 'get_masail' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    public static function get_masail() {
        return rest_ensure_response( DO_Masail_Module::get_masail( array( 'limit' => 20 ) ) );
    }
}

DO_API_Module::init();
