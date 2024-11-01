<?php

namespace StanCheckout;

/**
 * WcStanCheckoutAPI Stan apy API client
 *
 * Communicates with Stan API.
 *
 * @since      1.0.0
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes/classes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */
class WcStanCheckoutAPIWrapper {

    /**
	 * Create a payment for a client
	 * Doc : https://doc.stan-app.fr/#create-a-payment-invoice
     * 
     * @since 1.3.0
     * @param string $order_id ID of the order
     * @param int $amount Amount for the payment, must be a integer (1,23 should be 123)
     * 
	 * @return array JSON response
	 */
    public function legacy_create_payment( $order_id, $amount, $return_url, $customer_id = null ) {
        $payload = array(
            "order_id" => $order_id,
            "amount" => $amount,
            "return_url" => $return_url
        );

        if ( ! is_null( $customer_id ) ) {
            $payload["customer_id"] = $customer_id;
        }

        try {
            $payment = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/payments', 'POST', $payload );
            return $payment;
        } catch (\Exception $e) {
            throw new WcStanCheckoutException( 'Error raised during payment creation, reason: ' . "\n" . $e, __( 'Il y a eu un soucis lors de la création du paiement', 'wc-stan-checkout' ) );
        }
    }

    /**
	 * Create a customer given an order
     * 
     * @since 1.0.0
     * @param WC_Order $order The order to create a customer in Stan
     * 
	 * @return array JSON response
	 */
    public function create_customer_with_order( $order ) {
        $payload = array(
            "firstname" => $order->get_billing_first_name(),
            "lastname" => $order->get_billing_last_name(),
            "email" => $order->get_billing_email(),
            "phone_number" => $order->get_billing_phone(),
            "address" => array(
                "firstname" => $order->get_billing_first_name(),
                "lastname" => $order->get_billing_last_name(),
                "street_address" => $order->get_billing_address_1(),
                "street_address_line2" => $order->get_billing_address_2(),
                "locality" => $order->get_billing_city(),
                "zip_code" => $order->get_billing_postcode(),
                "country" => $order->get_billing_country(),
                "region" => $order->get_billing_state()
            )
        );

        try {
            $customer = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/customers', 'POST', $payload );
            return $customer;
        } catch (\Exception $e) {
            throw new WcStanCheckoutException( 'Error raised during customer creation, reason: ' . "\n" . $e, __( 'Il y a eu un soucis lors de la création du paiement', 'wc-stan-checkout' ) );
        }
    }

     /**
	 * Create a customer given a WC customer
     * 
     * @since 1.0.0
     * @param WC_Customer $customer The customer to create a customer in Stan
     * 
	 * @return array JSON response
	 */
    public function create_customer_with_wc_customer( $customer ) {
        $payload = array(
            "firstname" => $customer->get_first_name(),
            "lastname" => $customer->get_last_name(),
            "email" => $customer->get_email()
        );

        if ( ! empty( $customer->get_shipping_address_1() ) ) {
            $payload["phone_number"] = $customer->get_shipping_phone();
            $payload["address"] = array(
                "firstname" => $customer->get_shipping_first_name(),
                "lastname" => $customer->get_shipping_last_name(),
                "street_address" => $customer->get_shipping_address_1(),
                "street_address_line2" => $customer->get_shipping_address_2(),
                "locality" => $customer->get_shipping_city(),
                "zip_code" => $customer->get_shipping_postcode(),
                "country" => $customer->get_shipping_country(),
                "region" => $customer->get_shipping_state()
            );
        }

        try {
            $customer = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/customers', 'POST', $payload );
            return $customer;
        } catch (\Exception $e) {
            throw new WcStanCheckoutException( 'Error raised during customer creation, reason: ' . "\n" . $e, __( 'Il y a eu un soucis lors de la création du paiement', 'wc-stan-checkout' ) );
        }
    }

    /**
	 * Get payment by ID
     * 
     * @since 1.0.0
     * @param string $payment_id ID of the payment
     * 
	 * @return array JSON response
	 */
    public function get_payment( $payment_id ) {
        try {
            $payment = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/payments/' . $payment_id );
            return $payment;
        } catch(\Exception $e) {
            throw new WcStanCheckoutException( 'Error raised during get payment request, reason: ' . '\n' . $e );
        }
    }

