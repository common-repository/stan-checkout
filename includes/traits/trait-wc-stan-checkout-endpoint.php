<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait StanCheckoutEndpointController
 *
 * @since 1.0.0
 */
trait StanCheckoutEndpointController {
	protected $request_payload;
	protected $decode_request_payload;
    protected $session_id;

	/**
	 * Setup request
	 */
	public function handle_request_middleware() {
		ignore_user_abort( true );
		$GLOBALS[ 'is_webhook_request' ] = true;

		// Prevent third party plugin write their errors to output
		ob_start();

		$this->request_payload        = file_get_contents( 'php://input' );
		$this->decode_request_payload = json_decode( $this->request_payload );

		if ( isset( $_COOKIE[ STAN_SESSION_ID_COOKIE ] ) ) {
			$this->session_id = esc_html( sanitize_text_field( $_COOKIE[ STAN_SESSION_ID_COOKIE ] ) );
		} else {
            $this->session_id = get_session_id_from_header();
        }

		return $this->verify_hmac_header();
	}

	/**
	 * Check that data sent by Stan
	 * 
	 * @return bool
	 */
	public function verify_hmac_header() {
		$sig = get_signature_from_header();
		return verify_signature( $this->request_payload, $sig );
	}

}
