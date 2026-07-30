<?php
/**
 * Email template: Entrega fallida (HTML).
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

<p><?php esc_html_e( 'La siguiente entrega ha sido marcada como fallida:', 'localpilot' ); ?></p>

<p>
	<strong><?php esc_html_e( 'Pedido', 'localpilot' ); ?>:</strong>
	#<?php echo esc_html( $order->get_order_number() ); ?><br />
	<strong><?php esc_html_e( 'Cliente', 'localpilot' ); ?>:</strong>
	<?php echo esc_html( $order->get_formatted_billing_full_name() ); ?>
	<?php if ( ! empty( $extra['failed_reason'] ) ) : ?>
	<br /><strong><?php esc_html_e( 'Motivo', 'localpilot' ); ?>:</strong>
	<?php echo esc_html( $extra['failed_reason'] ); ?>
	<?php endif; ?>
	<?php if ( ! empty( $extra['driver_name'] ) ) : ?>
	<br /><strong><?php esc_html_e( 'Repartidor', 'localpilot' ); ?>:</strong>
	<?php echo esc_html( $extra['driver_name'] ); ?>
	<?php endif; ?>
</p>

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
