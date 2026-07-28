<?php
/**
 * My Account — Mis entregas: detail view.
 *
 * @see Localpilot_My_Account::render_detail()
 *
 * @var object                         $assignment Assignment row object.
 * @var WC_Order                       $order WooCommerce order.
 * @var Localpilot_Order_Delivery_Meta $meta Meta adapter.
 * @var string                         $status Current delivery status.
 * @var array                          $events Recent events.
 * @var bool                           $show_total Whether to show order total.
 * @var bool                           $can_accept Whether accept action is available.
 * @var bool                           $can_start Whether start action is available.
 * @var bool                           $can_complete Whether complete action is available.
 * @var bool                           $can_fail Whether fail action is available.
 * @var string                         $nonce_field Nonce field HTML.
 * @var int                            $driver_id Current user ID.
 */

defined( 'ABSPATH' ) || exit;

$status_label = Localpilot_Delivery_Status::label( $status );
$status_class = Localpilot_Delivery_Status::status_class( $status );
$is_terminal  = Localpilot_Delivery_Status::is_terminal( $status );

$status_icons = array(
	'assigned'         => 'dashicons-clipboard',
	'accepted'         => 'dashicons-yes-alt',
	'out_for_delivery' => 'dashicons-location-alt',
	'delivered'        => 'dashicons-saved',
	'failed'           => 'dashicons-warning',
	'cancelled'        => 'dashicons-no-alt',
);
$status_icon = isset( $status_icons[ $status ] ) ? $status_icons[ $status ] : 'dashicons-info-outline';

$shipping_name_parts = $order->get_shipping_first_name()
	? array( $order->get_shipping_first_name(), $order->get_shipping_last_name() )
	: array( $order->get_billing_first_name(), $order->get_billing_last_name() );
$shipping_name  = implode( ' ', array_filter( array_map( 'trim', $shipping_name_parts ) ) );
$shipping_phone = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
$address_parts  = array_filter(
	array(
		$order->get_shipping_address_1() ?: $order->get_billing_address_1(),
		$order->get_shipping_address_2() ?: $order->get_billing_address_2(),
		$order->get_shipping_city() ?: $order->get_billing_city(),
		$order->get_shipping_state() ?: $order->get_billing_state(),
		$order->get_shipping_postcode() ?: $order->get_billing_postcode(),
	)
);
$shipping_address = implode( ', ', array_unique( $address_parts ) );
$assignment_id    = (int) $assignment->id;
$account_url      = wc_get_account_endpoint_url( 'mis-entregas' );

$map_lat     = $meta->get_latitude();
$map_lng     = $meta->get_longitude();
$map_token   = get_option( 'lclplt_mapbox_token', '' );
$map_style   = get_option( 'lclplt_mapbox_style', 'streets-v12' );
$map_zoom    = get_option( 'lclplt_mapbox_zoom', 14 );
$map_enabled = 'yes' === get_option( 'lclplt_enable_mapbox', 'no' );
$has_coords  = is_numeric( $map_lat ) && is_numeric( $map_lng );
$maps_url    = $has_coords ? 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $map_lat . ',' . $map_lng ) : '';
$show_map    = $map_enabled && $has_coords && $map_token;

$received_by    = $meta->get_received_by();
$delivery_notes = $meta->get_delivery_notes();
$failed_reason  = $meta->get_failed_reason();
$proof_id       = $meta->get_proof_attachment_id();
$proof_url      = $proof_id ? wp_get_attachment_url( $proof_id ) : false;
$proof_thumb    = $proof_id ? wp_get_attachment_image_url( $proof_id, array( 480, 320 ) ) : false;

$failure_reasons = apply_filters(
	'lclplt_failure_reasons',
	array(
		'cliente_ausente'      => __( 'Cliente ausente', 'localpilot' ),
		'direccion_incorrecta' => __( 'Dirección incorrecta', 'localpilot' ),
		'producto_danado'      => __( 'Producto dañado', 'localpilot' ),
		'rechazado'            => __( 'Cliente rechazó el pedido', 'localpilot' ),
		'otro'                 => __( 'Otro', 'localpilot' ),
	)
);
$failed_reason_label = isset( $failure_reasons[ $failed_reason ] ) ? $failure_reasons[ $failed_reason ] : $failed_reason;

