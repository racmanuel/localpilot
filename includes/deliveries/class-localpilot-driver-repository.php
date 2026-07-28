<?php
/**
 * Driver repository.
 *
 * Queries WordPress users with the localpilot_driver role.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Driver repository.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Driver_Repository {

	/**
	 * User meta key for active status.
	 *
	 * @var string
	 */
	const ACTIVE_META_KEY = '_lclplt_driver_active';

	/**
	 * Get all drivers (users with the localpilot_driver role).
	 *
	 * @param string $orderby Field to order by.
	 * @param string $order   ASC or DESC.
	 * @return array Array of WP_User objects.
	 */
	public static function get_all( $orderby = 'display_name', $order = 'ASC' ) {
		$args = array(
			'role'    => Localpilot_Driver_Role::ROLE,
			'orderby' => $orderby,
			'order'   => $order,
			'fields'  => 'all',
		);

		$user_query = new WP_User_Query( $args );
		return $user_query->results;
	}

	/**
	 * Get only active drivers (eligible for assignment).
	 *
	 * Active status is controlled by the _lclplt_driver_active user meta.
	 *
	 * @return array Array of WP_User objects.
	 */
	public static function get_active() {
		$args = array(
			'role'      => Localpilot_Driver_Role::ROLE,
			'meta_key'  => self::ACTIVE_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value' => '1',
			'orderby'   => 'display_name',
			'order'     => 'ASC',
			'fields'    => 'all',
		);

		$user_query = new WP_User_Query( $args );
		return $user_query->results;
	}

	/**
	 * Get a single driver by user ID.
	 *
	 * @param int $user_id User ID.
	 * @return WP_User|null
	 */
	public static function get( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! in_array( Localpilot_Driver_Role::ROLE, (array) $user->roles, true ) ) {
			return null;
		}
		return $user;
	}

	/**
	 * Check if a driver is active.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_active( $user_id ) {
		return '1' === get_user_meta( $user_id, self::ACTIVE_META_KEY, true );
	}

	/**
	 * Check if a driver exists and is active (convenience for assignment).
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_active_driver( $user_id ) {
		$driver = self::get( $user_id );
		if ( null === $driver ) {
			return false;
		}
		return self::is_active( $user_id );
	}

	/**
	 * Get drivers formatted as a simple ID => display_name map.
	 * Useful for select dropdowns.
	 *
	 * @param bool $only_active Whether to return only active drivers.
	 * @return array Associative array of user_id => display_name.
	 */
	public static function get_dropdown_options( $only_active = true ) {
		$drivers = $only_active ? self::get_active() : self::get_all();
		$options = array();

		foreach ( $drivers as $driver ) {
			$options[ $driver->ID ] = $driver->display_name;
		}

		return $options;
	}

	/**
	 * Get the count of active assignments for a driver.
	 *
	 * @param int $driver_id User ID.
	 * @return int
	 */
	public static function get_active_assignment_count( $driver_id ) {
		return Localpilot_Assignment_Repository::count_active_by_driver( $driver_id );
	}
}
