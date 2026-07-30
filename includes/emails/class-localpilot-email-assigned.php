<?php
/**
 * Email: Pedido asignado al repartidor.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

class Localpilot_Email_Assigned extends Localpilot_Email {

	public function __construct() {
		parent::__construct(
			'lclplt_email_assigned',
			array(
				'title'       => __( 'LocalPilot — Pedido asignado', 'localpilot' ),
				'description' => __( 'Se envía al repartidor cuando se le asigna un pedido.', 'localpilot' ),
				'subject'     => __( '[{site_title}] Pedido #{order_number} asignado — {order_date}', 'localpilot' ),
				'heading'     => __( 'Nuevo pedido asignado', 'localpilot' ),
				'template'    => 'lclplt-email-assigned',
			)
		);
	}

	/**
	 * Domain event handler — hooked to lclplt_delivery_assigned.
	 */
	public static function on_delivery_assigned( $order_id, $driver_id, $assignment_id, $event_id ) {
		$driver = get_userdata( $driver_id );
		if ( ! $driver || empty( $driver->user_email ) ) {
			return;
		}
		self::trigger_static( 'lclplt_email_assigned', $order_id, array(
			'driver_email'  => $driver->user_email,
			'driver_name'   => $driver->display_name,
			'assignment_id' => $assignment_id,
			'event_id'      => $event_id,
		) );
	}

	public function trigger( $order_id, $extra = array() ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$driver_email = '';
		if ( ! empty( $extra['driver_email'] ) ) {
			$driver_email = $extra['driver_email'];
		} else {
			$assignment = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
			if ( $assignment && $assignment->driver_id ) {
				$driver = get_userdata( $assignment->driver_id );
				if ( $driver ) {
					$driver_email = $driver->user_email;
				}
			}
		}

		if ( ! $driver_email ) {
			return;
		}

		$this->recipient = $driver_email;
		parent::trigger( $order_id, $extra );
	}
}
