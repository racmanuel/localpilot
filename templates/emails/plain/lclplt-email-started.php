<?php
/**
 * Email template: Pedido en reparto (plain text).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;

echo "= " . esc_html( $email_heading ) . " =\n\n";

echo esc_html__( 'Hola,', 'localpilot' ) . "\n\n";
echo esc_html__( 'Tu pedido está en camino y pronto será entregado.', 'localpilot' ) . "\n\n";
echo esc_html__( 'Pedido:', 'localpilot' ) . ' #' . esc_html( $order->get_order_number() ) . "\n\n";
echo esc_html__( 'Gracias por tu paciencia.', 'localpilot' ) . "\n\n";

/**
 * Show user-defined additional content.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
}

echo esc_html( wp_strip_all_tags( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) ) . "\n";
