<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Klarna_Checkout_For_WooCommerce_API class.
 *
 * Class that talks to KCO API, wrapper for V2 and V3.
 */
class Klarna_Checkout_For_WooCommerce_API {

	/**
	 * API URLs
	 * - https://api.klarna.com/
	 * - https://api-na.klarna.com/
	 * - https://api.playground.klarna.com/
	 * - https://api-na.playground.klarna.com/
	 *
	 * Checkout API
	 * - Create checkout (order)
	 * - Retrieve checkout
	 * - Update checkout
	 */

	/**
	 * Klarna Checkout api URL.
	 *
	 * @var string
	 */
	private $api_url = '';

	/**
	 * Klarna Checkout merchant ID.
	 *
	 * @var string
	 */
	private $merchant_id = '';

	/**
	 * Klarna Checkout shared secret.
	 *
	 * @var string
	 */
	private $shared_secret = '';

	/**
	 * Klarna_Checkout_For_WooCommerce_API constructor.
	 *
	 * @param string $merchant_id   Klarna merchant ID.
	 * @param string $shared_secret Klarna shared secret.
	 *
	 * @TODO: Remove default values
	 */
	public function __construct( $merchant_id = 'N100033', $shared_secret = 'riz5Aith3bii%ch9' ) {
		$this->set_api_url( 'https://api-na.playground.klarna.com/checkout/v3/orders/' );
		$this->set_merchant_id( $merchant_id );
		$this->set_shared_secret( $shared_secret );
	}

