<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait StanCheckoutOrderController
 *
 * @since 1.0.0
 */
trait StanCheckoutOrderController {

    /**
     * Updates customer address
     * 
     * @since 1.0.0
     */
	public function update_customer_address( $address, $is_set_billing = false ) {
        if ( $address ) {
            $order_id = WC()->session->get( STAN_SESSION_ORDER_ID );
            $order = wc_get_order( $order_id );

            if ( ! $order ) {
                WcStanCheckoutLogger::log( 'Order ' . $order_id . ' not found when updating customer address' );
                return;
            }
    
            $address = array(
                WC_FIRST_NAME => $address->firstname,
                WC_LAST_NAME  => $address->lastname,
                WC_COMPANY    => $address->company,
                WC_EMAIL      => $address->email,
                WC_PHONE      => $address->phone_number,
                WC_ADDRESS_1  => $address->street_address,
                WC_ADDRESS_2  => $address->street_address_line2,
                WC_CITY       => $address->locality,
                WC_COUNTRY    => country_name_to_code( $address->country ),
                WC_POSTCODE   => $address->zip_code,
            );
    
            if( $is_set_billing ) {
                $order->set_address( $address, 'billing' );
            }
            $order->set_address( $address, 'shipping' );
            
            $order->save();

            WC()->session->set( 'customer_shipping_address', $address );
        }
    }
}
