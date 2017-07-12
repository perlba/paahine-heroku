<?php
/**
 * WooCommerce Paytrail Gateway
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Paytrail Gateway to newer
 * versions in the future. If you wish to customize WooCommerce Paytrail Gateway for your
 * needs please refer to http://docs.woocommerce.com/document/woocommerce-paytrail/
 *
 * @package   WC-Gateway-Paytrail/Gateway
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * The main Paytrail gateway class.
 *
 * @since 2.0.0
 */
class WC_Gateway_Paytrail extends SV_WC_Payment_Gateway_Hosted {


	/** @var \WC_Paytrail_API API instance **/
	protected $api;

	/** @var string merchant ID */
	protected $mechant_id;

	/** @var string merchant secret */
	protected $merchant_secret;

	/** @var string whether to send extended info to Paytrail */
	protected $extended_info;

	/** @var string language used on the Paytrail payment page */
	protected $locale;


	/**
	 * Construct the gateway.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct(
			WC_Paytrail::PLUGIN_ID,
			wc_paytrail(),
			array(
				'method_title'       => __( 'Paytrail', 'woocommerce-gateway-paytrail' ),
				'method_description' => __( 'Allow customers to securely pay using their preferred payment method with Paytrail.', 'woocommerce-gateway-paytrail' ),
				'payment_type'       => self::PAYMENT_TYPE_MULTIPLE,
			)
		);

		// set the gateway icon
		$this->icon = wc_paytrail()->get_plugin_url() . '/assets/images/paytrail.png';
	}


	/**
	 * Process payment for an order at checkout.
	 *
	 * @since 2.0.0
	 * @param int $order_id The order ID
	 * @return array
	 */
	public function process_payment( $order_id ) {

		$order = $this->get_order( $order_id );

		$result = $this->get_api()->create_payment_token( $order );

		return array(
			'result'   => 'success',
			'redirect' => $result->get_url(),
		);
	}


	/**
	 * Get the gateway API instance.
	 *
	 * @since 2.0.0
	 * @return \WC_Paytrail_API
	 */
	public function get_api() {

		// Return the API object if already instantiated
		if ( is_object( $this->api ) ) {
			return $this->api;
		}

		// Load the API classes
		require_once( $this->get_plugin()->get_plugin_path() . '/includes/api/class-wc-paytrail-api.php' );

		require_once( $this->get_plugin()->get_plugin_path() . '/includes/api/class-wc-paytrail-api-request.php' );
		require_once( $this->get_plugin()->get_plugin_path() . '/includes/api/class-wc-paytrail-api-response.php' );

		// Instantiate the API
		return $this->api = new WC_Paytrail_API( $this->get_merchant_id(), $this->get_merchant_secret() );
	}


	/**
	 * Get the payment response object.
	 *
	 * @since 2.0.0
	 * @param array $request_response_data $_REQUEST data
	 * @return \WC_Paytrail_API_Payment_Response
	 */
	protected function get_transaction_response( $request_response_data ) {

		require_once( $this->get_plugin()->get_plugin_path() . '/includes/api/class-wc-paytrail-api-payment-response.php' );

		return new WC_Paytrail_API_Payment_Response( $request_response_data, $this->get_merchant_secret() );
	}


	/**
	 * Validate the transaction response.
	 *
	 * @since 2.0.0
	 * @see \SV_WC_Payment_Gateway_Hosted::validate_transaction_response()
	 * @param \WC_Order $order the order object
	 * @param \SV_WC_Payment_Gateway_API_Payment_Notification_Response the response object
	 * @throws \SV_WC_Payment_Gateway_Exception
	 */
	protected function validate_transaction_response( $order, $response ) {

		// always validate the request data
		$response->validate_request_data();

		// if this is the first IPN response for this order, bail and bypass the
		// framework's duplicate order check as an initial followup IPN is expected
		if ( $response->is_ipn() && ! $this->get_order_meta( $order, 'ipn_response_recieved' ) ) {
			return;
		}

		parent::validate_transaction_response( $order, $response );
	}


	/**
	 * Processes the transaction response for the given order.
	 *
	 * @since 2.1.2
	 * @see \SV_WC_Payment_Gateway_Hosted::process_transaction_response()
	 * @param WC_Order $order the order
	 * @param SV_WC_Payment_Gateway_API_Payment_Notification_Response transaction response
	 * @return boolean true if transaction did not fail, false otherwise
	 */
	protected function process_transaction_response( $order, $response ) {

		// if this is an IPN response, mark it in the order so subsequent IPN
		// responses will trigger an appropriate error
		if ( $response->is_ipn() ) {
			$this->add_order_meta( $order, 'ipn_response_recieved', 'yes' );
		}

		if ( $order->needs_payment() ) {
			parent::process_transaction_response( $order, $response );
		}
	}


