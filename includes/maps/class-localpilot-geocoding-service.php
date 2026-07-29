<?php
/**
 * Geocoding service.
 *
 * Orchestrates the geocoding flow: builds an address from the order,
 * calls Mapbox, and persists the result in order meta.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/maps
 */

/**
 * Geocoding service.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/maps
 */
class Localpilot_Geocoding_Service {

	/**
	 * Geocode the shipping address of an order and persist the result.
	 *
	 * Only geocodes if:
	 *   - Mapbox integration is enabled in settings.
	 *   - The order does not already have a successful geocoding (unless manual).
	 *   - The order has a valid shipping/billing address.
	 *
	 * @param int $order_id Order ID.
	 * @return array|WP_Error Array with geocoding data on success, WP_Error otherwise.
	 */
	public static function geocode_order( $order_id ) {
		$enabled = get_option( 'lclplt_enable_mapbox', 'no' );
		if ( 'yes' !== $enabled ) {
			return new WP_Error(
				'lclplt_geocoding_disabled',
				__( 'Mapbox geocoding is disabled.', 'localpilot' )
			);
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'lclplt_order_not_found',
				__( 'Order not found.', 'localpilot' )
			);
		}

		$meta = new Localpilot_Order_Delivery_Meta( $order );

		// Do not re-geocode if already successfully geocoded (unless manual correction).
		$current_status = $meta->get_geocoding_status();
		if ( 'success' === $current_status ) {
			return new WP_Error(
				'lclplt_already_geocoded',
				__( 'Order already geocoded.', 'localpilot' )
			);
		}

		// Build address from order.
		$address = self::build_address( $order );
		if ( empty( $address ) ) {
			$meta->set( Localpilot_Order_Delivery_Meta::GEOCODING_STATUS, 'failed' )->save();
			return new WP_Error(
				'lclplt_no_address',
				__( 'Order has no shipping or billing address.', 'localpilot' )
			);
		}

		// Get Mapbox client config from settings.
		$token    = get_option( 'lclplt_mapbox_token', '' );
		$country  = get_option( 'lclplt_mapbox_country', '' );
		$language = get_option( 'lclplt_mapbox_language', 'es' );

		if ( empty( $token ) ) {
			$meta->set( Localpilot_Order_Delivery_Meta::GEOCODING_STATUS, 'failed' )->save();
			return new WP_Error(
				'lclplt_mapbox_no_token',
				__( 'Mapbox token is not configured.', 'localpilot' )
			);
		}

		$client  = new Localpilot_Mapbox_Client( $token, $country, $language );
		$result  = $client->geocode( $address );

		if ( is_wp_error( $result ) ) {
			$meta->set( Localpilot_Order_Delivery_Meta::GEOCODING_STATUS, 'failed' )->save();
			return $result;
		}

		$now = current_time( 'mysql', true );

		$meta->set_bulk( array(
			Localpilot_Order_Delivery_Meta::DELIVERY_LATITUDE  => $result['latitude'],
			Localpilot_Order_Delivery_Meta::DELIVERY_LONGITUDE => $result['longitude'],
			Localpilot_Order_Delivery_Meta::MAPBOX_PLACE_ID    => $result['place_id'],
			Localpilot_Order_Delivery_Meta::GEOCODED_ADDRESS   => $result['formatted_address'],
			Localpilot_Order_Delivery_Meta::GEOCODED_AT        => $now,
			Localpilot_Order_Delivery_Meta::GEOCODING_STATUS   => 'success',
		) )->save();

		// Log event.
		$assignment = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
		$assignment_id = $assignment ? (int) $assignment->id : 0;
		$driver_id     = $assignment ? (int) $assignment->driver_id : 0;

		Localpilot_Event_Repository::insert( array(
			'order_id'       => $order_id,
			'assignment_id'  => $assignment_id,
			'driver_id'      => $driver_id,
			'user_id'        => 0,
			'event_type'     => 'delivery_geocoded',
			'event_data'     => array(
				'status'    => 'success',
			),
		) );

		return $result;
	}

	/**
	 * Build a full address string from an order.
	 *
	 * @param WC_Order $order Order object.
	 * @return string Address string, or empty if no address available.
	 */
	public static function build_address( $order ) {
		$parts = array();

		// Prefer shipping address, fall back to billing.
		$address_1 = $order->get_shipping_address_1()
			? $order->get_shipping_address_1()
			: $order->get_billing_address_1();

		$address_2 = $order->get_shipping_address_2()
			? $order->get_shipping_address_2()
			: $order->get_billing_address_2();

		$city = $order->get_shipping_city()
			? $order->get_shipping_city()
			: $order->get_billing_city();

		$state = $order->get_shipping_state()
			? $order->get_shipping_state()
			: $order->get_billing_state();

		$postcode = $order->get_shipping_postcode()
			? $order->get_shipping_postcode()
			: $order->get_billing_postcode();

		$country = $order->get_shipping_country()
			? $order->get_shipping_country()
			: $order->get_billing_country();

		if ( ! empty( $address_1 ) ) {
			$parts[] = $address_1;
		}
		if ( ! empty( $address_2 ) ) {
			$parts[] = $address_2;
		}
		if ( ! empty( $city ) ) {
			$parts[] = $city;
		}
		if ( ! empty( $state ) ) {
			$parts[] = $state;
		}
		if ( ! empty( $postcode ) ) {
			$parts[] = $postcode;
		}
		if ( ! empty( $country ) ) {
			$parts[] = $country;
		}

		return implode( ', ', $parts );
	}

	/**
	 * Check if an order has valid geocoding coordinates.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public static function has_coordinates( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$lat  = $meta->get_latitude();
		$lng  = $meta->get_longitude();
		return ! empty( $lat ) && ! empty( $lng );
	}
}
