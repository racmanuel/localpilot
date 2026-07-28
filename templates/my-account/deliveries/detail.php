<?php
/**
 * My Account — Mis entregas: detail view.
 *
 * @see Localpilot_My_Account::render_detail()
 *
 * @var object         $assignment  Assignment row object.
 * @var WC_Order       $order       WooCommerce order.
 * @var Localpilot_Order_Delivery_Meta $meta  Meta adapter.
 * @var string         $status      Current delivery status.
 * @var array          $events      Recent events.
 * @var bool           $show_total  Whether to show order total.
 * @var bool           $can_accept  Whether accept action is available.
 * @var bool           $can_start   Whether start action is available.
 * @var bool           $can_complete Whether complete action is available.
 * @var bool           $can_fail    Whether fail action is available.
 * @var string         $nonce_field Nonce field HTML.
 * @var int            $driver_id   Current user ID.
 */

defined( 'ABSPATH' ) || exit;

$status_label = Localpilot_Delivery_Status::label( $status );
$status_class = Localpilot_Delivery_Status::status_class( $status );

$shipping_name    = $order->get_shipping_first_name() ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();
$shipping_address = $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1();
$shipping_city    = $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city();
$shipping_phone   = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
?>

<p>
	<a href="<?php echo esc_url( wc_get_account_endpoint_url( 'mis-entregas' ) ); ?>" class="woocommerce-button button">
		&larr; <?php esc_html_e( 'Volver a mis entregas', 'localpilot' ); ?>
	</a>
</p>

