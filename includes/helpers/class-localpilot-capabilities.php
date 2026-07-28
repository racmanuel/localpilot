<?php
/**
 * Capability helpers.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/helpers
 */

/**
 * Capability helpers.
 *
 * Centralises capability names and provides reusable checks.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/helpers
 */
class Localpilot_Capabilities {

	/*
	 * ------------------------------------------------------------------
	 * Capability constants (matching 13-contratos-compartidos.md)
	 * ------------------------------------------------------------------
	 */

	const VIEW_ASSIGNED_DELIVERIES = 'lclplt_view_assigned_deliveries';
	const ACCEPT_DELIVERY          = 'lclplt_accept_delivery';
	const START_DELIVERY           = 'lclplt_start_delivery';
	const COMPLETE_DELIVERY        = 'lclplt_complete_delivery';
	const FAIL_DELIVERY            = 'lclplt_fail_delivery';
	const UPLOAD_DELIVERY_PROOF    = 'lclplt_upload_delivery_proof';
	const MANAGE_DELIVERIES        = 'lclplt_manage_deliveries';

	/**
	 * All custom capabilities defined by LocalPilot.
	 *
	 * @return array
	 */
	public static function all() {
		return array(
			self::VIEW_ASSIGNED_DELIVERIES,
			self::ACCEPT_DELIVERY,
			self::START_DELIVERY,
			self::COMPLETE_DELIVERY,
			self::FAIL_DELIVERY,
			self::UPLOAD_DELIVERY_PROOF,
			self::MANAGE_DELIVERIES,
		);
	}

	/**
	 * Driver-level capabilities (assigned to localpilot_driver role).
	 *
	 * @return array
	 */
	public static function driver_caps() {
		return array(
			self::VIEW_ASSIGNED_DELIVERIES,
			self::ACCEPT_DELIVERY,
			self::START_DELIVERY,
			self::COMPLETE_DELIVERY,
			self::FAIL_DELIVERY,
			self::UPLOAD_DELIVERY_PROOF,
		);
	}

	/**
	 * Manager-level capabilities.
	 *
	 * @return array
	 */
	public static function manager_caps() {
		return array(
			self::MANAGE_DELIVERIES,
		);
	}

	/**
	 * Check if the current user can manage deliveries.
	 *
	 * @return bool
	 */
	public static function current_user_can_manage() {
		return current_user_can( self::MANAGE_DELIVERIES );
	}

	/**
	 * Check if a given user can manage deliveries.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public static function user_can_manage( $user_id ) {
		return user_can( $user_id, self::MANAGE_DELIVERIES );
	}
}
