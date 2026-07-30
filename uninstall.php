<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * We are not using an uninstall hook because WordPress perfoms bad when using it.
 * Even if below issue is "fixed", it did not resolve the perfomance issue.
 *
 * @see https://core.trac.wordpress.org/ticket/31792
 *
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - Check if the $_REQUEST['plugin'] content actually is localpilot/localpilot.php
 * - Check if the $_REQUEST['action'] content actually is delete-plugin
 * - Run a check_ajax_referer check to make sure it goes through authentication
 * - Run a current_user_can check to make sure current user can delete a plugin
 *
 * @todo Consider multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 * @package    Localpilot
 */

/**
 * Perform Uninstall Actions.
 *
 * If uninstall not called from WordPress,
 * If no uninstall action,
 * If not this plugin,
 * If no caps,
 * then exit.
 *
 * @since 1.0.0
 */
function lclplt_uninstall() {

	if ( ! defined( 'WP_UNINSTALL_PLUGIN' )
		|| empty( $_REQUEST )
		|| ! isset( $_REQUEST['plugin'] )
		|| ! isset( $_REQUEST['action'] )
		|| 'localpilot/localpilot.php' !== $_REQUEST['plugin']
		|| 'delete-plugin' !== $_REQUEST['action']
		|| ! check_ajax_referer( 'updates', '_ajax_nonce' )
		|| ! current_user_can( 'activate_plugins' )
	) {

		exit;

	}

	/**
	 * It is now safe to perform your uninstall actions here.
	 *
	 * By default, LocalPilot preserves data. Only delete if the
	 * lclplt_delete_data_on_uninstall option is explicitly set to 'yes'.
	 *
	 * @see https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/#method-2-uninstall-php
	 */

	$delete_data = get_option( 'lclplt_delete_data_on_uninstall', 'no' );

	if ( 'yes' !== $delete_data ) {
		return;
	}

	// Remove driver capabilities from role (preserves the role itself).
	if ( class_exists( 'Localpilot_Driver_Role' ) ) {
		Localpilot_Driver_Role::unregister();
	}

	// Drop custom tables.
	global $wpdb;
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lclplt_assignments' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'lclplt_events' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	delete_option( 'lclplt_db_version' );

	// Remove plugin options.
	$options = array(
		'lclplt_db_version',
		'lclplt_eligible_order_statuses',
		'lclplt_require_acceptance',
		'lclplt_show_order_total',
		'lclplt_require_received_by',
		'lclplt_require_proof',
		'lclplt_location_validation_enabled',
		'lclplt_location_validation_radius',
		'lclplt_location_validation_mode',
		'lclplt_max_proof_size',
		'lclplt_completed_order_status',
		'lclplt_failed_order_status',
		'lclplt_enable_mapbox',
		'lclplt_mapbox_token',
		'lclplt_mapbox_style',
		'lclplt_mapbox_zoom',
		'lclplt_mapbox_country',
		'lclplt_mapbox_language',
		'lclplt_auto_geocode',
		'lclplt_enable_routes',
		'lclplt_route_default_profile',
		'lclplt_route_profile_selector',
		'lclplt_email_started_enabled',
		'lclplt_delete_data_on_uninstall',
	);

	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Clean user meta for drivers.
	delete_metadata( 'user', 0, '_lclplt_driver_phone', '', true );
	delete_metadata( 'user', 0, '_lclplt_driver_active', '', true );
	delete_metadata( 'user', 0, '_lclplt_driver_vehicle_type', '', true );
	delete_metadata( 'user', 0, '_lclplt_driver_vehicle_plate', '', true );
	delete_metadata( 'user', 0, '_lclplt_driver_capacity', '', true );
	delete_metadata( 'user', 0, '_lclplt_driver_notes', '', true );

	// Clean order meta (HPOS compatible).
	$lclplt_meta_keys = array(
		'_lclplt_driver_id',
		'_lclplt_delivery_status',
		'_lclplt_assignment_id',
		'_lclplt_assigned_at',
		'_lclplt_accepted_at',
		'_lclplt_out_for_delivery_at',
		'_lclplt_delivered_at',
		'_lclplt_failed_at',
		'_lclplt_failed_reason',
		'_lclplt_received_by',
		'_lclplt_proof_attachment_id',
		'_lclplt_delivery_notes',
		'_lclplt_delivery_latitude',
		'_lclplt_delivery_longitude',
		'_lclplt_mapbox_place_id',
		'_lclplt_geocoded_address',
		'_lclplt_geocoded_at',
		'_lclplt_geocoding_status',
		'_lclplt_location_validation_status',
		'_lclplt_location_validation_distance',
		'_lclplt_location_validation_radius',
		'_lclplt_location_validation_accuracy',
		'_lclplt_location_validation_at',
		'_lclplt_location_validation_target_lat',
		'_lclplt_location_validation_target_lng',
		'_lclplt_delivery_location_lat',
		'_lclplt_delivery_location_lng',
	);

	if ( function_exists( 'wc_get_container' ) ) {
		// HPOS: use the orders table meta.
		foreach ( $lclplt_meta_keys as $meta_key ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}wc_orders_meta WHERE meta_key = %s", $meta_key ) );
		}
	} else {
		// Legacy: use post meta.
		foreach ( $lclplt_meta_keys as $meta_key ) {
			delete_metadata( 'post', 0, $meta_key, '', true );
		}
	}

}

lclplt_uninstall();
