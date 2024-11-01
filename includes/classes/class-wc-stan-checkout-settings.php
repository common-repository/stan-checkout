<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WcStanCheckoutSettings
 *
 */
class WcStanCheckoutSettings {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var WcStanCheckoutSettings|null
	 */
	private static $instance = null;

	/**
	 * @since 1.0.0
	 * @var array Stan Checkout settings.
	 */
	private $settings;

	/**
	 * Gets WcStanCheckoutSettings Instance.
	 *
	 * @return WcStanCheckoutSettings Instance
	 * @since 1.0.0
	 * @static
	 *
	 */
	// setting names
	const SETTING_NAME_ENABLED = 'enabled';
	const SETTING_NAME_TESTMODE = 'testmode';
	const SETTING_CLIENT_ID = 'client_id';
	const SETTING_CLIENT_SECRET = 'client_secret';
	const SETTING_CLIENT_TEST_ID = 'client_test_id';
	const SETTING_CLIENT_TEST_SECRET = 'client_test_secret';

	// setting key names
	const KEY_TITLE = 'title';
	const KEY_TYPE = 'type';
	const KEY_LABEL = 'label';
	const KEY_CLASS = 'class';
	const KEY_DESCRIPTION = 'description';
	const KEY_DESC_TIP = 'desc_tip';
	const KEY_OPTIONS = 'options';
	const KEY_PLACEHOLDER = 'placeholder';
	const KEY_DISABLED = 'disabled';
	const KEY_CSS = 'css';
	const KEY_CUSTOM_ATTRIBUTES = 'custom_attributes';
	const KEY_DEFAULT = 'default';

	// field type names
	const TYPE_CHECKBOX = 'checkbox';
	const TYPE_TITLE = 'title';
	const TYPE_TEXT = 'text';
	const TYPE_SELECT = 'select';

	// values
	const VALUE_YES = 'yes';
	const VALUE_NO = 'no';

