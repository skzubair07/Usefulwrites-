<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class CWLMP_Validation {
    public static function validate( $license_key, $domain ) {
        $license_key = CWLMP_Security::sanitize_key_input( $license_key );
        $domain      = CWLMP_Security::sanitize_domain( $domain );
        $key_hash     = CWLMP_Security::hash_license_key( $license_key );

        if ( empty( $license_key ) || empty( $domain ) ) {
            self::log( null, $key_hash, $domain, 'invalid', 'Missing key or domain.' );
            return self::response( false, 'missing_required_fields' );
        }

        $license = CWLMP_Licenses::get_license_by_key( $license_key );

        if ( empty( $license ) ) {
            self::log( null, $key_hash, $domain, 'invalid', 'License key not found.' );
            return self::response( false, 'license_not_found' );
        }

        if ( self::is_expired( $license ) ) {
            CWLMP_Licenses::update_license( $license['id'], array( 'status' => 'expired' ) );
            self::log( $license['id'], $key_hash, $domain, 'expired', 'License expired.' );
            return self::response( false, 'expired', $license );
        }

        if ( 'revoked' === $license['status'] ) {
            self::log( $license['id'], $key_hash, $domain, 'revoked', 'License revoked.' );
            return self::response( false, 'revoked', $license );
        }

        if ( empty( $license['bound_domain'] ) ) {
            CWLMP_Licenses::update_license( $license['id'], array( 'bound_domain' => $domain ) );
            $license['bound_domain'] = $domain;
        }

        if ( $license['bound_domain'] !== $domain ) {
            self::log( $license['id'], $key_hash, $domain, 'invalid', 'Domain mismatch.' );
            return self::response( false, 'domain_mismatch', $license );
        }

        self::log( $license['id'], $key_hash, $domain, 'valid', 'Validation successful.' );

        return self::response( true, 'valid', $license );
    }

    private static function is_expired( $license ) {
        if ( empty( $license['expiry_date'] ) ) {
            return false;
        }

        return strtotime( $license['expiry_date'] ) < time();
    }

    private static function response( $valid, $code, $license = array() ) {
        return array(
            'valid'          => (bool) $valid,
            'code'           => sanitize_key( $code ),
            'status'         => ! empty( $license['status'] ) ? $license['status'] : 'invalid',
            'expiry_date'    => ! empty( $license['expiry_date'] ) ? gmdate( 'c', strtotime( $license['expiry_date'] ) ) : null,
            'bound_domain'   => ! empty( $license['bound_domain'] ) ? $license['bound_domain'] : null,
            'assigned_email' => ! empty( $license['assigned_email'] ) ? $license['assigned_email'] : null,
        );
    }

    private static function log( $license_id, $key_hash, $domain, $result, $message ) {
        $ip_address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

        CWLMP_Licenses::log_attempt(
            array(
                'license_id' => $license_id,
                'key_hash'   => $key_hash,
                'domain'     => $domain,
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
                'result'     => $result,
                'message'    => $message,
            )
        );
    }
}
