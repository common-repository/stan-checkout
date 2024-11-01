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
class WcStanCheckoutCartAPI {
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
		$routes['/stan/checkouts'] = array(
			array( array( $this, 'handle_create_checkout_api' ), 'POST' | \WP_JSON_Server::ACCEPT_JSON ),
		);
		$routes['/stan/checkouts'] = array(
			array(
				array( $this, 'handle_update_cart_api' ), 'POST' | \WP_JSON_Server::ACCEPT_JSON,
			),
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
		register_rest_route( 'stan', '/checkouts', array(
			array(
                'methods' => 'POST',
                'callback' => array( $this, 'handle_create_checkout_api' ),
                'permission_callback' => '__return_true'
            )
		) );
		register_rest_route( 'stan', '/carts', array(
			array(
				'methods' => 'POST',
				'callback' => array( $this, 'handle_update_cart_api' ),
				'permission_callback' => '__return_true'
			)
		) );
	}

    /**
     * Handles customer API
     * 
     * @since 1.0.0
     */
    public function handle_create_checkout_api() {
		$this->handle_request_middleware();

		restore_session_from_session_id( $this->session_id );

		unset( WC()->session->chosen_shipping_methods );
		
		if ( isset( $this->decode_request_payload->product_id ) ) {
			add_filter( 'woocommerce_add_to_cart_redirect', '__return_empty_string' );
			WC()->cart->empty_cart();

			$variation_id = null;
			$variations = array();

			if ( isset( $this->decode_request_payload->variation_id ) && isset( $this->decode_request_payload->attributes ) ) {
				foreach( $this->decode_request_payload->attributes as $attribute )  {
					$variations[ $attribute->name ] = $attribute->value;
				}

				$variation_id = $this->decode_request_payload->variation_id;
			}

			WC()->cart->add_to_cart( $this->decode_request_payload->product_id, 1, $variation_id, $variations );
			remove_filter( 'woocommerce_add_to_cart_redirect', '__return_empty_string' );

			WC()->cart->maybe_set_cart_cookies();
			WC()->session->set( STAN_SESSION_USER_ID, get_current_user_id() );
			
			if ( isset( $_COOKIE[ STAN_SESSION_ID_COOKIE ] ) ) {
				save_session( esc_html( sanitize_text_field( $_COOKIE[ STAN_SESSION_ID_COOKIE ] ) ) );
			}
		}

		$client = new WcStanCheckoutAPIWrapper();

		try {
			$customer_id = null;

			if ( WC()->customer->get_email() !== null && WC()->customer->get_email() !== "" ) {
				$customer_id = get_user_meta( WC()->customer->get_id(), WC_STAN_CUSTOMER_ID, true );

				if ( ! $customer_id ) {
					$customer = $client->create_customer_with_wc_customer( WC()->customer );
	
					update_user_meta( WC()->customer->get_id(), WC_STAN_CUSTOMER_ID, $customer->id, true );
	
					$customer_id = $customer->id;
				}
			}

			$checkout = $client->create_checkout( $this->session_id, WC()->cart, $customer_id );

			return $checkout;
		} catch ( \Exception $e ) {
			WcStanCheckoutLogger::log( 'failed to create checkout. Reason ' . $e );
			return array(
				STAN_EVENT_RES_SUCCESS_FIELD => false
			);
		}

        return array(
			STAN_EVENT_RES_SUCCESS_FIELD => true,
			STAN_EVENT_RES_DATA_FIELD => $checkout
		);
    }

	/**
	 * Handles cart update API
	 * 
	 * @since 1.0.0
	 */
	public function handle_update_cart_api() {
		// TODO for updating cart items
		return array(
			STAN_EVENT_RES_SUCCESS_FIELD => false
        );
	}
}

new WcStanCheckoutCartAPI();