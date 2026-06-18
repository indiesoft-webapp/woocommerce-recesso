<?php
/**
 * Customer-facing account endpoint and form handling.
 *
 * @package IndieSoft\WooCommerceRecesso
 */

namespace IndieSoft\WooCommerceRecesso;

defined( 'ABSPATH' ) || exit;

final class Frontend {
	private static $instance = null;
	private $rendered_order_buttons = array();

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'account_menu_items' ) );
		add_action( 'woocommerce_account_' . Settings::get( 'endpoint', 'recesso' ) . '_endpoint', array( $this, 'render_account_page' ) );
		//add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'order_actions' ), 10, 2 );
		add_action( 'woocommerce_thankyou', array( $this, 'render_order_button_by_id' ), 20 );
		add_action( 'woocommerce_order_details_after_order_table', array( $this, 'render_order_button' ) );
		add_filter( 'the_content', array( $this, 'append_order_received_button' ), 20 );
		add_action( 'template_redirect', array( $this, 'handle_submission' ) );
		add_shortcode( 'indiesoft_recesso', array( $this, 'shortcode' ) );
	}

	public function add_endpoint() {
		add_rewrite_endpoint( Settings::get( 'endpoint', 'recesso' ), EP_ROOT | EP_PAGES );
	}

	public function add_query_vars( $vars ) {
		$vars[] = Settings::get( 'endpoint', 'recesso' );
		return $vars;
	}

	public function account_menu_items( $items ) {
		$endpoint = Settings::get( 'endpoint', 'recesso' );
		$new      = array();

		foreach ( $items as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'orders' === $key ) {
				$new[ $endpoint ] = __( 'Recesso e resi', 'indiesoft-woocommerce-recesso' );
			}
		}

		return $new;
	}

	public function order_actions( $actions, $order ) {
		if ( Eligibility::is_order_eligible( $order ) && $this->order_allows_new_request( $order ) ) {
			$url = wc_get_account_endpoint_url( Settings::get( 'endpoint', 'recesso' ) );
			$url = add_query_arg( 'order_id', $order->get_id(), $url );

			$actions['iswcr_request'] = array(
				'url'  => $url,
				'name' => Settings::get( 'button_label', __( 'Richiedi recesso', 'indiesoft-woocommerce-recesso' ) ),
			);
		}

		return $actions;
	}

	public function shortcode() {
		ob_start();
		$this->render_account_page();
		return ob_get_clean();
	}

	public function render_account_page() {
		$order_id = absint( $_GET['order_id'] ?? 0 );
		$order    = $order_id ? wc_get_order( $order_id ) : null;

		if ( isset( $_GET['iswcr-submitted'] ) ) {
			wc_print_notice( Settings::get( 'success_message' ), 'success' );
		}

		echo '<div class="iswcr-account">';
		echo '<h2>' . esc_html__( 'Richiesta di recesso', 'indiesoft-woocommerce-recesso' ) . '</h2>';

		if ( ! $order ) {
			if ( is_user_logged_in() ) {
				$this->render_orders_list();
			} else {
				$this->render_guest_lookup();
			}
			echo '</div>';
			return;
		}

		if ( ! $this->current_customer_can_access_order( $order ) ) {
			wc_print_notice( __( 'Non puoi inviare richieste per questo ordine.', 'indiesoft-woocommerce-recesso' ), 'error' );
			echo '</div>';
			return;
		}

		if ( ! Eligibility::is_order_eligible( $order ) ) {
			wc_print_notice( __( 'Questo ordine non e idoneo alla richiesta di recesso.', 'indiesoft-woocommerce-recesso' ), 'notice' );
			echo '</div>';
			return;
		}

		if ( ! $this->order_allows_new_request( $order ) ) {
			wc_print_notice( __( 'Esiste gia una richiesta aperta per questo ordine.', 'indiesoft-woocommerce-recesso' ), 'notice' );
			echo '</div>';
			return;
		}

		$this->render_form( $order );
		echo '</div>';
	}

	private function render_orders_list() {
		$orders = wc_get_orders(
			array(
				'customer_id' => get_current_user_id(),
				'limit'       => 20,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);

		echo '<p>' . esc_html__( 'Seleziona un ordine idoneo per aprire una richiesta.', 'indiesoft-woocommerce-recesso' ) . '</p>';
		echo '<table class="shop_table shop_table_responsive"><thead><tr><th>' . esc_html__( 'Ordine', 'indiesoft-woocommerce-recesso' ) . '</th><th>' . esc_html__( 'Data', 'indiesoft-woocommerce-recesso' ) . '</th><th>' . esc_html__( 'Azione', 'indiesoft-woocommerce-recesso' ) . '</th></tr></thead><tbody>';

		foreach ( $orders as $order ) {
			if ( ! Eligibility::is_order_eligible( $order ) || ! $this->order_allows_new_request( $order ) ) {
				continue;
			}

			$url = add_query_arg( 'order_id', $order->get_id(), wc_get_account_endpoint_url( Settings::get( 'endpoint', 'recesso' ) ) );
			echo '<tr><td>#' . esc_html( $order->get_order_number() ) . '</td><td>' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . '</td><td><a class="button" href="' . esc_url( $url ) . '">' . esc_html( Settings::get( 'button_label' ) ) . '</a></td></tr>';
		}

		echo '</tbody></table>';
	}

	private function render_guest_lookup() {
		if ( 'yes' !== Settings::get( 'allow_guest_by_email', 'yes' ) ) {
			wc_print_notice( __( 'Accedi al tuo account per richiedere il recesso.', 'indiesoft-woocommerce-recesso' ), 'notice' );
			return;
		}

		$order = null;
		if ( ! empty( $_POST['iswcr_action'] ) && 'lookup_order' === $_POST['iswcr_action'] ) {
			if ( isset( $_POST['iswcr_lookup_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iswcr_lookup_nonce'] ) ), 'iswcr_lookup_order' ) ) {
				$order_id = absint( $_POST['lookup_order_id'] ?? 0 );
				$email    = sanitize_email( wp_unslash( $_POST['lookup_email'] ?? '' ) );
				$order    = $order_id ? wc_get_order( $order_id ) : null;

				if ( ! $order || strtolower( $order->get_billing_email() ) !== strtolower( $email ) ) {
					$order = null;
					wc_print_notice( __( 'Ordine non trovato con questi dati.', 'indiesoft-woocommerce-recesso' ), 'error' );
				}
			}
		}

		if ( $order && Eligibility::is_order_eligible( $order ) && $this->order_allows_new_request( $order ) ) {
			$this->render_form( $order, $order->get_order_key() );
			return;
		}

		if ( $order ) {
			wc_print_notice( __( 'Questo ordine non e idoneo alla richiesta di recesso.', 'indiesoft-woocommerce-recesso' ), 'notice' );
		}
		?>
		<form method="post" class="iswcr-lookup-form">
			<?php wp_nonce_field( 'iswcr_lookup_order', 'iswcr_lookup_nonce' ); ?>
			<input type="hidden" name="iswcr_action" value="lookup_order">
			<p>
				<label for="iswcr_lookup_order_id"><?php esc_html_e( 'Numero ordine', 'indiesoft-woocommerce-recesso' ); ?></label>
				<input id="iswcr_lookup_order_id" type="number" name="lookup_order_id" required>
			</p>
			<p>
				<label for="iswcr_lookup_email"><?php esc_html_e( 'Email usata per l ordine', 'indiesoft-woocommerce-recesso' ); ?></label>
				<input id="iswcr_lookup_email" type="email" name="lookup_email" required>
			</p>
			<p><button type="submit" class="button"><?php esc_html_e( 'Avvia recesso', 'indiesoft-woocommerce-recesso' ); ?></button></p>
		</form>
		<?php
	}

	private function render_form( $order, $verified_guest_key = '' ) {
		$policy = str_replace( '{days}', absint( Settings::get( 'request_window_days', 14 ) ), Settings::get( 'policy_text' ) );
		?>
		<p><?php echo wp_kses_post( $policy ); ?></p>
		<?php if ( Eligibility::deadline_label( $order ) ) : ?>
			<p><strong><?php esc_html_e( 'Scadenza richiesta', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( Eligibility::deadline_label( $order ) ); ?></p>
		<?php endif; ?>
		<form method="post" class="iswcr-form">
			<?php wp_nonce_field( 'iswcr_submit_request', 'iswcr_nonce' ); ?>
			<input type="hidden" name="iswcr_action" value="submit_request">
			<input type="hidden" name="order_id" value="<?php echo esc_attr( $order->get_id() ); ?>">
			<?php if ( $verified_guest_key ) : ?>
				<input type="hidden" name="guest_key" value="<?php echo esc_attr( $verified_guest_key ); ?>">
			<?php endif; ?>

			<p>
				<label for="iswcr_reason"><?php esc_html_e( 'Motivo', 'indiesoft-woocommerce-recesso' ); ?></label>
				<select id="iswcr_reason" name="reason" required>
					<?php foreach ( Settings::lines_to_options( Settings::get( 'reasons' ) ) as $reason ) : ?>
						<option value="<?php echo esc_attr( $reason ); ?>"><?php echo esc_html( $reason ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p>
				<label for="iswcr_refund_method"><?php esc_html_e( 'Metodo preferito', 'indiesoft-woocommerce-recesso' ); ?></label>
				<select id="iswcr_refund_method" name="refund_method" required>
					<?php foreach ( Settings::lines_to_options( Settings::get( 'refund_methods' ) ) as $method ) : ?>
						<option value="<?php echo esc_attr( $method ); ?>"><?php echo esc_html( $method ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<fieldset>
				<legend><?php esc_html_e( 'Prodotti interessati', 'indiesoft-woocommerce-recesso' ); ?></legend>
				<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
					<label>
						<input type="checkbox" name="items[]" value="<?php echo esc_attr( $item_id ); ?>" checked>
						<?php echo esc_html( $item->get_name() ); ?> x <?php echo esc_html( $item->get_quantity() ); ?>
					</label><br>
				<?php endforeach; ?>
			</fieldset>

			<p>
				<label for="iswcr_message"><?php esc_html_e( 'Note', 'indiesoft-woocommerce-recesso' ); ?></label>
				<textarea id="iswcr_message" name="message" rows="5"></textarea>
			</p>

			<?php if ( 'yes' === Settings::get( 'terms_required' ) ) : ?>
				<p>
					<label><input type="checkbox" name="terms" value="1" required> <?php esc_html_e( 'Confermo di aver letto le condizioni di recesso.', 'indiesoft-woocommerce-recesso' ); ?></label>
				</p>
			<?php endif; ?>
			<p class="iswcr-confirmation">
				<label><input type="checkbox" name="confirm_withdrawal" value="1" required> <?php esc_html_e( 'Confermo di voler esercitare il diritto di recesso per il contratto relativo a questo ordine.', 'indiesoft-woocommerce-recesso' ); ?></label>
			</p>

			<p><button type="submit" class="button"><?php echo esc_html( Settings::get( 'button_label' ) ); ?></button></p>
		</form>
		<?php
	}

	public function handle_submission() {
		if ( empty( $_POST['iswcr_action'] ) || 'submit_request' !== $_POST['iswcr_action'] ) {
			return;
		}

		if ( ! isset( $_POST['iswcr_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['iswcr_nonce'] ) ), 'iswcr_submit_request' ) ) {
			wc_add_notice( __( 'Controllo di sicurezza non valido.', 'indiesoft-woocommerce-recesso' ), 'error' );
			return;
		}

		$order = wc_get_order( absint( $_POST['order_id'] ?? 0 ) );
		if ( ! $order || ! $this->current_customer_can_access_order( $order ) || ! Eligibility::is_order_eligible( $order ) || ! $this->order_allows_new_request( $order ) ) {
			wc_add_notice( __( 'Ordine non valido o non idoneo.', 'indiesoft-woocommerce-recesso' ), 'error' );
			return;
		}

		if ( 'yes' === Settings::get( 'terms_required' ) && empty( $_POST['terms'] ) ) {
			wc_add_notice( __( 'Devi accettare le condizioni di recesso.', 'indiesoft-woocommerce-recesso' ), 'error' );
			return;
		}

		if ( empty( $_POST['confirm_withdrawal'] ) ) {
			wc_add_notice( __( 'Devi confermare la volonta di esercitare il recesso.', 'indiesoft-woocommerce-recesso' ), 'error' );
			return;
		}

		$item_ids = array_map( 'absint', (array) ( $_POST['items'] ?? array() ) );
		$request_id = Requests_Table::instance()->create(
			array(
				'order_id'       => $order->get_id(),
				'user_id'        => get_current_user_id(),
				'customer_email' => $order->get_billing_email(),
				'customer_name'  => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'reason'         => sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) ),
				'message'        => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
				'items'          => $item_ids,
				'refund_method'  => sanitize_text_field( wp_unslash( $_POST['refund_method'] ?? '' ) ),
			)
		);

		if ( $request_id ) {
			$order->add_order_note( sprintf( __( 'Nuova richiesta di recesso #%d inviata dal cliente.', 'indiesoft-woocommerce-recesso' ), $request_id ) );
			$order->save();

			$request       = Requests_Table::instance()->get( $request_id );
			$request['id'] = $request_id;
			Emails::instance()->notify_created( $request, $order );

			wp_safe_redirect( add_query_arg( 'iswcr-submitted', '1', wc_get_account_endpoint_url( Settings::get( 'endpoint', 'recesso' ) ) ) );
			exit;
		}

		wc_add_notice( __( 'Non e stato possibile salvare la richiesta.', 'indiesoft-woocommerce-recesso' ), 'error' );
	}

	private function current_customer_can_access_order( $order ) {
		$user_id = get_current_user_id();

		if ( $user_id && (int) $order->get_user_id() === (int) $user_id ) {
			return true;
		}

		if ( 'yes' === Settings::get( 'allow_guest_by_email', 'yes' ) ) {
			$key = sanitize_text_field( wp_unslash( $_POST['guest_key'] ?? $_GET['key'] ?? '' ) );
			if ( $key && hash_equals( $order->get_order_key(), $key ) ) {
				return true;
			}
		}

		return apply_filters( 'iswcr_customer_can_access_order', false, $order );
	}

	public function render_order_button_by_id( $order_id ) {
		$order = $order_id ? wc_get_order( $order_id ) : null;
		if ( $order ) {
			echo $this->get_order_button_html( $order, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	public function render_order_button( $order ) {
		echo $this->get_order_button_html( $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function append_order_received_button( $content ) {
		if ( is_admin() || ! is_main_query() || ! in_the_loop() || ! function_exists( 'is_wc_endpoint_url' ) || ! is_wc_endpoint_url( 'order-received' ) ) {
			return $content;
		}

		$order_id = absint( get_query_var( 'order-received' ) );
		$order    = $order_id ? wc_get_order( $order_id ) : null;

		if ( ! $order ) {
			return $content;
		}

		if ( ! empty( $_GET['iswcr-form'] ) && $this->current_customer_can_access_order( $order ) && Eligibility::is_order_eligible( $order ) && $this->order_allows_new_request( $order ) ) {
			ob_start();
			echo '<div class="iswcr-account iswcr-order-received-form">';
			echo '<h2>' . esc_html__( 'Richiesta di recesso', 'indiesoft-woocommerce-recesso' ) . '</h2>';
			$this->render_form( $order, $order->get_order_key() );
			echo '</div>';
			return $content . ob_get_clean();
		}

		return $content . $this->get_order_button_html( $order, true );
	}

	private function get_order_button_html( $order, $use_current_order_received_url = false ) {
		if ( ! $order instanceof \WC_Order || ! Eligibility::is_order_eligible( $order ) || ! $this->order_allows_new_request( $order ) ) {
			return '';
		}

		$order_id = $order->get_id();
		if ( isset( $this->rendered_order_buttons[ $order_id ] ) ) {
			return '';
		}

		$this->rendered_order_buttons[ $order_id ] = true;

		$url = $use_current_order_received_url ? $this->get_order_received_form_url( $order ) : $this->get_order_request_url( $order );

		return '<p class="iswcr-order-button"><a class="button" href="' . esc_url( $url ) . '">' . esc_html( Settings::get( 'button_label' ) ) . '</a></p>';
	}

	private function get_order_request_url( $order ) {
		$url = wc_get_account_endpoint_url( Settings::get( 'endpoint', 'recesso' ) );
		$url = add_query_arg( 'order_id', $order->get_id(), $url );

		if ( ! is_user_logged_in() && 'yes' === Settings::get( 'allow_guest_by_email', 'yes' ) ) {
			$url = add_query_arg( 'key', $order->get_order_key(), $url );
		}

		return $url;
	}

	private function get_order_received_form_url( $order ) {
		$url = wc_get_endpoint_url( 'order-received', $order->get_id(), wc_get_checkout_url() );

		return add_query_arg(
			array(
				'key'        => $order->get_order_key(),
				'iswcr-form' => 1,
			),
			$url
		);
	}

	private function order_allows_new_request( $order ) {
		if ( 'yes' === Settings::get( 'allow_multiple', 'no' ) ) {
			return true;
		}

		$existing = Requests_Table::instance()->find_by_order( $order->get_id() );
		foreach ( $existing as $request ) {
			if ( ! in_array( $request['status'], array( 'rejected', 'completed', 'cancelled' ), true ) ) {
				return false;
			}
		}

		return true;
	}
}
