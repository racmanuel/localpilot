<?php
/**
 * Email registry — registers emails with WooCommerce and hooks into domain events.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */

defined( 'ABSPATH' ) || exit;

/**
 * Email registry.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */
class Localpilot_Email_Registry {

	/**
	 * Email class mapping.
	 *
	 * @var array
	 */
	const EMAILS = array(
		'lclplt_email_assigned'   => 'Localpilot_Email_Assigned',
		'lclplt_email_unassigned' => 'Localpilot_Email_Unassigned',
		'lclplt_email_started'    => 'Localpilot_Email_Started',
		'lclplt_email_completed'  => 'Localpilot_Email_Completed',
		'lclplt_email_failed'     => 'Localpilot_Email_Failed',
	);

	/**
	 * Hook into WooCommerce and domain events.
	 */
	public static function init() {
		add_filter( 'woocommerce_email_classes', array( __CLASS__, 'register_emails' ) );

		// Subscribe to domain events — handlers load WC_Emails on demand.
		add_action( 'lclplt_delivery_assigned', array( __CLASS__, 'delivery_assigned' ), 20, 4 );
		add_action( 'lclplt_delivery_unassigned', array( __CLASS__, 'delivery_unassigned' ), 20, 3 );
		add_action( 'lclplt_delivery_reassigned', array( __CLASS__, 'delivery_reassigned' ), 20, 5 );
		add_action( 'lclplt_delivery_started', array( __CLASS__, 'delivery_started' ), 20, 4 );
		add_action( 'lclplt_delivery_completed', array( __CLASS__, 'delivery_completed' ), 20, 4 );
		add_action( 'lclplt_delivery_failed', array( __CLASS__, 'delivery_failed' ), 20, 4 );
	}

	/**
	 * Lazy-load the email class file for a given ID.
	 *
	 * @param string $id Email ID.
	 * @return bool
	 */
	private static function load_email_class( $id ) {
		if ( ! isset( self::EMAILS[ $id ] ) || class_exists( self::EMAILS[ $id ], false ) ) {
			return class_exists( self::EMAILS[ $id ], false );
		}

		// Ensure WC_Email base is available.
		if ( ! class_exists( 'WC_Email', false ) ) {
			$wc_email_file = WP_PLUGIN_DIR . '/woocommerce/includes/emails/class-wc-email.php';
			if ( file_exists( $wc_email_file ) ) {
				require_once $wc_email_file;
			} else {
				return false;
			}
		}

		// Load base email class.
		$base_file = dirname( dirname( __FILE__ ) ) . '/emails/class-localpilot-email.php';
		if ( file_exists( $base_file ) ) {
			require_once $base_file;
		}

		// Derive filename from class name: Localpilot_Email_Assigned -> localpilot-email-assigned
		$class   = self::EMAILS[ $id ];
		$parts   = explode( '_', $class );
		$parts   = array_map( 'strtolower', $parts );
		$slug    = implode( '-', $parts );
		$file    = dirname( dirname( __FILE__ ) ) . '/emails/class-' . $slug . '.php';
		if ( file_exists( $file ) ) {
			require_once $file;
		}

		return class_exists( $class, false );
	}

	/**
	 * Register email classes with WooCommerce.
	 *
	 * @param array $emails Existing email classes.
	 * @return array
	 */
	public static function register_emails( $emails ) {
		foreach ( self::EMAILS as $id => $class ) {
			self::load_email_class( $id );
			if ( class_exists( $class, false ) ) {
				$emails[ $id ] = new $class();
			}
		}
		return $emails;
	}

