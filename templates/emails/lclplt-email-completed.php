<?php
/**
 * Email template: Entrega completada (HTML).
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

<p><?php esc_html_e( 'La siguiente entrega ha sido completada:', 'localpilot' ); ?></p>

<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #ddd;">
	<tbody>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Pedido', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;">#<?php echo esc_html( $order->get_order_number() ); ?></td>
		</tr>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Cliente', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;"><?php echo esc_html( $order->get_formatted_billing_full_name() ); ?></td>
		</tr>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Repartidor', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;"><?php echo esc_html( $extra['driver_name'] ?? '' ); ?></td>
		</tr>
		<?php if ( ! empty( $extra['received_by'] ) ) : ?>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Recibido por', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;"><?php echo esc_html( $extra['received_by'] ); ?></td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>

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
