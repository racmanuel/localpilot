<?php
/**
 * My Account — Mis entregas: list view.
 *
 * @see Localpilot_My_Account::render_list()
 *
 * @var array  $deliveries   Array of assignment row objects.
 * @var int    $total        Total matching assignments.
 * @var int    $total_pages  Total pages.
 * @var int    $current_page Current page.
 * @var int    $per_page     Items per page.
 * @var string $filter       Current filter key.
 * @var string $nonce_field  Nonce field HTML.
 * @var int    $driver_id    Current user ID.
 */

defined( 'ABSPATH' ) || exit;
?>

<?php
// Filter tabs.
$filter_links = array(
	'pendientes' => __( 'Pendientes', 'localpilot' ),
	'en_reparto' => __( 'En reparto', 'localpilot' ),
	'entregadas' => __( 'Entregadas', 'localpilot' ),
	'fallidas'   => __( 'Fallidas', 'localpilot' ),
	'todas'      => __( 'Todas', 'localpilot' ),
);
?>

<nav class="woocommerce-MyAccount-navigation lclplt-filter-nav">
	<ul>
		<?php foreach ( $filter_links as $key => $label ) : ?>
			<li class="<?php echo $filter === $key ? 'is-active' : ''; ?>">
				<a href="<?php echo esc_url( add_query_arg( 'filter', $key, wc_get_account_endpoint_url( 'mis-entregas' ) ) ); ?>">
					<?php echo esc_html( $label ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>

<?php if ( empty( $deliveries ) ) : ?>
	<div class="woocommerce-Message woocommerce-Message--info woocommerce-info">
		<?php if ( 'pendientes' === $filter ) : ?>
			<?php esc_html_e( 'No tienes entregas pendientes.', 'localpilot' ); ?>
		<?php else : ?>
			<?php esc_html_e( 'No se encontraron entregas para este filtro.', 'localpilot' ); ?>
		<?php endif; ?>
	</div>
<?php else : ?>
	<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive">
		<thead>
			<tr>
				<th class="woocommerce-orders-table__header"><?php esc_html_e( 'Pedido', 'localpilot' ); ?></th>
				<th class="woocommerce-orders-table__header"><?php esc_html_e( 'Fecha', 'localpilot' ); ?></th>
				<th class="woocommerce-orders-table__header"><?php esc_html_e( 'Dirección', 'localpilot' ); ?></th>
				<th class="woocommerce-orders-table__header"><?php esc_html_e( 'Estado', 'localpilot' ); ?></th>
				<th class="woocommerce-orders-table__header"><?php esc_html_e( 'Acción', 'localpilot' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $deliveries as $assignment ) :
				$order = wc_get_order( $assignment->order_id );
				if ( ! $order ) {
					continue;
				}
				$status_label = Localpilot_Delivery_Status::label( $assignment->status );
				$status_class = Localpilot_Delivery_Status::status_class( $assignment->status );
				$address      = $order->get_shipping_address_1()
					? $order->get_shipping_address_1() . ', ' . $order->get_shipping_city()
					: $order->get_billing_address_1() . ', ' . $order->get_billing_city();
				$detail_url   = esc_url( add_query_arg( 'mis-entregas', $assignment->id, wc_get_account_endpoint_url( 'mis-entregas' ) ) );
			?>
			<tr class="woocommerce-orders-table__row">
				<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Pedido', 'localpilot' ); ?>">
					<a href="<?php echo esc_url( $detail_url ); ?>">
						#<?php echo esc_html( $order->get_order_number() ); ?>
					</a>
				</td>
				<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Fecha', 'localpilot' ); ?>">
					<?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?>
				</td>
				<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Dirección', 'localpilot' ); ?>">
					<?php echo esc_html( $address ); ?>
				</td>
				<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Estado', 'localpilot' ); ?>">
					<mark class="lclplt-badge lclplt-badge--<?php echo esc_attr( $status_class ); ?>">
						<?php echo esc_html( $status_label ); ?>
					</mark>
				</td>
				<td class="woocommerce-orders-table__cell" data-title="<?php esc_attr_e( 'Acción', 'localpilot' ); ?>">
					<a href="<?php echo esc_url( $detail_url ); ?>" class="woocommerce-button button">
						<?php esc_html_e( 'Ver', 'localpilot' ); ?>
					</a>
				</td>
			</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( $total_pages > 1 ) : ?>
		<nav class="woocommerce-pagination">
			<?php
			echo paginate_links( array(
				'base'      => add_query_arg( 'pag', '%#%' ),
				'format'    => '',
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'total'     => $total_pages,
				'current'   => $current_page,
				'add_args'  => array( 'filter' => $filter ),
				'type'      => 'list',
			) );
			?>
		</nav>
	<?php endif; ?>
<?php endif; ?>
