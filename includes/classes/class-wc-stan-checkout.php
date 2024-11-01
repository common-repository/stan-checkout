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
 * @subpackage WcStanCheckout/includes/classes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */
class WcStanCheckout {

	/**
	 * Holds the instance
	 *
	 * @var object
	 * @static
	 */
	private static $instance;

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WcStanCheckoutLoader    $loader    Maintains and registers all hooks for the plugin.
	 */
	private static $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	private static $plugin_name = STAN_PLUGIN_NAME;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	private static $version = WOO_STAN_CHECKOUT_VERSION;

	/**
	 * Notices (array).
	 *
	 * @var array
	 */
	public static $notices = array();

	/**
	 * WooCommerce Stan Payment Gateway Admin Object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var    WcStanCheckoutAdmin object.
	 */
	public $plugin_admin;

	/**
	 * WooCommerce Stan Payment Gateway Frontend Object.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var    WcStanCheckoutPublic object.
	 */
	public $plugin_public;

	/**
	 * WooCommerce Stan i18n.
	 *
	 * @since  1.0
	 * @access public
	 *
	 * @var    WcStanCheckoutI18n object.
	 */
	public $plugin_i18n;

	/**
	 * Get the instance and store the class inside it. This plugin utilises
	 * the PHP singleton design pattern.
	 *
	 * @return object self::$instance Instance
	 * @see       WcStanCheckout();
	 *
	 * @uses      WcStanCheckout::init_hooks() Setup hooks and actions.
	 * @uses      WcStanCheckout::includes() Loads all the classes.
	 *
	 * @since     1.0.0
	 * @static
	 * @staticvar array $instance
	 * @access    public
	 *
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof WcStanCheckout ) ) {
			self::$instance = new WcStanCheckout();

			self::$instance->load_dependencies();
			self::$instance->install();
			self::$instance->set_locale();
			self::$instance->define_admin_hooks();
			self::$instance->define_public_hooks();
			self::$instance->define_hooks();

			new WcStanCheckoutButton();
		}

		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access protected
	 *
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wc-stan-checkout' ), '1.0' );
	}

	/**
	 * Disable Unserialize of the class.
	 *
	 * @return void
	 * @since  1.0.0
	 * @access protected
	 *
	 */
	public function __wakeup() {
		// Unserialize instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin&#8217; huh?', 'wc-stan-checkout' ), '1.0' );
	}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		self::$instance = $this;
	}

	/**
	 * Reset the instance of the class
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public static function reset() {
		self::$instance = null;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WcStanCheckoutLoader. Orchestrates the hooks of the plugin.
	 * - WcStanCheckoutI18n. Defines internationalization functionality.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		/**
		 * Load Stan Checkout constants
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/wc-stan-constant.php';

		/**
		 * Stan utils
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/wc-stan-utils.php';

		/**
		 * Load Stan admin settings
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-settings.php';

		/**
		 * For exceptions
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-exception.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-loader.php';

		/**
		 * Traits
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/traits/trait-wc-stan-checkout-endpoint.php';
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/traits/trait-wc-stan-checkout-customer.php';
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/traits/trait-wc-stan-checkout-order.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-i18n.php';

		/**
		 * The class responsible for logs.
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-logger.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/admin/class-wc-stan-checkout-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-public.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-payment-gateway.php';

		/**
		 * The class responsible for handling Stan api requests for checkout infos
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-api.php';

		/**
		 * The class responsible for handling Stan api requests for shipping infos
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-shipping-api.php';

		/**
		 * Order API
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-order-api.php';

		/**
		 * Stan checkout button
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-button.php';

		/**
		 * Stan checkout handler for checkouts
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-cart-api.php';

		/**
		 * Stan db
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-db.php';

		/**
		 * Handles payment callback & webhook
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-payment-api.php';

		/**
		 * Handles Stan webhooks call
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-webhook-api.php';

		/**
		 * The class responsible for providing helpers in stan api usage
		 */
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-api.php';
		require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-api-wrapper.php';

		self::$loader = new WcStanCheckoutLoader();
	}

	public function install() {
		wc_stan_db()->create_stan_sessions_table();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WcStanCheckoutI18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		self::$instance->plugin_i18n = new WcStanCheckoutI18n();

		self::$loader->add_action( 'plugins_loaded', self::$instance->plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		self::$instance->plugin_admin = new WcStanCheckoutAdmin( self::$plugin_name, self::$version );

		self::$loader->add_action( 'admin_enqueue_scripts', self::$instance->plugin_admin, 'enqueue_styles' );
        self::$loader->add_action( 'admin_enqueue_scripts', self::$instance->plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		self::$instance->plugin_public = new WcStanCheckoutPublic( self::$plugin_name, self::$version );

		self::$loader->add_action( 'wp_enqueue_scripts', self::$instance->plugin_public, 'enqueue_styles' );
		self::$loader->add_action( 'wp_enqueue_scripts', self::$instance->plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Get WcStanCheckoutSettings instance.
	 *
	 * @return WcStanCheckoutSettings
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function get_settings_instance() {
		return WcStanCheckoutSettings::instance();
	}

	/**
	 * Get WcStanCheckoutAPI instance.
	 * 
	 * @return WcStanCheckoutAPI
	 * @since 1.0.0
	 * @access public
	 */
	public function get_api() {
		require_once( WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-api.php' );
		return WcStanCheckoutAPI::instance();
	}

	/**
	 * @since 1.0.0
	 * @access private
	 */
	private function define_hooks() {
		add_filter( 'woocommerce_payment_gateways', array( $this, 'register_stan_payment_gateway' ) );
		add_action( 'init', array( $this, 'init_user_session' ) );
	}

	public function init_user_session() {
		if ( ! isset( $_COOKIE[ STAN_SESSION_ID_COOKIE ] ) && empty( get_session_id_from_header() ) ) {
			wc_setcookie( STAN_SESSION_ID_COOKIE, wp_generate_uuid4(), 0, true, true );
		}
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public static function run() {
		self::$loader->run();
    }

	/**
	 * Add the Gateway to WooCommerce
	 *
	 * @param $methods
	 *
	 * @return array
	 **/
	public function register_stan_payment_gateway( $methods ) {
		// Load Stan Payment Gateway Class.
		require_once( WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-payment-gateway.php' );
		$methods[] = '\StanCheckout\WcStanCheckoutPaymentGateway';

		return $methods;
	}
}
