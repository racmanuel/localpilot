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

		if ( ! class_exists( 'WC_Email' ) ) {
			return $emails;
		}

		// Load email base class lazily.
		$base_file = dirname( dirname( __FILE__ ) ) . '/emails/class-localpilot-email.php';
		if ( file_exists( $base_file ) ) {
			require_once $base_file;
		}

		if ( ! class_exists( 'Localpilot_Email', false ) ) {
			return $emails;
		}

		$emails['lclplt_email_assigned']   = new Localpilot_Email( 'lclplt_email_assigned', array(
			'title'          => __( 'LocalPilot — Pedido asignado', 'localpilot' ),
			'description'    => __( 'Se envía al repartidor cuando se le asigna un pedido.', 'localpilot' ),
			'subject'        => __( '[{site_title}] Pedido #{order_number} asignado — {order_date}', 'localpilot' ),
			'heading'        => __( 'Nuevo pedido asignado', 'localpilot' ),
			'template'       => 'lclplt-email-assigned',
			'recipient_type' => 'driver',
		) );

		$emails['lclplt_email_unassigned'] = new Localpilot_Email( 'lclplt_email_unassigned', array(
			'title'          => __( 'LocalPilot — Asignación retirada', 'localpilot' ),
			'description'    => __( 'Se envía al repartidor cuando se le retira o reasigna un pedido.', 'localpilot' ),
			'subject'        => __( '[{site_title}] Pedido #{order_number} — asignación retirada', 'localpilot' ),
			'heading'        => __( 'Asignación retirada', 'localpilot' ),
			'template'       => 'lclplt-email-unassigned',
			'recipient_type' => 'driver',
		) );

		$emails['lclplt_email_started']    = new Localpilot_Email( 'lclplt_email_started', array(
			'title'          => __( 'LocalPilot — Pedido en reparto', 'localpilot' ),
			'description'    => __( 'Se envía al cliente cuando el repartidor inicia el reparto.', 'localpilot' ),
			'subject'        => __( '[{site_title}] Tu pedido #{order_number} está en camino', 'localpilot' ),
			'heading'        => __( 'Tu pedido está en camino', 'localpilot' ),
			'template'       => 'lclplt-email-started',
			'recipient_type' => 'customer',
			'customer_email' => true,
			'option_enabled' => 'lclplt_email_started_enabled',
		) );

		$emails['lclplt_email_completed']  = new Localpilot_Email( 'lclplt_email_completed', array(
			'title'          => __( 'LocalPilot — Entrega completada', 'localpilot' ),
			'description'    => __( 'Se envía al administrador cuando una entrega se completa.', 'localpilot' ),
			'subject'        => __( '[{site_title}] Entrega #{order_number} completada', 'localpilot' ),
			'heading'        => __( 'Entrega completada', 'localpilot' ),
			'template'       => 'lclplt-email-completed',
			'recipient_type' => 'admin',
		) );

		$emails['lclplt_email_failed']     = new Localpilot_Email( 'lclplt_email_failed', array(
			'title'          => __( 'LocalPilot — Entrega fallida', 'localpilot' ),
			'description'    => __( 'Se envía al administrador cuando una entrega se marca como fallida.', 'localpilot' ),
			'subject'        => __( '[{site_title}] Entrega #{order_number} fallida', 'localpilot' ),
			'heading'        => __( 'Entrega fallida', 'localpilot' ),
			'template'       => 'lclplt-email-failed',
			'recipient_type' => 'admin',
		) );

		return $emails;
	}
}
