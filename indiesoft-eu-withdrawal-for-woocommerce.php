<?php
/**
<<<<<<< Updated upstream:indiesoft-woocommerce-recesso.php
 * Plugin Name:       IndieSoft EU WithDrawal for WooCommerce
=======
 * Plugin Name:       IndieSoft EU Withdrawal for WooCommerce
>>>>>>> Stashed changes:indiesoft-eu-withdrawal-for-woocommerce.php
 * Plugin URI:        https://github.com/indiesoft-webapp/woocommerce-recesso
 * Description:       Modulo configurabile per gestire richieste di recesso e reso su WooCommerce.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            IndieSoft
 * Author URI:        https://www.indiesoft.it/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       indiesoft-woocommerce-recesso
 * Domain Path:       /languages
 * WC requires at least: 8.2
 * WC tested up to:   10.8
 * Tested up to:      7.0
 *
 * @package IndieSoft\WooCommerceRecesso
 */

defined( 'ABSPATH' ) || exit;

define( 'ISWCR_VERSION', '1.0.1' );
define( 'ISWCR_FILE', __FILE__ );
define( 'ISWCR_PATH', plugin_dir_path( __FILE__ ) );
define( 'ISWCR_URL', plugin_dir_url( __FILE__ ) );
define( 'ISWCR_BASENAME', plugin_basename( __FILE__ ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

require_once ISWCR_PATH . 'includes/class-iswcr-autoloader.php';
IndieSoft\WooCommerceRecesso\Autoloader::register();

register_activation_hook( __FILE__, array( IndieSoft\WooCommerceRecesso\Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( IndieSoft\WooCommerceRecesso\Plugin::class, 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		load_plugin_textdomain( 'indiesoft-woocommerce-recesso', false, dirname( ISWCR_BASENAME ) . '/languages' );
		IndieSoft\WooCommerceRecesso\Plugin::instance()->init();
	}
);