	/**
	 * Send "Pedido asignado" email to the driver.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $driver_id     Driver user ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $event_id      Event ID.
	 */
	public static function delivery_assigned( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		if ( ! $driver || empty( $driver->user_email ) ) {
			return;
		}

		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails['lclplt_email_assigned'] ) ) {
			$emails['lclplt_email_assigned']->trigger( $order_id, array(
				'driver_email' => $driver->user_email,
				'driver_name'  => $driver->display_name,
				'assignment_id' => $assignment_id,
				'event_id'     => $event_id,
			) );
		}
	}

	/**
	 * Send "Asignación retirada" email to the previous driver.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $old_driver_id Previous driver ID.
	 * @param int $event_id      Event ID.
	 */
	public static function delivery_unassigned( $order_id, $old_driver_id, $event_id ) {
		$driver = get_userdata( $old_driver_id );
		if ( ! $driver || empty( $driver->user_email ) ) {
			return;
		}

		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails['lclplt_email_unassigned'] ) ) {
			$emails['lclplt_email_unassigned']->trigger( $order_id, array(
				'driver_email' => $driver->user_email,
				'driver_name'  => $driver->display_name,
			) );
		}
	}

	/**
	 * Send "Pedido asignado" email to the old driver when reassigned.
	 *
	 * @param int $order_id         Order ID.
	 * @param int $old_driver_id    Previous driver ID.
	 * @param int $new_driver_id    New driver ID.
	 * @param int $new_assignment_id New assignment ID.
	 * @param int $event_id         Event ID.
	 */
	public static function delivery_reassigned( $order_id, $old_driver_id, $new_driver_id, $new_assignment_id, $event_id ) {
		// Notify the old driver.
		$old_driver = get_userdata( $old_driver_id );
		if ( $old_driver && ! empty( $old_driver->user_email ) ) {
			$emails = WC_Emails::instance()->get_emails();
			if ( isset( $emails['lclplt_email_unassigned'] ) ) {
				$emails['lclplt_email_unassigned']->trigger( $order_id, array(
					'driver_email' => $old_driver->user_email,
					'driver_name'  => $old_driver->display_name,
				) );
			}
		}

		// Notify the new driver.
		$new_driver = get_userdata( $new_driver_id );
		if ( $new_driver && ! empty( $new_driver->user_email ) ) {
			$emails = WC_Emails::instance()->get_emails();
			if ( isset( $emails['lclplt_email_assigned'] ) ) {
				$emails['lclplt_email_assigned']->trigger( $order_id, array(
					'driver_email' => $new_driver->user_email,
					'driver_name'  => $new_driver->display_name,
					'assignment_id' => $new_assignment_id,
					'event_id'     => $event_id,
				) );
			}
		}
	}

	/**
	 * Send "Pedido en reparto" email to the customer.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $driver_id     Driver ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $event_id      Event ID.
	 */
	public static function delivery_started( $order_id, $driver_id, $assignment_id, $event_id ) {
		if ( 'yes' !== get_option( 'lclplt_email_started_enabled', 'yes' ) ) {
			return;
		}

		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails['lclplt_email_started'] ) ) {
			$emails['lclplt_email_started']->trigger( $order_id, array(
				'driver_id' => $driver_id,
				'assignment_id' => $assignment_id,
				'event_id'  => $event_id,
			) );
		}
	}

	/**
	 * Send "Entrega completada" email to admin.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $driver_id     Driver ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $event_id      Event ID.
	 */
	public static function delivery_completed( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver    = get_userdata( $driver_id );
		$order     = wc_get_order( $order_id );
		$meta      = $order ? new Localpilot_Order_Delivery_Meta( $order ) : null;

		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails['lclplt_email_completed'] ) ) {
			$emails['lclplt_email_completed']->trigger( $order_id, array(
				'driver_id'     => $driver_id,
				'driver_name'   => $driver ? $driver->display_name : '',
				'received_by'   => $meta ? $meta->get_received_by() : '',
				'assignment_id' => $assignment_id,
				'event_id'      => $event_id,
			) );
		}
	}

	/**
	 * Send "Entrega fallida" email to admin.
	 *
	 * @param int $order_id      Order ID.
	 * @param int $driver_id     Driver ID.
	 * @param int $assignment_id Assignment ID.
	 * @param int $event_id      Event ID.
	 */
	public static function delivery_failed( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver    = get_userdata( $driver_id );
		$order     = wc_get_order( $order_id );
		$meta      = $order ? new Localpilot_Order_Delivery_Meta( $order ) : null;

		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails['lclplt_email_failed'] ) ) {
			$emails['lclplt_email_failed']->trigger( $order_id, array(
				'driver_id'     => $driver_id,
				'driver_name'   => $driver ? $driver->display_name : '',
				'failed_reason' => $meta ? $meta->get_failed_reason() : '',
				'assignment_id' => $assignment_id,
				'event_id'      => $event_id,
			) );
		}
	}
}
