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
class WcStanCheckoutButton {

	/**
	 * @param $client_wrapper
	 */
	public function __construct(){
		$this->init();
	}

	/**
	 * Initiates the button configurations.
	 *
	 * @param $settings
	 * @param $client_wrapper
	 */
	public function init() {
		add_shortcode( STAN_BUTTON_SHORTCODE, array( $this, 'init_stan_checkout_button' ) );
		add_action( 'woocommerce_before_checkout_billing_form', array( $this, 'display_stan_checkout_button_in_checkout' ), 5 );
		add_action( 'woocommerce_before_cart_totals', array( $this, 'display_stan_checkout_button_in_cart_page' ), 5 );
		add_action( 'woocommerce_widget_shopping_cart_buttons', array( $this, 'display_stan_checkout_button_in_mini_cart_page' ), 10 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'display_stan_checkout_button_in_product_page' ), 10 );
	}

	/**
	 * Displays an error message to the user
	 *
	 * @param $error_code
	 *
	 * @return string
	 */
	public function make_error_output( $error_code, $error_message ) {
		ob_start();

		?>
		<div id="login_error">
			<strong><?php echo 'Erreur de connexion'; ?>: </strong>
			<?php print esc_html($error_message); ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Display Stan Checkout button in Woocommerce checkout page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_stan_checkout_button_in_checkout() {
		echo do_shortcode('[' . STAN_BUTTON_SHORTCODE . ' centered="true"]');
		echo '<h5 class="stan-checkout-text">—&nbsp;Ou&nbsp;compléter&nbsp;manuellement&nbsp;—</h5>';
		echo '<br />';
    }

	/**
	 * Display Stan Checkout button in Woocommerce cart page.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_stan_checkout_button_in_cart_page() {
		echo do_shortcode('[' . STAN_BUTTON_SHORTCODE . ' to-right="true"]');
    }

	/**
	 * Display Stan Checkout button in Woocommerce mini cart page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function display_stan_checkout_button_in_mini_cart_page() {
		echo do_shortcode('[' . STAN_BUTTON_SHORTCODE . ' centered="true"]');
	}

	/**
	 * Display Stan Checkout button in product page
	 * 
	 * @since 1.0.3
	 * @return void
	 */
	public function display_stan_checkout_button_in_product_page() {
		$product_id = get_the_ID();

		echo do_shortcode('[' . STAN_BUTTON_SHORTCODE . ' full-width="true" product-id="' . $product_id . '"]');
	}

	/**
	 * Creates a login button
	 *
	 * @return string
	 */
	public function init_stan_checkout_button( $attrs ) {
		$stan_session_id = wp_generate_uuid4();

		if ( ! isset( $_COOKIE[ STAN_SESSION_ID_COOKIE ] ) ) {
			wc_setcookie( STAN_SESSION_ID_COOKIE, $stan_session_id, 0, true, true );
		} else {
			$stan_session_id = $_COOKIE[ STAN_SESSION_ID_COOKIE ];
		}

		if ( WC()->cart ) {
			WC()->cart->maybe_set_cart_cookies();
			WC()->session->set( STAN_SESSION_USER_ID, get_current_user_id() );
			
			save_session( esc_html( sanitize_text_field( $stan_session_id ) ) );
		}

		$text = __( 'Achat Xpress', 'wc-stan-checkout' );
		if ( wc_stan()->get_settings_instance()->is_testmode() ) {
			$text .= __( ' (mode test)', 'wc-stan-checkout' );
		}
		
		$btn_class = 'stan-checkout--button' . (isset( $attrs['centered'] ) ? ' stan-checkout--centered' : '' );
		$btn_class .= (isset( $attrs['to-right'] ) ? ' stan-checkout--to-right' : '' );
		$btn_class .= (isset( $attrs['full-width'] ) ? ' stan-checkout--full-width' : '' );

		$product_attr = isset( $attrs['product-id'] ) ? 'data-product=' . $attrs['product-id'] : '';
		
		$logs_link = WP_DEBUG ? '<a class="stan-checkout--error-debug-link" href="' . admin_url('admin.php?page=wc-status&tab=logs') . '" target="_blank">' . __( 'DEBUG : Voir les logs', 'wc-stan-checkout' ) . '</a>' : '';

		ob_start();

		?>
			<div id="stan-checkout">
				<button type="button" class="<?php print esc_html( $btn_class ) ?>" <?php print esc_html( $product_attr ) ?> >
					<span class="stan-checkout--button-text">
						<?php print esc_html( $text ); ?>
					</span>
				</button>
				<div class="stan-checkout--error">
					<p class="stan-checkout--error-text">
						<?php print esc_html__( "Impossible de procéder à l'achat express pour cette commande. Merci de nous contacter !", 'wc-stan-checkout' ) ?>
						<?php print $logs_link ?>
					</p>
				</div>
			</div>
		<?php

		return ob_get_clean();
	}
}