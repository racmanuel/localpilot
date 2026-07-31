<?php
/**
 * Point-in-time delivery location validation.
 *
 * Compares one browser GPS position with the geocoded order destination when
 * a driver completes a delivery. This is not live tracking.
 *
 * @link       https://racmanuel.dev/
 * @since      1.1.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/location
 */

/**
 * Validates a driver's location at delivery completion time.
 *
 * @since      1.1.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/location
 */
class Localpilot_Location_Validation_Service {

	const ENABLED_OPTION = 'lclplt_location_validation_enabled';
	const RADIUS_OPTION  = 'lclplt_location_validation_radius';
	const MODE_OPTION    = 'lclplt_location_validation_mode';

	/**
	 * Check whether point validation is enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return 'yes' === get_option( self::ENABLED_OPTION, 'no' );
	}

	/**
	 * Get the configured validation radius.
	 *
	 * @return int Radius in metres.
	 */
	public static function get_radius() {
		$radius = absint( get_option( self::RADIUS_OPTION, 100 ) );
		return min( 5000, max( 10, $radius ? $radius : 100 ) );
	}

	/**
	 * Get the configured failure policy.
	 *
	 * @return string warning or blocking.
	 */
	public static function get_mode() {
		$mode = get_option( self::MODE_OPTION, 'warning' );
		return in_array( $mode, array( 'warning', 'blocking' ), true ) ? $mode : 'warning';
	}

	/**
	 * Get a safe, translatable message for a validation failure.
	 *
	 * @param string $status Validation status.
	 * @return string
	 */
	public static function get_error_message( $status ) {
		$messages = array(
			'permission_denied' => __( 'Debes permitir el acceso a tu ubicación para validar la entrega.', 'localpilot' ),
			'unavailable'       => __( 'No se pudo obtener tu ubicación. Activa el GPS e inténtalo nuevamente.', 'localpilot' ),
			'timeout'           => __( 'La solicitud de ubicación tardó demasiado. Inténtalo nuevamente.', 'localpilot' ),
			'stale'             => __( 'La ubicación recibida está desactualizada. Obtén una nueva ubicación.', 'localpilot' ),
			'low_accuracy'      => __( 'La precisión del GPS es insuficiente. Muévete a un lugar con mejor señal e inténtalo nuevamente.', 'localpilot' ),
			'outside_radius'    => __( 'Estás fuera del radio permitido para este destino.', 'localpilot' ),
			'no_destination'    => __( 'El pedido no tiene coordenadas de destino. La validación no se realizó.', 'localpilot' ),
			'disabled'          => __( 'La validación de ubicación está desactivada.', 'localpilot' ),
		);

		return isset( $messages[ $status ] ) ? $messages[ $status ] : __( 'No se pudo validar la ubicación de la entrega.', 'localpilot' );
	}

	/**
	 * Validate a single browser location against an order destination.
	 *
	 * The server calculates the distance. A client-provided result is never
	 * trusted. Missing destination coordinates remain non-blocking because a
	 * geocoding failure must not make the delivery impossible to complete.
	 *
	 * @param int          $order_id       Order ID.
	 * @param mixed        $latitude       Driver latitude.
	 * @param mixed        $longitude      Driver longitude.
	 * @param mixed        $accuracy       GPS accuracy in metres.
	 * @param mixed        $timestamp      Client timestamp in seconds or ms.
	 * @param string       $client_status  Browser status such as permission_denied.
	 * @return array Validation result.
	 */
	public static function validate( $order_id, $latitude, $longitude, $accuracy = null, $timestamp = null, $client_status = '' ) {
		$radius = self::get_radius();
		$mode   = self::get_mode();

		$result = array(
			'status'          => 'not_validated',
			'allowed'         => true,
			'distance_meters' => null,
			'radius_meters'   => $radius,
			'accuracy_meters' => null,
			'validated_at'    => current_time( 'mysql', true ),
			'target_latitude' => null,
			'target_longitude'=> null,
		);

		if ( ! self::is_enabled() ) {
			$result['status'] = 'disabled';
			return $result;
		}

		$order = wc_get_order( absint( $order_id ) );
		if ( ! $order ) {
			$result['status'] = 'not_validated';
			return $result;
		}

		$meta       = new Localpilot_Order_Delivery_Meta( $order );
		$target_lat = self::normalise_coordinate( $meta->get_latitude(), -90, 90 );
		$target_lng = self::normalise_coordinate( $meta->get_longitude(), -180, 180 );

		if ( null === $target_lat || null === $target_lng ) {
			$result['status'] = 'no_destination';
			return $result;
		}

		$result['target_latitude']  = $target_lat;
		$result['target_longitude'] = $target_lng;

		$client_status = sanitize_key( (string) $client_status );
		if ( in_array( $client_status, array( 'permission_denied', 'unavailable', 'timeout' ), true ) ) {
			return self::fail( $result, $client_status, $mode );
		}

		$driver_lat = self::normalise_coordinate( $latitude, -90, 90 );
		$driver_lng = self::normalise_coordinate( $longitude, -180, 180 );
		if ( null === $driver_lat || null === $driver_lng ) {
			return self::fail( $result, 'unavailable', $mode );
		}

		$accuracy = self::normalise_accuracy( $accuracy );
		$result['accuracy_meters'] = $accuracy;

		$client_timestamp = self::normalise_timestamp( $timestamp );
		if ( null !== $client_timestamp && ( $client_timestamp > time() + 60 || $client_timestamp < time() - 300 ) ) {
			return self::fail( $result, 'stale', $mode );
		}

		$distance = self::distance_in_meters( $driver_lat, $driver_lng, $target_lat, $target_lng );
		$result['distance_meters'] = round( $distance, 2 );

		if ( null !== $accuracy && $accuracy > max( 100, $radius ) ) {
			return self::fail( $result, 'low_accuracy', $mode );
		}

		if ( $distance <= $radius ) {
			$result['status'] = 'passed';
			return $result;
		}

		return self::fail( $result, 'outside_radius', $mode );
	}

