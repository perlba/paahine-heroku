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
 * The API response object.
 *
 * @since 2.0.0
 */
class WC_Paytrail_API_Response extends SV_WC_API_JSON_Response {


	/**
	 * Get the payment URL.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function get_url() {

		return $this->url;
	}


	/**
	 * Get the response error code, if available.
	 *
	 * @since 2.0.0
	 * @return string|null
	 */
	public function get_error_code() {

		return $this->errorCode;
	}


	/**
	 * Get the response error message, if available.
	 *
	 * @since 2.0.0
	 * @return string|null
	 */
	public function get_error_message() {

		return $this->errorMessage;
	}


	/**
	 * Determine if this response returned an error.
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function has_error() {

		return ( $this->get_error_code() );
	}


}
