<?php

namespace StanCheckout;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */

/**
 * Retrieve cart from session id
 * 
 * @since 1.0.0
 * @return boolean Whether it succeed or not
 */
function restore_session_from_session_id( $session_id ) {
    $base_session_data = wc_stan_db()->get_session( STAN_DB_SESSION_KEY . $session_id );

    if ( ! is_null( WC()->cart ) ) {
        WC()->cart->empty_cart( false );
    } else {
        wc_load_cart();
    }

    if ( is_null( WC()->session ) ) {
        WC()->session = new \WC_Session_Handler();
        WC()->session->init();
    }

    try {
        if ( !is_array( $base_session_data ) ) {
            // TODO log no data
            return false;
        }
        foreach ( $base_session_data as $key => $value ) {
            WC()->session->set( $key, maybe_unserialize( $value ) );
        }

        $totals   = isset( $base_session_data['cart_totals'] ) ? (array) maybe_unserialize( $base_session_data['cart_totals'] ) : array();
		$cart     = isset( $base_session_data['cart'] ) ? (array) maybe_unserialize( $base_session_data['cart'] ) : array();
		$customer = (array) maybe_unserialize( @$base_session_data['customer'] );

        if ( $customer ) {
            $user = get_user_by( 'ID', $customer['id'] );

            if ( ! is_user_logged_in() && $user ) {
                wp_set_current_user( $user->ID );
                WC()->customer = new \WC_Customer( $user->ID, true );
                // Customer should be saved during shutdown.
                add_action( 'shutdown', array( WC()->customer, 'save' ), 10 );
            }
        }

        if ( empty( $cart ) ) {
            // TODO log cart is empty
			return false;
		}

        if ( ! empty( $total ) ) {
            WC()->cart->set_totals( $totals );
        }
		WC()->cart->set_applied_coupons( (array) maybe_unserialize( $base_session_data['applied_coupons'] ) );
		WC()->cart->set_coupon_discount_totals( (array) maybe_unserialize( $base_session_data['coupon_discount_totals'] ) );
		WC()->cart->set_coupon_discount_tax_totals( (array) maybe_unserialize( $base_session_data['coupon_discount_tax_totals'] ) );
		WC()->cart->set_removed_cart_contents( (array) maybe_unserialize( $base_session_data['removed_cart_contents'] ) );

        if ( is_array( $cart ) ) {
			$cart_contents = array();
			foreach ( $cart as $key => $values ) {
				$product_id = $values[ 'variation_id' ] ? $values[ 'variation_id' ] : $values[ 'product_id' ];
				$product    = wc_get_product( $product_id );
				if ( empty( $product ) || ! $product->exists() ) {
					// TODO log product doesnt exist
					return false;
				}

				if ( $product->is_purchasable() && $values['quantity'] > 0 ) {
					$values = (array) maybe_unserialize( $values );
					$session_data          = array_merge( $values, array( 'data' => $product ) );
					$cart_contents[ $key ] = apply_filters( 'woocommerce_get_cart_item_from_session', $session_data, $values, $key );

					WC()->cart->set_cart_contents( $cart_contents );
				} else {
					// TODO log product not purchasable

					return false;
				}
			}
		}
    } catch( \Exception $e ) {
        // TODO log
        return false;
    }
}

/**
 * Retrieve session ID from header request
 * 
 * @since 1.0.0
 * @return string
 */
function get_session_id_from_header() {
    if ( isset( $_SERVER[ 'HTTP_' . STAN_SESSION_ID_HEADER ] ) ) {
        return esc_html( sanitize_text_field( $_SERVER[ 'HTTP_' . STAN_SESSION_ID_HEADER ] ) );
    }
    return '';
}

/**
 * Retrieve request signature from header request
 * 
 * @since 1.0.0
 * @return string
 */
