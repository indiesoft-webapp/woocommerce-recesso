<?php
/**
 * Plugin settings.
 *
 * @package IndieSoft\WooCommerceRecesso
 */

namespace IndieSoft\WooCommerceRecesso;

defined( 'ABSPATH' ) || exit;

final class Settings {
	const OPTION_NAME = 'iswcr_settings';

	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'update_option_' . self::OPTION_NAME, array( $this, 'maybe_flush_rewrites' ), 10, 2 );
	}

	public static function defaults() {
		return array(
			'enabled'              => 'yes',
			'endpoint'             => 'recesso',
			'request_window_days'  => 14,
			'eligible_statuses'    => array( 'pending', 'on-hold', 'processing', 'completed' ),
			'reasons'              => "Ho cambiato idea\nProdotto non conforme\nProdotto danneggiato\nOrdine errato",
			'refund_methods'       => "Rimborso sul metodo di pagamento originale\nBuono store\nSostituzione prodotto",
			'policy_text'          => 'Puoi esercitare il diritto di recesso entro {days} giorni dalla data dell ordine, se previsto dalle condizioni di vendita.',
			'button_label'         => 'Recedere dal contratto qui',
			'success_message'      => 'La dichiarazione di recesso e stata trasmessa correttamente. Riceverai una ricevuta via email.',
			'customer_email'       => 'yes',
			'admin_email'          => get_option( 'admin_email' ),
			'admin_email_enabled'  => 'yes',
			'terms_required'       => 'yes',
			'allow_guest_by_email' => 'yes',
			'allow_multiple'       => 'no',
		);
	}

	public static function get( $key = null, $default = null ) {
		$settings = wp_parse_args( get_option( self::OPTION_NAME, array() ), self::defaults() );

		if ( null === $key ) {
			return $settings;
		}

		return array_key_exists( $key, $settings ) ? $settings[ $key ] : $default;
	}

	public function register_settings() {
		register_setting(
			'iswcr_settings_group',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	public function sanitize( $input ) {
		$defaults = self::defaults();
		$input    = is_array( $input ) ? $input : array();

		return array(
			'enabled'              => empty( $input['enabled'] ) ? 'no' : 'yes',
			'endpoint'             => sanitize_title( $input['endpoint'] ?? $defaults['endpoint'] ),
			'request_window_days'  => max( 0, absint( $input['request_window_days'] ?? $defaults['request_window_days'] ) ),
			'eligible_statuses'    => array_values( array_map( 'wc_clean', (array) ( $input['eligible_statuses'] ?? $defaults['eligible_statuses'] ) ) ),
			'reasons'              => sanitize_textarea_field( $input['reasons'] ?? $defaults['reasons'] ),
			'refund_methods'       => sanitize_textarea_field( $input['refund_methods'] ?? $defaults['refund_methods'] ),
			'policy_text'          => wp_kses_post( $input['policy_text'] ?? $defaults['policy_text'] ),
			'button_label'         => sanitize_text_field( $input['button_label'] ?? $defaults['button_label'] ),
			'success_message'      => sanitize_text_field( $input['success_message'] ?? $defaults['success_message'] ),
			'customer_email'       => empty( $input['customer_email'] ) ? 'no' : 'yes',
			'admin_email'          => sanitize_email( $input['admin_email'] ?? $defaults['admin_email'] ),
			'admin_email_enabled'  => empty( $input['admin_email_enabled'] ) ? 'no' : 'yes',
			'terms_required'       => empty( $input['terms_required'] ) ? 'no' : 'yes',
			'allow_guest_by_email' => empty( $input['allow_guest_by_email'] ) ? 'no' : 'yes',
			'allow_multiple'       => empty( $input['allow_multiple'] ) ? 'no' : 'yes',
		);
	}

	public function maybe_flush_rewrites( $old_value, $value ) {
		$old_endpoint = is_array( $old_value ) && ! empty( $old_value['endpoint'] ) ? $old_value['endpoint'] : self::defaults()['endpoint'];
		$new_endpoint = is_array( $value ) && ! empty( $value['endpoint'] ) ? $value['endpoint'] : self::defaults()['endpoint'];

		if ( $old_endpoint !== $new_endpoint ) {
			flush_rewrite_rules();
		}
	}

	public static function lines_to_options( $value ) {
		$lines = preg_split( '/\r\n|\r|\n/', (string) $value );
		$lines = array_map( 'trim', $lines );
		return array_values( array_filter( $lines ) );
	}
}
