<?php

namespace StanCheckout;

/**
 * Manage payments
 *
 * @since 1.0.0
 */
class WcStanCheckoutPayment {

    /**
     * Handles payment from POST webhook
     * 
     * @since 1.0.0
     */
    public function handle_payment_update( $payload ) {
        $order = wc_get_order( $payload->order_id );
        $success = self::check_payment_and_update_order( $order, array(
            "payment_id" => $payload->id,
            "payment_status" => $payload->payment_status
        ) );

        return array(
			STAN_EVENT_RES_SUCCESS_FIELD => $success,
			STAN_EVENT_RES_DATA_FIELD => array(
				"order_id" => $payload->order_id
			)
		);
    }

    /**
     * Checks stan payment and update linked order accordingly
     * 
     * @since 1.0.0
     * @return true|false wheither it's success or not
     */
    public static function check_payment_and_update_order( $order, $payment = null /* StanPayment */ ) {
        try {
            if ( is_null( $payment ) ) {
                $client = new WcStanCheckoutAPIWrapper();
                $payment = $client->get_payment( $order->get_transaction_id() );
            }

            if ( $order->get_transaction_id() != $payment[ 'payment_id' ] ) {
                WcStanCheckoutLogger::log( 'Order ' . $order->id . ' does not match Stan payment ID ' . $payment[ 'payment_id' ] . ', got' . $order->get_transaction_id() );
                return false;
            }

            switch ( $payment[ 'payment_status' ] ) {
                case STAN_PAYMENT_STATUS_SUCCESS:
                    $order->payment_complete( $payment[ 'payment_id' ] );
                    WcStanCheckoutLogger::log( 'Order ' . $order->id . ' is complete!' );
                    break;
                case STAN_PAYMENT_STATUS_PENDING:
                    $order->set_status( WC_PAYMENT_ON_HOLD, 'Payment is being processed by the bank.' );
                    WcStanCheckoutLogger::log( 'Order ' . $order->id . ' is still pending' );
                    break;
                case STAN_PAYMENT_STATUS_AUTH_REQUIRED:
                case STAN_PAYMENT_STATUS_PREPARED:
                    $order->set_status( WC_PAYMENT_PENDING );
                    WcStanCheckoutLogger::log( 'Order ' . $order->id . ' require an authentication during payment.');
                    break;
                case STAN_PAYMENT_STATUS_EXPIRED:
                case STAN_PAYMENT_STATUS_CANCELLED:
                    $order->set_status( WC_PAYMENT_CANCELLED );
                    WcStanCheckoutLogger::log( 'Order ' . $order->id . ' payment has been cancelled.');
                    break;
                case STAN_PAYMENT_STATUS_PARTIALLY_REFUNDED:
                case STAN_PAYMENT_STATUS_REFUNDED:
                    $order->set_status( WC_PAYMENT_REFUNDED );
                    WcStanCheckoutLogger::log( 'Order ' . $order->id . ' payment has been refunded.');
                    break;
                default:
                    $order->set_status( WC_PAYMENT_FAILED );
                    WcStanCheckoutLogger::log( 'Order ' . $order->id . ' payment failed.');
            }

            $order->save();

            return true;
        } catch (\Exception $e) {
            WcStanCheckoutLogger::log( 'Error raised during order check order_id = ' . $order->id . ', reason: ' . "\n" . $e);
            return false;
        }
    }
}