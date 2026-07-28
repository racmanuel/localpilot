<?php
/**
 * Human-readable delivery event presentation helpers.
 *
 * @link       https://racmanuel.dev/
 * @since      1.1.5
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/helpers
 */

/**
 * Centralises translated event labels and Dashicons used by admin and public timelines.
 *
 * @since 1.1.5
 */
class Localpilot_Event_Presenter {

	/**
	 * Get a human-readable event label.
	 *
	 * @param string $event_type Event type.
	 * @return string
	 */
	public static function label( $event_type ) {
		$labels = array(
			'delivery_assigned'         => __( 'Repartidor asignado', 'localpilot' ),
			'delivery_reassigned'       => __( 'Repartidor reasignado', 'localpilot' ),
			'delivery_unassigned'       => __( 'Asignación retirada', 'localpilot' ),
			'delivery_accepted'         => __( 'Entrega aceptada', 'localpilot' ),
			'delivery_started'          => __( 'Reparto iniciado', 'localpilot' ),
			'delivery_completed'        => __( 'Entrega completada', 'localpilot' ),
			'delivery_failed'           => __( 'Entrega fallida', 'localpilot' ),
			'delivery_cancelled'        => __( 'Entrega cancelada', 'localpilot' ),
			'delivery_geocoded'         => __( 'Destino geocodificado', 'localpilot' ),
			'delivery_location_updated' => __( 'Destino corregido', 'localpilot' ),
		);

		return isset( $labels[ $event_type ] ) ? $labels[ $event_type ] : __( 'Actividad de entrega', 'localpilot' );
	}

	/**
	 * Get a Dashicon class for an event type.
	 *
	 * @param string $event_type Event type.
	 * @return string
	 */
	public static function icon( $event_type ) {
		$icons = array(
			'delivery_assigned'         => 'dashicons-admin-users',
			'delivery_reassigned'       => 'dashicons-update',
			'delivery_unassigned'       => 'dashicons-dismiss',
			'delivery_accepted'         => 'dashicons-yes-alt',
			'delivery_started'          => 'dashicons-location-alt',
			'delivery_completed'        => 'dashicons-saved',
			'delivery_failed'           => 'dashicons-warning',
			'delivery_cancelled'        => 'dashicons-no-alt',
			'delivery_geocoded'         => 'dashicons-location',
			'delivery_location_updated' => 'dashicons-edit-location',
		);

		return isset( $icons[ $event_type ] ) ? $icons[ $event_type ] : 'dashicons-info-outline';
	}
}
