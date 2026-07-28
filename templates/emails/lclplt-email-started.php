<?php
/**
 * Email template: Pedido en reparto (HTML).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;
?>

<p><?php esc_html_e( 'Hola,', 'localpilot' ); ?></p>

<p><?php esc_html_e( 'Tu pedido está en camino y pronto será entregado.', 'localpilot' ); ?></p>

<h2><?php esc_html_e( 'Detalles del pedido', 'localpilot' ); ?></h2>

<table cellspacing="0" cellpadding="6" style="width:100%;border:1px solid #ddd;">
	<tbody>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Pedido', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;">#<?php echo esc_html( $order->get_order_number() ); ?></td>
		</tr>
	</tbody>
</table>

<p><?php esc_html_e( 'Gracias por tu paciencia.', 'localpilot' ); ?></p>
