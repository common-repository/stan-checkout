<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trait StanCheckoutCustomerController
 *
 * @since 1.0.0
 */
trait StanCheckoutCustomerController {
	
	/**
	 * Create a use if not exist
	 * 
	 * @since 1.0.0
	 * @return int $user_id
	 */
	public function maybe_create_user( $email, $firstname, $lastname, $phone_number ) {
		$user_id = WC()->session->get( STAN_SESSION_USER_ID );

		if ( $user_id > 0 ) {
			return $user_id;
		}
		if ( email_exists( $email ) ) {
			WcStanCheckoutLogger::log( 'user ' . $email . ' already exist, using it instead of creating new one' );
			return get_user_by( 'email', $email )->get( 'id' );
		}
		if ( username_exists( $email ) ) {
			WcStanCheckoutLogger::log( 'username ' . $email . ' already exist, using it instead of creating new one' );
			return get_user_by( 'login', $email )->get( 'id' );
		}

		$user_data = array(
			'user_login' => $email,
			'user_pass' => wp_generate_password( 32, true, true ),
			'user_email' => $email,
			'first_name' => $firstname,
			'last_name' => $lastname,
			'role' => 'customer'
		);

		$uid = wp_insert_user( $user_data );

		update_user_meta( $uid, 'mobile', $phone_number );

		WcStanCheckoutLogger::log( 'User ' . $uid . ' created' );

		return $uid;
	}
}
