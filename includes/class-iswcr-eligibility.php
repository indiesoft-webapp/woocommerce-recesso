<?php
/**
 * Order eligibility rules.
 *
 * @package IndieSoft\ReturnWithdrawalRequest
 */

namespace IndieSoft\ReturnWithdrawalRequest;

defined( 'ABSPATH' ) || exit;

final class Eligibility {
	public static function is_order_eligible( $order ) {
		if ( ! $order instanceof \WC_Order ) {
			return false;
		}

		if ( 'yes' !== Settings::get( 'enabled' ) ) {
			return false;
		}

		$statuses = (array) Settings::get( 'eligible_statuses', array() );
		if ( ! in_array( $order->get_status(), $statuses, true ) ) {
			return false;
		}

		$days    = absint( Settings::get( 'request_window_days', 14 ) );
		$created = $order->get_date_created();

		if ( $days > 0 && $created ) {
			$deadline = $created->getTimestamp() + ( DAY_IN_SECONDS * $days );
			if ( time() > $deadline ) {
				return false;
			}
		}

		return apply_filters( 'iswcr_order_is_eligible', true, $order );
	}

	public static function deadline_label( $order ) {
		$days    = absint( Settings::get( 'request_window_days', 14 ) );
		$created = $order instanceof \WC_Order ? $order->get_date_created() : null;

		if ( ! $created || 0 === $days ) {
			return '';
		}

		return date_i18n( wc_date_format(), $created->getTimestamp() + ( DAY_IN_SECONDS * $days ) );
	}
}
