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

	public function trigger( $order_id, $extra = array() ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Get driver email from extra or assignment.
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
