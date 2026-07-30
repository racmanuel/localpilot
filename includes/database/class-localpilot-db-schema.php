<?php
/**
 * Database table name helpers and teardown.
 *
 * Provides table name resolution used by repositories and query classes,
 * and drops the custom tables during uninstall.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/database
 */

/**
 * Database table name helpers and teardown.
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