	// urls
	const STAN_API_HOST_PROD = 'https://api.stan-app.fr';

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * WcStanCheckoutSettings constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->read_settings();
		add_action( 'update_option_' . STAN_SETTINGS_OPTION_NAME, array( $this, 'after_settings_update' ), 10, 3 );
	}

	/**
	 * Read settings from database.
	 *
	 * @since 1.0.0
	 */
	public function read_settings() {
		$settings       = get_option( STAN_SETTINGS_OPTION_NAME, array() );
		$this->settings = array_merge( $this->get_default_settings(), $settings );
	}

	/**
	 * Return settings
	 *
	 * @since 1.0.0
	 */
	public function get_settings() {
		return $this->settings;
	}

	/**
	 * Generate default settings by Form Fields array
	 *
	 * @since 1.0.0
	 */
	public function get_default_settings() {
		return array(
			self::SETTING_NAME_ENABLED => self::VALUE_YES,
			self::SETTING_NAME_TESTMODE => self::VALUE_YES,
			self::SETTING_CLIENT_ID => '',
			self::SETTING_CLIENT_SECRET => '',
			self::SETTING_CLIENT_TEST_ID => '',
			self::SETTING_CLIENT_TEST_SECRET => '',
		);
	}

	/**
	 * Return list of hidden settings
	 *
	 * @since 1.0.0
	 */
	public function get_hidden_settings() {
		return array();
	}

	public function get_disable_settings() {
		return array();
	}

	/**
	 * Create array with Form Fields for class WcStanCheckoutPaymentGateway
	 *
	 * @since 1.0.0
	 */
	public function get_form_fields() {

		/**
		 * Settings for Stan Checkout plugin.
		 */
		$form_fields = array(
			self::SETTING_NAME_ENABLED => array(
				self::KEY_TITLE => __( 'Etat', 'wc-stan-checkout' ),
				self::KEY_TYPE  => self::TYPE_CHECKBOX,
				self::KEY_LABEL => __( 'Activer Stan Checkout', 'wc-stan-checkout' ),
			),
			self::SETTING_NAME_TESTMODE => array(
				self::KEY_TITLE => __( 'Testmode', 'wc-stan-checkout' ),
				self::KEY_TYPE  => self::TYPE_CHECKBOX,
				self::KEY_LABEL => __( 'Activer le mode test', 'wc-stan-checkout' ),
			),
			self::SETTING_CLIENT_ID => array(
				self::KEY_TITLE => __( 'Identifiant du client', 'wc-stan-checkout' ),
				self::KEY_TYPE => self::TYPE_TEXT,
				self::KEY_DESCRIPTION => __( "Il s'agit de votre identifiant client, il vous a été transmis par email lors de votre inscription. Si vous n'en avez pas ou avez perdu votre code, rendez-vous sur <a href='https://compte.stan-app.fr/signup' target='_blank'>stan-app.fr</a>.", 'wc-stan-checkout' ),
				self::KEY_DESC_TIP    => true,
			),
			self::SETTING_CLIENT_SECRET => array(
				self::KEY_TITLE => __( 'Code secret du client', 'wc-stan-checkout' ),
				self::KEY_TYPE => self::TYPE_TEXT,
				self::KEY_DESCRIPTION => __(  "Il s'agit du code secret associé à votre identifiant client, il vous a été transmis par email lors de votre inscription. Si vous n'en avez pas ou avez perdu votre code, rendez-vous sur <a href='https://compte.stan-app.fr/signup' target='_blank'>stan-app.fr</a>.", 'wc-stan-checkout' ),
				self::KEY_DESC_TIP    => true,
			),
			self::SETTING_CLIENT_TEST_ID => array(
				self::KEY_TITLE => __( 'Identifiant du client TEST', 'wc-stan-checkout' ),
				self::KEY_TYPE => self::TYPE_TEXT,
				self::KEY_DESCRIPTION => __(  "Il s'agit de votre identifiant client en MODE TEST, il vous a été transmis par email lors de votre inscription. Si vous n'en avez pas ou avez perdu votre code, rendez-vous sur <a href='https://compte.stan-app.fr/signup' target='_blank'>stan-app.fr</a>.", 'wc-stan-checkout' ),
				self::KEY_DESC_TIP    => true,
			),
			self::SETTING_CLIENT_TEST_SECRET => array(
				self::KEY_TITLE => __( 'Code secret du client TEST', 'wc-stan-checkout' ),
				self::KEY_TYPE => self::TYPE_TEXT,
				self::KEY_DESCRIPTION => __(  "Il s'agit du code secret de test associé à votre identifiant client, il vous a été transmis par email lors de votre inscription. Si vous n'en avez pas ou avez perdu votre code, rendez-vous sur <a href='https://compte.stan-app.fr/signup' target='_blank'>stan-app.fr</a>.", 'wc-stan-checkout' ),
				self::KEY_DESC_TIP    => true,
			)
		);
		foreach ( $this->get_default_settings() as $setting_name => $default_value ) {
			$form_fields[ $setting_name ][ self::KEY_DEFAULT ] = $this->settings ? $this->settings[ $setting_name ] : $default_value;
		}
		foreach ( $this->get_hidden_settings() as $setting_name ) {
			if ( empty( $this->settings[ $setting_name ] ) ) {
				unset( $form_fields[ $setting_name ] );
			}
		}
		foreach ( $this->get_disable_settings() as $setting_name ) {
			unset( $form_fields[ $setting_name ] );
		}

		return $form_fields;
	}

	/**
	 * Check if testmode or not.
	 *
	 * @return bool
	 * @since 1.0.0
	 */
	public function is_testmode() {
		return ( self::VALUE_YES === $this->settings[ self::SETTING_NAME_TESTMODE ] );
	}

	public function get_hmac_secret() {
		if ( $this->is_testmode() ) {
			return $this->settings[ self::SETTING_CLIENT_TEST_SECRET ];
		}
		return $this->settings[ self::SETTING_CLIENT_SECRET ];
	}

	/**
	 * Check if setting is enabled
	 *
	 * @param string $setting_name
	 *
	 * @return bool
	 * @since 1.0.0
	 *
	 */
	public function is_setting_enabled( $setting_name ) {
		return self::VALUE_YES === $this->settings[ $setting_name ];
	}

	/**
	 * Actions after updating settings
	 * We need to reload settings as well as update feature switches if necessary
	 *
	 * @param $old_value
	 * @param $value
	 * @param $option
	 */
	public function after_settings_update( $old_value, $value, $option ) {
		$old_settings = $this->settings;
		$this->read_settings();
	}

	/**
	 * Returns setting value if it doesn't empty and default value otherwise
	 * Removes trailing slash from the default value
	 *
	 * @param $setting_name
	 * @param $default
	 *
	 * @return string
	 * @since 1.0.0
	 *
	 */
	private function get_custom_url_value_or_default( $setting_name, $default ) {
		$setting_value = $this->settings[ $setting_name ];

		return ! empty( $setting_value ) ? rtrim( $setting_value, '/' ) : rtrim( $default );
	}

	/**
	 * Get API host
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public function get_api_host() {
		if ( defined( 'WOO_STAN_CHECKOUT_CUSTOM_HOST' ) && WP_DEBUG ) {
			WcStanCheckoutLogger::log( 'Using Custom API HOST ' . WOO_STAN_CHECKOUT_CUSTOM_HOST );
			return WOO_STAN_CHECKOUT_CUSTOM_HOST;
		} 
		return self::STAN_API_HOST_PROD;
	}
}
