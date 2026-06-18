<?php
/**
 * Lightweight class autoloader.
 *
 * @package IndieSoft\WooCommerceRecesso
 */

namespace IndieSoft\WooCommerceRecesso;

defined( 'ABSPATH' ) || exit;

final class Autoloader {
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'autoload' ) );
	}

	public static function autoload( $class ) {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$relative = strtolower( str_replace( '_', '-', $relative ) );
		$parts    = explode( '\\', $relative );
		$file     = 'class-iswcr-' . array_pop( $parts ) . '.php';
		$path     = ISWCR_PATH . ( $parts ? strtolower( implode( '/', $parts ) ) . '/' : 'includes/' ) . $file;

		if ( is_readable( $path ) ) {
			require_once $path;
		}
	}
}
