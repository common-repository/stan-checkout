<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://compte.stan-app.fr
 * @since      1.0.0
 *
 * @package    WcStanCheckout
 * @subpackage WcStanCheckout/includes
 */


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
 * @subpackage WcStanCheckout/includes
 * @author     Brightweb <jonathan@brightweb.cloud>
 */
class WcStanCheckoutPaymentGateway extends \WC_Payment_Gateway {

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version = WOO_STAN_CHECKOUT_VERSION;

    /**
	 * Plugin settings
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WcStanCheckoutSettings    $settings    The settings
	 */
    public $settings;

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
        // Load the settings.
		$this->init_form_fields();
		$this->init_settings();
        
		$this->id = STAN_CHECKOUT_GATEWAY_NAME;
		$this->icon = '';
		$this->has_fields = false;
		$this->method_title = 'Paiement avec Stan';
		$this->method_description = 'Extension de Stan Xpress Checkout pour WooCommerce';

		$this->supports = array(
			'products'
		);

		$this->title = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
        $this->enabled = $this->get_option( 'enabled' );
        $this->settings = wc_stan()->get_settings_instance()->get_settings();

		add_action( 'woocommerce_api_' . $this->id, array( $this, 'handle_notify_order' ) );

        add_filter( 'woocommerce_gateway_title', [ $this, 'filter_gateway_title' ], 10, 2 );

