<?php
/**
 * Delivery status constants, labels, and transition rules.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Delivery status constants, labels, and transition rules.
 *
 * Single source of truth for delivery states, valid transitions,
 * and human-readable labels. No side effects.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Delivery_Status {

	/**
	 * Delivery status constants.
	 *
	 * @var string
	 */
	const UNASSIGNED      = 'unassigned';
	const ASSIGNED        = 'assigned';
	const ACCEPTED        = 'accepted';
	const OUT_FOR_DELIVERY = 'out_for_delivery';
	const DELIVERED       = 'delivered';
	const FAILED          = 'failed';
	const CANCELLED       = 'cancelled';

	/**
	 * Terminal statuses — no further transitions allowed.
	 *
	 * @var array
	 */
	const TERMINAL_STATUSES = array(
		self::DELIVERED,
		self::FAILED,
		self::CANCELLED,
	);

	/**
	 * Active statuses — delivery is in progress.
	 *
	 * @var array
	 */
	const ACTIVE_STATUSES = array(
		self::ASSIGNED,
		self::ACCEPTED,
		self::OUT_FOR_DELIVERY,
	);

	/**
	 * All valid statuses.
	 *
	 * @return array
	 */
	public static function all() {
		return array(
			self::UNASSIGNED,
			self::ASSIGNED,
			self::ACCEPTED,
			self::OUT_FOR_DELIVERY,
			self::DELIVERED,
			self::FAILED,
			self::CANCELLED,
		);
	}

	/**
	 * Check if a status is valid.
	 *
	 * @param string $status Status to check.
	 * @return bool
	 */
	public static function is_valid( $status ) {
		return in_array( $status, self::all(), true );
	}

	/**
	 * Check if a status is terminal.
	 *
	 * @param string $status Status to check.
	 * @return bool
	 */
	public static function is_terminal( $status ) {
		return in_array( $status, self::TERMINAL_STATUSES, true );
	}

	/**
	 * Check if a status is active (in progress).
	 *
	 * @param string $status Status to check.
	 * @return bool
	 */
	public static function is_active( $status ) {
		return in_array( $status, self::ACTIVE_STATUSES, true );
	}

	/**
	 * Get human-readable label for a status.
	 *
	 * @param string $status Status constant.
	 * @return string Translated label.
	 */
	public static function label( $status ) {
		$labels = array(
			self::UNASSIGNED      => __( 'Sin asignar', 'localpilot' ),
			self::ASSIGNED        => __( 'Asignado', 'localpilot' ),
			self::ACCEPTED        => __( 'Aceptado', 'localpilot' ),
			self::OUT_FOR_DELIVERY => __( 'En reparto', 'localpilot' ),
			self::DELIVERED       => __( 'Entregado', 'localpilot' ),
			self::FAILED          => __( 'Fallido', 'localpilot' ),
			self::CANCELLED       => __( 'Cancelado', 'localpilot' ),
		);

		return isset( $labels[ $status ] ) ? $labels[ $status ] : $status;
	}

	/**
	 * Get allowed transitions from a given status.
	 *
	 * Returns array of [target_status => actor] pairs.
	 * The actor indicates who can trigger the transition:
	 *   'manager'  – user with lclplt_manage_deliveries
	 *   'driver'   – assigned driver with the appropriate capability
	 *
	 * @param string $current_status Current delivery status.
	 * @return array Allowed transitions as [status => actor].
	 */
	public static function allowed_transitions( $current_status ) {
		$transitions = array(
			self::UNASSIGNED => array(
				self::ASSIGNED => 'manager',
			),
			self::ASSIGNED => array(
				self::ACCEPTED  => 'driver',
				self::OUT_FOR_DELIVERY => 'driver', // When acceptance is disabled.
				self::CANCELLED => 'manager',
			),
			self::ACCEPTED => array(
				self::OUT_FOR_DELIVERY => 'driver',
				self::CANCELLED       => 'manager',
			),
			self::OUT_FOR_DELIVERY => array(
				self::DELIVERED => 'driver',
				self::FAILED    => 'driver',
				self::CANCELLED => 'manager',
			),
			self::DELIVERED  => array(),
			self::FAILED     => array(),
			self::CANCELLED  => array(),
		);

		return isset( $transitions[ $current_status ] ) ? $transitions[ $current_status ] : array();
	}

	/**
	 * Check if a transition is allowed.
	 *
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 * @return bool
	 */
	public static function transition_allowed( $from, $to ) {
		$allowed = self::allowed_transitions( $from );
		return array_key_exists( $to, $allowed );
	}

	/**
	 * Get the actor for a given transition.
	 *
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 * @return string|false 'manager', 'driver', or false if not allowed.
	 */
	public static function transition_actor( $from, $to ) {
		$allowed = self::allowed_transitions( $from );
		if ( array_key_exists( $to, $allowed ) ) {
			return $allowed[ $to ];
		}
		return false;
	}

	/**
	 * Get CSS-friendly status class for UI styling.
	 *
	 * @param string $status Status constant.
	 * @return string CSS class suffix.
	 */
	public static function status_class( $status ) {
		$classes = array(
			self::UNASSIGNED      => 'unassigned',
			self::ASSIGNED        => 'assigned',
			self::ACCEPTED        => 'accepted',
			self::OUT_FOR_DELIVERY => 'out-for-delivery',
			self::DELIVERED       => 'delivered',
			self::FAILED          => 'failed',
			self::CANCELLED       => 'cancelled',
		);

		return isset( $classes[ $status ] ) ? $classes[ $status ] : 'unknown';
	}
}
