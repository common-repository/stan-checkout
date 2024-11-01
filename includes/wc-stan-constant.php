<?php

namespace StanCheckout;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// plugin
const STAN_ORDER_META_PAYMENT_ID = 'stan_payment_id';
const STAN_CHECKOUT_GATEWAY_NAME = 'wc-stan-checkout-gateway';
const STAN_BUTTON_SHORTCODE = 'stan_checkout_button';

// payment
const STAN_PAYMENT_STATUS_SUCCESS = 'payment_success';
const STAN_PAYMENT_STATUS_PENDING = 'payment_pending';
const STAN_PAYMENT_STATUS_AUTH_REQUIRED = 'payment_a_required';
const STAN_PAYMENT_STATUS_EXPIRED = 'payment_expired';
const STAN_PAYMENT_STATUS_CANCELLED = 'payment_cancelled';
const STAN_PAYMENT_STATUS_PREPARED = 'payment_prepared';
const STAN_PAYMENT_STATUS_PARTIALLY_REFUNDED = 'payment_p_refunded';
const STAN_PAYMENT_STATUS_REFUNDED = 'payment_refunded';
const STAN_PAYMENT_STATUS_FAILURE = 'payment_failure';
const STAN_PAYMENT_STATUS_HOLDING = 'payment_holding';

// shipping method
const LPC_RELAY = 'lpc_relay';
const LPC_META_PICKUP_LOCATION_ID = '_lpc_meta_pickUpLocationId';
const LPC_META_PICKUP_LOCATION_LABEL = '_lpc_meta_pickUpLocationLabel';
const LPC_META_PICKUP_PRODUCT_CODE = '_lpc_meta_pickUpProductCode';
const STAN_META_PICKUP_LOCATION_ID = '_stan_meta_relay_id';
const STAN_META_PICKUP_LOCATION_LABEL = '_stan_meta_relay_name';
const STAN_META_PICKUP_PRODUCT_CODE = '_stan_meta_relay_code';

// db
const STAN_DB_SESSION_KEY = 'session_data_';

// header & cookies & session
const STAN_SESSION_ID_COOKIE = 'stan_sessid';
const STAN_SESSION_ID_HEADER = 'X_STAN_SESSID';
const STAN_SIGNATURE_HEADER = 'X_HMAC_SIGNATURE';
const STAN_SESSION_USER_ID = 'user_id';
const STAN_SESSION_ORDER_ID = 'order_id';

// stan fields
const STAN_SHIPPING_METHOD_ID = 'shipping_method_id';
const STAN_SHIPPING_COST = 'shipping_cost';
const STAN_SHIPPING_LABEL = 'shipping_label';
const STAN_SHIPPING_RELAY_NETWORKS = 'relay_point_networks';

const STAN_CUSTOMER_FIRSTNAME = 'firstname';
const STAN_CUSTOMER_LASTNAME = 'lastname';
const STAN_CUSTOMER_EMAIL = 'email';
const STAN_CUSTOMER_PHONE = 'phone_number';
const STAN_CUSTOMER_ADDRESS = 'street_address';
const STAN_CUSTOMER_ADDRESS2 = 'street_address_line2';
const STAN_CUSTOMER_CITY = 'locality';
const STAN_CUSTOMER_POSTAL_CODE = 'zip_code';
const STAN_CUSTOMER_COUNTRY = 'country';

const STAN_FIELD_NAME_ERROR = 'error';

// woocommerce fields & enum
const WC_ADDRESS_1  = 'address_1';
const WC_ADDRESS_2  = 'address_2';
const WC_FIRST_NAME = 'first_name';
const WC_LAST_NAME  = 'last_name';
const WC_CITY       = 'city';
const WC_STATE      = 'state';
const WC_POSTCODE   = 'postcode';
const WC_COUNTRY    = 'country';
const WC_PHONE      = 'phone';
const WC_EMAIL      = 'email';
const WC_COMPANY    = 'company';
const WC_STAN_CUSTOMER_ID = 'stan_customer_id';

const WC_PAYMENT_PENDING = 'wc-pending';
const WC_PAYMENT_PROCESSING = 'wc-processing';
const WC_PAYMENT_ON_HOLD = 'wc-on-hold';
const WC_PAYMENT_COMPLETED = 'wc-completed';
const WC_PAYMENT_CANCELLED = 'wc-cancelled';
const WC_PAYMENT_REFUNDED = 'wc-refunded';
const WC_PAYMENT_FAILED = 'wc-failed';

// http
const HTTP_STATUS_OK             = 200;
const HTTP_STATUS_BAD_REQUEST 	 = 400;
const HTTP_STATUS_UNAUTHORIZED   = 401;
const HTTP_STATUS_FORBIDDEN      = 403;
const HTTP_STATUS_UNPROCESSABLE  = 422;
const HTTP_STATUS_INTERNAL_ERROR = 500;
const HTTP_STATUS_NOT_FOUND      = 404;

// notify
const STAN_EVENT_RES_SUCCESS_FIELD = 'success';
const STAN_EVENT_RES_DATA_FIELD = 'data';
const STAN_EVENT_TYPE_PAYMENT_STATUS_CHANGED = 'payment.status_changed';
const STAN_EVENT_TYPE_PAYMENT_CREATED = 'payment.created';
const STAN_EVENT_TYPE_CUSTOMER_CREATED = 'customer.created';
const STAN_EVENT_TYPE_CUSTOMER_SHIPPING_ADDRESS_CHANGED = 'customer.shipping_address_changed';
const STAN_EVENT_TYPE_CUSTOMER_AUTHENTICATED = 'customer.authenticated';
const STAN_EVENT_TYPE_CHECKOUT_LINE_ITEM_CHANGED = 'checkout.line_item_changed';
const STAN_EVENT_TYPE_CHECKOUT_SHIPPING_METHOD_CHANGED = 'checkout.shipping_method_changed';