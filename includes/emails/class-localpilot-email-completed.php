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

	public function trigger( $order_id, $extra = array() ) {
		$recipient = $this->get_recipient();
		if ( ! $recipient ) {
			$this->recipient = get_option( 'admin_email' );
		}
		parent::trigger( $order_id, $extra );
	}
}
