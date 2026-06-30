<?php
/**
 * Plugin Name:       IndieSoft Return and Withdrawal Requests for WooCommerce
 * Plugin URI:        https://github.com/indiesoft-webapp/woocommerce-recesso
 * Description:       Modulo configurabile per gestire richieste di recesso e reso su WooCommerce.
 * Version:           1.2.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            IndieSoft
 * Author URI:        https://www.indiesoft.it/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       indiesoft-return-withdrawal-requests-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 8.2
 * WC tested up to:   10.8
 * Tested up to:      7.0
 *
 * @package IndieSoft\ReturnWithdrawalRequest
 */

defined( 'ABSPATH' ) || exit;

define( 'IRWR_VERSION', '1.2.0' );
define( 'IRWR_FILE', __FILE__ );
define( 'IRWR_PATH', plugin_dir_path( __FILE__ ) );
define( 'IRWR_URL', plugin_dir_url( __FILE__ ) );
define( 'IRWR_BASENAME', plugin_basename( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

require_once IRWR_PATH . 'includes/class-iswcr-autoloader.php';
IndieSoft\ReturnWithdrawalRequest\Autoloader::register();

register_activation_hook( __FILE__, array( IndieSoft\ReturnWithdrawalRequest\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( IndieSoft\ReturnWithdrawalRequest\Plugin::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'indiesoft-return-withdrawal-requests-woocommerce', false, dirname( IRWR_BASENAME ) . '/languages' );
		IndieSoft\ReturnWithdrawalRequest\Plugin::instance()->init();
	}
);
