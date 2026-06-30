<?php
/**
 * Installation routines.
 *
 * @package IndieSoft\ReturnWithdrawalRequest
 */

namespace IndieSoft\ReturnWithdrawalRequest;

defined( 'ABSPATH' ) || exit;

final class Installer {
	public static function install() {
		self::create_tables();

		if ( false === get_option( Settings::OPTION_NAME, false ) ) {
			add_option( Settings::OPTION_NAME, Settings::defaults() );
		}

		update_option( 'iswcr_version', IRWR_VERSION );
	}

	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = Requests_Table::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			customer_email varchar(190) NOT NULL DEFAULT '',
			customer_name varchar(190) NOT NULL DEFAULT '',
			status varchar(40) NOT NULL DEFAULT 'new',
			reason varchar(190) NOT NULL DEFAULT '',
			message longtext NULL,
			items longtext NULL,
			refund_method varchar(80) NOT NULL DEFAULT '',
			admin_note longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY order_id (order_id),
			KEY user_id (user_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}
}
