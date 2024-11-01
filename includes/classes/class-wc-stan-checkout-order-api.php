<?php

namespace StanCheckout;

/**
 * Manage order
 *
 * @since 1.0.0
 */
class WcStanCheckoutOrder {
	use StanCheckoutCustomerController;

    /**
     * Handles order API
     * 
     * @since 1.0.0
     */
    public function handle_create_order_api( $session_id, $payload ) {
		$checkout = WC()->checkout();

		$order_id = $checkout->create_order( array() );
		$order = wc_get_order( $order_id );

		$user_id = $this->maybe_create_user(
			$payload->email,
			$payload->firstname,
			$payload->lastname,
			$payload->phone_number
		);

		$order->set_customer_id( $user_id );

		$order->calculate_totals();

		$order->set_payment_method( STAN_CHECKOUT_GATEWAY_NAME );

		$order->save();

		WC()->session->set( STAN_SESSION_ORDER_ID, $order_id );
		save_session( $session_id );

		WcStanCheckoutLogger::log( 'Order ' . $order_id . ' created' );

		return array(
			STAN_EVENT_RES_SUCCESS_FIELD => true,
			STAN_EVENT_RES_DATA_FIELD => array(
				"order_id" => strval( $order_id )
			)
		);
    }
}