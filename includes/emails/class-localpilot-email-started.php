<?php
/**
 * Email: Pedido en reparto al cliente.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

class Localpilot_Email_Started extends Localpilot_Email {

	public function __construct() {
		parent::__construct(
			'lclplt_email_started',
			array(
				'title'       => __( 'LocalPilot — Pedido en reparto', 'localpilot' ),
				'description' => __( 'Se envía al cliente cuando el repartidor inicia el reparto.', 'localpilot' ),
				'subject'     => __( '[{site_title}] Tu pedido #{order_number} está en camino', 'localpilot' ),
				'heading'     => __( 'Tu pedido está en camino', 'localpilot' ),
				'template'    => 'lclplt-email-started',
			)
		);
		$this->customer_email = true;
	}

	public function trigger( $order_id, $extra = array() ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}
		$this->recipient = $order->get_billing_email();
		parent::trigger( $order_id, $extra );
	}

	public function is_enabled() {
		$enabled = get_option( 'lclplt_email_started_enabled', 'yes' );
		return 'yes' === $enabled && parent::is_enabled();
	}
}
