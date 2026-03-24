<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLMP_API {
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    public static function register_routes() {
        register_rest_route(
            'cw-license/v1',
            '/validate',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'validate_license' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'license_key' => array(
                        'required'          => true,
                        'sanitize_callback' => array( 'CWLMP_Security', 'sanitize_key_input' ),
                    ),
                    'domain'      => array(
                        'required'          => true,
                        'sanitize_callback' => array( 'CWLMP_Security', 'sanitize_domain' ),
                    ),
                ),
            )
        );
    }

    public static function validate_license( WP_REST_Request $request ) {
        $result = CWLMP_Validation::validate(
            $request->get_param( 'license_key' ),
            $request->get_param( 'domain' )
        );

        return rest_ensure_response( $result );
    }
}