	/**
	 * Mark a validation result as failed.
	 *
	 * Failures are advisory in "warning" mode and blocking in "blocking"
	 * mode, so `allowed` is derived from the policy here instead of being
	 * repeated at every failure branch.
	 *
	 * @param array  $result Validation result being built.
	 * @param string $status Failure status.
	 * @param string $mode   Failure policy (warning|blocking).
	 * @return array
	 */
	private static function fail( $result, $status, $mode ) {
		$result['status']  = $status;
		$result['allowed'] = 'warning' === $mode;
		return $result;
	}

	/**
	 * Normalise a coordinate and enforce its geographic range.
	 *
	 * @param mixed $value Coordinate.
	 * @param float $min Minimum value.
	 * @param float $max Maximum value.
	 * @return float|null
	 */
	private static function normalise_coordinate( $value, $min, $max ) {
		if ( ! is_numeric( $value ) || ! is_finite( (float) $value ) ) {
			return null;
		}

		$value = (float) $value;
		return ( $value >= $min && $value <= $max ) ? $value : null;
	}

	/**
	 * Normalise GPS accuracy.
	 *
	 * @param mixed $value Accuracy in metres.
	 * @return float|null
	 */
	private static function normalise_accuracy( $value ) {
		if ( '' === $value || null === $value || ! is_numeric( $value ) || ! is_finite( (float) $value ) ) {
			return null;
		}

		$value = (float) $value;
		return $value >= 0 ? round( $value, 2 ) : null;
	}

	/**
	 * Normalise a client timestamp to Unix seconds.
	 *
	 * @param mixed $value Timestamp in seconds or milliseconds.
	 * @return int|null
	 */
	private static function normalise_timestamp( $value ) {
		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			return null;
		}

		$value = (float) $value;
		if ( $value > 20000000000 ) {
			$value /= 1000;
		}

		return (int) round( $value );
	}

	/**
	 * Calculate great-circle distance using the Haversine formula.
	 *
	 * @param float $lat1 First latitude.
	 * @param float $lng1 First longitude.
	 * @param float $lat2 Second latitude.
	 * @param float $lng2 Second longitude.
	 * @return float Distance in metres.
	 */
	private static function distance_in_meters( $lat1, $lng1, $lat2, $lng2 ) {
		$earth_radius = 6371000;
		$lat_delta    = deg2rad( $lat2 - $lat1 );
		$lng_delta    = deg2rad( $lng2 - $lng1 );
		$lat1         = deg2rad( $lat1 );
		$lat2         = deg2rad( $lat2 );

		$a = sin( $lat_delta / 2 ) * sin( $lat_delta / 2 )
			+ cos( $lat1 ) * cos( $lat2 )
			* sin( $lng_delta / 2 ) * sin( $lng_delta / 2 );

		return 2 * $earth_radius * asin( min( 1, sqrt( $a ) ) );
	}
}
