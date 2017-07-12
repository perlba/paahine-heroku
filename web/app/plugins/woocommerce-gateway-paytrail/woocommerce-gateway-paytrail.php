<?php
/**
 * Plugin Name: WooCommerce Paytrail Gateway
 * Plugin URI: http://www.woocommerce.com/products/woocommerce-paytrail/
 * Description: Accept payment in WooCommerce with the Paytrail gateway
 * Author: SkyVerge
 * Author URI: http://www.woocommerce.com/
 * Version: 2.2.0
 * Text Domain: woocommerce-gateway-paytrail
 * Domain Path: /i18n/languages/
 *
 * Copyright: (c) 2011-2017, SkyVerge, Inc. (info@skyverge.com)
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   WC-Paytrail
 * @author    SkyVerge
 * @category  Payment-Gateways
 * @copyright Copyright (c) 2011-2017, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

// Required functions
if ( ! function_exists( 'woothemes_queue_update' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'woo-includes/woo-functions.php' );
}

// Plugin updates
woothemes_queue_update( plugin_basename( __FILE__ ), 'e09af7e519e970419cc6407165b51575', '18628' );

// WC active check
if ( ! is_woocommerce_active() ) {
	return;
}

// Required library class
if ( ! class_exists( 'SV_WC_Framework_Bootstrap' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'lib/skyverge/woocommerce/class-sv-wc-framework-bootstrap.php' );
}

SV_WC_Framework_Bootstrap::instance()->register_plugin( '4.6.0', __( 'WooCommerce Paytrail Gateway', 'woocommerce-gateway-paytrail' ), __FILE__, 'init_woocommerce_gateway_paytrail', array(
	'is_payment_gateway'   => true,
	'minimum_wc_version'   => '2.5.5',
	'minimum_wp_version'   => '4.1',
	'backwards_compatible' => '4.4',
) );

function init_woocommerce_gateway_paytrail() {

class WC_Paytrail extends SV_WC_Payment_Gateway_Plugin {


	/** string version number */
	const VERSION = '2.2.0';

	/** @var WC_Paytrail single instance of this plugin */
	protected static $instance;

	/** string plugin ID */
	const PLUGIN_ID = 'paytrail';

	/** string gateway ID */
	const GATEWAY_ID = 'paytrail';

	/** string gateway class name */
	const GATEWAY_CLASS_NAME = 'WC_Gateway_Paytrail';


	/**
	 * Setup main plugin class
	 *
	 * @since 2.0.0
	 */
	public function __construct() {

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'gateways'    => array( self::GATEWAY_ID => self::GATEWAY_CLASS_NAME ),
				'currencies'  => array( 'EUR' ),
				'text_domain' => 'woocommerce-gateway-paytrail',
			)
		);

		// load any required files
		add_action( 'sv_wc_framework_plugins_loaded', array( $this, 'includes' ) );
	}


	/**
	 * Load any required files
	 *
	 * @since 2.0.0
	 */
	public function includes() {

		// The gateway
		require_once( $this->get_plugin_path() . '/includes/class-wc-gateway-paytrail.php' );
	}


	/**
	 * Return deprecated/removed hooks
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_deprecated_hooks()
	 * @return array
	 */
	protected function get_deprecated_hooks() {

		$deprecated_hooks = array(
			'valid_paytrail_payment' => array(
				'version'     => '2.0.0',
				'removed'     => true,
			),
		);

		return $deprecated_hooks;
	}


	/** Helper methods ******************************************************/


	/**
	 * Main Paytrail instance, ensures only one instance is/can be loaded
	 *
	 * @since 2.0.0
	 * @see wc_paytrail()
	 * @return WC_Paytrail
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/**
	 * Returns the plugin name, localized
	 *
	 * @since 2.0.0
	 * @see SV_WC_Payment_Gateway::get_plugin_name()
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'WooCommerce Paytrail', 'woocommerce-gateway-paytrail' );
	}


	/**
	 * Gets the plugin documentation URL
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_documentation_url()
	 * @return string
	 */
	public function get_documentation_url() {
		return 'http://docs.woocommerce.com/document/woocommerce-paytrail/';
	}


	/**
	 * Gets the plugin support URL
	 *
	 * @since 2.0.0
	 * @see SV_WC_Plugin::get_support_url()
	 * @return string
	 */
	public function get_support_url() {

		return 'https://woocommerce.com/my-account/tickets/';
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 2.0.0
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


	/**
	 * Perform installation tasks.
	 *
	 * @since 2.0.0
	 */
	public function install() {

		if ( get_option( 'woocommerce_suomen_verkkomaksut_settings' ) ) {

			// versions prior to 1.2 were named differently and did not set a db version option
			$this->upgrade( '1.1.0' );
		}
	}


	/**
	 * Upgrade to the currently installed version.
	 *
	 * @since 2.0.0
	 * @param string $installed_version currently installed version
	 */
	public function upgrade( $installed_version ) {

		// upgrade to 1.2.0
		if ( version_compare( $installed_version, '1.2.0', '<' ) ) {

			$settings = get_option( 'woocommerce_suomen_verkkomaksut_settings' );

			$settings['title']       = str_ireplace( 'Suomen Verkkomaksut', 'Paytrail', $settings['title'] );
			$settings['description'] = str_ireplace( 'Suomen Verkkomaksut', 'Paytrail', $settings['description'] );

			delete_option( 'woocommerce_suomen_verkkomaksut_settings' );
			update_option( 'woocommerce_paytrail_settings', $settings );
		}

		// upgrade to 2.0.0
		if ( version_compare( $installed_version, '2.0.0', '<' ) ) {

			$this->log( 'Starting upgrade to 2.0.0' );

			/** upgrade the settings **/

			$settings = get_option( 'woocommerce_paytrail_settings', array() );

			if ( isset( $settings['debug'] ) ) {

				$settings['debug_mode'] = ( 'yes' === $settings['debug'] ) ? 'log' : 'off';

				unset( $settings['debug'] );
			}

			update_option( 'woocommerce_paytrail_settings', $settings );

			$this->log( 'Settings updated' );

			/** upgrade the order meta **/

			global $wpdb;

			$rows = $wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_wc_paytrail_trans_id' ), array( 'meta_key' => '_wc_paytrail_transaction_id' ) );

			$this->log( sprintf( '%d orders updated for transaction ID meta', $rows ) );

			/** upgrade complete **/

			$this->log( 'Completed upgrade for 2.0.0' );
		}
	}


} // end \WC_Paytrail


/**
 * Returns the One True Instance of Paytrail
 *
 * @since 2.0.0
 * @return WC_Paytrail
 */
function wc_paytrail() {
	return WC_Paytrail::instance();
}


// fire it up!
wc_paytrail();


} // init_woocommerce_gateway_paytrail
