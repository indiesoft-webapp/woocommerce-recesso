<?php
/**
 * Email notifications.
 *
 * @package IndieSoft\WooCommerceRecesso
 */

namespace IndieSoft\WooCommerceRecesso;

defined( 'ABSPATH' ) || exit;

final class Emails {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {}

	public function notify_created( $request, $order ) {
		$subject = sprintf( __( 'Richiesta di recesso #%1$d per ordine #%2$s', 'indiesoft-woocommerce-recesso' ), $request['id'], $order->get_order_number() );
		$message = $this->render_message( $request, $order );
		$heading = __( 'Nuova richiesta di recesso', 'indiesoft-woocommerce-recesso' );
		$mailer = WC()->mailer();
		$message = $mailer->wrap_message( $heading, $message );
		$admin_email = sanitize_email( Settings::get( 'admin_email', get_option( 'admin_email' ) ) );
		$customer_email = is_email( $request['customer_email'] ?? '' ) ? $request['customer_email'] : $order->get_billing_email();
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' <' . $admin_email . '>',
		);

		if ( 'yes' === Settings::get( 'admin_email_enabled' ) && is_email( $admin_email ) ) {
			wp_mail( $admin_email, $subject, $message, $headers );
		}

		if ( 'yes' === Settings::get( 'customer_email' ) && is_email( $customer_email ) ) {
			wp_mail( $customer_email, $subject, $message, $headers );
		}
	}

	public function notify_status_changed( $request, $order ) {
		if ( 'yes' !== Settings::get( 'customer_email' ) ) {
			return;
		}

		$customer_email = is_email( $request['customer_email'] ?? '' ) ? $request['customer_email'] : $order->get_billing_email();
		if ( ! is_email( $customer_email ) ) {
			return;
		}

		$subject = sprintf( __( 'Aggiornamento richiesta di recesso #%d', 'indiesoft-woocommerce-recesso' ), $request['id'] );
		$message = $this->render_message( $request, $order );
		$heading = __( 'Aggiornamento richiesta di recesso', 'indiesoft-woocommerce-recesso' );
		$mailer = WC()->mailer();
		$message = $mailer->wrap_message( $heading, $message );
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			'From: ' . wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' <' . sanitize_email( Settings::get( 'admin_email', get_option( 'admin_email' ) ) ) . '>',
		);

		wp_mail( $customer_email, $subject, $message, $headers );
	}

	private function render_message( $request, $order ) {
		ob_start();
		?>
		<p><?php echo esc_html__( 'Dettagli richiesta di recesso', 'indiesoft-woocommerce-recesso' ); ?></p>
		<ul>
			<li><strong><?php esc_html_e( 'Ordine', 'indiesoft-woocommerce-recesso' ); ?>:</strong> #<?php echo esc_html( $order->get_order_number() ); ?></li>
			<li><strong><?php esc_html_e( 'Data e ora ricezione', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $request['created_at'] ) ); ?></li>
			<li><strong><?php esc_html_e( 'Stato', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( Admin::status_label( $request['status'] ) ); ?></li>
			<li><strong><?php esc_html_e( 'Motivo', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( $request['reason'] ); ?></li>
			<li><strong><?php esc_html_e( 'Metodo preferito', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( $request['refund_method'] ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( 'Dichiarazione', 'indiesoft-woocommerce-recesso' ); ?>:</strong><br>
		<?php echo esc_html( sprintf( __( 'Il cliente %1$s dichiara di esercitare il diritto di recesso per il contratto relativo all ordine #%2$s.', 'indiesoft-woocommerce-recesso' ), $request['customer_name'] ?: $request['customer_email'], $order->get_order_number() ) ); ?></p>
		<?php if ( ! empty( $request['message'] ) ) : ?>
			<p><?php echo nl2br( esc_html( $request['message'] ) ); ?></p>
		<?php endif; ?>
		<?php if ( ! empty( $request['admin_note'] ) ) : ?>
			<p><strong><?php esc_html_e( 'Nota amministratore', 'indiesoft-woocommerce-recesso' ); ?>:</strong><br><?php echo nl2br( esc_html( $request['admin_note'] ) ); ?></p>
		<?php endif; ?>
		<?php
		return (string) ob_get_clean();
	}
}
