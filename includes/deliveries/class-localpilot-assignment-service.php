<?php
/**
 * Assignment service.
 *
 * Orchestrates assigning, reassigning, and unassigning deliveries.
 * Coordinates Assignment_Repository, Event_Repository, and Order_Delivery_Meta.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Assignment service.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Assignment_Service {

	/**
	 * Assign a driver to an order.
	 *
	 * 1. Validates eligibility: order exists, no active assignment, driver active.
	 * 2. Creates a new assignment row.
	 * 3. Updates order meta.
	 * 4. Creates an event.
	 * 5. Adds a private order note.
	 * 6. Fires the lclplt_delivery_assigned hook.
	 *
	 * @param int $order_id  WooCommerce order ID.
	 * @param int $driver_id User ID of the driver.
	 * @param int $actor_id  User ID performing the assignment.
	 *
	 * @return int|WP_Error Assignment ID on success, WP_Error on failure.
	 */
	/**
	 * Get eligible order statuses for assignment.
	 *
	 * @return array
	 */
	public static function get_eligible_statuses() {
		$defaults = array( 'processing', 'on-hold' );
		$statuses = get_option( 'lclplt_eligible_order_statuses', $defaults );
		if ( ! is_array( $statuses ) || empty( $statuses ) ) {
			return $defaults;
		}
		return $statuses;
	}

	/**
	 * Check if an order is eligible for delivery assignment.
	 *
	 * @param WC_Order $order Order object.
	 * @return bool|WP_Error True if eligible, WP_Error otherwise.
	 */
	public static function is_order_eligible( $order ) {
		$eligible_statuses = self::get_eligible_statuses();
		$order_status      = $order->get_status();

		if ( ! in_array( $order_status, $eligible_statuses, true ) ) {
			return new WP_Error(
				'lclplt_order_not_eligible',
				sprintf(
					/* translators: %s: current order status */
					__( 'El pedido está en estado "%s" y no es elegible para asignación.', 'localpilot' ),
					wc_get_order_status_name( $order_status )
				)
			);
		}

		return true;
	}

	public static function assign( $order_id, $driver_id, $actor_id = 0 ) {
		// Validate actor.
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		if ( ! $actor_id || ! user_can( $actor_id, Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			return new WP_Error(
				'lclplt_forbidden',
				__( 'No tienes permiso para asignar entregas.', 'localpilot' )
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

		// Check order eligibility.
		$eligible = self::is_order_eligible( $order );
		if ( is_wp_error( $eligible ) ) {
			return $eligible;
		}

		// Check for existing active assignment.
		if ( Localpilot_Assignment_Repository::has_active_assignment( $order_id ) ) {
			return new WP_Error(
				'lclplt_already_assigned',
				__( 'El pedido ya tiene una asignación activa. Reasigna o retira primero.', 'localpilot' )
			);
		}

		// Validate driver exists and is active.
		if ( ! Localpilot_Driver_Repository::is_active_driver( $driver_id ) ) {
			return new WP_Error(
				'lclplt_driver_inactive',
				__( 'El repartidor no está activo o no existe.', 'localpilot' )
			);
		}

		// Create assignment.
		$assignment_id = Localpilot_Assignment_Repository::insert(
			array(
				'order_id'    => $order_id,
				'driver_id'   => $driver_id,
				'assigned_by' => $actor_id,
				'status'      => Localpilot_Delivery_Status::ASSIGNED,
			)
		);

		if ( ! $assignment_id ) {
			return new WP_Error(
				'lclplt_conflict',
				__( 'Error al crear la asignación. Intenta de nuevo.', 'localpilot' )
			);
		}

		// Update order meta.
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$meta->set_bulk( array(
			Localpilot_Order_Delivery_Meta::DRIVER_ID       => $driver_id,
			Localpilot_Order_Delivery_Meta::DELIVERY_STATUS  => Localpilot_Delivery_Status::ASSIGNED,
			Localpilot_Order_Delivery_Meta::ASSIGNMENT_ID    => $assignment_id,
			Localpilot_Order_Delivery_Meta::ASSIGNED_AT      => current_time( 'mysql', true ),
		) )->save();

		// Create event.
		$event_id = Localpilot_Event_Repository::insert( array(
			'order_id'      => $order_id,
			'assignment_id' => $assignment_id,
			'driver_id'     => $driver_id,
			'user_id'       => $actor_id,
			'event_type'    => 'delivery_assigned',
			'event_data'    => array(
				'driver_id'     => $driver_id,
				'assigned_by'   => $actor_id,
				'assignment_id' => $assignment_id,
			),
		) );

		// Add order note.
		$driver_user = get_userdata( $driver_id );
		$driver_name = $driver_user ? $driver_user->display_name : '#' . $driver_id;
		$order->add_order_note(
			sprintf(
				/* translators: %s: driver display name */
				__( 'LocalPilot: Pedido asignado a %s.', 'localpilot' ),
				$driver_name
			),
			false, // Not customer note.
			true   // Private.
		);

		/**
		 * Fired after a delivery has been assigned.
		 *
		 * @param int $order_id      Order ID.
		 * @param int $driver_id     Assigned driver user ID.
		 * @param int $assignment_id New assignment ID.
		 * @param int $event_id      Event ID.
		 */
		do_action( 'lclplt_delivery_assigned', $order_id, $driver_id, $assignment_id, $event_id );

		return $assignment_id;
	}

	/**
	 * Reassign an order from its current driver to a new driver.
	 *
	 * Closes the current active assignment and creates a new one.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $new_driver_id New driver user ID.
	 * @param int $actor_id      User ID performing the reassignment.
	 *
	 * @return int|WP_Error New assignment ID on success, WP_Error on failure.
	 */
	public static function reassign( $order_id, $new_driver_id, $actor_id = 0 ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		if ( ! $actor_id || ! user_can( $actor_id, Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			return new WP_Error(
				'lclplt_forbidden',
				__( 'No tienes permiso para reasignar entregas.', 'localpilot' )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'lclplt_delivery_not_found',
				__( 'El pedido no existe.', 'localpilot' )
			);
		}

		if ( ! Localpilot_Driver_Repository::is_active_driver( $new_driver_id ) ) {
			return new WP_Error(
				'lclplt_driver_inactive',
				__( 'El nuevo repartidor no está activo o no existe.', 'localpilot' )
			);
		}

		// Get current active assignment.
		$current = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
		if ( null === $current ) {
			// No active assignment — just create a new one.
			return self::assign( $order_id, $new_driver_id, $actor_id );
		}

		$old_driver_id = (int) $current->driver_id;

		// No-op if trying to reassign to the same driver.
		if ( $old_driver_id === (int) $new_driver_id ) {
			return new WP_Error(
				'lclplt_already_assigned',
				__( 'El pedido ya está asignado a este repartidor.', 'localpilot' )
			);
		}

		// Close current assignment as cancelled.
		$closed = Localpilot_Assignment_Repository::close(
			(int) $current->id,
			Localpilot_Delivery_Status::CANCELLED
		);

		if ( ! $closed ) {
			return new WP_Error(
				'lclplt_conflict',
				__( 'Error al cerrar la asignación anterior.', 'localpilot' )
			);
		}

		// Create new assignment.
		$new_assignment_id = Localpilot_Assignment_Repository::insert(
			array(
				'order_id'    => $order_id,
				'driver_id'   => $new_driver_id,
				'assigned_by' => $actor_id,
				'status'      => Localpilot_Delivery_Status::ASSIGNED,
			)
		);

		if ( ! $new_assignment_id ) {
			// Critical: old assignment was closed but new one failed.
			// Log the error; the order ends up with no active assignment.
			do_action( 'lclplt_reassignment_failed', $order_id, $old_driver_id, $new_driver_id, $actor_id );
			return new WP_Error(
				'lclplt_conflict',
				__( 'Error al crear la nueva asignación. La asignación anterior fue cerrada; reasigna nuevamente.', 'localpilot' )
			);
		}

		// Update order meta.
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$meta->set_bulk( array(
			Localpilot_Order_Delivery_Meta::DRIVER_ID       => $new_driver_id,
			Localpilot_Order_Delivery_Meta::DELIVERY_STATUS  => Localpilot_Delivery_Status::ASSIGNED,
			Localpilot_Order_Delivery_Meta::ASSIGNMENT_ID    => $new_assignment_id,
			Localpilot_Order_Delivery_Meta::ASSIGNED_AT      => current_time( 'mysql', true ),
		) )->save();

		// Event for the reassignment.
		$event_id = Localpilot_Event_Repository::insert( array(
			'order_id'      => $order_id,
			'assignment_id' => $new_assignment_id,
			'driver_id'     => $new_driver_id,
			'user_id'       => $actor_id,
			'event_type'    => 'delivery_reassigned',
			'event_data'    => array(
				'old_driver_id' => $old_driver_id,
				'new_driver_id' => $new_driver_id,
				'assigned_by'   => $actor_id,
				'assignment_id' => $new_assignment_id,
			),
		) );

		$new_driver = get_userdata( $new_driver_id );
		$new_name   = $new_driver ? $new_driver->display_name : '#' . $new_driver_id;
		$old_driver = get_userdata( $old_driver_id );
		$old_name   = $old_driver ? $old_driver->display_name : '#' . $old_driver_id;

		$order->add_order_note(
			sprintf(
				/* translators: 1: old driver name, 2: new driver name */
				__( 'LocalPilot: Reasignado de %1$s a %2$s.', 'localpilot' ),
				$old_name,
				$new_name
			),
			false,
			true
		);

		/**
		 * Fired after a delivery has been reassigned.
		 *
		 * @param int $order_id         Order ID.
		 * @param int $old_driver_id    Previous driver ID.
		 * @param int $new_driver_id    New driver ID.
		 * @param int $new_assignment_id New assignment ID.
		 * @param int $event_id         Event ID.
		 */
		do_action( 'lclplt_delivery_reassigned', $order_id, $old_driver_id, $new_driver_id, $new_assignment_id, $event_id );

		return $new_assignment_id;
	}

	/**
	 * Unassign (remove) a driver from an order without reassigning.
	 *
	 * Closes the active assignment as cancelled and resets order meta to unassigned.
	 *
	 * @param int $order_id Order ID.
	 * @param int $actor_id User ID performing the unassignment.
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public static function unassign( $order_id, $actor_id = 0 ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		if ( ! $actor_id || ! user_can( $actor_id, Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			return new WP_Error(
				'lclplt_forbidden',
				__( 'No tienes permiso para retirar asignaciones.', 'localpilot' )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'lclplt_delivery_not_found',
				__( 'El pedido no existe.', 'localpilot' )
			);
		}

		$current = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
		if ( null === $current ) {
			return new WP_Error(
				'lclplt_delivery_not_found',
				__( 'El pedido no tiene una asignación activa.', 'localpilot' )
			);
		}

		$old_driver_id = (int) $current->driver_id;

		// Close as cancelled.
		Localpilot_Assignment_Repository::close(
			(int) $current->id,
			Localpilot_Delivery_Status::CANCELLED
		);

		// Reset order meta to unassigned.
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$meta->set_bulk( array(
			Localpilot_Order_Delivery_Meta::DRIVER_ID        => 0,
			Localpilot_Order_Delivery_Meta::DELIVERY_STATUS   => Localpilot_Delivery_Status::UNASSIGNED,
			Localpilot_Order_Delivery_Meta::ASSIGNMENT_ID     => 0,
		) )->save();

		$event_id = Localpilot_Event_Repository::insert( array(
			'order_id'      => $order_id,
			'assignment_id' => (int) $current->id,
			'driver_id'     => $old_driver_id,
			'user_id'       => $actor_id,
			'event_type'    => 'delivery_unassigned',
			'event_data'    => array(
				'driver_id'   => $old_driver_id,
				'unassigned_by' => $actor_id,
			),
		) );

		$old_driver = get_userdata( $old_driver_id );
		$old_name   = $old_driver ? $old_driver->display_name : '#' . $old_driver_id;

		$order->add_order_note(
			sprintf(
				/* translators: %s: driver display name */
				__( 'LocalPilot: Asignación retirada a %s.', 'localpilot' ),
				$old_name
			),
			false,
			true
		);

		/**
		 * Fired after a delivery has been unassigned.
		 *
		 * @param int $order_id      Order ID.
		 * @param int $old_driver_id Previous driver ID.
		 * @param int $event_id      Event ID.
		 */
		do_action( 'lclplt_delivery_unassigned', $order_id, $old_driver_id, $event_id );

		return true;
	}
}
