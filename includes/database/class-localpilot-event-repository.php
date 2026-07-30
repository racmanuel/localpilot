<?php
/**
 * Event repository.
 *
 * Data access layer for the lclplt_events table.
 * All queries use $wpdb->prepare() and timestamps are UTC.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */

/**
 * Event repository.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */
class Localpilot_Event_Repository {

	/**
	 * Get the events table name.
	 *
	 * @return string
	 */
	private static function table() {
		global $wpdb;
		return $wpdb->prefix . 'lclplt_events';
	}

	/**
	 * Insert a new event.
	 *
	 * @param array $data Event data.
	 *   Required: order_id, event_type.
	 *   Optional: assignment_id, driver_id, user_id, event_data (array), ip_address.
	 *
	 * @return int|false Inserted ID or false.
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'order_id'      => 0,
			'assignment_id' => null,
			'driver_id'     => null,
			'user_id'       => null,
			'event_type'    => '',
			'event_data'    => null,
			'ip_address'    => null,
			'created_at'    => current_time( 'mysql', true ),
		);

		$data = wp_parse_args( $data, $defaults );

		// Serialise event_data as JSON.
		if ( isset( $data['event_data'] ) && is_array( $data['event_data'] ) ) {
			$data['event_data'] = wp_json_encode( $data['event_data'] );
		}

		$result = $wpdb->insert(
			self::table(),
			$data,
			array(
				'%d',  // order_id
				'%d',  // assignment_id
				'%d',  // driver_id
				'%d',  // user_id
				'%s',  // event_type
				'%s',  // event_data (JSON)
				'%s',  // ip_address
				'%s',  // created_at
			)
		);

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Get events for a given order, newest first.
	 *
	 * @param int $order_id Order ID.
	 * @param int $limit    Max results.
	 * @param int $offset   Offset.
	 * @return array Array of row objects.
	 */
	public static function get_by_order( $order_id, $limit = 50, $offset = 0 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE order_id = %d ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d',
				$order_id,
				$limit,
				$offset
			)
		);
	}

	/**
	 * Get events for a given assignment.
	 *
	 * @param int $assignment_id Assignment ID.
	 * @param int $limit         Max results.
	 * @return array
	 */
	public static function get_by_assignment( $assignment_id, $limit = 50 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE assignment_id = %d ORDER BY created_at ASC, id ASC LIMIT %d',
				$assignment_id,
				$limit
			)
		);
	}

	/**
	 * Get the most recent assignment events in chronological display order.
	 *
	 * @param int $assignment_id Assignment ID.
	 * @param int $limit         Max results.
	 * @return array
	 */
	public static function get_recent_by_assignment( $assignment_id, $limit = 5 ) {
		global $wpdb;

		$events = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE assignment_id = %d ORDER BY created_at DESC, id DESC LIMIT %d',
				$assignment_id,
				$limit
			)
		);

		return array_reverse( $events );
	}

	/**
	 * Get events by type for a given order.
	 *
	 * @param int    $order_id  Order ID.
	 * @param string $event_type Event type.
	 * @param int    $limit      Max results.
	 * @return array
	 */
	public static function get_by_order_and_type( $order_id, $event_type, $limit = 10 ) {
		global $wpdb;

		return $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT * FROM ' . self::table() . ' WHERE order_id = %d AND event_type = %s ORDER BY created_at DESC, id DESC LIMIT %d',
				$order_id,
				$event_type,
				$limit
			)
		);
	}

	/**
	 * Delete all events for a set of order IDs.
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
