<?php
/**
 * LocalPilot meta box panel for the order edit screen.
 *
 * Variables defined by Localpilot_Order_Editor::render_meta_box():
 * $order, $meta, $status, $driver_id, $assignment_id, $can_manage.
 *
 * @package Localpilot
 * @subpackage Localpilot/admin/partials
 */

defined( 'ABSPATH' ) || exit;

$is_terminal = Localpilot_Delivery_Status::is_terminal( $status );
$driver      = $driver_id ? get_userdata( $driver_id ) : false;
$driver_name = $driver ? $driver->display_name : '';

$location_labels = array(
	'passed'            => __( 'Validada', 'localpilot' ),
	'outside_radius'    => __( 'Fuera del radio', 'localpilot' ),
	'permission_denied' => __( 'Permiso denegado', 'localpilot' ),
	'unavailable'       => __( 'No disponible', 'localpilot' ),
	'timeout'           => __( 'Tiempo agotado', 'localpilot' ),
	'stale'             => __( 'Desactualizada', 'localpilot' ),
	'low_accuracy'      => __( 'Precisión insuficiente', 'localpilot' ),
	'no_destination'    => __( 'Sin destino geocodificado', 'localpilot' ),
	'disabled'          => __( 'Desactivada', 'localpilot' ),
);
$location_status   = $meta->get_location_validation_status();
$location_label    = isset( $location_labels[ $location_status ] ) ? $location_labels[ $location_status ] : __( 'Sin validar', 'localpilot' );
$location_distance = $meta->get_location_validation_distance();
$location_radius   = $meta->get_location_validation_radius();
$location_accuracy = $meta->get_location_validation_accuracy();
$location_at       = $meta->get_location_validation_at();
$location_tone     = 'neutral';

if ( 'passed' === $location_status ) {
	$location_tone = 'success';
} elseif ( in_array( $location_status, array( 'outside_radius', 'low_accuracy', 'stale', 'no_destination', 'disabled' ), true ) ) {
	$location_tone = 'warning';
} elseif ( in_array( $location_status, array( 'permission_denied', 'unavailable', 'timeout' ), true ) ) {
	$location_tone = 'error';
}

$progress_steps = array(
	array(
		'status' => Localpilot_Delivery_Status::ASSIGNED,
		'label'  => __( 'Asignado', 'localpilot' ),
		'time'   => $meta->get_assigned_at(),
	),
	array(
		'status' => Localpilot_Delivery_Status::ACCEPTED,
		'label'  => __( 'Aceptado', 'localpilot' ),
		'time'   => $meta->get_accepted_at(),
	),
	array(
		'status' => Localpilot_Delivery_Status::OUT_FOR_DELIVERY,
		'label'  => __( 'En reparto', 'localpilot' ),
		'time'   => $meta->get_out_for_delivery_at(),
	),
);

if ( Localpilot_Delivery_Status::FAILED === $status ) {
	$progress_steps[] = array(
		'status' => Localpilot_Delivery_Status::FAILED,
		'label'  => __( 'Fallido', 'localpilot' ),
		'time'   => $meta->get_failed_at(),
	);
} elseif ( Localpilot_Delivery_Status::CANCELLED === $status ) {
	$progress_steps[] = array(
		'status' => Localpilot_Delivery_Status::CANCELLED,
		'label'  => __( 'Cancelado', 'localpilot' ),
		'time'   => '',
	);
} else {
	$progress_steps[] = array(
		'status' => Localpilot_Delivery_Status::DELIVERED,
		'label'  => __( 'Entregado', 'localpilot' ),
		'time'   => $meta->get_delivered_at(),
	);
}

$proof_id       = $meta->get_proof_attachment_id();
$received_by    = $meta->get_received_by();
$delivery_notes = $meta->get_delivery_notes();
$failed_reason  = $meta->get_failed_reason();
$has_proof_data = $proof_id || $received_by || $delivery_notes || $failed_reason || $location_status;