	/**
	 * Handle the order when its transaction is approved.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order the order object
	 * @param \WC_Paytrail_API_Payment_Response $response the response object
	 * @param array $note_args Optional. The order note arguments. @see `SV_WC_Payment_Gateway_Hosted::add_transaction_approved_order_note()`
	 */
	protected function do_transaction_approved( WC_Order $order, $response, $note_args = array() ) {

		if ( $response->get_method_name() ) {

			$note_args = array(
				'method_title'    => $response->get_method_name(),
				'additional_note' => ' ' . sprintf(
					esc_html__( 'via %s', 'woocommerce-gateway-paytrail' ),
					$this->get_method_title()
				),
			);

		}

		WC()->cart->empty_cart();

		parent::do_transaction_approved( $order, $response, $note_args );
	}


	/**
	 * Get an array of form fields specific for this method.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_method_form_fields() {

		$form_fields = array(
			'merchant_id' => array(
				'title'       => __( 'Merchant ID', 'woocommerce-gateway-paytrail' ),
				'type'        => 'text',
				'description' => __( 'Please enter your Paytrail merchant id; this is needed in order to take payment.', 'woocommerce-gateway-paytrail' ),
				'default'     => ''
			),
			'merchant_secret' => array(
				'title'       => __( 'Merchant Secret', 'woocommerce-gateway-paytrail' ),
				'type'        => 'password',
				'description' => __( 'Please enter your Paytrail merchant secret; this is needed in order to take payment.', 'woocommerce-gateway-paytrail' ),
				'default'     => ''
			),
			'extended_info' => array(
				'title'       => __( 'Use extended info', 'woocommerce-gateway-paytrail' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable extended info', 'woocommerce-gateway-paytrail' ),
				'description' => __( 'When activated plugin will send extended user info to Paytrail instead of just sending the amount of payment.', 'woocommerce-gateway-paytrail'),
				'default'     => 'yes'
			),
			'locale' => array(
				'title'       => __( 'Language', 'woocommerce-gateway-paytrail' ),
				'type'        => 'select',
				'label'       => __( 'Select the language of the Paytrail pay page', 'woocommerce-gateway-paytrail' ),
				'default'     => 'yes',
				'options'      => array(
					'fi_FI' => __( 'Finnish', 'woocommerce-gateway-paytrail' ),
					'en_US' => __( 'English', 'woocommerce-gateway-paytrail' ),
					'sv_SE' => __( 'Swedish', 'woocommerce-gateway-paytrail' ),
				),
			),
		);

		return $form_fields;
	}


	/**
	 * Determine if the gateway is properly configured to perform transactions.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway::is_configured()
	 * @return bool
	 */
	protected function is_configured() {

		$is_configured = parent::is_configured();

		if ( ! $this->get_merchant_id() || ! $this->get_merchant_secret() ) {
			$is_configured = false;
		}

		return $is_configured;
	}


	/**
	 * Get the default payment method title.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_default_title() {

		return __( 'Pay with Paytrail', 'woocommerce-gateway-paytrail' );
	}


	/**
	 * Get the default payment method description.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	protected function get_default_description() {

		return __( 'Pay via Paytrail. You can pay with online bank account or with a credit card.', 'woocommerce-gateway-paytrail' );
	}


	/**
	 * Get the configured merchant ID.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_merchant_id() {

		return $this->merchant_id;
	}


	/**
	 * Get the configured merchant secret.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_merchant_secret() {

		return $this->merchant_secret;
	}


	/**
	 * Get the configured payment page locale.
	 *
	 * @since 2.0.0
	 * @return string $locale the locale of Paytrail pay page.
	 */
	public function get_locale() {

		/**
		 * Filters the configured payment page locale.
		 *
		 * @since 2.0.1
		 * @param string $locale the locale of Paytrail pay page. One of 'fi_FI', 'en_US', 'sv_SE'
		 */
		return apply_filters( 'wc_gateway_paytrail_locale', $this->locale );
	}


	/**
	 * Get the hosted payment page URL.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order Optional. The order object.
	 * @return string
	 */
	public function get_hosted_pay_page_url( $order = null ) {

		return 'https://payment.paytrail.com';
	}


	/**
	 * Determine if extended information should be sent on payment.
	 *
	 * This includes order line items and customer contact information.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function use_extended_info() {

		return 'yes' === $this->extended_info;
	}


}
