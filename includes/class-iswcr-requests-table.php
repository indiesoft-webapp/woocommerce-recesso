<?php
/**
 * Data access for withdrawal requests.
 *
 * @package IndieSoft\ReturnWithdrawalRequest
 */

namespace IndieSoft\ReturnWithdrawalRequest;

defined( 'ABSPATH' ) || exit;

final class Requests_Table {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {}

	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . 'iswcr_requests';
	}

	public function create( $data ) {
		global $wpdb;

		$now = current_time( 'mysql' );
		$row = wp_parse_args(
			$data,
			array(
				'order_id'       => 0,
				'user_id'        => 0,
				'customer_email' => '',
				'customer_name'  => '',
				'status'         => 'new',
				'reason'         => '',
				'message'        => '',
				'items'          => array(),
				'refund_method'  => '',
				'admin_note'     => '',
				'created_at'     => $now,
				'updated_at'     => $now,
			)
		);

		$row['items'] = wp_json_encode( array_values( (array) $row['items'] ) );

		$wpdb->insert( self::table_name(), $row );

		return $wpdb->insert_id ? absint( $wpdb->insert_id ) : 0;
	}

	public function update_status( $id, $status, $admin_note = '' ) {
		global $wpdb;

		return (bool) $wpdb->update(
			self::table_name(),
			array(
				'status'     => sanitize_key( $status ),
				'admin_note' => wp_kses_post( $admin_note ),
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => absint( $id ) )
		);
	}

	public function get( $id ) {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
			    'SELECT * FROM %i WHERE id = %d',
		    	self::table_name(),
			    absint( $id )
			),
			ARRAY_A
		);

		if ( $row ) {
			$row['items'] = json_decode( $row['items'], true ) ?: array();
		}

		return $row;
	}

	public function find_by_order( $order_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
			    'SELECT * FROM %i WHERE order_id = %d ORDER BY created_at DESC',
		    	self::table_name(),
			    absint( $order_id )
			),
			ARRAY_A
		);			
	}

	public function query( $args = array() ) {
		global $wpdb;
		
		$args = wp_parse_args(
			$args,
			array(
				'status'   => '',
				'per_page' => 20,
				'paged'    => 1,
			)
		);
		
		$where  = 'WHERE 1=1';
		$params = array( self::table_name() ); // %i come primo placeholder
		
		if ( $args['status'] ) {
			$where   .= ' AND status = %s';
			$params[] = sanitize_key( $args['status'] );
		}
		
		$limit    = max( 1, absint( $args['per_page'] ) );
		$offset   = max( 0, ( absint( $args['paged'] ) - 1 ) * $limit );
		//$sql      = 'SELECT * FROM %i ' . $where . ' ORDER BY created_at DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;
		
		return $wpdb->get_results( // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
			$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				'SELECT * FROM %i ' . $where . ' ORDER BY created_at DESC LIMIT %d OFFSET %d',
				$params
			), 
			ARRAY_A 
		);
	}

	public function count( $status = '' ) {
		global $wpdb;

		if ( $status ) {
			return (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM %i WHERE status = %s',
					self::table_name(),
					sanitize_key( $status )
				)
			);
		}

		return (int) $wpdb->get_var( // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter
    	$wpdb->prepare( 'SELECT COUNT(*) FROM %i', self::table_name() )
		);
	}
}
