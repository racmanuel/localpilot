<?php
/**
 * WooCommerce email registrations for LocalPilot.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers custom email classes with WooCommerce.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */
class Localpilot_Emails {

	/**
	 * Register custom WooCommerce email classes.
	 *
	 * @param array $emails WooCommerce email classes.
	 * @return array
	 */
	public static function register_email_classes( $emails ) {
		if ( ! is_array( $emails ) ) {
			$emails = array();
		}

		if ( ! class_exists( 'Localpilot_Email_Assigned' ) ) {
			return $emails;
		}

		$emails['lclplt_email_assigned']   = new Localpilot_Email_Assigned();
		$emails['lclplt_email_unassigned'] = new Localpilot_Email_Unassigned();
		$emails['lclplt_email_started']    = new Localpilot_Email_Started();
		$emails['lclplt_email_completed']  = new Localpilot_Email_Completed();
		$emails['lclplt_email_failed']     = new Localpilot_Email_Failed();

		return $emails;
	}
}
