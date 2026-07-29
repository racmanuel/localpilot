<?php
/**
 * My Account — Mis entregas: list view.
 *
 * @see Localpilot_My_Account::render_list()
 *
 * @see Localpilot_My_Account::render_list()
 *
 * @var array $deliveries
 * @var int   $total
 * @var int   $total_pages
 * @var int   $current_page
 * @var int   $per_page
 * @var string $filter
 * @var array $filter_counts
 */

defined( 'ABSPATH' ) || exit;

$filter_links = array(
	'pendientes' => __( 'Pendientes', 'localpilot' ),
	'en_reparto' => __( 'En reparto', 'localpilot' ),
	'entregadas' => __( 'Entregadas', 'localpilot' ),
	'fallidas'   => __( 'Fallidas', 'localpilot' ),
	'todas'      => __( 'Todas', 'localpilot' ),
);

$filter_counts = wp_parse_args(
	(array) $filter_counts,
	array_fill_keys( array_keys( $filter_links ), 0 )
);

$summary_cards = array(
	'pendientes' => array(
		'label' => __( 'Pendientes', 'localpilot' ),
		'icon'  => 'dashicons-clipboard',
	),
	'en_reparto' => array(
		'label' => __( 'En reparto', 'localpilot' ),
		'icon'  => 'dashicons-location-alt',
	),
	'entregadas' => array(
		'label' => __( 'Entregadas', 'localpilot' ),
		'icon'  => 'dashicons-yes-alt',
	),
	'fallidas' => array(
		'label' => __( 'Fallidas', 'localpilot' ),
		'icon'  => 'dashicons-warning',
	),
);

$get_detail_url = static function( $assignment_id ) {
	return add_query_arg(
		'mis-entregas',
		absint( $assignment_id ),
		wc_get_account_endpoint_url( 'mis-entregas' )
	);
};

$get_action_label = static function( $status ) {
	$labels = array(
		'assigned'         => __( 'Aceptar', 'localpilot' ),
		'accepted'         => __( 'Iniciar reparto', 'localpilot' ),
		'out_for_delivery' => __( 'Completar', 'localpilot' ),
	);

	return isset( $labels[ $status ] ) ? $labels[ $status ] : __( 'Ver entrega', 'localpilot' );
};

$get_status_icon = static function( $status ) {
	$icons = array(
		'assigned'         => 'dashicons-clipboard',
		'accepted'         => 'dashicons-yes-alt',
		'out_for_delivery' => 'dashicons-location-alt',
		'delivered'        => 'dashicons-saved',
		'failed'           => 'dashicons-warning',
		'cancelled'        => 'dashicons-no-alt',
	);

	return isset( $icons[ $status ] ) ? $icons[ $status ] : 'dashicons-info-outline';
};

$get_action_icon = static function( $status ) {
	$icons = array(
		'assigned'         => 'dashicons-yes-alt',
		'accepted'         => 'dashicons-location',
		'out_for_delivery' => 'dashicons-yes-alt',
	);

	return isset( $icons[ $status ] ) ? $icons[ $status ] : 'dashicons-visibility';
};
?>

