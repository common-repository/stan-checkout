<?php

namespace StanCheckout;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes/classes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */
class WcStanCheckoutDeactivator {

	/**
	 * Do something when the plugin is deactivated
	 *
	 *
	 * @since    0.1.0
	 */
	public static function deactivate() {
		$url = 'https://account.stan-app.fr/account/pkcg94c5ggj9n4aycr7gnvnmhrkctr/integrations/notify';

		$body = array(
			'website' => site_url(),
			'source' => 'stan-checkout',
			'stack' => 'wordpress',
			'is_active' => false
		);

		$headers = array(
			'Content-Type' => 'application/json',
			'Authorization' => 'ApiKey xjGc42kfJxTZtR4KGeBUnN4H34V5HwBa3U'
		);

		wp_remote_post( $url, array(
			'body' => wp_json_encode( $body ),
			'headers' => $headers
		));
	}

}
