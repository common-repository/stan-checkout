<?php

namespace StanCheckout;

/**
 * Log anything that go through wc-stan-checkout
 *
 * @since 1.0.0
 */
class WcStanCheckoutLogger {

	public static $logger;
	const WC_LOG_FILENAME = 'wc-stan-checkout';

	public static function log( $message ) {
		if ( !class_exists( 'WC_Logger' ) ) {
			return;
		}

		if ( apply_filters( 'wc_stan_checkout_logging', true, $message ) ) {
			if ( empty( self::$logger ) ) {
				self::$logger = wc_get_logger();
			}

			$log_entry = '>>>> Stan Log <<<<' . "\n" . $message . "\n" . '>>>> Stan End Log <<<<' . "\n\n";

			self::$logger->debug( $log_entry, array( 'source' => self::WC_LOG_FILENAME ) );
		}
	}
}
