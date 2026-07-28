<?php
/**
 * Driver role and capability manager.
 *
 * Registers the localpilot_driver role with appropriate capabilities
 * in an idempotent way. Safe to call multiple times.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Driver role and capability manager.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Driver_Role {

	/**
	 * Role name.
	 *
	 * @var string
	 */
	const ROLE = 'localpilot_driver';

	/**
	 * Display name for the role.
	 *
	 * @var string
	 */
	const DISPLAY_NAME = 'LocalPilot Repartidor';

	/**
	 * Register the driver role and assign capabilities.
	 *
	 * Idempotent — safe to call on every activation and admin_init.
	 */
	public static function register() {
		$role = get_role( self::ROLE );

		if ( null === $role ) {
			$role = add_role(
				self::ROLE,
				self::DISPLAY_NAME,
				array(
					'read' => true,
				)
			);
		}

		if ( null === $role ) {
			return;
		}

		// Assign driver-level capabilities.
		foreach ( Localpilot_Capabilities::driver_caps() as $cap ) {
			if ( ! $role->has_cap( $cap ) ) {
				$role->add_cap( $cap );
			}
		}

		// Ensure administrator has manage_deliveries.
		$admin_role = get_role( 'administrator' );
		if ( null !== $admin_role && ! $admin_role->has_cap( Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			$admin_role->add_cap( Localpilot_Capabilities::MANAGE_DELIVERIES );
		}

		// Ensure shop_manager has manage_deliveries.
		$manager_role = get_role( 'shop_manager' );
		if ( null !== $manager_role && ! $manager_role->has_cap( Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			$manager_role->add_cap( Localpilot_Capabilities::MANAGE_DELIVERIES );
		}
	}

	/**
	 * Remove driver capabilities from the role.
	 *
	 * Does NOT delete the role itself — only strips custom caps.
	 * Called during uninstall if opt-in.
	 */
	public static function unregister() {
		$role = get_role( self::ROLE );
		if ( null === $role ) {
			return;
		}

		foreach ( Localpilot_Capabilities::all() as $cap ) {
			$role->remove_cap( $cap );
		}

		// Also remove from administrator and shop_manager.
		$admin_role = get_role( 'administrator' );
		if ( null !== $admin_role ) {
			$admin_role->remove_cap( Localpilot_Capabilities::MANAGE_DELIVERIES );
		}

		$manager_role = get_role( 'shop_manager' );
		if ( null !== $manager_role ) {
			$manager_role->remove_cap( Localpilot_Capabilities::MANAGE_DELIVERIES );
		}
	}

	/**
	 * Check if a user has the driver role.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function is_driver( $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		return in_array( self::ROLE, (array) $user->roles, true );
	}
}
