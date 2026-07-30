<?php
/**
 * Fired during plugin activation
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @todo This should probably be in one class together with Deactivator Class.
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes
 * @author     racmanuel <developer@racmanuel.dev>
 */
class Localpilot_Activator {

	/**
	 * The $_REQUEST during plugin activation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      array    $request    The $_REQUEST array during plugin activation.
	 */
	private static $request = array();

	/**
	 * The $_REQUEST['plugin'] during plugin activation.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin    The $_REQUEST['plugin'] value during plugin activation.
	 */
	private static $plugin = LOCALPILOT_BASE_NAME;

	/**
	 * Activate the plugin.
	 *
	 * Checks if the plugin was (safely) activated.
	 * Place to add any custom action during plugin activation.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		if ( false === self::get_request()
			|| false === self::validate_request( self::$plugin )
			|| false === self::check_caps()
		) {
			if ( isset( $_REQUEST['plugin'] ) ) {
				if ( ! check_admin_referer( 'activate-plugin_' . self::$request['plugin'] ) ) {
					exit;
				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( ! check_admin_referer( 'bulk-plugins' ) ) {
					exit;
				}
			}
		}

		/**
		 * The plugin is now safely activated.
		 * Perform your activation actions here.
		 */

		// Load dependencies needed for activation.
		if ( function_exists( 'lclplt_is_woocommerce_active' ) && lclplt_is_woocommerce_active() ) {
			$plugin_root = dirname( dirname( __FILE__ ) );

			require_once $plugin_root . '/includes/deliveries/class-localpilot-delivery-status.php';
			require_once $plugin_root . '/includes/helpers/class-localpilot-capabilities.php';
			require_once $plugin_root . '/includes/database/class-localpilot-assignment-repository.php';
			require_once $plugin_root . '/includes/database/class-localpilot-event-repository.php';
			require_once $plugin_root . '/includes/deliveries/class-localpilot-driver-role.php';

			self::create_tables();
			Localpilot_Driver_Role::register();

			// Flush rewrite rules so the mis-entregas endpoint works immediately.
			add_rewrite_endpoint( 'mis-entregas', EP_ROOT | EP_PAGES );
			flush_rewrite_rules();
		}

	}

	/**
	 * Get the request.
	 *
	 * Gets the $_REQUEST array and checks if necessary keys are set.
	 * Populates self::request with necessary and sanitized values.
	 *
	 * @since    1.0.0
	 * @return bool|array false or self::$request array.
	 */
	private static function get_request() {

		if ( ! empty( $_REQUEST )
			&& isset( $_REQUEST['_wpnonce'] )
			&& isset( $_REQUEST['action'] )
		) {
			if ( isset( $_REQUEST['plugin'] ) ) {
				if ( false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'activate-plugin_' . sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) ) ) ) {

					self::$request['plugin'] = sanitize_text_field( wp_unslash( $_REQUEST['plugin'] ) );
					self::$request['action'] = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );

					return self::$request;

				}
			} elseif ( isset( $_REQUEST['checked'] ) ) {
				if ( false !== wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'bulk-plugins' ) ) {

					self::$request['action'] = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) );
					self::$request['plugins'] = array_map( 'sanitize_text_field', wp_unslash( $_REQUEST['checked'] ) );

					return self::$request;

				}
			}
		} else {

			return false;
		}

	}

	/**
	 * Validate the Request data.
	 *
	 * Validates the $_REQUESTed data is matching this plugin and action.
	 *
	 * @since    1.0.0
	 * @param string $plugin The Plugin folder/name.php.
	 * @return bool false if either plugin or action does not match, else true.
	 */
	private static function validate_request( $plugin ) {

		if ( isset( self::$request['plugin'] )
			&& $plugin === self::$request['plugin']
			&& 'activate' === self::$request['action']
		) {

			return true;

		} elseif ( isset( self::$request['plugins'] )
			&& 'activate-selected' === self::$request['action']
			&& in_array( $plugin, self::$request['plugins'] )
		) {
			return true;
		}

		return false;

	}

	/**
	 * Check Capabilities.
	 *
	 * We want no one else but users with activate_plugins or above to be able to active this plugin.
	 *
	 * @since    1.0.0
	 * @return bool false if no caps, else true.
	 */
	private static function check_caps() {

		if ( current_user_can( 'activate_plugins' ) ) {
			return true;
		}

		return false;

	}

	/**
	 * Create or update the custom database tables using dbDelta.
	 *
	 * Idempotent — safe to call multiple times.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		$assignments_table = $wpdb->prefix . 'lclplt_assignments';
		$events_table      = $wpdb->prefix . 'lclplt_events';

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

		update_option( 'lclplt_db_version', 1 );
	}

}

