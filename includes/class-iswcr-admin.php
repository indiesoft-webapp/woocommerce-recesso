<?php
/**
 * Admin pages.
 *
 * @package IndieSoft\WooCommerceRecesso
 */

namespace IndieSoft\WooCommerceRecesso;

defined( 'ABSPATH' ) || exit;

final class Admin {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_iswcr_update_request', array( $this, 'handle_update_request' ) );
	}

	public function admin_menu() {
		add_menu_page(
			__( 'Recesso WooCommerce', 'indiesoft-woocommerce-recesso' ),
			__( 'Recesso', 'indiesoft-woocommerce-recesso' ),
			'manage_woocommerce',
			'iswcr-requests',
			array( $this, 'render_requests_page' ),
			'dashicons-undo',
			56
		);

		add_submenu_page(
			'iswcr-requests',
			__( 'Richieste recesso', 'indiesoft-woocommerce-recesso' ),
			__( 'Richieste', 'indiesoft-woocommerce-recesso' ),
			'manage_woocommerce',
			'iswcr-requests',
			array( $this, 'render_requests_page' )
		);

		add_submenu_page(
			'iswcr-requests',
			__( 'Impostazioni recesso', 'indiesoft-woocommerce-recesso' ),
			__( 'Impostazioni', 'indiesoft-woocommerce-recesso' ),
			'manage_woocommerce',
			'iswcr-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'iswcr' ) ) {
			return;
		}

		wp_enqueue_style( 'iswcr-admin', ISWCR_URL . 'assets/admin.css', array(), ISWCR_VERSION );
	}

	public static function status_label( $status ) {
		$labels = array(
			'new'        => __( 'Nuova', 'indiesoft-woocommerce-recesso' ),
			'in_review'  => __( 'In verifica', 'indiesoft-woocommerce-recesso' ),
			'approved'   => __( 'Approvata', 'indiesoft-woocommerce-recesso' ),
			'rejected'   => __( 'Respinta', 'indiesoft-woocommerce-recesso' ),
			'completed'  => __( 'Completata', 'indiesoft-woocommerce-recesso' ),
			'cancelled'  => __( 'Annullata', 'indiesoft-woocommerce-recesso' ),
		);

		return $labels[ $status ] ?? $status;
	}

	public function render_requests_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'indiesoft-woocommerce-recesso' ) );
		}

		$view_id = absint( $_GET['request_id'] ?? 0 );
		if ( $view_id ) {
			$this->render_request_detail( $view_id );
			return;
		}

		$status   = sanitize_key( $_GET['status'] ?? '' );
		$requests = Requests_Table::instance()->query(
			array(
				'status'   => $status,
				'per_page' => 50,
			)
		);
		?>
		<div class="wrap iswcr-wrap">
			<?php $this->render_brand_header( __( 'Richieste di recesso', 'indiesoft-woocommerce-recesso' ) ); ?>
			<?php if ( isset( $_GET['updated'] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Richiesta aggiornata.', 'indiesoft-woocommerce-recesso' ); ?></p></div>
			<?php endif; ?>
			<ul class="subsubsub">
				<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=iswcr-requests' ) ); ?>"><?php esc_html_e( 'Tutte', 'indiesoft-woocommerce-recesso' ); ?></a> | </li>
				<?php foreach ( array( 'new', 'in_review', 'approved', 'rejected', 'completed' ) as $key ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'status', $key, admin_url( 'admin.php?page=iswcr-requests' ) ) ); ?>"><?php echo esc_html( self::status_label( $key ) ); ?></a> | </li>
				<?php endforeach; ?>
			</ul>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'ID', 'indiesoft-woocommerce-recesso' ); ?></th>
					<th><?php esc_html_e( 'Ordine', 'indiesoft-woocommerce-recesso' ); ?></th>
					<th><?php esc_html_e( 'Cliente', 'indiesoft-woocommerce-recesso' ); ?></th>
					<th><?php esc_html_e( 'Motivo', 'indiesoft-woocommerce-recesso' ); ?></th>
					<th><?php esc_html_e( 'Stato', 'indiesoft-woocommerce-recesso' ); ?></th>
					<th><?php esc_html_e( 'Data', 'indiesoft-woocommerce-recesso' ); ?></th>
				</tr></thead>
				<tbody>
					<?php if ( empty( $requests ) ) : ?>
						<tr><td colspan="6"><?php esc_html_e( 'Nessuna richiesta trovata.', 'indiesoft-woocommerce-recesso' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $requests as $request ) : ?>
						<tr>
							<td><a href="<?php echo esc_url( add_query_arg( 'request_id', $request['id'], admin_url( 'admin.php?page=iswcr-requests' ) ) ); ?>">#<?php echo esc_html( $request['id'] ); ?></a></td>
							<td><a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $request['order_id'] ) ) ); ?>">#<?php echo esc_html( $request['order_id'] ); ?></a></td>
							<td><?php echo esc_html( $request['customer_name'] ?: $request['customer_email'] ); ?></td>
							<td><?php echo esc_html( $request['reason'] ); ?></td>
							<td><span class="iswcr-status iswcr-status-<?php echo esc_attr( $request['status'] ); ?>"><?php echo esc_html( self::status_label( $request['status'] ) ); ?></span></td>
							<td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $request['created_at'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	private function render_request_detail( $request_id ) {
		$request = Requests_Table::instance()->get( $request_id );
		$order   = $request ? wc_get_order( $request['order_id'] ) : null;

		if ( ! $request || ! $order ) {
			wp_die( esc_html__( 'Richiesta non trovata.', 'indiesoft-woocommerce-recesso' ) );
		}
		?>
		<div class="wrap iswcr-wrap">
			<?php $this->render_brand_header( sprintf( __( 'Richiesta #%d', 'indiesoft-woocommerce-recesso' ), $request_id ) ); ?>
			<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=iswcr-requests' ) ); ?>">&larr; <?php esc_html_e( 'Torna alle richieste', 'indiesoft-woocommerce-recesso' ); ?></a></p>
			<div class="iswcr-grid">
				<section class="iswcr-panel">
					<h2><?php esc_html_e( 'Dettagli', 'indiesoft-woocommerce-recesso' ); ?></h2>
					<p><strong><?php esc_html_e( 'Ordine', 'indiesoft-woocommerce-recesso' ); ?>:</strong> #<?php echo esc_html( $order->get_order_number() ); ?></p>
					<p><strong><?php esc_html_e( 'Cliente', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( $request['customer_name'] ); ?> &lt;<?php echo esc_html( $request['customer_email'] ); ?>&gt;</p>
					<p><strong><?php esc_html_e( 'Motivo', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( $request['reason'] ); ?></p>
					<p><strong><?php esc_html_e( 'Metodo preferito', 'indiesoft-woocommerce-recesso' ); ?>:</strong> <?php echo esc_html( $request['refund_method'] ); ?></p>
					<p><strong><?php esc_html_e( 'Messaggio', 'indiesoft-woocommerce-recesso' ); ?>:</strong><br><?php echo nl2br( esc_html( $request['message'] ) ); ?></p>
				</section>
				<section class="iswcr-panel">
					<h2><?php esc_html_e( 'Aggiorna stato', 'indiesoft-woocommerce-recesso' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'iswcr_update_request_' . $request_id ); ?>
						<input type="hidden" name="action" value="iswcr_update_request">
						<input type="hidden" name="request_id" value="<?php echo esc_attr( $request_id ); ?>">
						<p>
							<label for="iswcr_status"><?php esc_html_e( 'Stato', 'indiesoft-woocommerce-recesso' ); ?></label>
							<select id="iswcr_status" name="status">
								<?php foreach ( array( 'new', 'in_review', 'approved', 'rejected', 'completed', 'cancelled' ) as $status ) : ?>
									<option value="<?php echo esc_attr( $status ); ?>" <?php selected( $request['status'], $status ); ?>><?php echo esc_html( self::status_label( $status ) ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label for="iswcr_admin_note"><?php esc_html_e( 'Nota per il cliente', 'indiesoft-woocommerce-recesso' ); ?></label>
							<textarea id="iswcr_admin_note" name="admin_note" rows="5" class="large-text"><?php echo esc_textarea( $request['admin_note'] ); ?></textarea>
						</p>
						<?php submit_button( __( 'Aggiorna richiesta', 'indiesoft-woocommerce-recesso' ) ); ?>
					</form>
				</section>
			</div>
		</div>
		<?php
	}

	public function handle_update_request() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'indiesoft-woocommerce-recesso' ) );
		}

		$request_id = absint( $_POST['request_id'] ?? 0 );
		check_admin_referer( 'iswcr_update_request_' . $request_id );

		$status     = sanitize_key( $_POST['status'] ?? 'new' );
		$admin_note = wp_kses_post( wp_unslash( $_POST['admin_note'] ?? '' ) );

		Requests_Table::instance()->update_status( $request_id, $status, $admin_note );
		$request = Requests_Table::instance()->get( $request_id );
		$order   = $request ? wc_get_order( $request['order_id'] ) : null;

		if ( $order ) {
			$order->add_order_note( sprintf( __( 'Richiesta di recesso #%1$d aggiornata a: %2$s', 'indiesoft-woocommerce-recesso' ), $request_id, self::status_label( $status ) ) );
			$order->save();
			Emails::instance()->notify_status_changed( $request, $order );
		}

		wp_safe_redirect( add_query_arg( array( 'page' => 'iswcr-requests', 'request_id' => $request_id, 'updated' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Permessi insufficienti.', 'indiesoft-woocommerce-recesso' ) );
		}

		$settings = Settings::get();
		$statuses = wc_get_order_statuses();
		?>
		<div class="wrap iswcr-wrap">
			<?php $this->render_brand_header( __( 'Impostazioni recesso', 'indiesoft-woocommerce-recesso' ) ); ?>
			<form method="post" action="options.php">
				<?php settings_fields( 'iswcr_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Abilita modulo', 'indiesoft-woocommerce-recesso' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[enabled]" value="yes" <?php checked( $settings['enabled'], 'yes' ); ?>> <?php esc_html_e( 'Attivo', 'indiesoft-woocommerce-recesso' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="iswcr_endpoint"><?php esc_html_e( 'Endpoint account', 'indiesoft-woocommerce-recesso' ); ?></label></th>
						<td><input id="iswcr_endpoint" class="regular-text" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[endpoint]" value="<?php echo esc_attr( $settings['endpoint'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="iswcr_days"><?php esc_html_e( 'Giorni disponibili', 'indiesoft-woocommerce-recesso' ); ?></label></th>
						<td><input id="iswcr_days" type="number" min="0" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[request_window_days]" value="<?php echo esc_attr( $settings['request_window_days'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Stati ordine idonei', 'indiesoft-woocommerce-recesso' ); ?></th>
						<td>
							<?php foreach ( $statuses as $status_key => $label ) : ?>
								<?php $value = str_replace( 'wc-', '', $status_key ); ?>
								<label class="iswcr-check"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[eligible_statuses][]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, (array) $settings['eligible_statuses'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="iswcr_reasons"><?php esc_html_e( 'Motivi selezionabili', 'indiesoft-woocommerce-recesso' ); ?></label></th>
						<td><textarea id="iswcr_reasons" class="large-text" rows="5" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[reasons]"><?php echo esc_textarea( $settings['reasons'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="iswcr_methods"><?php esc_html_e( 'Metodi preferiti', 'indiesoft-woocommerce-recesso' ); ?></label></th>
						<td><textarea id="iswcr_methods" class="large-text" rows="4" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[refund_methods]"><?php echo esc_textarea( $settings['refund_methods'] ); ?></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="iswcr_policy"><?php esc_html_e( 'Testo informativo', 'indiesoft-woocommerce-recesso' ); ?></label></th>
						<td><textarea id="iswcr_policy" class="large-text" rows="5" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[policy_text]"><?php echo esc_textarea( $settings['policy_text'] ); ?></textarea><p class="description"><?php esc_html_e( 'Usa {days} per inserire il numero di giorni configurato.', 'indiesoft-woocommerce-recesso' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="iswcr_button"><?php esc_html_e( 'Etichetta pulsante', 'indiesoft-woocommerce-recesso' ); ?></label></th>
						<td><input id="iswcr_button" class="regular-text" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[button_label]" value="<?php echo esc_attr( $settings['button_label'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Notifiche', 'indiesoft-woocommerce-recesso' ); ?></th>
						<td>
							<label class="iswcr-check"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[customer_email]" value="yes" <?php checked( $settings['customer_email'], 'yes' ); ?>> <?php esc_html_e( 'Invia email al cliente', 'indiesoft-woocommerce-recesso' ); ?></label>
							<label class="iswcr-check"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[admin_email_enabled]" value="yes" <?php checked( $settings['admin_email_enabled'], 'yes' ); ?>> <?php esc_html_e( 'Invia email amministratore', 'indiesoft-woocommerce-recesso' ); ?></label>
							<input type="email" class="regular-text" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[admin_email]" value="<?php echo esc_attr( $settings['admin_email'] ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Conferme cliente', 'indiesoft-woocommerce-recesso' ); ?></th>
						<td>
							<label class="iswcr-check"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[terms_required]" value="yes" <?php checked( $settings['terms_required'], 'yes' ); ?>> <?php esc_html_e( 'Richiedi accettazione condizioni', 'indiesoft-woocommerce-recesso' ); ?></label>
							<label class="iswcr-check"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[allow_guest_by_email]" value="yes" <?php checked( $settings['allow_guest_by_email'], 'yes' ); ?>> <?php esc_html_e( 'Consenti recesso per ordini guest tramite numero ordine ed email', 'indiesoft-woocommerce-recesso' ); ?></label>
							<label class="iswcr-check"><input type="checkbox" name="<?php echo esc_attr( Settings::OPTION_NAME ); ?>[allow_multiple]" value="yes" <?php checked( $settings['allow_multiple'], 'yes' ); ?>> <?php esc_html_e( 'Consenti piu richieste aperte per lo stesso ordine', 'indiesoft-woocommerce-recesso' ); ?></label>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	private function render_brand_header( $title ) {
		$logo = ISWCR_URL . 'assets/images/vendor-logo.png';
		echo '<div class="iswcr-brand">';
		echo '<img src="' . esc_url( $logo ) . '" alt="IndieSoft">';
		echo '<div><h1>' . esc_html( $title ) . '</h1><p>IndieSoft WooCommerce Recesso</p></div>';
		echo '</div>';
	}
}
