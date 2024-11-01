<?php

namespace StanCheckout;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://compte.stan-app.fr
 * @since             1.0.0
 * @package           WcStanCheckout
 *
 * @wordpress-plugin
 * Plugin Name:       Stan Xpress Checkout
 * Plugin URI:        https://compte.stan-app.fr
 * Description:       Vous perdez des utilisateurs lorsque vous demandez de s'inscrire, remplir les formulaires est la première raison qui mène les utilisateurs à quitter un site. Avec Stan Checkout vos utilisateurs s'inscrivent sans formulaire, sans contrainte.
 * Version:           1.3.5
 * Author:            Brightweb
 * Author URI:        https://stan-business.fr
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       wc-stan-checkout
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! defined( 'WOO_STAN_CHECKOUT_PLUGIN_DIR' ) ) {
	define( 'WOO_STAN_CHECKOUT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'WOO_STAN_CHECKOUT_PLUGIN_URL' ) ) {
	define( 'WOO_STAN_CHECKOUT_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
}
if ( ! defined( 'WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE' ) ) {
	define( 'WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE', WOO_STAN_CHECKOUT_PLUGIN_DIR . 'includes' );
}

const STAN_PLUGIN_NAME = 'wc-stan-checkout';
const STAN_SETTINGS_OPTION_NAME = 'woocommerce_wc-stan-checkout-gateway_settings';

/**
 * WooCommerce fallback notice.
 *
 * @since 1.0.0
 * @return string
 */
function woocommerce_stan_checkout_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Stan Checkout requires WooCommerce to be installed and active. You can download %s here.', 'wc-stan-checkout' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

/**
 * WooCommerce not supported fallback notice.
 *
 * @since 1.0.0
 * @return string
 */
function woocommerce_stan_checkout_wc_not_supported() {
	/* translators: $1. Current WooCommerce version. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'Stan Checkout doesn\'t support current WooCommerce version.', 'wc-stan-checkout' ) ) . '</strong></p></div>';
}

/**
 * Currently plugin version.
 * Start at version 0.1.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WOO_STAN_CHECKOUT_VERSION', '1.0.0' );

/**
 * Detects if WP Rest API is activated
 */
define( 'WOO_STAN_CHECKOUT_WP_REST_API_ADDON', ( has_action( 'init', 'json_api_init' ) && defined( 'JSON_API_VERSION' ) ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/classes/class-wc-stan-checkout-activator.php
 */
function activate_wc_stan_checkout() {
	require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-activator.php';
	WcStanCheckoutActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/classes/class-wc-stan-checkout-deactivator.php
 */
function deactivate_wc_stan_checkout() {
	require_once WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout-deactivator.php';
	WcStanCheckoutDeactivator::deactivate();
}

register_activation_hook( __FILE__, 'StanCheckout\activate_wc_stan_checkout' );
register_deactivation_hook( __FILE__, 'StanCheckout\deactivate_wc_stan_checkout' );

add_action( 'plugins_loaded', 'StanCheckout\run_wc_stan_checkout' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wc_stan_checkout() {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}

	if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
        add_action(
            'admin_notices',
            function() {
                /* translators: 1. URL link. */
                echo '<div class="error"><p><strong>' . sprintf( esc_html__( "Stan Checkout nécessite l'extension WooCommerce pour être actif et fonctionnel. Vous pouvez télécharger Woocommerce ici %s.", 'wc-stan-checkout' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
            }
        );

		return;
	} else {
		add_action( 'before_woocommerce_init', 'StanCheckout\check_wc_stan_compatibility' );
	}

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and public-facing site hooks.
	 */
	require WOO_STAN_CHECKOUT_PLUGIN_DIR_INCLUDE . '/classes/class-wc-stan-checkout.php';

	wc_stan()->run();
}

/**
 * Declare incompatible if blocks are used
 * 
 * @since 1.0.1
 */
function check_wc_stan_compatibility() {
	$checkout_page_id = wc_get_page_id( 'checkout' );

	if ( has_block( 'woocommerce/checkout', $checkout_page_id ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', plugin_dir_path( __FILE__ ), false );
	}
}

// Handlers multiple names
add_filter( 'plugin_action_links_wc-stan-checkout/wc-stan-checkout.php', 'StanCheckout\display_stan_payment_gateway_settings_link' );
function display_stan_payment_gateway_settings_link( $links ) {
	$url = esc_url( add_query_arg(
		array(
			'page' => 'wc-settings',
			'tab' => 'checkout',
			'section' => 'wc-stan-checkout-gateway',
		),
		get_admin_url() . 'admin.php'
	) );

	$settings_link = "<a href='$url'>Configurer</a>";

	array_unshift(
		$links,
		$settings_link
	);

	return $links;
}

/**
 * Main instance of WooCommerce Stan Payment Gateway.
 *
 *
 * @return WcStanCheckout
 * @since  1.0.0
 */
function wc_stan() {
	return WcStanCheckout::get_instance();
}
