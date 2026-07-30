<?php
/**
 * Email: Entrega fallida al administrador.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

class Localpilot_Email_Failed extends Localpilot_Email {

	public function __construct() {
		parent::__construct(
			'lclplt_email_failed',
			array(
				'title'       => __( 'LocalPilot — Entrega fallida', 'localpilot' ),
				'description' => __( 'Se envía al administrador cuando una entrega se marca como fallida.', 'localpilot' ),
				'subject'     => __( '[{site_title}] Entrega #{order_number} fallida', 'localpilot' ),
				'heading'     => __( 'Entrega fallida', 'localpilot' ),
				'template'    => 'lclplt-email-failed',
				'recipient'   => '',
			)
		);
	}

	/**
	 * Domain event handler — hooked to lclplt_delivery_failed.
	 */
	public static function on_delivery_failed( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		$order  = wc_get_order( $order_id );
		$meta   = $order ? new Localpilot_Order_Delivery_Meta( $order ) : null;

		self::trigger_static( 'lclplt_email_failed', $order_id, array(
			'driver_id'     => $driver_id,
			'driver_name'   => $driver ? $driver->display_name : '',
			'failed_reason' => $meta ? $meta->get_failed_reason() : '',
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