function get_signature_from_header() {
    if ( isset( $_SERVER[ 'HTTP_' . esc_html( sanitize_text_field( STAN_SIGNATURE_HEADER ) ) ] ) ) {
        return esc_html( sanitize_text_field( $_SERVER[ 'HTTP_' . STAN_SIGNATURE_HEADER ] ) );
    }
    return '';
}

/**
 * Convert currency amount in standard format
 * 
 * @since 1.0.0
 */
function convert_monetary_value_to_integer( $amount, $currency_code = null ) {
	$precision = 2;
    return (int) round( $amount * 10 ** $precision );
}

/**
 * Transform a country name to country code
 * 
 * @since 1.0.0
 */
function country_name_to_code( $country_name, $region = null ) {
    $country_codes = array(
        "france" => "FR",
        "chine" => "CN",
    );

    if ( array_key_exists( strtolower( $country_name ), $country_codes ) ) {
        return $country_codes[ strtolower( $country_name ) ];
    }

    return $country_name;
}

/**
 * Saves a session with session_id as key
 * 
 * @since 1.0.0
 */
function save_session( $session_id ) {
    if ( WC()->cart ) {
        WC()->cart->set_session();
        WC()->cart->maybe_set_cart_cookies();
    
        WC()->customer->save();
        
        WC()->session->save_data();
    
        $session_data = WC()->session->get_session_data();
    
        wc_stan_db()->insert_or_update_session( STAN_DB_SESSION_KEY . $session_id, $session_data );
    }
}

/**
 * Returns the minimal amount for payment
 * 
 * @since 1.0.0
 */
function get_minimum_amount() {
    return 100;
}

/**
 * Returns the maximal amount for payment
 * 
 * @since 1.0.0
 */
function get_maximum_amount() {
    return -1;
}

/**
 * Get order shipping method
 *
 * @since 1.0.0
 */
function get_order_shipping_method( $order ) {
    $shipping_methods = $order->get_shipping_methods();
    if ( $shipping_methods ) {
        /* @var WC_Order_Item_Shipping $shipping_method */
        $shipping_method = current( $shipping_methods );
    } else {
        $shipping_method = new \WC_Order_Item_Shipping();
    }

    return $shipping_method;
}

/**
 * Check that data sent by Stan server.
 *
 * @param $payload        Data in body
 * @param $signature      Header signature
 *
 * @return bool     true if the request is authenticated, otherwise false
 * @since 1.0.0
 */
function verify_signature( $payload, $signature ) {
	return ( 'sha256=' . compute_signature( $payload ) == $signature );
}

/**
 * Compute signature using secret key
 *
 * @param $payload a string for which a signature is required
 *
 * @return string
 * @since 1.0.0
 */
function compute_signature( $payload ) {
	$secret_key = wc_stan()->get_settings_instance()->get_hmac_secret();
	return base64_encode( hash_hmac( 'sha256', $payload, $secret_key, true ) );
}

/**
 * Get and parse boxtal relay point networks
 * 
 * @return array<string>
 * @since 1.3.0
 */
function parse_boxtal_relay_point_networks( $boxtal_relay_networks ) {
    $relay_point_networks = array();

    foreach( $boxtal_relay_networks as $boxtal_data ) {
        foreach( $boxtal_data['parcel_point_network'] as $boxtal_parcel_network ) {
            $relay_point_network = "";
            switch( $boxtal_parcel_network ) {
                case 'MONR_NETWORK':
                    $relay_point_network = 'mondial_relay';
                    break;
                case 'UPSE_NETWORK':
                    $relay_point_network = 'ups';
                    break;
                case 'COPR_NETWORK':
                    $relay_point_network = 'prive';
                    break;
                case 'SOGP_NETWORK': // relais colis
                    $relay_point_network = 'relais_colis';
                    break;
                case 'CHRP_NETWORK':
                    $relay_point_network = 'chronopost';
                    break;
            }

            array_push( $relay_point_networks, $relay_point_network );
        }
    }

    return $relay_point_networks;
}