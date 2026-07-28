<?php
/**
 * Delivery query service.
 *
 * Provides filtered, paginated queries for deliveries visible to
 * managers and drivers, respecting ownership and permissions.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Delivery query service.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Delivery_Query {

	/**
	 * Default number of items per page.
	 *
	 * @var int
	 */
	const DEFAULT_PER_PAGE = 20;

	/**
	 * Get deliveries visible to the current manager.
	 *
	 * @param array $args Query arguments.
	 *   status  (string|null) Filter by status.
	 *   driver  (int|null)    Filter by driver ID.
	 *   page    (int)         Page number.
	 *   per_page (int)        Results per page.
	 *
	 * @return array {
	 *   @type array  $items       Array of assignment row objects.
	 *   @type int    $total       Total matching results.
	 *   @type int    $total_pages Number of pages.
	 *   @type int    $page        Current page.
	 * }
	 */
	public static function get_for_manager( array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => null,
			'driver'   => null,
			'page'     => 1,
			'per_page' => self::DEFAULT_PER_PAGE,
		);

		$args    = wp_parse_args( $args, $defaults );
		$table   = Localpilot_DB_Schema::assignments_table();
		$where   = array( '1=1' );
		$params  = array();

		if ( $args['status'] ) {
			$where[]  = 'a.status = %s';
			$params[] = $args['status'];
		}

		if ( $args['driver'] ) {
			$where[]  = 'a.driver_id = %d';
			$params[] = (int) $args['driver'];
		}

		$where_sql = implode( ' AND ', $where );

		// Count.
		$count_sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'SELECT COUNT(*) FROM ' . $table . ' a WHERE ' . $where_sql,
			$params
		);
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$per_page = max( 1, (int) $args['per_page'] );
		$page     = max( 1, (int) $args['page'] );
		$offset   = ( $page - 1 ) * $per_page;
		$total_pages = (int) ceil( $total / $per_page );

		// Results.
		$select_params = array_merge( $params, array( $per_page, $offset ) );
		$select_sql = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'SELECT a.* FROM ' . $table . ' a WHERE ' . $where_sql . ' ORDER BY a.id DESC LIMIT %d OFFSET %d',
			$select_params
		);
		$items = $wpdb->get_results( $select_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => $page,
		);
	}

	/**
	 * Get deliveries visible to a specific driver.
	 *
	 * Only returns assignments owned by this driver.
	 *
	 * @param int   $driver_id User ID.
	 * @param array $args      Query arguments.
	 *   status   (string|null) Single status.
	 *   statuses (array|null)  Multiple statuses.
	 *   page     (int)         Page number.
	 *   per_page (int)         Results per page.
	 *
	 * @return array {
	 *   @type array  $items       Array of assignment row objects.
	 *   @type int    $total       Total matching results.
	 *   @type int    $total_pages Number of pages.
	 *   @type int    $page        Current page.
	 * }
	 */
	public static function get_for_driver( $driver_id, array $args = array() ) {
		global $wpdb;

		$defaults = array(
			'status'   => null,
			'statuses' => null,
			'page'     => 1,
			'per_page' => self::DEFAULT_PER_PAGE,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = Localpilot_DB_Schema::assignments_table();

		if ( ! empty( $args['statuses'] ) && is_array( $args['statuses'] ) ) {
			$items = Localpilot_Assignment_Repository::get_by_driver_statuses(
				$driver_id,
				$args['statuses'],
				$args['per_page'],
				( max( 1, (int) $args['page'] ) - 1 ) * $args['per_page']
			);

			$total = self::count_driver_latest_by_statuses( $driver_id, $args['statuses'] );

			$per_page     = max( 1, (int) $args['per_page'] );
			$total_pages  = (int) ceil( $total / $per_page );

			return array(
				'items'       => $items,
				'total'       => $total,
				'total_pages' => $total_pages,
				'page'        => max( 1, (int) $args['page'] ),
			);
		}

		$items = Localpilot_Assignment_Repository::get_by_driver(
			$driver_id,
			$args['status'],
			$args['per_page'],
			( max( 1, (int) $args['page'] ) - 1 ) * $args['per_page']
		);

		$total       = Localpilot_Assignment_Repository::count_by_driver( $driver_id, $args['status'] );
		$per_page    = max( 1, (int) $args['per_page'] );
		$total_pages = (int) ceil( $total / $per_page );

		return array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => $total_pages,
			'page'        => max( 1, (int) $args['page'] ),
		);
	}

	/**
	 * Count assignments for a driver grouped by delivery status.
	 *
	 * @param int $driver_id Driver user ID.
	 * @return array<string, int>
	 */
	public static function count_by_driver_status( $driver_id ) {
		global $wpdb;

		$statuses = Localpilot_Delivery_Status::all();
		$counts  = array_fill_keys( $statuses, 0 );
		$table   = Localpilot_DB_Schema::assignments_table();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT latest.status, COUNT(*) AS total FROM ' . $table . ' latest INNER JOIN (SELECT order_id, MAX(id) AS latest_id FROM ' . $table . ' WHERE driver_id = %d GROUP BY order_id) newest ON newest.latest_id = latest.id GROUP BY latest.status',
				$driver_id
			)
		);

		foreach ( $rows as $row ) {
			if ( isset( $counts[ $row->status ] ) ) {
				$counts[ $row->status ] = (int) $row->total;
			}
		}

		return $counts;
	}

	/**
	 * Count the latest assignment per order for one driver and statuses.
	 *
	 * @param int   $driver_id Driver user ID.
	 * @param array $statuses  Statuses to include.
	 * @return int
	 */
	private static function count_driver_latest_by_statuses( $driver_id, array $statuses ) {
		global $wpdb;

		if ( empty( $statuses ) ) {
			return 0;
		}

		$table        = Localpilot_DB_Schema::assignments_table();
		$placeholders = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
		$params       = array_merge( array( $driver_id ), $statuses );

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT COUNT(*) FROM ' . $table . ' latest INNER JOIN (SELECT order_id, MAX(id) AS latest_id FROM ' . $table . ' WHERE driver_id = %d GROUP BY order_id) newest ON newest.latest_id = latest.id WHERE latest.status IN (' . $placeholders . ')',
				$params
			)
		);
	}

	/**
	 * Get the active delivery for an order, if any.
	 *
	 * @param int $order_id Order ID.
	 * @return object|null Assignment row or null.
	 */
	public static function get_active_by_order( $order_id ) {
		return Localpilot_Assignment_Repository::get_active_by_order( $order_id );
	}

	/**
	 * Get the delivery status label for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return string Translated status label or '—'.
	 */
	public static function get_status_label_for_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '—';
		}

		$meta   = new Localpilot_Order_Delivery_Meta( $order );
		$status = $meta->get_delivery_status();

		if ( empty( $status ) || Localpilot_Delivery_Status::UNASSIGNED === $status ) {
			return '—';
		}

		return Localpilot_Delivery_Status::label( $status );
	}
}