$location_status = $meta->get_location_validation_status();
$location_labels = array(
	'passed'            => __( 'Ubicación validada', 'localpilot' ),
	'outside_radius'    => __( 'Fuera del radio configurado', 'localpilot' ),
	'permission_denied' => __( 'Permiso de ubicación denegado', 'localpilot' ),
	'unavailable'       => __( 'Ubicación no disponible', 'localpilot' ),
	'timeout'           => __( 'La ubicación tardó demasiado', 'localpilot' ),
	'stale'             => __( 'Ubicación desactualizada', 'localpilot' ),
	'low_accuracy'      => __( 'Precisión insuficiente', 'localpilot' ),
	'no_destination'    => __( 'Destino sin coordenadas', 'localpilot' ),
	'disabled'          => __( 'Validación desactivada', 'localpilot' ),
);
$location_label    = isset( $location_labels[ $location_status ] ) ? $location_labels[ $location_status ] : __( 'Sin validación registrada', 'localpilot' );
$location_distance = $meta->get_location_validation_distance();
$location_radius   = $meta->get_location_validation_radius();
$location_tone     = 'neutral';
if ( 'passed' === $location_status ) {
	$location_tone = 'success';
} elseif ( in_array( $location_status, array( 'outside_radius', 'low_accuracy', 'stale', 'no_destination', 'disabled' ), true ) ) {
	$location_tone = 'warning';
} elseif ( in_array( $location_status, array( 'permission_denied', 'unavailable', 'timeout' ), true ) ) {
	$location_tone = 'error';
}

$has_proof_data = $received_by || $delivery_notes || $failed_reason || $proof_id || $location_status;
?>

