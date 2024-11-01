<?php

namespace StanCheckout;

/**
 * Manage shipping methods
 *
 * @since 1.0.0
 */
class WcStanCheckoutShipping {
	use StanCheckoutOrderController;

    /**
     * Handles shipping API
     * 
     * @since 1.0.0
     */
    public function handle_shipping_methods_api( $payload ) {
		$shipping_address = $payload;

		$this->update_customer_address( $shipping_address, true );

		$available_shipping_methods = array();

		$cart_packages = $this->get_cart_packages( country_name_to_code( $shipping_address->country ), $shipping_address->zip_code, $shipping_address->locality );
		WC()->shipping->calculate_shipping( $cart_packages );

		foreach ( WC()->shipping->packages as $package_id => $package ) {
			if ( WC()->session->__isset( 'shipping_for_package_' . $package_id ) ) {
				foreach ( WC()->session->get( 'shipping_for_package_' . $package_id )['rates'] as $shipping_rate_id => $shipping_rate ) {
					$shipping_method = array(
						STAN_SHIPPING_METHOD_ID => $shipping_rate->get_method_id(),
						STAN_SHIPPING_COST => 
							convert_monetary_value_to_integer( $shipping_rate->get_cost() )
							+ convert_monetary_value_to_integer( $shipping_rate->get_shipping_tax() ),
						STAN_SHIPPING_LABEL => $shipping_rate->label,
					);

					if ( class_exists( '\Boxtal\BoxtalConnectWoocommerce\Shipping_Method\Controller' ) ) {
						$boxtal_relay_networks = \Boxtal\BoxtalConnectWoocommerce\Shipping_Method\Controller::get_pricing_items($shipping_rate->get_id());
						$relay_networks = parse_boxtal_relay_point_networks( $boxtal_relay_networks );
						if ( ! empty( $relay_networks ) ) {
							$shipping_method[ STAN_SHIPPING_RELAY_NETWORKS ] = $relay_networks;
						}
					}

					array_push( $available_shipping_methods, $shipping_method );

					WcStanCheckoutLogger::log( 'push shipping method ' . $shipping_rate->get_method_id() );
				}
			}
		}

		return array(
			STAN_EVENT_RES_SUCCESS_FIELD => true,
			STAN_EVENT_RES_DATA_FIELD => $available_shipping_methods
		);
    }

	/**
	 * Handles shipping method select
	 * 
	 * @since 1.0.0
	 */
	public function handle_select_shipping_method_api( $session_id, $payload ) {
		$order_id = WC()->session->get( STAN_SESSION_ORDER_ID );

		$order = wc_get_order( $order_id );

		if ( is_null( $order ) || ! $order ) {
			WcStanCheckoutLogger::log( 'cant find order when handling shipping method' );
			return array(
				STAN_EVENT_RES_SUCCESS_FIELD => false
			);
		}
		
		WC()->session->set( 'chosen_shipping_methods', array( $payload->shipping_method_id ) );

		$cart_packages = $this->get_cart_packages( country_name_to_code( $order->shipping_country ), $order->shipping_postcode, $order->shipping_city );
		WC()->shipping->calculate_shipping( $cart_packages );

		foreach ( $cart_packages as $package_id => $package ) {
			if ( WC()->session->__isset( 'shipping_for_package_'.$package_id ) ) {
				foreach ( WC()->session->get( 'shipping_for_package_' . $package_id )['rates'] as $shipping_rate_id => $shipping_rate ) {
					if ( $shipping_rate->get_method_id() === $payload->shipping_method_id ) {
						$order_shipping_method = current( $order->get_shipping_methods() );
						if ( $order_shipping_method ) {
							$shipping_items = (array) $order->get_items( 'shipping' );
							foreach ( $shipping_items as $item_id => $item ) {
								$order->remove_item( $item_id );
							}
						}

						if ( isset( $payload->relay_point ) ) {
							$order->set_shipping_city( $payload->relay_point->city );
							$order->set_shipping_company( $payload->relay_point->name );
							$order->set_shipping_country( $payload->relay_point->country );
							$order->set_shipping_postcode( $payload->relay_point->postal_code );
							$order->set_shipping_address_1( $payload->relay_point->street );
							$order->save();

							switch( $payload->shipping_method_id ) {
								case LPC_RELAY:
									$order->update_meta_data( LPC_META_PICKUP_LOCATION_ID, $payload->relay_point->id );
									$order->update_meta_data( LPC_META_PICKUP_LOCATION_LABEL, $payload->relay_point->name );
									$order->update_meta_data( LPC_META_PICKUP_PRODUCT_CODE, $payload->relay_point->code );
									break;
								default:
									$order->update_meta_data( STAN_META_PICKUP_LOCATION_ID, $payload->relay_point->id );
									$order->update_meta_data( STAN_META_PICKUP_LOCATION_LABEL, $payload->relay_point->name );
									$order->update_meta_data( STAN_META_PICKUP_PRODUCT_CODE, $payload->relay_point->code );
									break;
							}
						}

						$order->add_shipping( $shipping_rate );

						break;
					}
				}
			}
		}

		WC()->cart->calculate_totals();
		WC()->cart->calculate_shipping();

		$order->calculate_shipping();
		$order->calculate_totals();
		$order->save();

		save_session( $session_id );

		return array(
			STAN_EVENT_RES_SUCCESS_FIELD => true,
			STAN_EVENT_RES_DATA_FIELD => array(
				"total_amount" => convert_monetary_value_to_integer( floatval( $order->get_total() ) ),
				"shipping_amount" => convert_monetary_value_to_integer( floatval( $order->get_shipping_total() ) )
			)
		);
	}

	private function get_cart_packages( $country_code, $postalcode, $city )
	{
		$packages = array();

		foreach ( WC()->cart->get_shipping_packages() as $package_id => $package ) {
			$package['destination']['country'] = $country_code;
			$package['destination']['city'] = $city;
			$package['destination']['postcode'] = $postalcode;

			array_push( $packages, $package );
		}

		return $packages;
	}
}