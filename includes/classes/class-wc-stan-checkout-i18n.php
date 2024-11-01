<?php

namespace StanCheckout;

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://compte.stan-app.fr
 * @since      1.0.0
 *
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes/classes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes/classes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */
class WcStanCheckoutI18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wc-stan-checkout',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
