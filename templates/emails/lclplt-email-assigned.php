<?php
/**
 * Email template: Pedido asignado (HTML).
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

<p><?php esc_html_e( 'Se te ha asignado un nuevo pedido para entrega.', 'localpilot' ); ?></p>

<p>
	<strong><?php esc_html_e( 'Pedido', 'localpilot' ); ?>:</strong>
	#<?php echo esc_html( $order->get_order_number() ); ?><br />
	<strong><?php esc_html_e( 'Cliente', 'localpilot' ); ?>:</strong>
	<?php echo esc_html( $order->get_formatted_billing_full_name() ); ?><br />
	<strong><?php esc_html_e( 'Dirección', 'localpilot' ); ?>:</strong>
	<?php
	$addr = $order->get_shipping_address_1() ?: $order->get_billing_address_1();
	$city = $order->get_shipping_city() ?: $order->get_billing_city();
	echo esc_html( $addr . ', ' . $city );
	?><br />
	<?php if ( $order->get_billing_phone() ) : ?>
	<strong><?php esc_html_e( 'Teléfono', 'localpilot' ); ?>:</strong>
	<?php echo esc_html( $order->get_billing_phone() ); ?>
	<?php endif; ?>
</p>

<p><?php
	printf(
		wp_kses_post( __( 'Puedes ver los detalles de esta entrega en tu <a href="%s">panel de Mi cuenta</a>.', 'localpilot' ) ),
		esc_url( wc_get_account_endpoint_url( 'mis-entregas' ) )
	);
?></p>

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
