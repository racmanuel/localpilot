<?php
/**
 * Database schema manager.
 *
 * Creates and upgrades the custom tables used by LocalPilot.
 * All operations are idempotent and versioned.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */

/**
 * Database schema manager.
 *
 * Handles creation and versioned upgrades of the lclplt_assignments
 * and lclplt_events tables using dbDelta().
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */
class Localpilot_DB_Schema {

	/**
	 * Option name that stores the current schema version.
	 *
	 * @var string
	 */
	const DB_VERSION_OPTION = 'lclplt_db_version';

	/**
	 * Current schema version (increment for structural changes).
	 *
	 * @var int
	 */
	const DB_VERSION = 1;

	/**
	 * Get the table name for assignments.
	 *
	 * @global wpdb $wpdb
	 * @return string
	 */
	public static function assignments_table() {
		global $wpdb;
		return $wpdb->prefix . 'lclplt_assignments';
	}

	/**
	 * Get the table name for events.
	 *
	 * @global wpdb $wpdb
	 * @return string
	 */
	public static function events_table() {
		global $wpdb;
		return $wpdb->prefix . 'lclplt_events';
	}

	/**
	 * Run the schema upgrade if the stored version is behind.
	 *
	 * Call during activation and on admin_init for plugin updates.
	 *
	 * @return bool True if an upgrade was performed.
	 */
	public static function maybe_upgrade() {
		$current_version = (int) get_option( self::DB_VERSION_OPTION, 0 );

		if ( $current_version >= self::DB_VERSION ) {
			return false;
		}

		self::create_tables();

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );

		return true;
	}

	/**
	 * Create or update the custom tables.
	 *
	 * Uses dbDelta() which is idempotent — safe to call multiple times.
	 *
	 * @global wpdb $wpdb
	 */
	public static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$assignments_table = self::assignments_table();
		$events_table      = self::events_table();

		$sql = "CREATE TABLE {$assignments_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			driver_id bigint(20) unsigned NOT NULL,
			assigned_by bigint(20) unsigned NOT NULL,
			status varchar(32) NOT NULL DEFAULT 'assigned',
			assigned_at datetime DEFAULT NULL,
			accepted_at datetime DEFAULT NULL,
			out_for_delivery_at datetime DEFAULT NULL,
			delivered_at datetime DEFAULT NULL,
			failed_at datetime DEFAULT NULL,
			cancelled_at datetime DEFAULT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY driver_id (driver_id),
			KEY status (status),
			KEY driver_status (driver_id, status),
			KEY order_status (order_id, status)
		) {$charset_collate};";

		$sql_events = "CREATE TABLE {$events_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			order_id bigint(20) unsigned NOT NULL,
			assignment_id bigint(20) unsigned DEFAULT NULL,
			driver_id bigint(20) unsigned DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			event_type varchar(64) NOT NULL,
			event_data longtext DEFAULT NULL,
			ip_address varchar(45) DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY order_id (order_id),
			KEY assignment_id (assignment_id),
			KEY driver_id (driver_id),
			KEY event_type (event_type)
		) {$charset_collate};";

		dbDelta( $sql );
		dbDelta( $sql_events );
	}

	/**
	 * Drop the custom tables.
	 *
	 * Used only during uninstall with opt-in.
	 *
	 * @global wpdb $wpdb
	 */
	public static function drop_tables() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::assignments_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'DROP TABLE IF EXISTS ' . self::events_table() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		delete_option( self::DB_VERSION_OPTION );
	}
}
