<?php
/**
 * Email template: Pedido en reparto (HTML).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;

/*
 * @hooked WC_Emails::email_header() Output the email header.
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php esc_html_e( 'Hola,', 'localpilot' ); ?></p>

<p><?php esc_html_e( 'Tu pedido está en camino y pronto será entregado.', 'localpilot' ); ?></p>

<p>
	<strong><?php esc_html_e( 'Pedido', 'localpilot' ); ?>:</strong>
	#<?php echo esc_html( $order->get_order_number() ); ?>
</p>

<p><?php esc_html_e( 'Gracias por tu paciencia.', 'localpilot' ); ?></p>

<?php
/**
 * Show user-defined additional content.
 */
if ( $additional_content ) {
	echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer.
 */
do_action( 'woocommerce_email_footer', $email ); ?>
