<?php
/**
 * Email: Asignación retirada al repartidor anterior.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

class Localpilot_Email_Unassigned extends Localpilot_Email {

	public function __construct() {
		parent::__construct(
			'lclplt_email_unassigned',
			array(
				'title'       => __( 'LocalPilot — Asignación retirada', 'localpilot' ),
				'description' => __( 'Se envía al repartidor cuando se le retira o reasigna un pedido.', 'localpilot' ),
				'subject'     => __( '[{site_title}] Pedido #{order_number} — asignación retirada', 'localpilot' ),
				'heading'     => __( 'Asignación retirada', 'localpilot' ),
				'template'    => 'lclplt-email-unassigned',
			)
		);
	}

	public function trigger( $order_id, $extra = array() ) {
		$driver_email = ! empty( $extra['driver_email'] ) ? $extra['driver_email'] : '';
		if ( ! $driver_email ) {
			return;
		}
		$this->recipient = $driver_email;
		parent::trigger( $order_id, $extra );
	}
}
