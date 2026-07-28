<?php
/**
 * Assignment repository.
 *
 * Data access layer for the lclplt_assignments table.
 * All queries use $wpdb->prepare() and timestamps are UTC.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */

/**
 * Assignment repository.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */
class Localpilot_Assignment_Repository {

	/**
	 * Get the assignments table name.
	 *
	 * @return string
	 */
	private static function table() {
		return Localpilot_DB_Schema::assignments_table();
	}

	/**
	 * Retrieve a single assignment by ID.
	 *
	 * @param int $assignment_id Assignment ID.
	 * @return object|null Row object or null.
	 */
	public static function get( $assignment_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE id = %d',
				$assignment_id
			)
		);
	}

	/**
	 * Get the active (non-terminal) assignment for a given order.
	 *
	 * @param int $order_id Order ID.
	 * @return object|null Row object or null if none active.
	 */
	public static function get_active_by_order( $order_id ) {
		global $wpdb;

		$terminal = Localpilot_Delivery_Status::TERMINAL_STATUSES;
		$placeholders = array_fill( 0, count( $terminal ), '%s' );
		$placeholders_str = implode( ',', $placeholders );
		$params = array_merge( array( $order_id ), $terminal );

		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE order_id = %d AND status NOT IN (' . $placeholders_str . ') ORDER BY id DESC LIMIT 1',
				$params
			)
		);
	}

	/**
	 * Get the latest assignment for a given order (any status).
	 *
	 * @param int $order_id Order ID.
	 * @return object|null Row object or null.
	 */
	public static function get_latest_by_order( $order_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE order_id = %d ORDER BY id DESC LIMIT 1',
				$order_id
			)
		);
	}

	/**
	 * Query assignments for a given driver, with optional status filter.
	 *
	 * @param int         $driver_id User ID of the driver.
	 * @param string|null $status    Optional status to filter by.
	 * @param int         $limit     Max results (default 20).
	 * @param int         $offset    Offset for pagination (default 0).
	 * @return array Array of row objects.
	 */
	public static function get_by_driver( $driver_id, $status = null, $limit = 20, $offset = 0 ) {
		global $wpdb;

		$sql = 'SELECT * FROM ' . self::table() . ' WHERE driver_id = %d';
		$params = array( $driver_id );

		if ( $status ) {
			$sql .= ' AND status = %s';
			$params[] = $status;
		}

		$sql .= ' ORDER BY id DESC LIMIT %d OFFSET %d';
		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$sql,
				$params
			)
		);
	}

	/**
	 * Query assignments for a given driver with multiple statuses.
	 *
	 * @param int   $driver_id User ID of the driver.
	 * @param array $statuses  Array of status strings to include.
	 * @param int   $limit     Max results.
	 * @param int   $offset    Offset.
	 * @return array Array of row objects.
	 */
	public static function get_by_driver_statuses( $driver_id, array $statuses, $limit = 20, $offset = 0 ) {
		global $wpdb;

		if ( empty( $statuses ) ) {
			return array();
		}

		$placeholders = array_fill( 0, count( $statuses ), '%s' );
		$placeholders_str = implode( ',', $placeholders );
		$params = array_merge( array( $driver_id ), $statuses );

		$sql = 'SELECT * FROM ' . self::table()
			. ' WHERE driver_id = %d AND status IN (' . $placeholders_str . ')'
			. ' ORDER BY id DESC LIMIT %d OFFSET %d';

		$params[] = $limit;
		$params[] = $offset;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$sql,
				$params
			)
		);
	}

	/**
	 * Count assignments for a given driver, with optional status filter.
	 *
	 * @param int         $driver_id User ID.
	 * @param string|null $status    Optional status.
	 * @return int
	 */
	public static function count_by_driver( $driver_id, $status = null ) {
		global $wpdb;

		$sql = 'SELECT COUNT(*) FROM ' . self::table() . ' WHERE driver_id = %d';
		$params = array( $driver_id );

		if ( $status ) {
			$sql .= ' AND status = %s';
			$params[] = $status;
		}

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$sql,
				$params
			)
		);
	}

	/**
	 * Count active (non-terminal) assignments for a driver.
	 *
	 * @param int $driver_id User ID.
	 * @return int
	 */
	public static function count_active_by_driver( $driver_id ) {
		global $wpdb;

		$terminal = Localpilot_Delivery_Status::TERMINAL_STATUSES;
		$placeholders = array_fill( 0, count( $terminal ), '%s' );
		$placeholders_str = implode( ',', $placeholders );
		$params = array_merge( array( $driver_id ), $terminal );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT COUNT(*) FROM ' . self::table() . ' WHERE driver_id = %d AND status NOT IN (' . $placeholders_str . ')',
				$params
			)
		);
	}

	/**
	 * Check if an order already has an active assignment.
	 *
	 * @param int $order_id Order ID.
	 * @return bool
	 */
	public static function has_active_assignment( $order_id ) {
		$row = self::get_active_by_order( $order_id );
		return null !== $row;
	}

	/**
	 * Insert a new assignment.
	 *
	 * @param array $data Associative array of columns and values.
	 * @return int|false Inserted ID or false on failure.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'order_id'      => 0,
			'driver_id'     => 0,
			'assigned_by'   => 0,
			'status'        => 'assigned',
			'assigned_at'   => current_time( 'mysql', true ),
			'created_at'    => current_time( 'mysql', true ),
			'updated_at'    => current_time( 'mysql', true ),
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			self::table(),
			$data,
			array(
				'%d', // order_id
				'%d', // driver_id
				'%d', // assigned_by
				'%s', // status
				'%s', // assigned_at
				'%s', // accepted_at
				'%s', // out_for_delivery_at
				'%s', // delivered_at
				'%s', // failed_at
				'%s', // cancelled_at
				'%s', // created_at
				'%s', // updated_at
			)
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update an assignment's status and timestamp.
	 *
	 * @param int    $assignment_id Assignment ID.
	 * @param string $status        New status.
	 * @return bool
	 */
	public static function update_status( $assignment_id, $status ) {
		global $wpdb;

		$timestamp_column = self::status_timestamp_column( $status );
		$data = array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql', true ),
		);

		if ( $timestamp_column ) {
			$data[ $timestamp_column ] = current_time( 'mysql', true );
		}

		return (bool) $wpdb->update(
			self::table(),
			$data,
			array( 'id' => $assignment_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Update the driver of an existing active assignment (reassign).
	 *
	 * @param int $assignment_id Assignment ID.
	 * @param int $new_driver_id New driver user ID.
	 * @return bool
	 */
	public static function update_driver( $assignment_id, $new_driver_id ) {
		global $wpdb;

		return (bool) $wpdb->update(
			self::table(),
			array(
				'driver_id'  => $new_driver_id,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $assignment_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Close an assignment (set terminal status) without timestamps.
	 * Used when cancelling an existing assignment during reassignment.
	 *
	 * @param int    $assignment_id Assignment ID.
	 * @param string $status        Terminal status.
	 * @return bool
	 */
	public static function close( $assignment_id, $status ) {
		global $wpdb;

		$timestamp_column = self::status_timestamp_column( $status );
		$data = array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql', true ),
		);

		if ( $timestamp_column ) {
			$data[ $timestamp_column ] = current_time( 'mysql', true );
		}

		return (bool) $wpdb->update(
			self::table(),
			$data,
			array( 'id' => $assignment_id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get the timestamp column name for a given status.
	 *
	 * @param string $status Status constant.
	 * @return string|null Column name or null.
	 */
	private static function status_timestamp_column( $status ) {
		$map = array(
			Localpilot_Delivery_Status::ASSIGNED        => 'assigned_at',
			Localpilot_Delivery_Status::ACCEPTED        => 'accepted_at',
			Localpilot_Delivery_Status::OUT_FOR_DELIVERY => 'out_for_delivery_at',
			Localpilot_Delivery_Status::DELIVERED       => 'delivered_at',
			Localpilot_Delivery_Status::FAILED          => 'failed_at',
			Localpilot_Delivery_Status::CANCELLED       => 'cancelled_at',
		);

		return isset( $map[ $status ] ) ? $map[ $status ] : null;
	}

	/**
	 * Get all assignments for an order (historical).
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public static function get_by_order( $order_id ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE order_id = %d ORDER BY id DESC',
				$order_id
			)
		);
	}

	/**
	 * Delete all assignments for a set of order IDs.
	 * Used only during uninstall with opt-in.
	 *
	 * @param array $order_ids Array of order IDs.
	 * @return int Number of rows deleted.
	 */
	public static function delete_by_orders( array $order_ids ) {
		global $wpdb;

		if ( empty( $order_ids ) ) {
			return 0;
		}

		$placeholders = array_fill( 0, count( $order_ids ), '%d' );
		$placeholders_str = implode( ',', $placeholders );

		$wpdb->query(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'DELETE FROM ' . self::table() . ' WHERE order_id IN (' . $placeholders_str . ')',
				$order_ids
			)
		);

		return $wpdb->rows_affected;
	}
}