<div class="lclplt-deliveries-page">
	<header class="lclplt-deliveries-header">
		<div>
			<h2><?php esc_html_e( 'Mis entregas', 'localpilot' ); ?></h2>
			<p><?php esc_html_e( 'Consulta tus asignaciones y actualiza cada entrega desde un solo lugar.', 'localpilot' ); ?></p>
		</div>
		<span class="lclplt-deliveries-total">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %s: number of deliveries */
					_n( '%s entrega', '%s entregas', (int) $filter_counts['todas'], 'localpilot' ),
					number_format_i18n( (int) $filter_counts['todas'] )
				)
			);
			?>
		</span>
	</header>

	<section class="lclplt-deliveries-summary" aria-label="<?php esc_attr_e( 'Resumen de entregas', 'localpilot' ); ?>">
		<?php foreach ( $summary_cards as $key => $card ) : ?>
			<a class="lclplt-summary-stat lclplt-summary-stat--<?php echo esc_attr( $key ); ?>" href="<?php echo esc_url( add_query_arg( 'filter', $key, wc_get_account_endpoint_url( 'mis-entregas' ) ) ); ?>">
				<span class="dashicons <?php echo esc_attr( $card['icon'] ); ?>" aria-hidden="true"></span>
				<span class="lclplt-summary-stat__content">
					<strong><?php echo esc_html( number_format_i18n( (int) $filter_counts[ $key ] ) ); ?></strong>
					<span><?php echo esc_html( $card['label'] ); ?></span>
				</span>
			</a>
		<?php endforeach; ?>
	</section>

	<nav class="lclplt-filter-nav" aria-label="<?php esc_attr_e( 'Filtrar entregas', 'localpilot' ); ?>">
		<div class="lclplt-filter-nav__scroll">
			<?php foreach ( $filter_links as $key => $label ) : ?>
				<a class="lclplt-filter-link <?php echo $filter === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'filter', $key, wc_get_account_endpoint_url( 'mis-entregas' ) ) ); ?>"<?php echo $filter === $key ? ' aria-current="page"' : ''; ?>>
					<span><?php echo esc_html( $label ); ?></span>
					<span class="lclplt-filter-link__count"><?php echo esc_html( number_format_i18n( (int) $filter_counts[ $key ] ) ); ?></span>
				</a>
			<?php endforeach; ?>
		</div>
	</nav>

	<?php if ( empty( $deliveries ) ) : ?>
		<div class="lclplt-empty-state" role="status">
			<span class="dashicons <?php echo 'pendientes' === $filter ? 'dashicons-yes-alt' : 'dashicons-clipboard'; ?>" aria-hidden="true"></span>
			<div>
				<h3>
					<?php
					if ( 'pendientes' === $filter ) {
						esc_html_e( 'No tienes entregas pendientes', 'localpilot' );
					} else {
						echo esc_html( sprintf( /* translators: %s: current filter label */ __( 'No hay entregas %s', 'localpilot' ), strtolower( $filter_links[ $filter ] ?? $filter ) ) );
					}
					?>
				</h3>
				<p>
					<?php
					if ( 'pendientes' === $filter ) {
						esc_html_e( 'Cuando se te asigne una nueva entrega, aparecerá aquí.', 'localpilot' );
					} else {
						esc_html_e( 'Prueba otro filtro para consultar tus entregas.', 'localpilot' );
					}
					?>
				</p>
			</div>
		</div>
	<?php else : ?>
		<div class="lclplt-delivery-list-wrapper">
			<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive lclplt-delivery-list">
				<thead>
					<tr>
						<th class="woocommerce-orders-table__header lclplt-delivery-list__order"><?php esc_html_e( 'Entrega', 'localpilot' ); ?></th>
						<th class="woocommerce-orders-table__header lclplt-delivery-list__destination"><?php esc_html_e( 'Destino', 'localpilot' ); ?></th>
						<th class="woocommerce-orders-table__header lclplt-delivery-list__date"><?php esc_html_e( 'Fecha', 'localpilot' ); ?></th>
						<th class="woocommerce-orders-table__header lclplt-delivery-list__status"><?php esc_html_e( 'Estado', 'localpilot' ); ?></th>
						<th class="woocommerce-orders-table__header lclplt-delivery-list__action"><span class="screen-reader-text"><?php esc_html_e( 'Acción', 'localpilot' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $deliveries as $assignment ) : ?>
						<?php
						$order = wc_get_order( $assignment->order_id );
						if ( ! $order ) {
							continue;
						}

						$status_label = Localpilot_Delivery_Status::label( $assignment->status );
						$status_class = Localpilot_Delivery_Status::status_class( $assignment->status );
						$status_icon  = $get_status_icon( $assignment->status );
						$action_icon  = $get_action_icon( $assignment->status );
						$address_parts = array_filter(
							array(
								$order->get_shipping_address_1() ?: $order->get_billing_address_1(),
								$order->get_shipping_address_2() ?: $order->get_billing_address_2(),
								$order->get_shipping_city() ?: $order->get_billing_city(),
								$order->get_shipping_state() ?: $order->get_billing_state(),
							)
						);
						$address_parts = array_unique( $address_parts );
						$address      = implode( ', ', $address_parts );
						$recipient    = $order->get_shipping_first_name() ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();
						$detail_url   = $get_detail_url( $assignment->id );
						$action_label = $get_action_label( $assignment->status );
						$action_class = Localpilot_Delivery_Status::is_active( $assignment->status ) ? 'button-primary' : '';
						?>
						<tr class="woocommerce-orders-table__row lclplt-delivery-row lclplt-delivery-row--<?php echo esc_attr( $status_class ); ?>">
							<td class="woocommerce-orders-table__cell lclplt-delivery-list__order" data-title="<?php esc_attr_e( 'Entrega', 'localpilot' ); ?>">
								<a class="lclplt-delivery-number" href="<?php echo esc_url( $detail_url ); ?>">#<?php echo esc_html( $order->get_order_number() ); ?></a>
								<?php if ( $recipient ) : ?><span class="lclplt-delivery-recipient"><?php echo esc_html( $recipient ); ?></span><?php endif; ?>
							</td>
							<td class="woocommerce-orders-table__cell lclplt-delivery-list__destination" data-title="<?php esc_attr_e( 'Destino', 'localpilot' ); ?>">
								<?php if ( $address ) : ?>
									<span><?php echo esc_html( $address ); ?></span>
								<?php else : ?>
									<span class="lclplt-missing-data"><?php esc_html_e( 'Dirección no disponible', 'localpilot' ); ?></span>
								<?php endif; ?>
							</td>
							<td class="woocommerce-orders-table__cell lclplt-delivery-list__date" data-title="<?php esc_attr_e( 'Fecha', 'localpilot' ); ?>">
								<time datetime="<?php echo esc_attr( $order->get_date_created() ? $order->get_date_created()->date( 'c' ) : '' ); ?>"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></time>
							</td>
							<td class="woocommerce-orders-table__cell lclplt-delivery-list__status" data-title="<?php esc_attr_e( 'Estado', 'localpilot' ); ?>">
								<mark class="lclplt-badge lclplt-badge--<?php echo esc_attr( $status_class ); ?>"><span class="dashicons <?php echo esc_attr( $status_icon ); ?> lclplt-badge-icon" aria-hidden="true"></span><?php echo esc_html( $status_label ); ?></mark>
							</td>
							<td class="woocommerce-orders-table__cell lclplt-delivery-list__action" data-title="<?php esc_attr_e( 'Acción', 'localpilot' ); ?>">
								<a href="<?php echo esc_url( $detail_url ); ?>" class="woocommerce-button button <?php echo esc_attr( $action_class ); ?>"><span class="dashicons <?php echo esc_attr( $action_icon ); ?> lclplt-action-icon" aria-hidden="true"></span><?php echo esc_html( $action_label ); ?></a>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
			<nav class="woocommerce-pagination lclplt-delivery-pagination" aria-label="<?php esc_attr_e( 'Paginación de entregas', 'localpilot' ); ?>">
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
</div>
