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
 * The API request object.
 *
 * @since 2.0.0
 */
class WC_Paytrail_API_Request extends SV_WC_API_JSON_Request {


	/**
	 * Construct the request.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		$this->path = '/api-payment/create';
	}


	/**
	 * Set the parameters for a new payment.
	 *
	 * @since 2.0.0
	 * @param \WC_Order $order The order object
	 * @param string $locale The payment form locale
	 * @param string $return_url The return URL on successful payment
	 * @param bool $extended_info Whether to send extended transaction info or just the order total
	 */
	public function set_payment_params( WC_Order $order, $locale, $return_url, $extended_info = false ) {

		$params = array(
			'orderNumber' => preg_replace( '/[^0-9]/', '', $order->get_order_number() ),
			'currency'    => SV_WC_Order_Compatibility::get_prop( $order, 'currency', 'view' ),
			'locale'      => $locale,
			'urlSet'      => array(
				'success'      => $return_url,
				'failure'      => $order->get_cancel_order_url_raw(),
				'notification' => add_query_arg( 'notification', 'payment', $return_url ),
			),
		);

		// If transactions should include extended information, add it
		if ( $extended_info ) {

			$params['orderDetails'] = array(
				'includeVat' => 1,
				'contact'    => $this->get_formatted_contact( $order ),
				'products'   => $this->get_formatted_items( $order ),
			);

		// Otherwise, just send along the order total
		} else {

			$params['price'] = $order->payment_total;
		}

		/**
		 * Filter the hosted pay page parameters.
		 *
		 * @since 2.0.0
		 * @param array $params Form parameters in name => value format
		 * @param \WC_Order $order The order object
		 * @param \WC_Gateway_Paytrail $gateway The gateway instance
		 */
		$params = apply_filters( 'wc_paytrail_payment_params', $params, $order, $this );

		$this->params = $params;
	}


	/**
	 * Get the order items formatted for the Paytrail API.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_formatted_items( WC_Order $order ) {

		$items = array();

		// Add the product costs
		foreach ( $order->get_items() as $item ) {

			if ( ! $item['qty'] ) {
				continue;
			}

			// Calculate tax percentage
			$line_tax_total = $order->get_item_total( $item, false, false );
			$tax_percentage = $line_tax_total ? ( $order->get_item_tax( $item, false ) / $line_tax_total ) * 100 : 0;

			$product = $order->get_product_from_item( $item );

			$items[] = array(
				'title'    => $item['name'],
				'code'     => $product->get_sku() ? substr( $product->get_sku(), 0, 16 ) : '',
				'amount'   => $item['qty'],
				'price'    => wc_format_decimal( $order->get_item_total( $item, true ), 2 ),
				'vat'      => wc_format_decimal( $tax_percentage, 2 ),
				'discount' => '0.00',
				'type'     => 1,
			);
		}

		// Add the shipping costs
		foreach ( $order->get_shipping_methods() as $method ) {

			$taxes = ( SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) ? $method['taxes']['total'] : $method['taxes'];

			// Calculate tax percentage
			$total          = $method['cost'];
			$tax_total      = array_sum( maybe_unserialize( $taxes ) );
			$tax_percentage = ( $tax_total && $total ) ? ( $tax_total / $total ) * 100 : 0;

			$items[] = array(
				'title'    => $method['name'],
				'code'     => substr( $method['method_id'], 0, 16 ),
				'amount'   => 1,
				'price'    => wc_format_decimal( $total + $tax_total, 2 ),
				'vat'      => wc_format_decimal( $tax_percentage, 2 ),
				'discount' => '0.00',
				'type'     => 2,
			);
		}

		// Add any fees
		foreach ( $order->get_fees() as $fee ) {

			// Calculate tax rate
			$line_tax_total = $order->get_item_total( $item, false, false );
			$tax_percentage = $line_tax_total ? ( $order->get_item_tax( $item, false ) / $line_tax_total ) * 100 : 0;

			$items[] = array(
				'title'    => $fee['name'],
				'amount'   => 1,
				'price'    => wc_format_decimal( $order->get_line_total( $fee ), 2 ),
				'vat'      => wc_format_decimal( $tax_percentage, 2 ),
				'discount' => '0.00',
				'type'     => 1,
			);
		}

		/**
		 * Filter the order line items for payment.
		 *
		 * @since 2.0.0
		 * @param array $items {
		 *     The order line items.
		 *
		 *     @type string $title    Item title (product name, fee name, ect...)
		 *     @type string $code     Item code (SKU or other unique ID)
		 *     @type int    $amount   Item quantity
		 *     @type float  $price    Total item cost
		 *     @type float  $vat      Item tax
		 *     @type float  $discount Discount amount. Unused.
		 *     @type int    $type     Item type. 1 = standard, 2 = shipping, 3 = handling
		 * }
		 */
		return apply_filters( 'wc_paytrail_payment_order_items', $items );
	}


	/**
	 * Get the order contact formatted for the Paytrail API.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	protected function get_formatted_contact( WC_Order $order ) {

		$postcode = SV_WC_Order_Compatibility::get_prop( $order, 'billing_postcode' );

		$contact = array(
			'telephone'   => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_phone' ), 64 ),
			'email'       => SV_WC_Order_Compatibility::get_prop( $order, 'billing_email' ),
			'firstName'   => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_first_name' ), 64 ),
			'lastName'    => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_last_name' ), 64 ),
			'companyName' => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_company' ), 128 ),
			'address'     => array(
				'street'       => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_address_1' ), 128 ),
				'postalCode'   => empty( $postcode ) ? '-' : SV_WC_Helper::str_truncate( $postcode, 16 ),
				'postalOffice' => SV_WC_Helper::str_truncate( SV_WC_Order_Compatibility::get_prop( $order, 'billing_city' ), 16 ),
				'country'      => SV_WC_Order_Compatibility::get_prop( $order, 'billing_country' ),
			),
		);

		/**
		 * Filter the order contact for payment.
		 *
		 * @since 2.0.0
		 * @param array $contact
		 */
		return apply_filters( 'wc_paytrail_payment_contact', $contact );
	}


}