$map_enabled = 'yes' === get_option( 'lclplt_enable_mapbox', 'no' );
$map_token   = get_option( 'lclplt_mapbox_token', '' );
$map_style   = get_option( 'lclplt_mapbox_style', 'streets-v12' );
$map_zoom    = get_option( 'lclplt_mapbox_zoom', 14 );
$map_lat     = $meta->get_latitude();
$map_lng     = $meta->get_longitude();

if ( $is_terminal && '' !== (string) $meta->get_location_validation_target_lat() && '' !== (string) $meta->get_location_validation_target_lng() ) {
	$map_lat = $meta->get_location_validation_target_lat();
	$map_lng = $meta->get_location_validation_target_lng();
}

$driver_lat        = $meta->get_delivery_location_lat();
$driver_lng        = $meta->get_delivery_location_lng();
$validation_radius = $location_radius ? absint( $location_radius ) : Localpilot_Location_Validation_Service::get_radius();
$gstatus           = $meta->get_geocoding_status();
$events            = $assignment_id ? Localpilot_Event_Repository::get_recent_by_assignment( $assignment_id, 8 ) : array();
?>

<div class="lclplt-panel">
	<?php wp_nonce_field( 'lclplt_order_delivery', 'lclplt_delivery_nonce' ); ?>
	<input type="hidden" name="lclplt_delivery_action" value="" id="lclplt_delivery_action_input" />

	<section class="lclplt-section lclplt-section--summary" aria-labelledby="lclplt-summary-title">
		<div class="lclplt-section__heading">
			<div>
				<h3 id="lclplt-summary-title"><?php esc_html_e( 'Resumen de entrega', 'localpilot' ); ?></h3>
				<p><?php esc_html_e( 'Información operativa y estado actual del pedido.', 'localpilot' ); ?></p>
			</div>
			<mark class="lclplt-badge lclplt-badge--<?php echo esc_attr( Localpilot_Delivery_Status::status_class( $status ) ); ?>">
				<?php echo esc_html( Localpilot_Delivery_Status::label( $status ) ); ?>
			</mark>
		</div>

		<div class="lclplt-summary-grid">
			<div class="lclplt-summary-card">
				<span class="dashicons dashicons-admin-users" aria-hidden="true"></span>
				<div>
					<span class="lclplt-summary-card__label"><?php esc_html_e( 'Repartidor', 'localpilot' ); ?></span>
					<strong><?php echo $driver_name ? esc_html( $driver_name ) : esc_html__( 'Sin asignar', 'localpilot' ); ?></strong>
					<?php if ( $driver_id && ! $driver ) : ?>
						<small><?php esc_html_e( 'El usuario ya no existe.', 'localpilot' ); ?></small>
					<?php endif; ?>
				</div>
			</div>
			<div class="lclplt-summary-card">
				<span class="dashicons dashicons-location" aria-hidden="true"></span>
				<div>
					<span class="lclplt-summary-card__label"><?php esc_html_e( 'Validación', 'localpilot' ); ?></span>
					<strong><?php echo esc_html( $location_label ); ?></strong>
					<?php if ( null !== $location_distance && '' !== (string) $location_distance ) : ?>
						<small>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: measured distance, 2: allowed radius, both in metres */
									__( '%1$s m de %2$s m', 'localpilot' ),
									number_format_i18n( (float) $location_distance, 2 ),
									number_format_i18n( (int) $validation_radius )
								)
							);
							?>
						</small>
					<?php endif; ?>
				</div>
			</div>
			<div class="lclplt-summary-card">
				<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
				<div>
					<span class="lclplt-summary-card__label"><?php esc_html_e( 'Última actualización', 'localpilot' ); ?></span>
					<strong>
						<?php
						$last_update = $meta->get_delivered_at() ?: ( $meta->get_failed_at() ?: ( $meta->get_out_for_delivery_at() ?: ( $meta->get_accepted_at() ?: $meta->get_assigned_at() ) ) );
						echo $last_update ? esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $last_update ) ) ) : esc_html__( 'Sin actividad', 'localpilot' );
						?>
					</strong>
				</div>
			</div>
		</div>
	</section>

	<?php if ( Localpilot_Delivery_Status::UNASSIGNED !== $status ) : ?>
		<section class="lclplt-section" aria-labelledby="lclplt-progress-title">
			<div class="lclplt-section__heading">
				<div>
					<h3 id="lclplt-progress-title"><?php esc_html_e( 'Progreso de la entrega', 'localpilot' ); ?></h3>
					<p><?php esc_html_e( 'Secuencia registrada para esta asignación.', 'localpilot' ); ?></p>
				</div>
			</div>
			<ol class="lclplt-progress">
				<?php foreach ( $progress_steps as $step ) : ?>
					<?php
					$step_classes = array( 'lclplt-progress__step' );
					if ( $step['time'] ) {
						$step_classes[] = 'is-complete';
					}
					if ( $step['status'] === $status ) {
						$step_classes[] = 'is-current';
					}
					if ( in_array( $step['status'], array( Localpilot_Delivery_Status::FAILED, Localpilot_Delivery_Status::CANCELLED ), true ) ) {
						$step_classes[] = 'is-negative';
					}
					?>
					<li class="<?php echo esc_attr( implode( ' ', $step_classes ) ); ?>"<?php echo $step['status'] === $status ? ' aria-current="step"' : ''; ?>>
						<span class="lclplt-progress__marker" aria-hidden="true"><span class="dashicons dashicons-yes"></span></span>
						<strong><?php echo esc_html( $step['label'] ); ?></strong>
						<?php if ( $step['time'] ) : ?>
							<time datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $step['time'] ) ) ); ?>">
								<?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $step['time'] ) ) ); ?>
							</time>
						<?php else : ?>
							<span><?php esc_html_e( 'Pendiente', 'localpilot' ); ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>

	<?php if ( $has_proof_data ) : ?>
		<section class="lclplt-section" aria-labelledby="lclplt-proof-title">
			<div class="lclplt-section__heading">
				<div>
					<h3 id="lclplt-proof-title"><?php esc_html_e( 'Prueba de entrega', 'localpilot' ); ?></h3>
					<p><?php esc_html_e( 'Receptor, evidencia y resultado de la validación puntual.', 'localpilot' ); ?></p>
				</div>
			</div>

			<div class="lclplt-proof-grid">
				<div class="lclplt-validation-card lclplt-validation-card--<?php echo esc_attr( $location_tone ); ?>">
					<span class="dashicons <?php echo 'passed' === $location_status ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>" aria-hidden="true"></span>
					<div>
						<span class="lclplt-validation-card__eyebrow"><?php esc_html_e( 'Ubicación al completar', 'localpilot' ); ?></span>
						<strong><?php echo esc_html( $location_label ); ?></strong>
						<?php if ( null !== $location_distance && '' !== (string) $location_distance ) : ?>
							<p>
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: measured distance, 2: allowed radius, both in metres */
										__( '%1$s m de %2$s m permitidos', 'localpilot' ),
										number_format_i18n( (float) $location_distance, 2 ),
										number_format_i18n( (int) $validation_radius )
									)
								);
								?>
							</p>
						<?php endif; ?>
						<div class="lclplt-validation-card__meta">
							<?php if ( null !== $location_accuracy && '' !== (string) $location_accuracy ) : ?>
								<span><?php echo esc_html( sprintf( /* translators: %s: GPS accuracy in metres */ __( 'Precisión: %s m', 'localpilot' ), number_format_i18n( (float) $location_accuracy, 2 ) ) ); ?></span>
							<?php endif; ?>
							<?php if ( $location_at ) : ?>
								<span><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $location_at ) ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>
				</div>

				<dl class="lclplt-delivery-details">
					<?php if ( $received_by ) : ?>
						<div><dt><?php esc_html_e( 'Recibido por', 'localpilot' ); ?></dt><dd><?php echo esc_html( $received_by ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $failed_reason ) : ?>
						<div><dt><?php esc_html_e( 'Motivo del fallo', 'localpilot' ); ?></dt><dd><?php echo esc_html( $failed_reason ); ?></dd></div>
					<?php endif; ?>
					<?php if ( $delivery_notes ) : ?>
						<div><dt><?php esc_html_e( 'Notas', 'localpilot' ); ?></dt><dd><?php echo nl2br( esc_html( $delivery_notes ) ); ?></dd></div>
					<?php endif; ?>
				</dl>

				<?php if ( $proof_id ) : ?>
					<div class="lclplt-proof-media">
						<span class="lclplt-proof-media__label"><?php esc_html_e( 'Evidencia', 'localpilot' ); ?></span>
						<?php
						$proof_url   = wp_get_attachment_url( $proof_id );
						$proof_thumb = wp_get_attachment_image_url( $proof_id, array( 240, 180 ) );
						?>
						<?php if ( $proof_thumb && $proof_url ) : ?>
							<a href="<?php echo esc_url( $proof_url ); ?>" target="_blank" rel="noopener noreferrer">
								<img src="<?php echo esc_url( $proof_thumb ); ?>" alt="<?php esc_attr_e( 'Evidencia de entrega', 'localpilot' ); ?>" width="240" height="180" />
								<span><?php esc_html_e( 'Abrir evidencia', 'localpilot' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(abre en una pestaña nueva)', 'localpilot' ); ?></span></span>
							</a>
						<?php elseif ( $proof_url ) : ?>
							<a href="<?php echo esc_url( $proof_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ver archivo de evidencia', 'localpilot' ); ?></a>
						<?php else : ?>
							<span><?php echo esc_html( '#' . $proof_id ); ?></span>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $can_manage ) : ?>
		<section class="lclplt-section" aria-labelledby="lclplt-management-title">
			<div class="lclplt-section__heading">
				<div>
					<h3 id="lclplt-management-title"><?php esc_html_e( 'Gestión de asignación', 'localpilot' ); ?></h3>
					<p><?php esc_html_e( 'Controla quién tiene acceso operativo a esta entrega.', 'localpilot' ); ?></p>
				</div>
			</div>

			<?php if ( $is_terminal ) : ?>
				<div class="lclplt-locked-notice">
					<span class="dashicons dashicons-lock" aria-hidden="true"></span>
					<div><strong><?php esc_html_e( 'Entrega cerrada', 'localpilot' ); ?></strong><p><?php esc_html_e( 'La asignación y el destino se muestran en modo de solo lectura.', 'localpilot' ); ?></p></div>
				</div>
			<?php else : ?>
				<div class="lclplt-assignment-controls">
					<label for="lclplt_driver_id"><?php esc_html_e( 'Repartidor', 'localpilot' ); ?></label>
					<select name="lclplt_driver_id" id="lclplt_driver_id" class="wc-enhanced-select">
						<option value=""><?php esc_html_e( 'Seleccionar repartidor', 'localpilot' ); ?></option>
						<?php foreach ( Localpilot_Driver_Repository::get_active() as $available_driver ) : ?>
							<option value="<?php echo esc_attr( $available_driver->ID ); ?>" <?php selected( $driver_id, $available_driver->ID ); ?>><?php echo esc_html( $available_driver->display_name ); ?></option>
						<?php endforeach; ?>
					</select>
					<div class="lclplt-assignment-actions">
						<?php if ( Localpilot_Delivery_Status::UNASSIGNED === $status ) : ?>
							<button type="submit" class="button button-primary" data-lclplt-action="assign"><?php esc_html_e( 'Asignar repartidor', 'localpilot' ); ?></button>
						<?php else : ?>
							<button type="submit" class="button button-primary" data-lclplt-action="reassign" data-lclplt-confirm="<?php esc_attr_e( '¿Reasignar a otro repartidor? El repartidor actual perderá acceso.', 'localpilot' ); ?>"><?php esc_html_e( 'Reasignar', 'localpilot' ); ?></button>
							<button type="submit" class="button lclplt-button--danger" data-lclplt-action="unassign" data-lclplt-confirm="<?php esc_attr_e( '¿Retirar la asignación actual? El repartidor perderá acceso al pedido.', 'localpilot' ); ?>"><?php esc_html_e( 'Retirar', 'localpilot' ); ?></button>
						<?php endif; ?>
					</div>
				</div>
			<?php endif; ?>
		</section>

		<section class="lclplt-section" aria-labelledby="lclplt-map-title">
			<div class="lclplt-section__heading">
				<div>
					<h3 id="lclplt-map-title"><?php esc_html_e( 'Ubicación de la entrega', 'localpilot' ); ?></h3>
					<p>
						<?php
						if ( 'success' === $gstatus ) {
							esc_html_e( 'Destino geocodificado automáticamente.', 'localpilot' );
						} elseif ( 'manual' === $gstatus ) {
							esc_html_e( 'Destino corregido manualmente.', 'localpilot' );
						} elseif ( 'failed' === $gstatus ) {
							esc_html_e( 'La geocodificación automática falló.', 'localpilot' );
						} else {
							esc_html_e( 'Destino pendiente de geocodificación.', 'localpilot' );
						}
						?>
					</p>
				</div>
				<?php if ( $is_terminal ) : ?><span class="lclplt-readonly-label"><span class="dashicons dashicons-lock" aria-hidden="true"></span><?php esc_html_e( 'Solo lectura', 'localpilot' ); ?></span><?php endif; ?>
			</div>

			<div class="lclplt-map-legend" aria-label="<?php esc_attr_e( 'Leyenda del mapa', 'localpilot' ); ?>">
				<span><i class="lclplt-map-swatch lclplt-map-swatch--destination" aria-hidden="true"></i><?php esc_html_e( 'Destino', 'localpilot' ); ?></span>
				<span><i class="lclplt-map-swatch lclplt-map-swatch--delivery" aria-hidden="true"></i><?php esc_html_e( 'GPS de entrega', 'localpilot' ); ?></span>
				<span><i class="lclplt-map-swatch lclplt-map-swatch--radius" aria-hidden="true"></i><?php echo esc_html( sprintf( /* translators: %s: validation radius in metres */ __( 'Radio de %s m', 'localpilot' ), number_format_i18n( $validation_radius ) ) ); ?></span>
			</div>

			<?php if ( $map_enabled && $map_token && is_numeric( $map_lat ) && is_numeric( $map_lng ) ) : ?>
				<div class="lclplt-map-toolbar" role="group" aria-label="<?php esc_attr_e( 'Controles del mapa', 'localpilot' ); ?>">
					<button type="button" class="button button-small" data-lclplt-map-action="fit"><?php esc_html_e( 'Ver zona completa', 'localpilot' ); ?></button>
					<button type="button" class="button button-small" data-lclplt-map-action="destination"><?php esc_html_e( 'Centrar destino', 'localpilot' ); ?></button>
					<?php if ( is_numeric( $driver_lat ) && is_numeric( $driver_lng ) ) : ?>
						<button type="button" class="button button-small" data-lclplt-map-action="delivery"><?php esc_html_e( 'Centrar GPS', 'localpilot' ); ?></button>
						<button type="button" class="button button-small" data-lclplt-copy-coords="<?php echo esc_attr( $driver_lat . ', ' . $driver_lng ); ?>" data-lclplt-copied-label="<?php esc_attr_e( 'Coordenadas copiadas.', 'localpilot' ); ?>" data-lclplt-copy-error-label="<?php esc_attr_e( 'No se pudieron copiar las coordenadas.', 'localpilot' ); ?>"><?php esc_html_e( 'Copiar GPS', 'localpilot' ); ?></button>
						<a class="button button-small" href="<?php echo esc_url( 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $driver_lat . ',' . $driver_lng ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Abrir en Google Maps', 'localpilot' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(abre en una pestaña nueva)', 'localpilot' ); ?></span></a>
					<?php endif; ?>
				</div>

				<div id="lclplt-admin-map" class="lclplt-admin-map"
					data-lat="<?php echo esc_attr( $map_lat ); ?>"
					data-lng="<?php echo esc_attr( $map_lng ); ?>"
					data-delivery-lat="<?php echo esc_attr( is_numeric( $driver_lat ) ? $driver_lat : '' ); ?>"
					data-delivery-lng="<?php echo esc_attr( is_numeric( $driver_lng ) ? $driver_lng : '' ); ?>"
					data-validation-radius="<?php echo esc_attr( $validation_radius ); ?>"
					data-token="<?php echo esc_attr( $map_token ); ?>"
					data-style="<?php echo esc_attr( $map_style ); ?>"
					data-zoom="<?php echo esc_attr( $map_zoom ); ?>"
					data-readonly="<?php echo $is_terminal ? '1' : '0'; ?>"
					data-destination-label="<?php esc_attr_e( 'Destino de entrega', 'localpilot' ); ?>"
					data-delivery-label="<?php esc_attr_e( 'Entrega registrada aquí', 'localpilot' ); ?>"
					data-error-label="<?php esc_attr_e( 'No se pudo cargar el mapa. Las coordenadas continúan disponibles debajo.', 'localpilot' ); ?>"></div>
				<p class="lclplt-map-status" id="lclplt-map-status" aria-live="polite"></p>
			<?php else : ?>
				<div class="lclplt-empty-state">
					<span class="dashicons dashicons-location-alt" aria-hidden="true"></span>
					<div><strong><?php esc_html_e( 'Mapa no disponible', 'localpilot' ); ?></strong><p><?php esc_html_e( 'Configura Mapbox y geocodifica el destino para mostrar la zona de validación.', 'localpilot' ); ?></p></div>
				</div>
			<?php endif; ?>

			<div class="lclplt-coordinates">
				<label for="lclplt_correction_lat"><?php esc_html_e( 'Latitud del destino', 'localpilot' ); ?><input type="text" name="lclplt_correction_lat" id="lclplt_correction_lat" value="<?php echo esc_attr( is_numeric( $map_lat ) ? $map_lat : '' ); ?>" readonly /></label>
				<label for="lclplt_correction_lng"><?php esc_html_e( 'Longitud del destino', 'localpilot' ); ?><input type="text" name="lclplt_correction_lng" id="lclplt_correction_lng" value="<?php echo esc_attr( is_numeric( $map_lng ) ? $map_lng : '' ); ?>" readonly /></label>
				<?php if ( ! $is_terminal && $map_enabled && $map_token && is_numeric( $map_lat ) && is_numeric( $map_lng ) ) : ?>
					<div class="lclplt-coordinates__action"><button type="submit" class="button" data-lclplt-action="update_location"><?php esc_html_e( 'Guardar ubicación', 'localpilot' ); ?></button></div>
				<?php endif; ?>
			</div>
			<?php if ( ! $is_terminal && $map_enabled && $map_token && is_numeric( $map_lat ) && is_numeric( $map_lng ) ) : ?>
				<p class="description"><?php esc_html_e( 'Arrastra el marcador del destino y guarda la ubicación para corregir sus coordenadas.', 'localpilot' ); ?></p>
			<?php endif; ?>
		</section>

		<?php if ( ! empty( $events ) ) : ?>
			<section class="lclplt-section" aria-labelledby="lclplt-events-title">
				<div class="lclplt-section__heading">
					<div><h3 id="lclplt-events-title"><?php esc_html_e( 'Actividad reciente', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Últimos eventos registrados para la asignación.', 'localpilot' ); ?></p></div>
				</div>
				<ol class="lclplt-event-timeline">
					<?php foreach ( $events as $event ) : ?>
						<?php $event_actor = ! empty( $event->user_id ) ? get_userdata( (int) $event->user_id ) : false; ?>
						<li>
							<span class="lclplt-event-timeline__icon dashicons <?php echo esc_attr( Localpilot_Order_Editor::event_icon( $event->event_type ) ); ?>" aria-hidden="true"></span>
							<div>
								<strong><?php echo esc_html( Localpilot_Order_Editor::event_label( $event->event_type ) ); ?></strong>
								<p>
									<time datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $event->created_at ) ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $event->created_at ) ) ); ?></time>
									<?php if ( $event_actor ) : ?><span aria-hidden="true"> · </span><?php echo esc_html( $event_actor->display_name ); ?><?php endif; ?>
								</p>
							</div>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
	<?php endif; ?>
</div>
