<?php
/**
 * BFC_API — communicates with the bookingfish.ca REST API.
 *
 * All HTTP calls are server-to-server via wp_remote_*, so there is no CORS
 * concern and no browser token exposure.
 *
 * Debug output goes to WordPress debug.log when WP_DEBUG and WP_DEBUG_LOG
 * are true in wp-config.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BFC_API {

    private $api_base;

    public function __construct() {
        $this->api_base = BFC_API_BASE;
    }

    // =========================================================================
    // Public methods
    // =========================================================================

    /**
     * Authenticate with bookingfish.ca and return the response body.
     *
     * @param string $email
     * @param string $password
     * @return array|WP_Error
     */
    public function login( $email, $password ) {
        bfc_log( "BFC_API::login — attempting login for: {$email}" );

        $response = wp_remote_post(
            $this->api_base . '/client/login',
            array(
                'body'    => wp_json_encode( array( 'email' => $email, 'password' => $password ) ),
                'headers' => array( 'Content-Type' => 'application/json' ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            bfc_log( 'BFC_API::login — wp_remote_post error: ' . $response->get_error_message(), 'ERROR' );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        bfc_log( "BFC_API::login — HTTP {$code} received." );

        return $this->parse_response( $response );
    }

    /**
     * Fetch the vendor's embed codes from bookingfish.ca.
     *
     * @param string $token Bearer token.
     * @return array|WP_Error
     */
    public function get_embed_codes( $token ) {
        bfc_log( 'BFC_API::get_embed_codes — fetching embed codes.' );

        $response = wp_remote_get(
            $this->api_base . '/client/embed-codes',
            array(
                'headers' => array( 'Authorization' => 'Bearer ' . $token ),
                'timeout' => 30,
            )
        );

        if ( is_wp_error( $response ) ) {
            bfc_log( 'BFC_API::get_embed_codes — wp_remote_get error: ' . $response->get_error_message(), 'ERROR' );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        bfc_log( "BFC_API::get_embed_codes — HTTP {$code} received." );

        $result = $this->parse_response( $response );

        // Nettoyer les codes d'intégration des entités HTML indésirables (&#038; → &)
        if ( ! is_wp_error( $result ) && isset( $result['success'] ) && $result['success'] === true ) {
            $result = $this->sanitize_embed_codes( $result );
        }

        return $result;
    }

    /**
     * Sanitize embed codes by decoding HTML entities back to their original characters.
     *
     * @param array $data The embed codes data from the API.
     * @return array The sanitized embed codes.
     */
    private function sanitize_embed_codes( $data ) {
        if ( isset( $data['calendar']['all_boats'] ) ) {
            $data['calendar']['all_boats'] = html_entity_decode( $data['calendar']['all_boats'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        }

        if ( isset( $data['calendar']['boats'] ) && is_array( $data['calendar']['boats'] ) ) {
            foreach ( $data['calendar']['boats'] as &$boat ) {
                if ( isset( $boat['code'] ) ) {
                    $boat['code'] = html_entity_decode( $boat['code'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                }
            }
        }

        if ( isset( $data['certificates'] ) && is_array( $data['certificates'] ) ) {
            foreach ( $data['certificates'] as &$cert ) {
                if ( isset( $cert['code'] ) ) {
                    $cert['code'] = html_entity_decode( $cert['code'], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                }
            }
        }

        bfc_log( 'BFC_API::sanitize_embed_codes — embed codes sanitized (HTML entities decoded).' );

        return $data;
    }

    /**
     * Demande un magic link à usage unique pour accéder à Boat Calendar
     * sur bookingfish.ca sans avoir à se reconnecter.
     *
     * @param string $token Bearer token.
     * @param string $tab   Tab to open on bookingfish.ca.
     * @return array|WP_Error  ['success', 'url'] on success.
     */
    public function get_magic_link( $token, $tab = 'calendar' ) {
        bfc_log( 'BFC_API::get_magic_link — requesting magic link for tab=' . $tab );

        $response = wp_remote_get(
            add_query_arg( 'tab', rawurlencode( $tab ), $this->api_base . '/client/magic-link' ),
            array(
                'headers' => array( 'Authorization' => 'Bearer ' . $token ),
                'timeout' => 15,
            )
        );

        if ( is_wp_error( $response ) ) {
            bfc_log( 'BFC_API::get_magic_link — error: ' . $response->get_error_message(), 'ERROR' );
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        bfc_log( "BFC_API::get_magic_link — HTTP {$code} received." );

        return $this->parse_response( $response );
    }

    /**
     * Invalidate the bearer token on bookingfish.ca.
     *
     * @param string $token
     * @return bool
     */
    public function logout( $token ) {
        bfc_log( 'BFC_API::logout — sending logout request.' );

        $response = wp_remote_post(
            $this->api_base . '/client/logout',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'application/json',
                ),
                'timeout' => 15,
            )
        );

        $success = ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response );
        bfc_log( 'BFC_API::logout — result: ' . ( $success ? 'OK' : 'FAILED' ) );
        return $success;
    }

    // =========================================================================
    // Stored connection helpers
    // =========================================================================

    /**
     * Check whether a valid (non-expired) token is stored.
     *
     * @return bool
     */
    public function is_connected() {
        $token   = get_option( 'bfc_auth_token', '' );
        $expires = (int) get_option( 'bfc_token_expires', 0 );

        if ( empty( $token ) ) {
            bfc_log( 'BFC_API::is_connected — no token stored.' );
            return false;
        }

        if ( $expires > 0 && $expires < time() ) {
            bfc_log( 'BFC_API::is_connected — token expired, clearing credentials.', 'WARNING' );
            $this->clear_stored_credentials();
            return false;
        }

        bfc_log( 'BFC_API::is_connected — connected, token valid until ' . gmdate( 'Y-m-d H:i:s', $expires ) . '.' );
        return true;
    }

    /**
     * Persist login data into WP options.
     *
     * @param array $data  Response body from /client/login.
     */
    public function store_credentials( array $data ) {
        update_option( 'bfc_auth_token',    $data['token'] );
        update_option( 'bfc_token_expires', $data['expires'] );
        update_option( 'bfc_vendor_email',  $data['vendor_email'] );
        update_option( 'bfc_vendor_name',   $data['vendor_name'] );
        bfc_log( 'BFC_API::store_credentials — credentials stored for ' . $data['vendor_email'] . '.' );
    }

    /**
     * Remove all stored credentials.
     */
    public function clear_stored_credentials() {
        update_option( 'bfc_auth_token',    '' );
        update_option( 'bfc_token_expires', 0 );
        update_option( 'bfc_vendor_email',  '' );
        update_option( 'bfc_vendor_name',   '' );
        update_option( 'bfc_embed_codes',   array() );
        update_option( 'bfc_last_sync',     0 );
        bfc_log( 'BFC_API::clear_stored_credentials — all credentials cleared.' );
    }

    /**
     * Return the stored bearer token string.
     *
     * @return string
     */
    public function get_stored_token() {
        return get_option( 'bfc_auth_token', '' );
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Parse a wp_remote_* response into an array or WP_Error.
     *
     * @param array|WP_Error $response
     * @return array|WP_Error
     */
    private function parse_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code < 200 || $code >= 300 ) {
            $message = isset( $body['message'] ) ? $body['message'] : "API error (HTTP {$code}).";
            bfc_log( "BFC_API::parse_response — error HTTP {$code}: {$message}", 'ERROR' );
            return new WP_Error( 'bfc_api_error', $message, array( 'status' => $code ) );
        }

        bfc_log( 'BFC_API::parse_response — success, response parsed.' );
        return is_array( $body ) ? $body : array();
    }
}
