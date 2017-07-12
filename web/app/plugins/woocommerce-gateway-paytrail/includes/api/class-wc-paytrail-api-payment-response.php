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
 * The API payment response object.
 *
 * @since 2.0.0
 */
class WC_Paytrail_API_Payment_Response implements SV_WC_Payment_Gateway_API_Payment_Notification_Response {


	/** @var array the request data */
	protected $request;

	/** @var string the merchant secret */
	protected $secret;


	/**
	 * Construct the response.
	 *
	 * @since 2.0.0
	 */
	public function __construct( $request, $secret ) {

		$this->request = $request;
		$this->secret  = $secret;
	}


	/**
	 * Validates the request data.
	 *
	 * @since 2.0.0
	 * @throws \SV_WC_Payment_Gateway_Exception if the request is invalid.
	 */
	public function validate_request_data() {

		$data      = $this->request;
		$auth_code = ( isset( $data['RETURN_AUTHCODE'] ) ) ? $data['RETURN_AUTHCODE'] : null;

		$hash = implode( '|', array_intersect_key( $data, array_flip( $this->get_valid_hash_params() ) ) );
		$hash = md5( $hash . '|' . $this->secret );

		if ( ! hash_equals( $auth_code, strtoupper( $hash ) ) ) {
			throw new SV_WC_Payment_Gateway_Exception( 'MD5 Hash does not match calculated hash.' );
		}
	}


	/**
	 * Gets the valid receipt parameters for generating the hash.
	 *
	 * @since 2.1.2
	 * @return array
	 */
	protected function get_valid_hash_params() {

		return array(
			'ORDER_NUMBER',
			'TIMESTAMP',
			'METHOD',
			'PAID',
		);
	}


	/**
	 * Get the order ID.
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function get_order_id() {

		$order_id = 0;

		if ( isset( $this->request['ORDER_NUMBER'] ) ) {
			$order_id = $this->request['ORDER_NUMBER'];
		}

		return $order_id;
	}


	/**
	 * Get the order object.
	 *
	 * @since 2.0.0
	 * @return \WC_Order|false
	 */
	public function get_order() {

		return wc_get_order( $this->get_order_id() );
	}


	/**
	 * Determine if the payment was approved.
	 *
	 * If the gateway gets to this point, the transaction is certainly approved so nothing to check here.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function transaction_approved() {
		return true;
	}


	/**
	 * Determine if the payment was held.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function transaction_held() {
		return false;
	}


	/**
	 * Determine if the payment was cancelled.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function transaction_cancelled() {
		return false;
	}


	/**
	 * Returns the payment type: 'credit-card', 'echeck', etc
	 *
	 * @since 2.0.0
	 * @return null
	 */
	public function get_payment_type() {
		return null;
	}


	/**
	 * Try and determine the payment type based on the returned ID.
	 *
	 * @since 2.0.0
	 * @return string|null
	 */
	public function get_method_name() {

		$method_id = $this->request['METHOD'];

		switch ( $method_id ) {

			case 1 :
				$method_name = __( 'Nordea', 'woocommerce-gateway-paytrail' );
			break;

			case 2 :
				$method_name = __( 'Osuuspankki', 'woocommerce-gateway-paytrail' );
			break;

			case 3 :
				$method_name = __( 'Danske Bank', 'woocommerce-gateway-paytrail' );
			break;

			case 5 :
				$method_name = __( 'Ålandsbanken', 'woocommerce-gateway-paytrail' );
			break;

			case 6 :
				$method_name = __( 'Handelsbanken', 'woocommerce-gateway-paytrail' );
			break;

			case 10 :
				$method_name = __( 'S-Pankki', 'woocommerce-gateway-paytrail' );
			break;

			case 50 :
				$method_name = __( 'Aktia', 'woocommerce-gateway-paytrail' );
			break;

			case 51 :
				$method_name = __( 'POP Pankki', 'woocommerce-gateway-paytrail' );
			break;

			case 52 :
				$method_name = __( 'Säästöpankki', 'woocommerce-gateway-paytrail' );
			break;

			case 53 :
				$method_name = __( 'Visa', 'woocommerce-gateway-paytrail' );
			break;

			case 54 :
				$method_name = __( 'MasterCard', 'woocommerce-gateway-paytrail' );
			break;

			default:
				$method_name = null;
			break;
		}

		return $method_name;
	}


	/**
	 * Returns the card PAN or checking account number, if available.
	 *
	 * @since 2.0.0
	 * @return null
	 */
	public function get_account_number() {
		return null;
	}


	/**
	 * Gets the response status code, or null if there is no status code
	 * associated with this transaction.
	 *
	 * @since 2.0.0
	 * @return null
	 */
	public function get_status_code() {
		return null;
	}


	/**
	 * Gets the response status message, or null if there is no status message
	 * associated with this transaction.
	 *
	 * @since 2.0.0
	 * @return null
	 */
	public function get_status_message() {
		return null;
	}


	/**
	 * Gets the response transaction id, or null if there is no transaction id
	 * associated with this transaction.
	 *
	 * @since 2.0.0
	 * @return string transaction id
	 */
	public function get_transaction_id() {

		return $this->request['PAID'];
	}


	/**
	 * Returns a message appropriate for a frontend user.  This should be used
	 * to provide enough information to a user to allow them to resolve an
	 * issue on their own, but not enough to help nefarious folks fishing for
	 * info.
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway_API_Response_Message_Helper
	 * @return null
	 */
	public function get_user_message() {
		return null;
	}


	/**
	 * Determine if this is an IPN response.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function is_ipn() {

		return isset( $this->request['notification'] );
	}


	/**
	 * Returns the string representation of this response
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway_API_Response::to_string()
	 * @return string response
	 */
	public function to_string() {
		return print_r( $this->request, true );
	}


	/**
	 * Returns the string representation of this response with any and all
	 * sensitive elements masked or removed
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway_API_Response::to_string_safe()
	 * @return string response safe for logging/displaying
	 */
	public function to_string_safe() {

		// no sensitive elements
		return $this->to_string();
	}
}
