<?php
/**
 * Delivery transition service.
 *
 * Validates and executes state transitions for delivery assignments.
 * Coordinates Assignment_Repository, Event_Repository, and Order_Delivery_Meta.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Delivery transition service.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Delivery_Transition_Service {

	/**
	 * Attempt a state transition.
	 *
	 * @param int    $order_id      Order ID.
	 * @param string $target_status Target delivery status.
	 * @param int    $actor_id      User ID performing the action.
	 * @param array  $extra         Optional extra data:
	 *   received_by        (string) Receiver name for delivered.
	 *   delivery_notes     (string) Notes for delivered/failed.
	 *   failed_reason      (string) Failure reason.
	 *   proof_attachment_id (int)   Attachment ID for proof.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function transition( $order_id, $target_status, $actor_id = 0, array $extra = array() ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		if ( ! $actor_id ) {
			return new WP_Error(
				'lclplt_forbidden',
				__( 'Debes iniciar sesión para realizar esta acción.', 'localpilot' )
			);
		}

		// Validate order.
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'lclplt_delivery_not_found',
				__( 'El pedido no existe.', 'localpilot' )
			);
		}

		// Get current active assignment.
		$assignment = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
		if ( null === $assignment ) {
			return new WP_Error(
				'lclplt_delivery_not_found',
				__( 'El pedido no tiene una asignación activa.', 'localpilot' )
			);
		}

		$assignment_id = (int) $assignment->id;
		$current_status = $assignment->status;
		$driver_id      = (int) $assignment->driver_id;

		// Validate the transition is allowed.
		$actor = Localpilot_Delivery_Status::transition_actor( $current_status, $target_status );
		if ( false === $actor ) {
			return new WP_Error(
				'lclplt_invalid_transition',
				sprintf(
					/* translators: 1: current status label, 2: target status label */
					__( 'No se puede cambiar de %1$s a %2$s.', 'localpilot' ),
					Localpilot_Delivery_Status::label( $current_status ),
					Localpilot_Delivery_Status::label( $target_status )
				)
			);
		}

		// Validate actor permissions.
		if ( 'driver' === $actor ) {
			// Must be the assigned driver.
			if ( (int) $actor_id !== $driver_id ) {
				return new WP_Error(
					'lclplt_forbidden',
					__( 'No tienes permiso para realizar esta acción en esta entrega.', 'localpilot' )
				);
			}

			// Must have the corresponding capability.
			if ( ! self::driver_has_cap_for_transition( $actor_id, $target_status ) ) {
				return new WP_Error(
					'lclplt_forbidden',
					__( 'No tienes permiso para realizar esta acción.', 'localpilot' )
				);
			}

			// If acceptance is required, 'assigned -> out_for_delivery' should be blocked for drivers.
			$require_acceptance = get_option( 'lclplt_require_acceptance', 'yes' );
			if ( 'yes' === $require_acceptance
				&& Localpilot_Delivery_Status::ASSIGNED === $current_status
				&& Localpilot_Delivery_Status::OUT_FOR_DELIVERY === $target_status
			) {
				return new WP_Error(
					'lclplt_invalid_transition',
					__( 'Debes aceptar la entrega antes de iniciar el reparto.', 'localpilot' )
				);
			}
		} elseif ( 'manager' === $actor ) {
			if ( ! user_can( $actor_id, Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
				return new WP_Error(
					'lclplt_forbidden',
					__( 'No tienes permiso para realizar esta acción.', 'localpilot' )
				);
			}
		}

		/**
		 * Filter whether a delivery transition is allowed.
		 *
		 * @param bool   $allowed        Whether the transition is allowed.
		 * @param string $current_status Current delivery status.
		 * @param string $target_status  Target delivery status.
		 * @param int    $order_id       Order ID.
		 * @param int    $actor_id       User ID.
		 */
		$allowed = apply_filters(
			'lclplt_delivery_transition_allowed',
			true,
			$current_status,
			$target_status,
			$order_id,
			$actor_id
		);

		if ( ! $allowed ) {
			return new WP_Error(
				'lclplt_invalid_transition',
				__( 'La transición no está permitida.', 'localpilot' )
			);
		}

		// Validate required fields based on target status.
		if ( Localpilot_Delivery_Status::DELIVERED === $target_status ) {
			$require_received_by = get_option( 'lclplt_require_received_by', 'yes' );
			$require_proof       = get_option( 'lclplt_require_proof', 'yes' );

			if ( 'yes' === $require_received_by && empty( $extra['received_by'] ) ) {
				return new WP_Error(
					'lclplt_validation_error',
					__( 'El nombre de quien recibe es obligatorio.', 'localpilot' )
				);
			}

			if ( 'yes' === $require_proof && empty( $extra['proof_attachment_id'] ) ) {
				return new WP_Error(
					'lclplt_validation_error',
					__( 'La evidencia de entrega es obligatoria.', 'localpilot' )
				);
			}
		}

		if ( Localpilot_Delivery_Status::FAILED === $target_status ) {
			if ( empty( $extra['failed_reason'] ) ) {
				return new WP_Error(
					'lclplt_validation_error',
					__( 'Debes indicar el motivo del fallo.', 'localpilot' )
				);
			}
		}

		// ---- Execute ----

		// 1. Update assignment status.
		Localpilot_Assignment_Repository::update_status( $assignment_id, $target_status );

		// 2. Update order meta.
		$meta_updates = array(
			Localpilot_Order_Delivery_Meta::DELIVERY_STATUS => $target_status,
		);

		switch ( $target_status ) {
			case Localpilot_Delivery_Status::ACCEPTED:
				$meta_updates[ Localpilot_Order_Delivery_Meta::ACCEPTED_AT ] = current_time( 'mysql', true );
				break;

			case Localpilot_Delivery_Status::OUT_FOR_DELIVERY:
				$meta_updates[ Localpilot_Order_Delivery_Meta::OUT_FOR_DELIVERY_AT ] = current_time( 'mysql', true );
				break;

			case Localpilot_Delivery_Status::DELIVERED:
				$meta_updates[ Localpilot_Order_Delivery_Meta::DELIVERED_AT ]  = current_time( 'mysql', true );
				$meta_updates[ Localpilot_Order_Delivery_Meta::RECEIVED_BY ]   = sanitize_text_field( $extra['received_by'] ?? '' );
				$meta_updates[ Localpilot_Order_Delivery_Meta::DELIVERY_NOTES ] = sanitize_textarea_field( $extra['delivery_notes'] ?? '' );
				if ( ! empty( $extra['proof_attachment_id'] ) ) {
					$meta_updates[ Localpilot_Order_Delivery_Meta::PROOF_ATTACHMENT_ID ] = (int) $extra['proof_attachment_id'];
				}
				if ( ! empty( $extra['location_validation'] ) && is_array( $extra['location_validation'] ) ) {
					$location = $extra['location_validation'];
					$meta_updates[ Localpilot_Order_Delivery_Meta::LOCATION_VALIDATION_STATUS ]   = sanitize_key( $location['status'] ?? 'not_validated' );
					$meta_updates[ Localpilot_Order_Delivery_Meta::LOCATION_VALIDATION_DISTANCE ] = null === ( $location['distance_meters'] ?? null ) ? '' : (float) $location['distance_meters'];
					$meta_updates[ Localpilot_Order_Delivery_Meta::LOCATION_VALIDATION_RADIUS ]   = absint( $location['radius_meters'] ?? 0 );
					$meta_updates[ Localpilot_Order_Delivery_Meta::LOCATION_VALIDATION_ACCURACY ] = null === ( $location['accuracy_meters'] ?? null ) ? '' : (float) $location['accuracy_meters'];
					$meta_updates[ Localpilot_Order_Delivery_Meta::LOCATION_VALIDATION_AT ]       = sanitize_text_field( $location['validated_at'] ?? current_time( 'mysql', true ) );
					if ( ! empty( $extra['location_driver_lat'] ) && ! empty( $extra['location_driver_lng'] ) ) {
						$meta_updates[ Localpilot_Order_Delivery_Meta::DELIVERY_LOCATION_LAT ] = (float) $extra['location_driver_lat'];
						$meta_updates[ Localpilot_Order_Delivery_Meta::DELIVERY_LOCATION_LNG ] = (float) $extra['location_driver_lng'];
					}
				}
				break;

			case Localpilot_Delivery_Status::FAILED:
				$meta_updates[ Localpilot_Order_Delivery_Meta::FAILED_AT]     = current_time( 'mysql', true );
				$meta_updates[ Localpilot_Order_Delivery_Meta::FAILED_REASON ] = sanitize_text_field( $extra['failed_reason'] ?? '' );
				$meta_updates[ Localpilot_Order_Delivery_Meta::DELIVERY_NOTES] = sanitize_textarea_field( $extra['delivery_notes'] ?? '' );
				break;
		}

		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$meta->set_bulk( $meta_updates )->save();

		// 3. Update WooCommerce order status if configured.
		self::maybe_update_order_status( $order, $target_status );

		// 4. Create event.
		$event_type = self::get_event_type_for_transition( $target_status );
		$event_data = array_merge(
			array(
				'from_status' => $current_status,
				'to_status'   => $target_status,
				'actor_id'    => $actor_id,
			),
			$extra
		);

		$event_id = Localpilot_Event_Repository::insert( array(
			'order_id'      => $order_id,
			'assignment_id' => $assignment_id,
			'driver_id'     => $driver_id,
			'user_id'       => $actor_id,
			'event_type'    => $event_type,
			'event_data'    => $event_data,
		) );

		// 5. Add order note.
		$note = self::build_transition_note( $target_status, $extra, $actor_id );
		if ( $note ) {
			$order->add_order_note( $note, false, true );
		}

		// 6. Fire domain hook.
		self::fire_hook( $target_status, $order_id, $driver_id, $assignment_id, $event_id );

		return true;
	}

	/**
	 * Update the WooCommerce order status if configured for this transition.
	 *
	 * @param WC_Order $order         Order object.
	 * @param string   $delivery_status Target delivery status.
	 */
	private static function maybe_update_order_status( $order, $delivery_status ) {
		$order_status_map = array(
			Localpilot_Delivery_Status::DELIVERED => get_option( 'lclplt_completed_order_status', 'completed' ),
			Localpilot_Delivery_Status::FAILED    => get_option( 'lclplt_failed_order_status', 'failed' ),
		);

		if ( isset( $order_status_map[ $delivery_status ] ) ) {
			$wc_status = $order_status_map[ $delivery_status ];
			if ( $wc_status && $order->get_status() !== $wc_status ) {
				$order->update_status( $wc_status, __( 'LocalPilot: ', 'localpilot' ) );
			}
		}
	}

	/**
	 * Check if a driver has the capability for a transition target.
	 *
	 * @param int    $driver_id     User ID.
	 * @param string $target_status Target delivery status.
	 * @return bool
	 */
	private static function driver_has_cap_for_transition( $driver_id, $target_status ) {
		$cap_map = array(
			Localpilot_Delivery_Status::ACCEPTED        => Localpilot_Capabilities::ACCEPT_DELIVERY,
			Localpilot_Delivery_Status::OUT_FOR_DELIVERY => Localpilot_Capabilities::START_DELIVERY,
			Localpilot_Delivery_Status::DELIVERED       => Localpilot_Capabilities::COMPLETE_DELIVERY,
			Localpilot_Delivery_Status::FAILED          => Localpilot_Capabilities::FAIL_DELIVERY,
		);

		if ( ! isset( $cap_map[ $target_status ] ) ) {
			return false;
		}

		return user_can( $driver_id, $cap_map[ $target_status ] );
	}

	/**
	 * Map delivery status to event type.
	 *
	 * @param string $status Delivery status.
	 * @return string Event type.
	 */
	private static function get_event_type_for_transition( $status ) {
		$map = array(
			Localpilot_Delivery_Status::ACCEPTED        => 'delivery_accepted',
			Localpilot_Delivery_Status::OUT_FOR_DELIVERY => 'delivery_started',
			Localpilot_Delivery_Status::DELIVERED       => 'delivery_completed',
			Localpilot_Delivery_Status::FAILED          => 'delivery_failed',
			Localpilot_Delivery_Status::CANCELLED       => 'delivery_cancelled',
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : 'delivery_' . $status;
	}

	/**
	 * Build a human-readable order note for the transition.
	 *
	 * @param string $target_status Target delivery status.
	 * @param array  $extra         Extra data.
	 * @param int    $actor_id      User ID.
	 * @return string|false Note text or false.
	 */
	private static function build_transition_note( $target_status, $extra, $actor_id ) {
		$actor = get_userdata( $actor_id );
		$actor_name = $actor ? $actor->display_name : '#' . $actor_id;

		switch ( $target_status ) {
			case Localpilot_Delivery_Status::ACCEPTED:
				return sprintf(
					/* translators: %s: actor name */
					__( 'LocalPilot: Entrega aceptada por %s.', 'localpilot' ),
					$actor_name
				);

			case Localpilot_Delivery_Status::OUT_FOR_DELIVERY:
				return sprintf(
					/* translators: %s: actor name */
					__( 'LocalPilot: Reparto iniciado por %s.', 'localpilot' ),
					$actor_name
				);

			case Localpilot_Delivery_Status::DELIVERED:
				$received = ! empty( $extra['received_by'] ) ? $extra['received_by'] : __( 'No especificado', 'localpilot' );
				return sprintf(
					/* translators: 1: actor name, 2: receiver name */
					__( 'LocalPilot: Entregado por %1$s. Recibido por: %2$s.', 'localpilot' ),
					$actor_name,
					$received
				);

			case Localpilot_Delivery_Status::FAILED:
				$reason = ! empty( $extra['failed_reason'] ) ? $extra['failed_reason'] : __( 'Sin motivo', 'localpilot' );
				return sprintf(
					/* translators: 1: actor name, 2: failure reason */
					__( 'LocalPilot: Entrega fallida por %1$s. Motivo: %2$s.', 'localpilot' ),
					$actor_name,
					$reason
				);

			case Localpilot_Delivery_Status::CANCELLED:
				return sprintf(
					/* translators: %s: actor name */
					__( 'LocalPilot: Entrega cancelada por %s.', 'localpilot' ),
					$actor_name
				);
		}

		return false;
	}

	/**
	 * Fire the appropriate domain hook after a successful transition.
	 *
	 * @param string $status        Target status.
	 * @param int    $order_id      Order ID.
	 * @param int    $driver_id     Driver ID.
	 * @param int    $assignment_id Assignment ID.
	 * @param int    $event_id      Event ID.
	 */
	private static function fire_hook( $status, $order_id, $driver_id, $assignment_id, $event_id ) {
		switch ( $status ) {
			case Localpilot_Delivery_Status::ACCEPTED:
				do_action( 'lclplt_delivery_accepted', $order_id, $driver_id, $assignment_id, $event_id );
				break;

			case Localpilot_Delivery_Status::OUT_FOR_DELIVERY:
				do_action( 'lclplt_delivery_started', $order_id, $driver_id, $assignment_id, $event_id );
				break;

			case Localpilot_Delivery_Status::DELIVERED:
				do_action( 'lclplt_delivery_completed', $order_id, $driver_id, $assignment_id, $event_id );
				break;

			case Localpilot_Delivery_Status::FAILED:
				do_action( 'lclplt_delivery_failed', $order_id, $driver_id, $assignment_id, $event_id );
				break;
		}
	}
}
