<?php
/**
 * Email template: Asignación retirada (HTML).
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

<p><?php printf( esc_html__( 'Hola %s,', 'localpilot' ), esc_html( $extra['driver_name'] ?? __( 'repartidor', 'localpilot' ) ) ); ?></p>

<p><?php esc_html_e( 'La asignación del siguiente pedido ha sido retirada o reasignada a otro repartidor.', 'localpilot' ); ?></p>

<p><strong>#<?php echo esc_html( $order->get_order_number() ); ?></strong> — <?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></p>

<p><?php esc_html_e( 'Ya no tienes acceso a esta entrega en tu panel de Mi cuenta.', 'localpilot' ); ?></p>

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