<div class="lclplt-deliveries-page lclplt-delivery-detail">
	<a href="<?php echo esc_url( $account_url ); ?>" class="lclplt-detail-back">
		<span class="dashicons dashicons-arrow-left-alt2" aria-hidden="true"></span>
		<?php esc_html_e( 'Volver a mis entregas', 'localpilot' ); ?>
	</a>

	<header class="lclplt-detail-header">
		<div>
			<span class="lclplt-detail-header__eyebrow"><?php esc_html_e( 'Detalle de entrega', 'localpilot' ); ?></span>
			<h2><?php echo esc_html( sprintf( /* translators: %s: order number */ __( 'Entrega #%s', 'localpilot' ), $order->get_order_number() ) ); ?></h2>
			<?php if ( $order->get_date_created() ) : ?>
				<p><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><time datetime="<?php echo esc_attr( $order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time></p>
			<?php endif; ?>
		</div>
		<mark class="lclplt-badge lclplt-badge--<?php echo esc_attr( $status_class ); ?>"><span class="dashicons <?php echo esc_attr( $status_icon ); ?> lclplt-badge-icon" aria-hidden="true"></span><?php echo esc_html( $status_label ); ?></mark>
	</header>

	<section class="lclplt-detail-card lclplt-detail-card--destination" aria-labelledby="lclplt-destination-title">
		<div class="lclplt-detail-card__heading">
			<span class="lclplt-detail-card__icon dashicons dashicons-location-alt" aria-hidden="true"></span>
			<div><h3 id="lclplt-destination-title"><?php esc_html_e( 'Cliente y destino', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Información principal para realizar esta entrega.', 'localpilot' ); ?></p></div>
		</div>
		<div class="lclplt-destination-grid">
			<div class="lclplt-destination-person">
				<span class="lclplt-detail-label"><?php esc_html_e( 'Cliente', 'localpilot' ); ?></span>
				<strong><?php echo $shipping_name ? esc_html( $shipping_name ) : esc_html__( 'Nombre no disponible', 'localpilot' ); ?></strong>
				<?php if ( $shipping_address ) : ?><address><?php echo esc_html( $shipping_address ); ?></address><?php else : ?><span class="lclplt-missing-data"><?php esc_html_e( 'Dirección no disponible', 'localpilot' ); ?></span><?php endif; ?>
			</div>
			<dl class="lclplt-detail-facts">
				<div><dt><?php esc_html_e( 'Teléfono', 'localpilot' ); ?></dt><dd><?php if ( $shipping_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $shipping_phone ) ); ?>"><?php echo esc_html( $shipping_phone ); ?></a><?php else : ?><span class="lclplt-missing-data"><?php esc_html_e( 'No disponible', 'localpilot' ); ?></span><?php endif; ?></dd></div>
				<?php if ( $show_total ) : ?><div><dt><?php esc_html_e( 'Total del pedido', 'localpilot' ); ?></dt><dd><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></dd></div><?php endif; ?>
			</dl>
		</div>
		<div class="lclplt-detail-actions" aria-label="<?php esc_attr_e( 'Acciones rápidas', 'localpilot' ); ?>">
			<?php if ( $shipping_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $shipping_phone ) ); ?>" class="woocommerce-button button"><span class="dashicons dashicons-phone" aria-hidden="true"></span><?php esc_html_e( 'Llamar al cliente', 'localpilot' ); ?></a><?php endif; ?>
			<?php if ( $maps_url ) : ?><a href="<?php echo esc_url( $maps_url ); ?>" target="_blank" rel="noopener noreferrer" class="woocommerce-button button"><span class="dashicons dashicons-location" aria-hidden="true"></span><?php esc_html_e( 'Abrir navegación', 'localpilot' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(abre en una pestaña nueva)', 'localpilot' ); ?></span></a><?php endif; ?>
			<?php if ( $shipping_address ) : ?><button type="button" class="woocommerce-button button" data-lclplt-copy-address="<?php echo esc_attr( $shipping_address ); ?>" data-copy-label="<?php esc_attr_e( 'Copiar dirección', 'localpilot' ); ?>" data-copied-label="<?php esc_attr_e( 'Dirección copiada', 'localpilot' ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span data-lclplt-copy-text><?php esc_html_e( 'Copiar dirección', 'localpilot' ); ?></span></button><?php endif; ?>
		</div>
		<p class="lclplt-copy-status screen-reader-text" data-lclplt-copy-status role="status" aria-live="polite"></p>
	</section>

	<?php if ( $show_map ) : ?>
		<section class="lclplt-detail-card lclplt-detail-card--map" aria-labelledby="lclplt-public-map-title">
			<div class="lclplt-detail-card__heading">
				<span class="lclplt-detail-card__icon dashicons dashicons-location" aria-hidden="true"></span>
				<div><h3 id="lclplt-public-map-title"><?php esc_html_e( 'Ubicación de la entrega', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Usa el mapa como referencia y abre la navegación para iniciar la ruta.', 'localpilot' ); ?></p></div>
			</div>
			<div id="lclplt-map" class="lclplt-public-map" data-lat="<?php echo esc_attr( $map_lat ); ?>" data-lng="<?php echo esc_attr( $map_lng ); ?>" data-token="<?php echo esc_attr( $map_token ); ?>" data-style="<?php echo esc_attr( $map_style ); ?>" data-zoom="<?php echo esc_attr( $map_zoom ); ?>" data-destination-label="<?php esc_attr_e( 'Destino de entrega', 'localpilot' ); ?>" data-loading-label="<?php esc_attr_e( 'Cargando mapa…', 'localpilot' ); ?>" data-error-label="<?php esc_attr_e( 'No se pudo cargar el mapa. Puedes abrir la navegación con el botón disponible.', 'localpilot' ); ?>"></div>
			<p class="lclplt-public-map__status" data-lclplt-map-status role="status" aria-live="polite"><?php esc_html_e( 'Cargando mapa…', 'localpilot' ); ?></p>
		</section>
	<?php endif; ?>

	<?php if ( $order->get_items() ) : ?>
		<section class="lclplt-detail-card" aria-labelledby="lclplt-products-title">
			<div class="lclplt-detail-card__heading"><span class="lclplt-detail-card__icon dashicons dashicons-cart" aria-hidden="true"></span><div><h3 id="lclplt-products-title"><?php esc_html_e( 'Productos', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Contenido que debe llegar al cliente.', 'localpilot' ); ?></p></div></div>
			<ul class="lclplt-product-list"><?php foreach ( $order->get_items() as $item ) : ?><li><span><?php echo $item->get_name() ? esc_html( $item->get_name() ) : esc_html__( 'Producto sin nombre', 'localpilot' ); ?></span><strong><?php echo esc_html( sprintf( /* translators: %s: product quantity */ __( '× %s', 'localpilot' ), $item->get_quantity() ) ); ?></strong></li><?php endforeach; ?></ul>
		</section>
	<?php endif; ?>

	<?php if ( $is_terminal ) : ?>
		<section class="lclplt-detail-card lclplt-closed-card lclplt-closed-card--<?php echo esc_attr( $status_class ); ?>" aria-labelledby="lclplt-closed-title"><span class="dashicons <?php echo esc_attr( $status_icon ); ?>" aria-hidden="true"></span><div><h3 id="lclplt-closed-title"><?php esc_html_e( 'Entrega cerrada', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Esta entrega no tiene acciones pendientes. Consulta debajo la prueba y la actividad registrada.', 'localpilot' ); ?></p></div></section>
	<?php endif; ?>

	<?php if ( $has_proof_data ) : ?>
		<section class="lclplt-detail-card" aria-labelledby="lclplt-proof-title">
			<div class="lclplt-detail-card__heading"><span class="lclplt-detail-card__icon dashicons dashicons-camera" aria-hidden="true"></span><div><h3 id="lclplt-proof-title"><?php esc_html_e( 'Prueba de entrega', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Información registrada al cerrar la entrega.', 'localpilot' ); ?></p></div></div>
			<div class="lclplt-proof-layout">
				<dl class="lclplt-detail-facts lclplt-detail-facts--proof"><?php if ( $received_by ) : ?><div><dt><?php esc_html_e( 'Recibido por', 'localpilot' ); ?></dt><dd><?php echo esc_html( $received_by ); ?></dd></div><?php endif; ?><?php if ( $failed_reason_label ) : ?><div><dt><?php esc_html_e( 'Motivo del fallo', 'localpilot' ); ?></dt><dd><?php echo esc_html( $failed_reason_label ); ?></dd></div><?php endif; ?><?php if ( $delivery_notes ) : ?><div><dt><?php esc_html_e( 'Notas', 'localpilot' ); ?></dt><dd><?php echo nl2br( esc_html( $delivery_notes ) ); ?></dd></div><?php endif; ?></dl>
				<?php if ( $location_status ) : ?><div class="lclplt-location-result lclplt-location-result--<?php echo esc_attr( $location_tone ); ?>"><span class="dashicons <?php echo 'passed' === $location_status ? 'dashicons-yes-alt' : 'dashicons-info-outline'; ?>" aria-hidden="true"></span><div><span><?php esc_html_e( 'Validación puntual', 'localpilot' ); ?></span><strong><?php echo esc_html( $location_label ); ?></strong><?php if ( '' !== (string) $location_distance && '' !== (string) $location_radius ) : ?><small><?php echo esc_html( sprintf( /* translators: 1: measured distance, 2: allowed radius, both in metres */ __( '%1$s m de %2$s m permitidos', 'localpilot' ), number_format_i18n( (float) $location_distance, 2 ), number_format_i18n( (float) $location_radius, 0 ) ) ); ?></small><?php endif; ?></div></div><?php endif; ?>
				<?php if ( $proof_url ) : ?><a class="lclplt-proof-image" href="<?php echo esc_url( $proof_url ); ?>" target="_blank" rel="noopener noreferrer"><?php if ( $proof_thumb ) : ?><img src="<?php echo esc_url( $proof_thumb ); ?>" alt="<?php esc_attr_e( 'Evidencia de entrega', 'localpilot' ); ?>" width="480" height="320" /><?php else : ?><span class="dashicons dashicons-media-default" aria-hidden="true"></span><?php endif; ?><strong><?php esc_html_e( 'Abrir evidencia', 'localpilot' ); ?></strong><span class="screen-reader-text"> <?php esc_html_e( '(abre en una pestaña nueva)', 'localpilot' ); ?></span></a><?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<?php if ( $can_start || ( $can_accept && ! $can_start ) ) : ?>
		<section class="lclplt-detail-card lclplt-next-step" aria-labelledby="lclplt-next-step-title">
			<div class="lclplt-detail-card__heading"><span class="lclplt-detail-card__icon dashicons dashicons-controls-forward" aria-hidden="true"></span><div><h3 id="lclplt-next-step-title"><?php esc_html_e( 'Siguiente paso', 'localpilot' ); ?></h3><p><?php echo $can_start ? esc_html__( 'Confirma que sales hacia el destino para actualizar el seguimiento.', 'localpilot' ) : esc_html__( 'Acepta la asignación antes de comenzar el reparto.', 'localpilot' ); ?></p></div></div>
			<form method="post" class="lclplt-action-form lclplt-action-form--quick"><?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" /><input type="hidden" name="lclplt_delivery_action" value="<?php echo $can_start ? 'start' : 'accept'; ?>" /><button type="submit" class="woocommerce-button button alt"><span class="dashicons <?php echo $can_start ? 'dashicons-location-alt' : 'dashicons-yes-alt'; ?>" aria-hidden="true"></span><?php echo $can_start ? esc_html__( 'Iniciar reparto', 'localpilot' ) : esc_html__( 'Aceptar entrega', 'localpilot' ); ?></button></form>
		</section>
	<?php endif; ?>

	<?php if ( $can_complete ) : ?>
		<section class="lclplt-detail-card lclplt-completion-card" aria-labelledby="lclplt-complete-title">
			<div class="lclplt-detail-card__heading"><span class="lclplt-detail-card__icon dashicons dashicons-saved" aria-hidden="true"></span><div><h3 id="lclplt-complete-title"><?php esc_html_e( 'Completar entrega', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Registra quién recibió el pedido y adjunta la evidencia solicitada.', 'localpilot' ); ?></p></div></div>
			<form method="post" enctype="multipart/form-data" class="lclplt-action-form lclplt-action-form--complete">
				<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" />
				<div class="lclplt-form-grid">
					<p class="form-row"><label for="lclplt_received_by"><?php esc_html_e( 'Recibido por', 'localpilot' ); ?><?php if ( 'yes' === get_option( 'lclplt_require_received_by', 'yes' ) ) : ?> <abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'localpilot' ); ?>">*</abbr><?php endif; ?></label><input type="text" name="lclplt_received_by" id="lclplt_received_by" class="input-text" <?php echo 'yes' === get_option( 'lclplt_require_received_by', 'yes' ) ? 'required' : ''; ?> placeholder="<?php esc_attr_e( 'Nombre de quien recibe', 'localpilot' ); ?>" /></p>
					<p class="form-row"><label for="lclplt_delivery_notes"><?php esc_html_e( 'Notas de entrega', 'localpilot' ); ?></label><textarea name="lclplt_delivery_notes" id="lclplt_delivery_notes" class="input-text" rows="3" placeholder="<?php esc_attr_e( 'Observaciones (opcional)', 'localpilot' ); ?>"></textarea></p>
				</div>
				<p class="form-row lclplt-file-field"><label for="lclplt_proof"><?php esc_html_e( 'Evidencia (foto)', 'localpilot' ); ?><?php if ( 'yes' === get_option( 'lclplt_require_proof', 'yes' ) ) : ?> <abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'localpilot' ); ?>">*</abbr><?php endif; ?></label><input type="file" name="lclplt_proof" id="lclplt_proof" accept="image/jpeg,image/png,image/webp" class="input-text" <?php echo 'yes' === get_option( 'lclplt_require_proof', 'yes' ) ? 'required' : ''; ?> /><small><?php esc_html_e( 'JPG, PNG o WebP. Máximo', 'localpilot' ); ?> <?php echo esc_html( get_option( 'lclplt_max_proof_size', 5 ) . ' MB.' ); ?></small></p>
				<?php if ( Localpilot_Location_Validation_Service::is_enabled() ) : ?>
					<div class="lclplt-location-validation is-idle" data-lclplt-location-validation="1" data-mode="<?php echo esc_attr( Localpilot_Location_Validation_Service::get_mode() ); ?>" data-message-request="<?php esc_attr_e( 'Obteniendo tu ubicación actual…', 'localpilot' ); ?>" data-message-success="<?php esc_attr_e( 'Ubicación obtenida. Se validará en el servidor al completar.', 'localpilot' ); ?>" data-message-permission="<?php esc_attr_e( 'Debes permitir el acceso a tu ubicación o continuar con advertencia.', 'localpilot' ); ?>" data-message-unavailable="<?php esc_attr_e( 'No se pudo obtener tu ubicación. Activa el GPS e inténtalo nuevamente.', 'localpilot' ); ?>" data-message-timeout="<?php esc_attr_e( 'La solicitud de ubicación tardó demasiado. Inténtalo nuevamente.', 'localpilot' ); ?>">
						<span class="lclplt-location-validation__icon dashicons dashicons-location" aria-hidden="true"></span><div class="lclplt-location-validation__content"><strong><?php esc_html_e( 'Validación de ubicación', 'localpilot' ); ?></strong><p class="lclplt-location-validation__message" role="status" aria-live="polite"><?php esc_html_e( 'Al completar, se solicitará una ubicación puntual para compararla con el destino.', 'localpilot' ); ?></p><div class="lclplt-location-validation__actions"><button type="button" class="button lclplt-location-retry" hidden><?php esc_html_e( 'Intentar nuevamente', 'localpilot' ); ?></button><?php if ( 'warning' === Localpilot_Location_Validation_Service::get_mode() ) : ?><button type="button" class="button lclplt-location-continue" hidden><?php esc_html_e( 'Continuar con advertencia', 'localpilot' ); ?></button><?php endif; ?></div></div>
						<input type="hidden" name="lclplt_location_latitude" value="" /><input type="hidden" name="lclplt_location_longitude" value="" /><input type="hidden" name="lclplt_location_accuracy" value="" /><input type="hidden" name="lclplt_location_timestamp" value="" /><input type="hidden" name="lclplt_location_status" value="" />
					</div>
				<?php endif; ?>
				<input type="hidden" name="lclplt_delivery_action" value="complete" /><button type="submit" class="woocommerce-button button alt lclplt-complete-submit"><span class="dashicons dashicons-saved" aria-hidden="true"></span><?php esc_html_e( 'Completar entrega', 'localpilot' ); ?></button>
			</form>
		</section>
	<?php endif; ?>

	<?php if ( $can_fail ) : ?>
		<details class="lclplt-failure-panel">
			<summary><span class="dashicons dashicons-warning" aria-hidden="true"></span><span><strong><?php esc_html_e( '¿No se pudo realizar la entrega?', 'localpilot' ); ?></strong><small><?php esc_html_e( 'Reporta el motivo y adjunta evidencia si la tienes.', 'localpilot' ); ?></small></span></summary>
			<form method="post" enctype="multipart/form-data" class="lclplt-action-form lclplt-action-form--fail">
				<?php echo $nonce_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><input type="hidden" name="lclplt_assignment_id" value="<?php echo esc_attr( $assignment_id ); ?>" /><input type="hidden" name="lclplt_delivery_action" value="fail" />
				<p class="form-row"><label for="lclplt_failed_reason"><?php esc_html_e( 'Motivo del fallo', 'localpilot' ); ?> <abbr class="required" title="<?php esc_attr_e( 'Obligatorio', 'localpilot' ); ?>">*</abbr></label><select name="lclplt_failed_reason" id="lclplt_failed_reason" class="input-text" required><option value=""><?php esc_html_e( 'Seleccionar motivo…', 'localpilot' ); ?></option><?php foreach ( $failure_reasons as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></p>
				<p class="form-row lclplt-file-field"><label for="lclplt_proof_fail"><?php esc_html_e( 'Evidencia (foto)', 'localpilot' ); ?></label><input type="file" name="lclplt_proof" id="lclplt_proof_fail" accept="image/jpeg,image/png,image/webp" class="input-text" /><small><?php esc_html_e( 'Opcional. JPG, PNG o WebP. Máximo', 'localpilot' ); ?> <?php echo esc_html( get_option( 'lclplt_max_proof_size', 5 ) . ' MB.' ); ?></small></p>
				<button type="submit" class="woocommerce-button button lclplt-button--danger"><span class="dashicons dashicons-warning" aria-hidden="true"></span><?php esc_html_e( 'Reportar fallo', 'localpilot' ); ?></button>
			</form>
		</details>
	<?php endif; ?>

	<?php if ( ! empty( $events ) ) : ?>
		<section class="lclplt-detail-card" aria-labelledby="lclplt-history-title">
			<div class="lclplt-detail-card__heading"><span class="lclplt-detail-card__icon dashicons dashicons-backup" aria-hidden="true"></span><div><h3 id="lclplt-history-title"><?php esc_html_e( 'Actividad de la entrega', 'localpilot' ); ?></h3><p><?php esc_html_e( 'Secuencia registrada para esta asignación.', 'localpilot' ); ?></p></div></div>
			<ol class="lclplt-public-timeline">
				<?php foreach ( $events as $event ) : ?><?php $is_driver_event = ! empty( $event->user_id ) && (int) $event->user_id === (int) $driver_id; ?><li><span class="lclplt-public-timeline__icon dashicons <?php echo esc_attr( Localpilot_Event_Presenter::icon( $event->event_type ) ); ?>" aria-hidden="true"></span><div><strong><?php echo esc_html( Localpilot_Event_Presenter::label( $event->event_type ) ); ?></strong><p><time datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $event->created_at ) ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' H:i', strtotime( $event->created_at ) ) ); ?></time><span aria-hidden="true"> · </span><?php echo $is_driver_event ? esc_html__( 'Realizado por ti', 'localpilot' ) : esc_html__( 'Gestión de tienda', 'localpilot' ); ?></p></div></li><?php endforeach; ?>
			</ol>
		</section>
	<?php endif; ?>
+</div>
