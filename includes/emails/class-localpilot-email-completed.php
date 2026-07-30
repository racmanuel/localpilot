<?php
/**
 * Email: Entrega completada al administrador.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

class Localpilot_Email_Completed extends Localpilot_Email {

	public function __construct() {
		parent::__construct(
			'lclplt_email_completed',
			array(
				'title'       => __( 'LocalPilot — Entrega completada', 'localpilot' ),
				'description' => __( 'Se envía al administrador cuando una entrega se completa.', 'localpilot' ),
				'subject'     => __( '[{site_title}] Entrega #{order_number} completada', 'localpilot' ),
				'heading'     => __( 'Entrega completada', 'localpilot' ),
				'template'    => 'lclplt-email-completed',
				'recipient'   => '',
			)
		);
	}

	/**
	 * Domain event handler — hooked to lclplt_delivery_completed.
	 */
	public static function on_delivery_completed( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		$order  = wc_get_order( $order_id );
		$meta   = $order ? new Localpilot_Order_Delivery_Meta( $order ) : null;

		self::trigger_static( 'lclplt_email_completed', $order_id, array(
			'driver_id'     => $driver_id,
			'driver_name'   => $driver ? $driver->display_name : '',
			'received_by'   => $meta ? $meta->get_received_by() : '',
			'assignment_id' => $assignment_id,
			'event_id'      => $event_id,
		) );
	}

	public function trigger( $order_id, $extra = array() ) {
		$recipient = $this->get_recipient();
		if ( ! $recipient ) {
			$this->recipient = get_option( 'admin_email' );
		}
		parent::trigger( $order_id, $extra );
	}
}