	/**
	 * Creates Klarna Checkout order.
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function request_pre_create_order() {
		$request_url = 'https://api-na.playground.klarna.com/checkout/v3/orders';
		$request_args = array(
			'headers' => $this->get_request_headers(),
			'body'    => $this->get_request_body(),
		);

		$response = wp_safe_remote_post( $request_url, $request_args );

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			$klarna_order = json_decode( $response['body'] );
			$this->save_order_id_to_session( $klarna_order->order_id );

			return $klarna_order;
		} else {
			return new WP_Error( 'Error creating Klarna order.' );
		}
	}

	/**
	 * Retrieve ongoing Klarna order.
	 *
	 * @param  string $klarna_order_id Klarna order ID.
	 * @return object $klarna_order    Klarna order.
	 */
	public function request_pre_retrieve_order( $klarna_order_id ) {
		$request_url = 'https://api-na.playground.klarna.com/checkout/v3/orders/' . $klarna_order_id;
		$request_args = array(
			'headers' => $this->get_request_headers(),
		);

		$response = wp_safe_remote_get( $request_url, $request_args );

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			$klarna_order = json_decode( $response['body'] );
			return $klarna_order;
		} else {
			return new WP_Error( 'Error retrieving Klarna order.' );
		}
	}

	/**
	 * Update ongoing Klarna order.
	 *
	 * @param  string $klarna_order_id Klarna order ID.
	 * @return object $klarna_order    Klarna order.
	 */
	public function request_pre_update_order( $klarna_order_id ) {
		$request_url = 'https://api-na.playground.klarna.com/checkout/v3/orders/' . $klarna_order_id;
		$request_args = array(
			'headers' => $this->get_request_headers(),
			'body'    => $this->get_request_body(),
		);

		$response = wp_safe_remote_post( $request_url, $request_args );

		if ( $response['response']['code'] >= 200 && $response['response']['code'] <= 299 ) {
			$klarna_order = json_decode( $response['body'] );

			return $klarna_order;
		} else {
			return new WP_Error( 'Error creating Klarna order.' );
		}

	}


	/**
	 * Acknowledges Klarna Checkout order.
	 *
	 * @param  string $klarna_order_id Klarna order ID.
	 *
	 * @return WP_Error|array $response
	 */
	public function request_post_get_order( $klarna_order_id ) {
		$request_url = 'https://api-na.playground.klarna.com/ordermanagement/v1/orders/' . $klarna_order_id;
		$request_args = array(
			'headers' => $this->get_request_headers(),
		);

		$response = wp_safe_remote_get( $request_url, $request_args );
		return $response;
	}

	/**
	 * Acknowledges Klarna Checkout order.
	 *
	 * @param  string $klarna_order_id Klarna order ID.
	 *
	 * @return WP_Error|array $response
	 */
	public function request_post_acknowledge_order( $klarna_order_id ) {
		$request_url = 'https://api-na.playground.klarna.com/ordermanagement/v1/orders/' . $klarna_order_id . '/acknowledge';
		$request_args = array(
			'headers' => $this->get_request_headers(),
		);

		$response = wp_safe_remote_post( $request_url, $request_args );
		return $response;
	}

	/**
	 * Adds WooCommerce order ID to Klarna order as merchant_reference. And clear Klarna order ID value from WC session.
	 *
	 * @param  string $klarna_order_id     Klarna order ID.
	 * @param  array  $merchant_references Array of merchant references.
	 *
	 * @return WP_Error|array $response
	 */
	public function request_post_set_merchant_reference( $klarna_order_id, $merchant_references ) {
		$request_url = 'https://api-na.playground.klarna.com/ordermanagement/v1/orders/' . $klarna_order_id . '/merchant-references';
		$request_args = array(
			'headers' => $this->get_request_headers(),
			'method' => 'PATCH',
			'body' => wp_json_encode( array(
				'merchant_reference1' => $merchant_references['merchant_reference1'],
				'merchant_reference2' => $merchant_references['merchant_reference2'],
			) ),
		);

		$response = wp_safe_remote_request( $request_url, $request_args );
		return $response;
	}

	/**
	 * Set Klarna Checkout API URL.
	 *
	 * @param string $api_url Klarna Checkout API URL.
	 */
	public function set_api_url( $api_url ) {
		$this->api_url = $api_url;
	}

	/**
	 * Set Klarna Checkout merchant ID.
	 *
	 * @param string $merchant_id Klarna Checkout merchant ID.
	 */
	public function set_merchant_id( $merchant_id ) {
		$this->merchant_id = $merchant_id;
	}

	/**
	 * Set Klarna Checkout shared secret.
	 *
	 * @param string $shared_secret Klarna Checkout shared secret.
	 */
	public function set_shared_secret( $shared_secret ) {
		$this->shared_secret = $shared_secret;
	}

	/**
	 * Gets Klarna order from WC_Session
	 *
	 * @return array|string
	 */
	public function get_order_id_from_session() {
		return WC()->session->get( 'klarna_order_id' );
	}

	/**
	 * Saves Klarna order ID to WooCommerce session.
	 *
	 * @param string $order_id Klarna order ID.
	 */
	public function save_order_id_to_session( $order_id ) {
		WC()->session->set( 'klarna_order_id', $order_id );
	}

	/**
	 * Gets Klarna Checkout order.
	 *
	 * If WC_Session value for Klarna order ID exists, attempt to retrieve that order.
	 * If this fails, create a new one and retrieve it.
	 * If WC_Session value for Klarna order ID does not exist, create a new order and retrieve it.
	 */
	public function get_order() {
		$order_id = $this->get_order_id_from_session();

		if ( $order_id ) {
			$order = $this->request_pre_retrieve_order( $order_id );

			if ( ! $order ) {
				$order = $this->request_pre_create_order();
			} elseif ( 'checkout_incomplete' === $order->status ) {
				// Only update order if its status is incomplete.
				$order = $this->request_pre_update_order( $order_id );
			}
		} else {
			$order = $this->request_pre_create_order();
		}

		return $order;
	}

	/**
	 * Gets KCO iframe snippet from KCO order.
	 *
	 * @param Klarna_Order $order Klarna Checkout order.
	 *
	 * @return mixed
	 */
	public function get_snippet( $order ) {
		return $order->html_snippet;
	}

	/**
	 * Gets Klarna merchant ID.
	 *
	 * @return string
	 */
	public function get_merchant_id() {
		return $this->merchant_id;
	}

	/**
	 * Gets Klarna shared secret.
	 *
	 * @return string
	 */
	public function get_shared_secret() {
		return $this->shared_secret;
	}

	/**
	 * Gets country for Klarna purchase.
	 *
	 * @return string
	 */
	public function get_purchase_country() {
		return 'US';
	}

	/**
	 * Gets currency for Klarna purchase.
	 *
	 * @return string
	 */
	public function get_purchase_currency() {
		return 'USD';
	}

	/**
	 * Gets locale for Klarna purchase.
	 *
	 * @return string
	 */
	public function get_locale() {
		return 'en-US';
	}

	/**
	 * Gets merchant URLs for Klarna purchase.
	 *
	 * @return array
	 * @TODO: Add remaining URLs (validation, shipping_option_update, address_update, notification, country_change).
	 */
	public function get_merchant_urls() {
		return KCO_WC()->merchant_urls->get_urls();
	}

	/**
	 * Gets Klarna API request URL.
	 *
	 * @param string $request API request type (create, retrieve, update).
	 *
	 * @return string
	 */
	public function get_request_url( $request ) {
		switch ( $request ) {
			case 'create':
				$request_url = $this->api_url;
				break;
			case 'retrieve':
				$request_url = $this->api_url;
				break;
			case 'update':
				$request_url = $this->api_url;
				break;
			default:
				$request_url = $this->api_url;
		}

		return $request_url;
	}

	/**
	 * Gets Klarna API request headers.
	 *
	 * @return array
	 */
	public function get_request_headers() {
		$request_headers = array(
			'Authorization' => 'Basic ' . base64_encode( $this->get_merchant_id() . ':' . $this->get_shared_secret() ),
			'Content-Type'  => 'application/json',
		);

		return $request_headers;
	}

	/**
	 * Gets Klarna API request body.
	 *
	 * @return false|string
	 */
	public function get_request_body() {
		KCO_WC()->order_lines->process_data();

		$request_body = wp_json_encode( array(
			'purchase_country'  => $this->get_purchase_country(),
			'purchase_currency' => $this->get_purchase_currency(),
			'locale'            => $this->get_locale(),
			'merchant_urls'     => $this->get_merchant_urls(),
			'order_amount'      => KCO_WC()->order_lines->get_order_amount(),
			'order_tax_amount'  => KCO_WC()->order_lines->get_order_tax_amount(),
			'order_lines'       => KCO_WC()->order_lines->get_order_lines(),
		) );

		return $request_body;
	}

}
