<?php

namespace StanCheckout;

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes/classes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */

defined( 'ABSPATH' ) || exit;

/**
 * WcStanCheckoutDB Class.
 */
class WcStanCheckoutDB {

	/**
	 * @var object The single instance of WcStanCheckoutDB
	 */
	private static $_instance;

	/**
	 * @var string Stan Checkout session table name
	 */
	protected $_session_table;

	/**
	 * Get the instance and use the functions inside it.
	 *
	 * This plugin utilises the PHP singleton design pattern.
	 *
	 * @return object self::$_instance Instance
	 *
	 * @since     1.0.0
	 * @static
	 * @access    public
	 *
	 */
	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wc-stan-checkout' ), '1.0' );
	}

	/**
	 * Disable Unserialize of the class.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function __wakeup() {
		// Unserialize instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wc-stan-checkout' ), '1.0' );
	}

	/**
	 * Reset the instance of the class
	 *
	 * @since  1.2.8
	 * @access public
	 */
	public static function reset() {
		self::$_instance = null;
	}

	/**
	 * Constructor for this class.
	 */
	public function __construct() {
		self::$_instance = $this;
		$this->init();
	}

	/**
	 * Init WcStanCheckoutDB class.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function init() {
		$this->_session_table              = $GLOBALS['wpdb']->prefix . 'woocommerce_stan_checkout_sessions';
	}

	/**
	 * Returns session data.
	 *
	 * @param string $session_key Session key.
	 * @param mixed $default Default value to return if the session does not exist.
	 *                             If an empty $session_key provided, just ignore any default value and return false
	 *
	 * @return string|array|boolean
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function get_session( $session_key, $default = false ) {
		if ( empty( $session_key ) ) {
			return false;
		}

		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "SELECT session_value FROM {$this->_session_table} WHERE session_key = %s", $session_key ) );

		if ( is_null( $value ) ) {
			$value = get_option( $session_key, $default ); // Backwards compatibility
		}

		return maybe_unserialize( $value );
	}

	/**
	 * Updates the session data if the session key exists and the session value changes,
	 * if the session key does not exist, then it will be added with the session value.
	 *
	 * @param string $session_key Session key.
	 * @param mixed $session_value Session value.
	 *
	 * @return bool  False if session is not updated and true if session is updated.
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function insert_or_update_session( $session_key, $session_value ) {
		if ( empty( $session_key ) ) {
			return false;
		}

		global $wpdb;

		// Update a session row in table if it exists or insert a session row into table if the row does not already exist.
		$result = $wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->_session_table} (`session_key`, `session_value`, `created_at`, `updated_at`) VALUES (%s, %s, %d, %d)
				ON DUPLICATE KEY UPDATE `session_value` = VALUES(`session_value`), `updated_at` = VALUES(`updated_at`)",
				$session_key,
				maybe_serialize( $session_value ),
				time(),
				time()
			)
		);

		return ( $result !== false );
	}

	/**
	 * Update field update_at for session row to prevent its deleting due cleanup
	 * Do nothing if key doesn't exist
	 *
	 * @param string $session_key Session key.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function update_session_time( $session_key ) {
		if ( empty( $session_key ) ) {
			return;
		}

		global $wpdb;

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->_session_table} set `updated_at`=%d where `session_key`=%s",
				time(),
				$session_key
			)
		);
	}

	/**
	 * Deletes a session from the database.
	 *
	 * @param string $session_key Session key.
	 *
	 * @return bool  False if session is not deleted and true if session is deleted.
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function delete_session( $session_key ) {
		if ( empty( $session_key ) ) {
			return false;
		}

		global $wpdb;

		$result = $wpdb->delete(
			$this->_session_table,
			array(
				'session_key' => $session_key,
			),
			array(
				'%s',
			)
		);

		return ( $result !== false );
	}

	/**
	 * Insert the session data
	 * if the session key is already exist, then return false.
	 *
	 * @param string $session_key Session key.
	 * @param mixed $session_value Session value.
	 *
	 * @return bool  True if session is inserted and false if not
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function insert_session( $session_key, $session_value ) {
		if ( empty( $session_key ) ) {
			return false;
		}

		global $wpdb;

		$result = $wpdb->insert(
			$this->_session_table,
			array(
				'session_key'   => $session_key,
				'session_value' => maybe_serialize( $session_value ),
				'created_at'    => time(),
				'updated_at'    => time()
			),
			array(
				'%s',
				'%s',
				'%d',
				'%d'
			)
		);

		return ( $result !== false );
	}

	/**
	 * Return time when the session data was created.
	 *
	 * @param string $session_key Session key.
	 *
	 * @return integer|false
	 * @since  1.0.0
	 * @access public
	 *
	 */
	public function get_session_created_at_time( $session_key ) {
		if ( empty( $session_key ) ) {
			return false;
		}

		global $wpdb;

		$value = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM {$this->_session_table} WHERE session_key = %s", $session_key ) );

		return $value;
	}

	/**
	 * Get Stan sessions schema Table schema.
	 *
	 * @return string
	 * @since 1.0.0
	 * @static
	 * @access private
	 *
	 */
	public function get_stan_sessions_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE IF NOT EXISTS {$wpdb->prefix}woocommerce_stan_checkout_sessions (
  ID BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  session_key varchar(191) NOT NULL,
  session_value longtext NOT NULL,
  created_at BIGINT UNSIGNED NOT NULL,
  updated_at BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY  (session_key),
  UNIQUE KEY ID (ID)
) $collate;
		";

		return $tables;
	}

	/**
	 * Sets up the database tables which the plugin needs to function.
	 *
	 * @since 1.0.0
	 * @static
	 * @access public
	 */
	public function create_stan_sessions_table() {
		/**
		 *
		 * Tables:
		 *      woocommerce_stan_checkout_sessions - Table for storing various sessions in Stan plugin
		 */
		global $wpdb;
		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( wc_stan_db()->get_stan_sessions_schema() );
	}

}

/**
 * Returns the instance of WcStanCheckoutDB to use globally.
 *
 * @return WcStanCheckoutDB
 * @since  1.0.0
 *
 */
function wc_stan_db() {
	return WcStanCheckoutDB::get_instance();
}

?>