<div class="lclplt-delivery-detail">
	<table class="woocommerce-orders-table shop_table">
		<tbody>
			<tr>
				<th><?php esc_html_e( 'Pedido', 'localpilot' ); ?></th>
				<td>
					<strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong>
					— <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Estado', 'localpilot' ); ?></th>
				<td>
					<mark class="lclplt-badge lclplt-badge--<?php echo esc_attr( $status_class ); ?>">
						<?php echo esc_html( $status_label ); ?>
					</mark>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Cliente', 'localpilot' ); ?></th>
				<td><?php echo esc_html( $shipping_name ); ?></td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Teléfono', 'localpilot' ); ?></th>
				<td>
					<?php if ( $shipping_phone ) : ?>
						<a href="tel:<?php echo esc_attr( $shipping_phone ); ?>"><?php echo esc_html( $shipping_phone ); ?></a>
					<?php else : ?>
						<span class="na">—</span>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Dirección', 'localpilot' ); ?></th>
				<td>
					<?php echo esc_html( $shipping_address ); ?>,
					<?php echo esc_html( $shipping_city ); ?>
				</td>
			</tr>

			<?php if ( $show_total ) : ?>
			<tr>
				<th><?php esc_html_e( 'Total', 'localpilot' ); ?></th>
				<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>

	<?php
	// Map — only if coordinates exist.
	$map_lat = $meta->get_latitude();
	$map_lng = $meta->get_longitude();
	$map_token = get_option( 'lclplt_mapbox_token', '' );
	$map_style = get_option( 'lclplt_mapbox_style', 'streets-v12' );
	$map_zoom  = get_option( 'lclplt_mapbox_zoom', 14 );
	$map_enabled = 'yes' === get_option( 'lclplt_enable_mapbox', 'no' );
	?>

	<?php if ( $map_enabled && $map_lat && $map_lng && $map_token ) : ?>
		<h3><?php esc_html_e( 'Ubicación', 'localpilot' ); ?></h3>
		<div id="lclplt-map"
			style="width:100%;height:300px;border-radius:4px;margin-bottom:1em;"
			data-lat="<?php echo esc_attr( $map_lat ); ?>"
			data-lng="<?php echo esc_attr( $map_lng ); ?>"
			data-token="<?php echo esc_attr( $map_token ); ?>"
			data-style="<?php echo esc_attr( $map_style ); ?>"
			data-zoom="<?php echo esc_attr( $map_zoom ); ?>"></div>
		<p>
			<a href="https://www.google.com/maps/dir/?api=1&destination=<?php echo esc_attr( $map_lat . ',' . $map_lng ); ?>"
				target="_blank"
				rel="noopener"
				class="woocommerce-button button">
				<?php esc_html_e( 'Abrir en Google Maps', 'localpilot' ); ?>
			</a>
		</p>
	<?php endif; ?>

	<?php if ( $order->get_items() ) : ?>
	<h3><?php esc_html_e( 'Productos', 'localpilot' ); ?></h3>
	<table class="woocommerce-orders-table shop_table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Producto', 'localpilot' ); ?></th>
				<th><?php esc_html_e( 'Cant.', 'localpilot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $order->get_items() as $item ) : ?>
			<tr>
				<td><?php echo esc_html( $item->get_name() ); ?></td>
				<td><?php echo esc_html( $item->get_quantity() ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<?php
	// Display receiver and notes if available.
	$received_by = $meta->get_received_by();
	$delivery_notes = $meta->get_delivery_notes();
	?>
	<?php if ( $received_by ) : ?>
	<h3><?php esc_html_e( 'Datos de la entrega', 'localpilot' ); ?></h3>
	<table class="woocommerce-orders-table shop_table">
		<tbody>
			<?php if ( $received_by ) : ?>
			<tr>
				<th><?php esc_html_e( 'Recibido por', 'localpilot' ); ?></th>
				<td><?php echo esc_html( $received_by ); ?></td>
			</tr>
			<?php endif; ?>
			<?php if ( $delivery_notes ) : ?>
			<tr>
				<th><?php esc_html_e( 'Notas', 'localpilot' ); ?></th>
				<td><?php echo esc_html( $delivery_notes ); ?></td>
			</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>

<?php
// Action forms.
$assignment_id = (int) $assignment->id;
?>

<?php if ( $can_accept ) : ?>
	<form method="post" class="lclplt-action-form">
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" />
		<input type="hidden" name="lclplt_delivery_action" value="accept" />
		<button type="submit" class="woocommerce-button button alt">
			<?php esc_html_e( 'Aceptar entrega', 'localpilot' ); ?>
		</button>
	</form>
<?php endif; ?>

<?php if ( $can_start ) : ?>
	<form method="post" class="lclplt-action-form">
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" />
		<input type="hidden" name="lclplt_delivery_action" value="start" />
		<button type="submit" class="woocommerce-button button">
			<?php esc_html_e( 'Iniciar reparto', 'localpilot' ); ?>
		</button>
	</form>
<?php endif; ?>

<?php if ( $can_complete ) : ?>
	<form method="post" enctype="multipart/form-data" class="lclplt-action-form lclplt-action-form--complete">
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" />

		<h3><?php esc_html_e( 'Completar entrega', 'localpilot' ); ?></h3>

		<p class="form-row">
			<label for="lclplt_received_by">
				<?php esc_html_e( 'Recibido por', 'localpilot' ); ?>
				<?php if ( 'yes' === get_option( 'lclplt_require_received_by', 'yes' ) ) : ?>
					<abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'localpilot' ); ?>">*</abbr>
				<?php endif; ?>
			</label>
			<input type="text"
				name="lclplt_received_by"
				id="lclplt_received_by"
				class="input-text"
				<?php echo 'yes' === get_option( 'lclplt_require_received_by', 'yes' ) ? 'required' : ''; ?>
				placeholder="<?php esc_attr_e( 'Nombre de quien recibe', 'localpilot' ); ?>" />
		</p>

		<p class="form-row">
			<label for="lclplt_delivery_notes">
				<?php esc_html_e( 'Notas de entrega', 'localpilot' ); ?>
			</label>
			<textarea name="lclplt_delivery_notes"
				id="lclplt_delivery_notes"
				class="input-text"
				rows="3"
				placeholder="<?php esc_attr_e( 'Observaciones (opcional)', 'localpilot' ); ?>"></textarea>
		</p>

		<p class="form-row">
			<label for="lclplt_proof">
				<?php esc_html_e( 'Evidencia (foto)', 'localpilot' ); ?>
				<?php if ( 'yes' === get_option( 'lclplt_require_proof', 'yes' ) ) : ?>
					<abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'localpilot' ); ?>">*</abbr>
				<?php endif; ?>
			</label>
			<input type="file"
				name="lclplt_proof"
				id="lclplt_proof"
				accept="image/jpeg,image/png,image/webp"
				class="input-text"
				<?php echo 'yes' === get_option( 'lclplt_require_proof', 'yes' ) ? 'required' : ''; ?> />
			<small style="display:block;color:#666;font-size:11px;margin-top:4px;">
				<?php esc_html_e( 'JPG, PNG o WebP. Máximo', 'localpilot' ); ?>
				<?php echo esc_html( get_option( 'lclplt_max_proof_size', 5 ) . ' MB.' ); ?>
			</small>
		</p>

		<?php if ( Localpilot_Location_Validation_Service::is_enabled() ) : ?>
		<p class="form-row lclplt-location-validation" data-lclplt-location-validation="1"
			data-mode="<?php echo esc_attr( Localpilot_Location_Validation_Service::get_mode() ); ?>"
			data-message-request="<?php esc_attr_e( 'Obteniendo tu ubicación actual…', 'localpilot' ); ?>"
			data-message-success="<?php esc_attr_e( 'Ubicación obtenida. Se validará en el servidor al completar.', 'localpilot' ); ?>"
			data-message-permission="<?php esc_attr_e( 'Debes permitir el acceso a tu ubicación o continuar con advertencia.', 'localpilot' ); ?>"
			data-message-unavailable="<?php esc_attr_e( 'No se pudo obtener tu ubicación. Activa el GPS e inténtalo nuevamente.', 'localpilot' ); ?>"
			data-message-timeout="<?php esc_attr_e( 'La solicitud de ubicación tardó demasiado. Inténtalo nuevamente.', 'localpilot' ); ?>">
			<strong><?php esc_html_e( 'Validación de ubicación', 'localpilot' ); ?></strong>
			<small class="lclplt-location-validation__message" role="status" aria-live="polite" style="display:block;color:#666;margin-top:4px;">
				<?php esc_html_e( 'Al completar, se solicitará tu ubicación actual para compararla con el destino.', 'localpilot' ); ?>
			</small>
			<input type="hidden" name="lclplt_location_latitude" value="" />
			<input type="hidden" name="lclplt_location_longitude" value="" />
			<input type="hidden" name="lclplt_location_accuracy" value="" />
			<input type="hidden" name="lclplt_location_timestamp" value="" />
			<input type="hidden" name="lclplt_location_status" value="" />
			<button type="button" class="button lclplt-location-retry" style="display:none;margin-top:6px;">
				<?php esc_html_e( 'Intentar nuevamente', 'localpilot' ); ?>
			</button>
			<?php if ( 'warning' === Localpilot_Location_Validation_Service::get_mode() ) : ?>
				<button type="button" class="button lclplt-location-continue" style="display:none;margin-top:6px;">
					<?php esc_html_e( 'Continuar con advertencia', 'localpilot' ); ?>
				</button>
			<?php endif; ?>
		</p>
		<?php endif; ?>

		<input type="hidden" name="lclplt_delivery_action" value="complete" />
		<button type="submit" class="woocommerce-button button alt lclplt-complete-submit">
			<?php esc_html_e( 'Completar entrega', 'localpilot' ); ?>
		</button>
	</form>
