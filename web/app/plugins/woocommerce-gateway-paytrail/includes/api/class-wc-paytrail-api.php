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
 * @package   WC-Gateway-Paytrail/API
 * @author    SkyVerge
 * @copyright Copyright (c) 2011-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * The main API class.
 *
 * @since 2.0.0
 */
class WC_Paytrail_API extends SV_WC_API_Base {


	/** @var string request URI */
	protected $request_uri;

	/** @var string merchant ID */
	protected $merchant_id;

	/** @var string merchant secret */
	protected $merchant_secret;


	/**
	 * Construct the API.
	 *
	 * @since 2.0.0
	 */
	public function __construct( $merchant_id, $merchant_secret ) {

		// This is where the magic happens
		$this->request_uri = 'https://payment.paytrail.com';

		// Set the credentials
		$this->merchant_id     = $merchant_id;
		$this->merchant_secret = $merchant_secret;

		$this->set_http_basic_auth( $this->merchant_id, $this->merchant_secret );

		// Set the necessary headers
		$this->set_request_content_type_header( 'application/json' );
		$this->set_request_accept_header( 'application/json' );
		$this->set_request_header( 'X-Verkkomaksut-Api-Version', 1 );

		// Set response handler class
		$this->response_handler = 'WC_Paytrail_API_Response';
	}


	/**
	 * Send order details to Paytrail to generate the hosted pay page token.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order The order object
	 * @return \WC_Paytrail_API_Response The response object
	 */
	public function create_payment_token( WC_Order $order ) {

		$this->order = $order;

		$request = $this->get_new_request();

		$request->set_payment_params( $order, $this->get_gateway()->get_locale(), $this->get_gateway()->get_transaction_response_handler_url(), $this->get_gateway()->use_extended_info() );

		return $this->perform_request( $request );
	}


	/**
	 * Validate the response before parsing.
	 *
	 * @since 2.0.0
	 * @see SV_WC_API_Base::do_pre_parse_response_validation()
	 */
	protected function do_pre_parse_response_validation() {

		if ( 201 !== $this->get_response_code() && 400 !== $this->get_response_code() ) {

			throw new SV_WC_API_Exception( sprintf( 'Paytrail API Error - HTTP %s: %s', $this->get_response_code(), $this->get_response_message() ) );
		}
	}


	/**
	 * Validate the response after parsing.
	 *
	 * @since 2.0.0
	 * @see SV_WC_API_Base::do_post_parse_response_validation()
	 */
	protected function do_post_parse_response_validation() {

		$response = $this->get_response();

		if ( $response->has_error() ) {

			throw new SV_WC_API_Exception( sprintf( 'Paytrail API Error - %s: %s',  $response->get_error_code(), $response->get_error_message() ) );
		}
	}


	/**
	 * Get the request object.
	 *
	 * @since 2.0.0
	 * @param array $args Optional. Request arguments
	 * @return \WC_Paytrail_API_Request
	 */
	protected function get_new_request( $args = array() ) {

		return new WC_Paytrail_API_Request;
	}


	/**
	 * Get the gateway instance.
	 *
	 * @since 2.0.0
	 * @return \WC_Gateway_Paytrail
	 */
	protected function get_gateway() {

		return $this->get_plugin()->get_gateway();
	}


	/**
	 * Get the plugin instance.
	 *
	 * @since 2.0.0
	 * @return \WC_Paytrail
	 */
	protected function get_plugin() {

		return wc_paytrail();
	}


}
