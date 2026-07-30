<?php
/**
 * Email template: Asignación retirada (plain text).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

echo sprintf( esc_html__( 'Hola %s,', 'localpilot' ), esc_html( $extra['driver_name'] ?? __( 'repartidor', 'localpilot' ) ) ) . "\n\n";
echo esc_html__( 'La asignación del siguiente pedido ha sido retirada o reasignada a otro repartidor.', 'localpilot' ) . "\n\n";
echo '#' . esc_html( $order->get_order_number() ) . ' — ' . esc_html( wc_format_datetime( $order->get_date_created() ) ) . "\n\n";
echo esc_html__( 'Ya no tienes acceso a esta entrega en tu panel de Mi cuenta.', 'localpilot' ) . "\n\n";

/**
 * Show user-defined additional content.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) . "\n";
