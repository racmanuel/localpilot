<?php
/**
 * LocalPilot meta box panel for the order edit screen.
 *
 * Variables defined by the caller (Localpilot_Order_Editor::render_meta_box):
 *   $order         WC_Order
 *   $meta          Localpilot_Order_Delivery_Meta
 *   $status        string
 *   $driver_id     int
 *   $assignment_id int
 *   $can_manage    bool
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin/partials
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="lclplt-panel">
	<?php wp_nonce_field( 'lclplt_order_delivery', 'lclplt_delivery_nonce' ); ?>

	<?php if ( 'unassigned' === $status || empty( $status ) ) : ?>
		<p><em><?php esc_html_e( 'Sin asignar', 'localpilot' ); ?></em></p>
	<?php else : ?>
		<table class="lclplt-panel__info" style="width:100%; border-collapse:collapse;">
			<tbody>
				<tr>
					<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php esc_html_e( 'Estado', 'localpilot' ); ?></th>
					<td style="padding:2px 0;">
						<mark class="lclplt-badge lclplt-badge--<?php echo esc_attr( Localpilot_Delivery_Status::status_class( $status ) ); ?>">
							<?php echo esc_html( Localpilot_Delivery_Status::label( $status ) ); ?>
						</mark>
					</td>
				</tr>

				<?php if ( $driver_id ) : ?>
				<tr>
					<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php esc_html_e( 'Repartidor', 'localpilot' ); ?></th>
					<td style="padding:2px 0;">
						<?php
						$driver = get_userdata( $driver_id );
						if ( $driver ) {
							echo esc_html( $driver->display_name );
						} else {
							echo '<em>' . esc_html__( 'Usuario eliminado', 'localpilot' ) . '</em>';
						}
						?>
					</td>
				</tr>
				<?php endif; ?>

				<?php
				$timestamps = array(
					__( 'Asignado', 'localpilot' )       => $meta->get_assigned_at(),
					__( 'Aceptado', 'localpilot' )       => $meta->get_accepted_at(),
					__( 'En reparto', 'localpilot' )     => $meta->get_out_for_delivery_at(),
					__( 'Entregado', 'localpilot' )      => $meta->get_delivered_at(),
					__( 'Fallido', 'localpilot' )        => $meta->get_failed_at(),
				);
				foreach ( $timestamps as $label => $ts ) :
					if ( ! $ts ) {
						continue;
					}
					$formatted = wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $ts ) );
					?>
					<tr>
						<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php echo esc_html( $label ); ?></th>
						<td style="padding:2px 0;"><?php echo esc_html( $formatted ); ?></td>
					</tr>
				<?php endforeach; ?>

				<?php
				$gstatus = $meta->get_geocoding_status();
				if ( $gstatus ) :
				?>
				<tr>
					<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php esc_html_e( 'Geocod.', 'localpilot' ); ?></th>
					<td style="padding:2px 0;">
						<?php
						if ( 'success' === $gstatus ) {
							esc_html_e( 'Completada', 'localpilot' );
						} elseif ( 'failed' === $gstatus ) {
							esc_html_e( 'Fallida', 'localpilot' );
						} elseif ( 'manual' === $gstatus ) {
							esc_html_e( 'Manual', 'localpilot' );
						} else {
							esc_html_e( 'Pendiente', 'localpilot' );
						}
						?>
					</td>
				</tr>
				<?php endif; ?>

				<?php
				$received_by = $meta->get_received_by();
				if ( $received_by ) :
				?>
				<tr>
					<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php esc_html_e( 'Recibido por', 'localpilot' ); ?></th>
					<td style="padding:2px 0;"><?php echo esc_html( $received_by ); ?></td>
				</tr>
				<?php endif; ?>

				<?php
				$proof_id = $meta->get_proof_attachment_id();
				if ( $proof_id ) :
				?>
				<tr>
					<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php esc_html_e( 'Evidencia', 'localpilot' ); ?></th>
					<td style="padding:2px 0;">
						<?php
						$proof_url  = wp_get_attachment_url( $proof_id );
						$proof_thumb = wp_get_attachment_image_url( $proof_id, array( 150, 150 ) );
						if ( $proof_thumb ) {
							echo '<a href="' . esc_url( $proof_url ) . '" target="_blank">';
							echo '<img src="' . esc_url( $proof_thumb ) . '" alt="' . esc_attr__( 'Evidencia de entrega', 'localpilot' ) . '" style="max-width:150px;height:auto;border:1px solid #ddd;border-radius:4px;" />';
							echo '</a>';
						} elseif ( $proof_url ) {
							echo '<a href="' . esc_url( $proof_url ) . '" target="_blank">' . esc_html__( 'Ver archivo', 'localpilot' ) . '</a>';
						} else {
							echo '#' . esc_html( $proof_id );
						}
						?>
					</td>
				</tr>
				<?php endif; ?>

				<?php
				$location_status = $meta->get_location_validation_status();
				if ( $location_status ) :
					$location_labels = array(
						'passed'           => __( 'Validada', 'localpilot' ),
						'outside_radius'   => __( 'Fuera del radio', 'localpilot' ),
						'permission_denied'=> __( 'Permiso denegado', 'localpilot' ),
						'unavailable'      => __( 'No disponible', 'localpilot' ),
						'timeout'          => __( 'Tiempo agotado', 'localpilot' ),
						'stale'            => __( 'Desactualizada', 'localpilot' ),
						'low_accuracy'     => __( 'Precisión insuficiente', 'localpilot' ),
						'no_destination'   => __( 'Sin destino geocodificado', 'localpilot' ),
						'disabled'         => __( 'Desactivada', 'localpilot' ),
					);
					$location_label = isset( $location_labels[ $location_status ] ) ? $location_labels[ $location_status ] : __( 'No validada', 'localpilot' );
					$location_distance = $meta->get_location_validation_distance();
					$location_radius   = $meta->get_location_validation_radius();
					$location_accuracy = $meta->get_location_validation_accuracy();
					$location_at       = $meta->get_location_validation_at();
				?>
				<tr>
					<th style="text-align:left; padding:2px 4px 2px 0; font-weight:600;"><?php esc_html_e( 'Ubicación al entregar', 'localpilot' ); ?></th>
					<td style="padding:2px 0;">
						<strong><?php echo esc_html( $location_label ); ?></strong>
						<?php if ( '' !== (string) $location_distance && null !== $location_distance ) : ?>
							<br /><?php /* translators: %s: measured distance in metres */ ?><?php echo esc_html( sprintf( __( 'Distancia: %s m', 'localpilot' ), number_format_i18n( (float) $location_distance, 2 ) ) ); ?>
						<?php endif; ?>
						<?php if ( $location_radius ) : ?>
							<br /><?php /* translators: %s: configured radius in metres */ ?><?php echo esc_html( sprintf( __( 'Radio: %s m', 'localpilot' ), number_format_i18n( (int) $location_radius ) ) ); ?>
						<?php endif; ?>
						<?php if ( '' !== (string) $location_accuracy && null !== $location_accuracy ) : ?>
							<br /><?php /* translators: %s: reported GPS accuracy in metres */ ?><?php echo esc_html( sprintf( __( 'Precisión: %s m', 'localpilot' ), number_format_i18n( (float) $location_accuracy, 2 ) ) ); ?>
						<?php endif; ?>
						<?php if ( $location_at ) : ?>
							<br /><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $location_at ) ) ); ?>
						<?php endif; ?>
					</td>
				</tr>
				<?php endif; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php if ( $can_manage ) : ?>
		<hr style="margin:12px 0;" />

		<p>
			<label for="lclplt_driver_id" style="display:block; margin-bottom:4px; font-weight:600;">
				<?php esc_html_e( 'Repartidor', 'localpilot' ); ?>
			</label>
			<select name="lclplt_driver_id" id="lclplt_driver_id" class="wc-enhanced-select" style="width:100%;">
				<option value=""><?php esc_html_e( 'Seleccionar repartidor', 'localpilot' ); ?></option>
				<?php foreach ( Localpilot_Driver_Repository::get_active() as $driver ) : ?>
					<option value="<?php echo esc_attr( $driver->ID ); ?>" <?php selected( $driver_id, $driver->ID ); ?>>
						<?php echo esc_html( $driver->display_name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>

		<p style="margin-top:8px;">
			<?php if ( empty( $status ) || 'unassigned' === $status ) : ?>
				<input type="hidden" name="lclplt_delivery_action" value="assign" />
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Asignar repartidor', 'localpilot' ); ?>
				</button>
			<?php else : ?>
				<input type="hidden" name="lclplt_delivery_action" value="" id="lclplt_delivery_action_input" />
				<button type="submit" class="button"
					onclick="document.getElementById('lclplt_delivery_action_input').value='reassign';
					return confirm('<?php echo esc_js( __( '¿Reasignar a otro repartidor? El repartidor actual perderá acceso.', 'localpilot' ) ); ?>');">
					<?php esc_html_e( 'Reasignar', 'localpilot' ); ?>
				</button>
				<button type="submit" class="button"
					style="color:#a00;"
					onclick="document.getElementById('lclplt_delivery_action_input').value='unassign';
					return confirm('<?php echo esc_js( __( '¿Retirar la asignación actual? El repartidor perderá acceso al pedido.', 'localpilot' ) ); ?>');">
					<?php esc_html_e( 'Retirar', 'localpilot' ); ?>
				</button>
			<?php endif; ?>
		</p>

		<?php
		// Admin map for geocoding correction.
		$map_enabled  = 'yes' === get_option( 'lclplt_enable_mapbox', 'no' );
		$map_token    = get_option( 'lclplt_mapbox_token', '' );
		$map_style    = get_option( 'lclplt_mapbox_style', 'streets-v12' );
		$map_zoom     = get_option( 'lclplt_mapbox_zoom', 14 );
		$map_lat      = $meta->get_latitude();
		$map_lng      = $meta->get_longitude();
		?>
		<?php if ( $map_enabled && $map_token ) : ?>
			<hr style="margin:12px 0;" />
			<p style="font-weight:600; margin:0 0 4px;"><?php esc_html_e( 'Ubicación — mapa de entrega', 'localpilot' ); ?></p>
			<p style="font-size:11px; color:#666; margin:0 0 8px;">
				<?php esc_html_e( '🔴 Destino | 🟢 GPS de entrega | El círculo verde muestra el radio de validación configurado. Arrastra el marcador rojo para corregir el destino.', 'localpilot' ); ?>
			</p>
		<?php
		$driver_lat = $meta->get_delivery_location_lat();
		$driver_lng = $meta->get_delivery_location_lng();
		$validation_radius = Localpilot_Location_Validation_Service::get_radius();
		?>
		<div id="lclplt-admin-map"
			style="width:100%;height:350px;border-radius:4px;margin-bottom:8px;"
			data-lat="<?php echo esc_attr( $map_lat ?: '' ); ?>"
			data-lng="<?php echo esc_attr( $map_lng ?: '' ); ?>"
			data-delivery-lat="<?php echo esc_attr( $driver_lat ?: '' ); ?>"
			data-delivery-lng="<?php echo esc_attr( $driver_lng ?: '' ); ?>"
			data-validation-radius="<?php echo esc_attr( $validation_radius ); ?>"
			data-token="<?php echo esc_attr( $map_token ); ?>"
			data-style="<?php echo esc_attr( $map_style ); ?>"
			data-zoom="<?php echo esc_attr( $map_zoom ); ?>"></div>
			<p style="margin:0 0 4px;">
				<label style="font-size:11px;">
					<?php esc_html_e( 'Latitud:', 'localpilot' ); ?>
					<input type="text" name="lclplt_correction_lat" id="lclplt_correction_lat"
						value="<?php echo esc_attr( $map_lat ?: '' ); ?>"
						style="width:120px; font-size:11px;" readonly />
				</label>
				<label style="font-size:11px; margin-left:8px;">
					<?php esc_html_e( 'Longitud:', 'localpilot' ); ?>
					<input type="text" name="lclplt_correction_lng" id="lclplt_correction_lng"
						value="<?php echo esc_attr( $map_lng ?: '' ); ?>"
						style="width:120px; font-size:11px;" readonly />
				</label>
				<button type="submit" class="button button-small"
					style="margin-left:8px;"
					onclick="document.getElementById('lclplt_delivery_action_input').value='update_location';">
					<?php esc_html_e( 'Guardar ubicación', 'localpilot' ); ?>
				</button>
			</p>
		<?php endif; ?>

		<?php
		// Show recent events if any.
		if ( $assignment_id ) :
			$events = Localpilot_Event_Repository::get_by_assignment( $assignment_id, 5 );
			if ( ! empty( $events ) ) :
			?>
			<hr style="margin:12px 0;" />
			<p style="font-weight:600; margin:0 0 4px;"><?php esc_html_e( 'Eventos recientes', 'localpilot' ); ?></p>
			<ul style="margin:0; padding:0; list-style:none; font-size:11px; color:#666;">
				<?php foreach ( $events as $event ) : ?>
					<li style="padding:1px 0;">
						<?php
						$event_time = wp_date( get_option( 'time_format' ), strtotime( $event->created_at ) );
						$event_date = wp_date( get_option( 'date_format' ), strtotime( $event->created_at ) );
						echo esc_html( $event_date . ' ' . $event_time . ' — ' . $event->event_type );
						?>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php endif; ?>
		<?php endif; ?>
	<?php endif; ?>
</div>
