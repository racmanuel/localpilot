<?php
/**
 * Email template: Entrega completada (plain text).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

echo esc_html__( 'La siguiente entrega ha sido completada:', 'localpilot' ) . "\n\n";
echo esc_html__( 'Pedido:', 'localpilot' ) . ' #' . esc_html( $order->get_order_number() ) . "\n";
echo esc_html__( 'Cliente:', 'localpilot' ) . ' ' . esc_html( $order->get_formatted_billing_full_name() ) . "\n";
if ( ! empty( $extra['received_by'] ) ) {
	echo esc_html__( 'Recibido por:', 'localpilot' ) . ' ' . esc_html( $extra['received_by'] ) . "\n";
}
echo "\n\n";

/**
 * Show user-defined additional content.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) . "\n";