<?php endif; ?>

<?php if ( $can_fail ) : ?>
	<form method="post" enctype="multipart/form-data" class="lclplt-action-form lclplt-action-form--fail">
		<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" />
		<input type="hidden" name="lclplt_delivery_action" value="fail" />

		<h3><?php esc_html_e( 'Reportar fallo', 'localpilot' ); ?></h3>

		<p class="form-row">
			<label for="lclplt_failed_reason">
				<?php esc_html_e( 'Motivo del fallo', 'localpilot' ); ?>
				<abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'localpilot' ); ?>">*</abbr>
			</label>
			<select name="lclplt_failed_reason" id="lclplt_failed_reason" class="input-text" required>
				<option value=""><?php esc_html_e( 'Seleccionar motivo...', 'localpilot' ); ?></option>
				<?php
				$reasons = apply_filters( 'lclplt_failure_reasons', array(
					'cliente_ausente' => __( 'Cliente ausente', 'localpilot' ),
					'direccion_incorrecta' => __( 'Dirección incorrecta', 'localpilot' ),
					'producto_danado' => __( 'Producto dañado', 'localpilot' ),
					'rechazado'       => __( 'Cliente rechazó el pedido', 'localpilot' ),
					'otro'            => __( 'Otro', 'localpilot' ),
				) );
				foreach ( $reasons as $value => $label ) :
				?>
				<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</p>

		<p class="form-row">
			<label for="lclplt_proof_fail">
				<?php esc_html_e( 'Evidencia (foto)', 'localpilot' ); ?>
			</label>
			<input type="file"
				name="lclplt_proof"
				id="lclplt_proof_fail"
				accept="image/jpeg,image/png,image/webp"
				class="input-text" />
			<small style="display:block;color:#666;font-size:11px;margin-top:4px;">
				<?php esc_html_e( 'Opcional. JPG, PNG o WebP. Máximo', 'localpilot' ); ?>
				<?php echo esc_html( get_option( 'lclplt_max_proof_size', 5 ) . ' MB.' ); ?>
			</small>
		</p>

		<button type="submit" class="woocommerce-button button">
			<?php esc_html_e( 'Reportar fallo', 'localpilot' ); ?>
		</button>
	</form>
<?php endif; ?>

<?php if ( ! empty( $events ) ) : ?>
	<h3><?php esc_html_e( 'Historial', 'localpilot' ); ?></h3>
	<table class="woocommerce-orders-table shop_table">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Fecha', 'localpilot' ); ?></th>
				<th><?php esc_html_e( 'Evento', 'localpilot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $events as $event ) : ?>
			<tr>
				<td><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $event->created_at ) ) ); ?></td>
				<td><?php echo esc_html( $event->event_type ); ?></td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
