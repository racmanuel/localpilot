<?php
/**
 * Email template: Entrega fallida (HTML).
 *
 * @var WC_Email $email
 * @var WC_Order $order
 * @var array    $extra
 */

defined( 'ABSPATH' ) || exit;
?>

<p><?php esc_html_e( 'La siguiente entrega ha sido marcada como fallida:', 'localpilot' ); ?></p>

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
		<?php if ( ! empty( $extra['failed_reason'] ) ) : ?>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Motivo', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;"><?php echo esc_html( $extra['failed_reason'] ); ?></td>
		</tr>
		<?php endif; ?>
		<?php if ( ! empty( $extra['driver_name'] ) ) : ?>
		<tr>
			<th style="text-align:left;border:1px solid #ddd;"><?php esc_html_e( 'Repartidor', 'localpilot' ); ?></th>
			<td style="text-align:left;border:1px solid #ddd;"><?php echo esc_html( $extra['driver_name'] ); ?></td>
		</tr>
		<?php endif; ?>
	</tbody>
</table>
