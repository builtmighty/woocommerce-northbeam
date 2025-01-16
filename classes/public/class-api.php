<?php
/**
 * API.
 * 
 * Interacting with Northbeam API.
 * 
 * @package Northbeam
 * @since   1.0.0
 */
namespace Northbeam\Public;
class API {

    /**
     * Client ID.
     * 
     * @since   1.0.0
     */
    private $client_id;

    /** 
     * Authorization Key.
     * 
     * @since   1.0.0
     */
    private $auth_key;

    /**
     * Northbeam URL.
     * 
     * @since   1.0.0
     */
    private $url;

    /**
     * Construct.
     * 
     * @since   1.0.0
     */
    public function __construct() {

        // Check for required options.
        if( empty( get_option( 'northbeam_auth_key' ) ) || empty( get_option( 'northbeam_client_id' ) ) ) return;

        // Set client ID.
        $this->client_id = get_option( 'northbeam_client_id' );

        // Set authorization key.
        $this->auth_key = get_option( 'northbeam_auth_key' );

        // Set URL.
        $this->url = 'https://api.northbeam.io/v2/';

    }

    /**
     * Request.
     * 
     * @param   string  $endpoint   The endpoint to send the request to.
     * @param   string  $type       The type of request to send: POST, GET, etc.
     * 
     * @since   1.0.0
     */
    public function request( $endpoint, $body, $type = 'POST' ) {

        // Set.
        $response = wp_remote_post( $this->url . $endpoint, [
            'headers'   => [
                'Authorization'     => $this->auth_key,
                'Data-Client-ID'    => $this->client_id,
                'Content-Type'      => 'application/json',
            ],
            'method'    => $type,
            'body'      => json_encode( $body ),
        ] );

        error_log( 'NORTHBEAM REQUEST: ' . print_r( $response, true ) );

        // Check for errors.
        if( is_wp_error( $response ) ) {

            // Check for logging.
            if( ! get_option( 'northbeam_logging' ) ) return;

            // Get WooCommerce logger and log.
            $logger = wc_get_logger();
            $logger->error( 'Northbeam API Error: ' . $response->get_error_message(), [ 'source' => 'northbeam' ] );
            return;

        }

        // Return response code.
        return wp_remote_retrieve_response_code( $response );

    }

}