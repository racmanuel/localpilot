<?php
/**
 * Geocoding trigger handler — runs on delivery assignment.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/maps
 */

/**
 * Geocoding on delivery assignment callback.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/maps
 */
class Localpilot_Geocoding_Handler {

	/**
	 * Trigger geocoding when a delivery is assigned.
	 *
	 * Hooked to lclplt_delivery_assigned. Runs silently — failures are
	 * logged but never block the assignment flow.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $driver_id     Driver user ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $event_id      Event ID.
	 */
	public static function maybe_geocode_order( $order_id, $driver_id, $assignment_id, $event_id ) {
		if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
			return;
		}

		$enabled = get_option( 'lclplt_enable_mapbox', 'no' );
		if ( 'yes' !== $enabled ) {
			return;
		}

		$auto = get_option( 'lclplt_auto_geocode', 'yes' );
		if ( 'yes' !== $auto ) {
			return;
		}

		Localpilot_Geocoding_Service::geocode_order( $order_id );
	}
}
