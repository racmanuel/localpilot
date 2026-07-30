<?php
/**
 * Email template: Pedido asignado (plain text).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

echo sprintf( esc_html__( 'Hola %s,', 'localpilot' ), esc_html( $extra['driver_name'] ?? __( 'repartidor', 'localpilot' ) ) ) . "\n\n";
echo esc_html__( 'Se te ha asignado un nuevo pedido para entrega.', 'localpilot' ) . "\n\n";
echo esc_html__( 'Pedido:', 'localpilot' ) . ' #' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html__( 'Cliente:', 'localpilot' ) . ' ' . esc_html( $order->get_formatted_billing_full_name() ) . "\n";

$addr = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
$city = $order->get_shipping_city() ?: $order->get_billing_city();
echo esc_html__( 'Dirección:', 'localpilot' ) . ' ' . esc_html( $addr . ', ' . $city ) . "\n";

if ( $order->get_billing_phone() ) {
	echo esc_html__( 'Teléfono:', 'localpilot' ) . ' ' . esc_html( $order->get_billing_phone() ) . "\n";
}

echo "\n" . esc_html__( 'Puedes ver los detalles en tu panel de Mi cuenta.', 'localpilot' ) . "\n\n";

/**
 * Show user-defined additional content.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) . "\n";
