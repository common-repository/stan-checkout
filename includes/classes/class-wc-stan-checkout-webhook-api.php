<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage checkout
 *
 * @since 1.0.0
 */
class WcStanCheckoutWebhookAPI {
	use StanCheckoutEndpointController;
    /**
	 * Constructor Function.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function __construct() {
		if ( WOO_STAN_CHECKOUT_WP_REST_API_ADDON ) {
			add_filter( 'json_endpoints', array( $this, 'register_wp_rest_api_route' ) );
		} else {
			add_action( 'rest_api_init', array( $this, 'register_checkout_endpoint' ) );
		}
	}

    /**
	 * Register WP REST API route
	 *
	 * @param array $routes
	 *
	 * @return array
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function register_wp_rest_api_route( $routes ) {
		$routes['/stan/webhooks'] = array(
			array( array( $this, 'handle_webhook' ), 'POST' | \WP_JSON_Server::ACCEPT_JSON ),
		);
		
		return $routes;
	}

    /**
	 * Register wordpress endpoints
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function register_checkout_endpoint() {
		register_rest_route( 'stan', '/webhooks', array(
			array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_webhook' ),
                'permission_callback' => '__return_true'
            )
		) );
	}

    /**
     * Handles customer API
     * 
     * @since 1.0.0
     */
    public function handle_webhook() {
		$is_request_valid = $this->handle_request_middleware();

		if ( ! $is_request_valid ) {
			WcStanCheckoutLogger::log( 'Failed to verify Stan request, webhook event is ' . $this->decode_request_payload->event_type . ', provided signature was ' . get_signature_from_header() );
			return array(
				STAN_EVENT_RES_SUCCESS_FIELD => false
			);
		}

		restore_session_from_session_id( $this->session_id );

        WcStanCheckoutLogger::log( 'Received Stan notification ' . $this->decode_request_payload->event_type . ' with session id ' . $this->session_id );

		if (! isset( $this->decode_request_payload->payload ) ) {
			WcStanCheckoutLogger::log( 'Notify payload is missing' );
			http_response_code( HTTP_STATUS_BAD_REQUEST );
			return array(
				STAN_EVENT_RES_SUCCESS_FIELD => false
			);
		}
		
		$res = null;
        switch( $this->decode_request_payload->event_type ) {
			case STAN_EVENT_TYPE_PAYMENT_CREATED:
				WcStanCheckoutLogger::log( 'Get order ' . $this->decode_request_payload->payload->order_id );
				$order = wc_get_order( $this->decode_request_payload->payload->order_id );
				$order->set_transaction_id( $this->decode_request_payload->payload->id );
				$order->save();
				/* fallthrough */
			case STAN_EVENT_TYPE_PAYMENT_STATUS_CHANGED:
				$res = (new WcStanCheckoutPayment())->handle_payment_update( $this->decode_request_payload->payload,  );
				break;
            case STAN_EVENT_TYPE_CUSTOMER_SHIPPING_ADDRESS_CHANGED:
                $res = (new WcStanCheckoutShipping())->handle_shipping_methods_api( $this->decode_request_payload->payload );
				break;
			case STAN_EVENT_TYPE_CHECKOUT_SHIPPING_METHOD_CHANGED:
				$res = (new WcStanCheckoutShipping())->handle_select_shipping_method_api( $this->session_id, $this->decode_request_payload->payload );
				break;
            case STAN_EVENT_TYPE_CUSTOMER_AUTHENTICATED:
				/* fallthrough */
			case STAN_EVENT_TYPE_CUSTOMER_CREATED:
				$res = (new WcStanCheckoutOrder())->handle_create_order_api( $this->session_id, $this->decode_request_payload->payload );
				break;
        }

		ob_end_clean();

		if ( ! is_null( $res ) ) {
			return $res;
		}

		WcStanCheckoutLogger::log( 'Unhandled event ' . $this->decode_request_payload->event_type );

        return array(
            STAN_EVENT_RES_SUCCESS_FIELD => false
        );
    }
}

new WcStanCheckoutWebhookAPI();