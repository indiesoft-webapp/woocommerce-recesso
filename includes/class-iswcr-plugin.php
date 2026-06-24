<?php
/**
 * Plugin bootstrap.
 *
 * @package IndieSoft\WooCommerceRecesso
 */

namespace IndieSoft\WooCommerceRecesso;

defined( 'ABSPATH' ) || exit;

final class Plugin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public static function activate() {
		Installer::install();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public function init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}

		Settings::instance()->init();
		Requests_Table::instance()->init();
		Emails::instance()->init();
		Frontend::instance()->init();

		if ( is_admin() ) {
			Admin::instance()->init();
		}

		add_filter( 'plugin_action_links_' . ISWCR_BASENAME, array( $this, 'plugin_action_links' ) );
	}

	public function woocommerce_missing_notice() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'IndieSoft EU Withdrawal for WooCommerce richiede WooCommerce attivo.', 'indiesoft-woocommerce-recesso' );
		echo '</p></div>';
	}

	public function plugin_action_links( $links ) {
		$settings_url = admin_url( 'admin.php?page=iswcr-settings' );
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( $settings_url ),
				esc_html__( 'Impostazioni', 'indiesoft-woocommerce-recesso' )
			)
		);

		return $links;
	}
}