        // Save the gateway options.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options',
		) );

        add_filter( 'woocommerce_available_payment_gateways', array( $this, 'remove_stan_pay' ) );
	}

    /**
     * Removes Stan Pay from payment methods
     * 
     * @since 1.0.0
     */
    public function remove_stan_pay( $available_gateways ) {
        unset( $available_gateways[ STAN_CHECKOUT_GATEWAY_NAME ] );
        return $available_gateways;
    }

    /**
	 * Initiate Form Fields.
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		$this->form_fields = wc_stan()->get_settings_instance()->get_form_fields();
	}

    /**
     * Filter for the payment title
     * It update the payment title in case the plugin is in test mode
     * 
     * @since 1.3.0
     */
    public function filter_gateway_title( $title, $id ) {
        if (wc_stan()->get_settings_instance()->is_testmode() && $id === STAN_CHECKOUT_GATEWAY_NAME) {
            return '(MODE TEST) Tester Paiement avec Stan';
        }
        return $title;
    }

	/**
	 * Validates that the order meets the minimum order amount for Stan
	 *
	 * @since 1.0.0
	 * @param object $order
	 */
	public function validate_minimum_order_amount( $order ) {
        if ( $order->get_total() * 100 < get_minimum_amount() ) {
            throw new StanCheckout\WcStanCheckoutException( 'Did not meet minimum amount', sprintf( __( 'Le montant minimum pour payer avec Stan est de %s', 'wc-stan-checkout' ), wc_price( WC_Stan_Payment_Gateway_Helper::get_minimum_amount() / 100 ) ) );
		}
	}

    /**
	 * Validates that the order meets the minimum order amount for Stan
	 *
	 * @since 1.0.0
	 * @param object $order
	 */
	public function validate_maximum_order_amount( $order ) {
		if ( get_maximum_amount() > 0 && $order->get_total() * 100 > get_maximum_amount() ) {
			throw new StanCheckout\WcStanCheckoutException( 'Did not meet maximum amount', sprintf( __( 'Le montant maximum pour payer avec Stan est de %s', 'wc-stan-checkout' ), wc_price( WC_Stan_Payment_Gateway_Helper::get_maximum_amount() / 100 ) ) );
		}
	}
    
    /**
	 * Process payments.
	 *
	 * @param int $order_id Order ID
     * @since 1.0.0
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		if ( ! is_ssl() ) {
            if ( ! wc_stan()->get_settings_instance()->is_testmode() && (function_exists( "wp_get_environment_type" ) && wp_get_environment_type() != "local" ) ) {
                StanCheckout\WcStanCheckoutLogger::log( 'During payment in checkout. Your website must be in https (using SSL)' );
				wc_add_notice( "Le site nécessite d'être en HTTPS pour votre sécurité", 'error' );
				return;
			}
			wc_add_notice( 'Votre site doit être en HTTPS (SSL) pour la sécurité de vos clients. Vous pouvez continuer à tester en MODE TEST, cette erreur empêchera de faire des paiements avec Stan lorsque vous désactiverez le MODE TEST.', 'notice' );
        }

		$order = wc_get_order( $order_id );

        $client = new StanCheckout\WcStanCheckoutAPIWrapper();

		try {
            // Those 2 will throw exception if not valid.
			$this->validate_minimum_order_amount( $order );
			$this->validate_maximum_order_amount( $order );

            // TODO should be an option to add customer payment
            // Not required in testmode
            $customer_id = null;
            if ( ! wc_stan()->get_settings_instance()->is_testmode() ) {
                $customer = $client->create_customer_with_order( $order );
                $customer_id = $customer->id;
            }

            $amount = convert_monetary_value_to_integer( intval( $order->get_total() ) );

            $payment = $client->legacy_create_payment(
                strval( $order_id ),
                $amount,
                get_site_url() . '/?wc-api=WC_Stan_Payment',
                $customer_id
            );

            $order->set_transaction_id( $payment->payment_id );
            
            $order->set_status( 'wc-pending' );

			$payload = array(
                'subtotal_amount' => $order->get_subtotal(),
				'total_amount' => $order->get_total(),
				'shipping_amount' => $order->get_shipping_total(),
				'discount_amount' => $order->get_discount_total(),
				'vat_amount' => $order->get_total_tax(),
                'payment_id' => $payment->payment_id
			);

            $q = http_build_query( $payload );

			$order->add_meta_data( "wc_stan_payment_id", $payment->payment_id );

            $order->save();

			return array(
				'result' => 'success',
				'redirect' => $payment->redirect_url . '&' . $q
			);
		} catch (StanCheckout\WcStanCheckoutException $e) {
            wc_add_notice( $e->getLocalizedMessage(), 'error' );
			StanCheckout\WcStanCheckoutLogger::log( 'Error: ' . $e->getMessage() );

			return array(
				'result' => 'fail',
				'redirect' => '',
			);
		}
    }

    /**
	 * Webhook after the payment has been processed by Stan
	 *
     * @since     1.0.0
	 *
	 * @return array
	 */
	public function handle_notify_order() {
		$client = new WcStanCheckoutAPIWrapper();

        WcStanCheckoutLogger::log( 'Received Stan Callback' );

        try {
            $payment_id = esc_html( sanitize_text_field( $_GET[ 'payment_id' ] ) );

            $payment = $client->get_payment( $payment_id  );

            $order = wc_get_order( intval( $payment->order_id ) );

            if ( !$order ) {
                WcStanCheckoutLogger::log( 'Order ' . $payment->order_id . ' not found during callback order' );
                return wp_redirect( wc_get_cart_url() ); 
            }

            WcStanCheckoutPayment::check_payment_and_update_order( $order, array(
                "payment_id" => $payment_id,
                "payment_status" => $payment->payment_status
            ));

            switch ($payment->payment_status) {
                case STAN_PAYMENT_STATUS_SUCCESS:
                case STAN_PAYMENT_STATUS_PENDING:
                case STAN_PAYMENT_STATUS_HOLDING:
                    return wp_redirect( $this->get_return_url( $order ) );
                case STAN_PAYMENT_STATUS_AUTH_REQUIRED:
                case STAN_PAYMENT_STATUS_EXPIRED:
                case STAN_PAYMENT_STATUS_CANCELLED:
                case STAN_PAYMENT_STATUS_PREPARED:
                case STAN_PAYMENT_STATUS_FAILURE:
                    return wp_redirect( $order->get_checkout_payment_url( false ) ); 
            }

            WcStanCheckoutLogger::log( 'Unhandled payment status, redirect to checkout page' );
            return wp_redirect( $order->get_checkout_payment_url( false ) );;
        } catch (\Exception $e) {
            WcStanCheckoutLogger::log( 'Error raised during callback order, reason: ' . "\n" . $e);
            return wp_redirect( wc_get_cart_url() );
        }
    }

    /**
	 * Processes new settings
	 *
     * @since     1.0.0
	 *
	 * @return array
	 */
    public function process_admin_options() {
		$saved = parent::process_admin_options();

		return $saved;
    }

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}
    
    public function add_refresh_order_status_action( $actions ) {
        $actions[ 'check_payment_and_update_order' ] = 'Vérifier paiement Stan';
        
        return $actions;
    }

    public function get_icon() {
        $icon = '<img src="' . WOO_STAN_CHECKOUT_PLUGIN_URL . '/public/images/stan-pay.svg" id="stan-pay" class="icon" alt="Stan payment icon" />';
        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
    }
}