    /**
     * Create and initiate a Stan Checkout
     * 
     * @param WC_Cart $order The order to create a customer in Stan
     * @since 1.0.0
     */
    public function create_checkout( $session_id, $cart, $customer_id = null ) {
        try {
            $cart->calculate_totals();
            
            $payload = array(
                "return_url" => get_site_url() . '/?wc-api=' . STAN_CHECKOUT_GATEWAY_NAME,
                "total_amount" => convert_monetary_value_to_integer( floatval( $cart->total ) ),
                "subtotal_amount" => convert_monetary_value_to_integer( floatval( $cart->subtotal ) ),
                "tax_amount" => convert_monetary_value_to_integer( floatval( $cart->get_shipping_total() ) ),
                "discount_amount" => convert_monetary_value_to_integer( floatval( $cart->get_discount_total() ) ),
                "session_id" => $session_id,
                "discount_code" => implode( ',', $cart->applied_coupons ),
                "customer_id" => $customer_id,
            );
            
            $line_items = array();
            foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
                $product = $cart_item['data'];
                $product_instance = wc_get_product( $cart_item['product_id'] );
                $line_item = array(
                    "product_id" => strval( $cart_item['product_id'] ),
                    "title" => $product->get_name(),
                    "quantity" => intval( $cart_item['quantity'] ),
                    "unit_price" => convert_monetary_value_to_integer( floatval( $product->get_price() ) ),
                    "discount_amount" => convert_monetary_value_to_integer( floatval( $product->get_sale_price() ) ),
                    "description" => wp_strip_all_tags( $product_instance->get_description() ),
                    "sku" => $product->get_sku(),
                    "product_url" => $product->get_permalink( $cart_item ),
                );
                $image_url = get_the_post_thumbnail_url( $cart_item['product_id'] );
                if ( $image_url ) {
                    $line_item['image_url'] = $image_url;
                }
                array_push( $line_items, $line_item );
            }

            $payload['line_items'] = $line_items;

            $res = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/checkouts', 'POST', $payload );
            
            return $res;
        } catch ( \Exception $e ) {
            throw new WcStanCheckoutException( 'Error raised during checkout create, reason: ' . '\n' . $e );
        }
    }
    
    /**
	 * Update the account infos.
	 *
     * @since 1.0.0
     * @param array $payload Account infos to update
     * 
	 * @return array JSON response
	 */
    public function update_account_infos( $payload ) {
        try {
            $res = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/accounts', 'PATCH', $payload );
            return $res;
        } catch (\Exception $e) {
            WcStanCheckoutLogger::log( 'Error raised during settings update, reason: ' . "\n" . $e);
            throw new WcStanCheckoutException( 'Error raised during settings update, reason: ' . '\n' . $e );
        }
    }

    /**
	 * Update the account API clients redirection infos.
	 *
     * @since 1.0.0
     * 
	 * @return array JSON response
	 */
    public function legacy_update_account_client() {
        $url = get_site_url();
        $order_redirect_url = $url . '/?wc-api=WC_Stan_Payment';
        $oauth_redirect_url = $url . '/stan-easy-connect-authorize';

        $payload = array( 
            "payment_webhook_url" => $order_redirect_url,
            "oauth_redirect_url" => $oauth_redirect_url
        );

        try {
            $res = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/apis', 'PUT', $payload );
            return $res;
        } catch (\Exception $e) {
            throw new WcStanCheckoutException( 'Error raised during settings update, reason: ' . '\n' . $e );
        }
    }

    /**
	 * Get account infos.
	 *
     * @since 1.0.0
     * 
	 * @return array JSON response
	 */
    public function get_account_infos() {
        try {
            $res = WcStanCheckoutAPI::request( wc_stan()->get_settings_instance()->get_api_host() . '/v1/accounts', 'GET', array() );
            return $res;
        } catch(\Exception $e) {
            throw new WcStanCheckoutException( 'Error raised during account fetch, reason: ' . "\n" . $e );
        }
    }
}