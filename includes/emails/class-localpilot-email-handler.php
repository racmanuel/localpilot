<?php
/**
 * Email handler — registers emails with WooCommerce and responds to delivery events.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email registration and domain event callbacks.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */
class Localpilot_Email_Handler {

	/**
	 * Register email classes with WooCommerce.
	 *
	 * @param array $emails Existing email classes.
	 * @return array
	 */
	public static function register_emails( $emails ) {
		$emails['lclplt_email_assigned']   = new Localpilot_Email_Assigned();
		$emails['lclplt_email_unassigned'] = new Localpilot_Email_Unassigned();
		$emails['lclplt_email_started']    = new Localpilot_Email_Started();
		$emails['lclplt_email_completed']  = new Localpilot_Email_Completed();
		$emails['lclplt_email_failed']     = new Localpilot_Email_Failed();
		return $emails;
	}

	/**
	 * Send "Pedido asignado" email to the driver.
	 */
	public static function delivery_assigned( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		if ( ! $driver || empty( $driver->user_email ) ) {
			return;
		}
		self::trigger_email( 'lclplt_email_assigned', $order_id, array(
			'driver_email' => $driver->user_email,
			'driver_name'  => $driver->display_name,
			'assignment_id' => $assignment_id,
			'event_id'     => $event_id,
		) );
	}

	/**
	 * Send "Asignación retirada" email to the previous driver.
	 */
	public static function delivery_unassigned( $order_id, $old_driver_id, $event_id ) {
		$driver = get_userdata( $old_driver_id );
		if ( ! $driver || empty( $driver->user_email ) ) {
			return;
		}
		self::trigger_email( 'lclplt_email_unassigned', $order_id, array(
			'driver_email' => $driver->user_email,
			'driver_name'  => $driver->display_name,
		) );
	}

	/**
	 * Send emails to both drivers on reassignment.
	 */
	public static function delivery_reassigned( $order_id, $old_driver_id, $new_driver_id, $new_assignment_id, $event_id ) {
		self::delivery_unassigned( $order_id, $old_driver_id, $event_id );

		$new_driver = get_userdata( $new_driver_id );
		if ( $new_driver && ! empty( $new_driver->user_email ) ) {
			self::trigger_email( 'lclplt_email_assigned', $order_id, array(
				'driver_email' => $new_driver->user_email,
				'driver_name'  => $new_driver->display_name,
				'assignment_id' => $new_assignment_id,
				'event_id'     => $event_id,
			) );
		}
	}

	/**
	 * Send "Pedido en reparto" email to the customer.
	 */
	public static function delivery_started( $order_id, $driver_id, $assignment_id, $event_id ) {
		if ( 'yes' !== get_option( 'lclplt_email_started_enabled', 'yes' ) ) {
			return;
		}
		self::trigger_email( 'lclplt_email_started', $order_id, array(
			'driver_id' => $driver_id,
			'assignment_id' => $assignment_id,
			'event_id'  => $event_id,
		) );
	}

	/**
	 * Send "Entrega completada" email to admin.
	 */
	public static function delivery_completed( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		$order  = wc_get_order( $order_id );
		$meta   = $order ? new Localpilot_Order_Delivery_Meta( $order ) : null;

		self::trigger_email( 'lclplt_email_completed', $order_id, array(
			'driver_id'     => $driver_id,
			'driver_name'   => $driver ? $driver->display_name : '',
			'received_by'   => $meta ? $meta->get_received_by() : '',
			'assignment_id' => $assignment_id,
			'event_id'      => $event_id,
		) );
	}

	/**
	 * Send "Entrega fallida" email to admin.
	 */
	public static function delivery_failed( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		$order  = wc_get_order( $order_id );
		$meta   = $order ? new Localpilot_Order_Delivery_Meta( $order ) : null;

		self::trigger_email( 'lclplt_email_failed', $order_id, array(
			'driver_id'     => $driver_id,
			'driver_name'   => $driver ? $driver->display_name : '',
			'failed_reason' => $meta ? $meta->get_failed_reason() : '',
			'assignment_id' => $assignment_id,
			'event_id'      => $event_id,
		) );
	}

	/**
	 * Get a WooCommerce email instance and trigger it.
	 *
	 * @param string $email_id Email ID.
	 * @param int    $order_id Order ID.
	 * @param array  $extra    Extra data.
	 */
	private static function trigger_email( $email_id, $order_id, $extra = array() ) {
		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails[ $email_id ] ) ) {
			$emails[ $email_id ]->trigger( $order_id, $extra );
		}
	}
}
