<?php

namespace StanCheckout;

/**
 * WcStanCheckoutAPI Implements apis functionalities
 *
 * Communicates with Stan API.
 *
 * @since      1.0.0
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */
class WcStanCheckoutAPI {

    /**
	 * Gets secret key.
	 *
     * @since 1.0.0
     * 
	 * @return string The secret key
	 */
	public static function get_secret_key() {
		if ( wc_stan()->get_settings_instance()->is_testmode() ) {
			return wc_stan()->get_settings_instance()->get_settings()[ WcStanCheckoutSettings::SETTING_CLIENT_TEST_SECRET ];
		}
		return wc_stan()->get_settings_instance()->get_settings()[ WcStanCheckoutSettings::SETTING_CLIENT_SECRET ];
	}

    /**
	 * Gets client id.
	 *
     * @since 1.0.0
     * 
	 * @return string The client id
	 */
	public static function get_client_id() {
		if ( wc_stan()->get_settings_instance()->is_testmode() ) {
			return wc_stan()->get_settings_instance()->get_settings()[ WcStanCheckoutSettings::SETTING_CLIENT_TEST_ID ];
		}
		return wc_stan()->get_settings_instance()->get_settings()[ WcStanCheckoutSettings::SETTING_CLIENT_ID ];
	}


	/**
	 * Generates the headers to pass to API request.
	 *
	 * @since 1.0.0
	 */
	private static function get_headers() {
		return apply_filters(
			'woocommerce_stan_checkout_payment_gateway_req_headers',
			array(
				'Authorization' => 'Basic ' . base64_encode( self::get_client_id() . ':' . self::get_secret_key() ),
			)
		);
	}

	/**
	 * Send a request to Stan's API
	 *
	 * @since 1.0.0
     * @param string $uri
     * @param string $method
	 * @param array $request
     * @param array $custom_headers
	 * @return array
	 */
	public static function request( $uri, $method = 'GET', $request = array(), $custom_headers = array() ) {
		$current_headers = self::get_headers();

        $headers = array_merge( $current_headers, $custom_headers );

		WcStanCheckoutLogger::log( "Request {$method} {$uri}" );

		switch ( $method ) {
			case 'GET':
				$options = array(
					'method' => 'GET',
					'headers' => $headers,
					'timeout' => 100,
				);
				$response = WP_DEBUG ? wp_remote_get( $uri, $options ) : wp_safe_remote_get( $uri, $options );
                break;
            case 'PUT':
			case 'POST':
            case 'PATCH':
				$headers['Content-Type'] = 'application/json; charset=utf-8';
				$options = array(
					'method' => $method,
					'headers' => $headers,
					'body' => wp_json_encode( $request ),
					'timeout' => 100,
				);
				$response = WP_DEBUG ? wp_remote_request( $uri, $options ) : wp_safe_remote_request( $uri, $options );
				break;
        }

        if ( is_wp_error( $response ) ) {
            throw new \Exception( 'Wordpress error. Response : ' . wp_json_encode( $response ) );
        }

        $statusCode = $response['response']['code'];
		if ( empty( $response['body'] ) && $statusCode != 204 ) {
			throw new \Exception( 'Response body is missing' );
		}

		if ( $statusCode >= 400) {
			throw new \Exception( sprintf( "There was a problem connecting to the Stan API. Code %s. Response %s", $statusCode, wp_json_encode( $response ) ) );
		}

        if ( ! empty( $response['body'] ) ) {
            return json_decode( wp_remote_retrieve_body( $response ) );
        }

        return array();
	}
}