<?php
/**
 * Orders list columns and filters for HPOS and legacy screens.
 *
 * Adds Repartidor, Entrega and Ubicación columns plus filters
 * by driver and delivery status to the WooCommerce orders list.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * Orders list columns and filters.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */
class Localpilot_Orders_List {

	/**
	 * Add LocalPilot columns to the orders list.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;
			if ( 'order_status' === $key ) {
				$new_columns['lclplt_driver']  = __( 'Repartidor', 'localpilot' );
				$new_columns['lclplt_status']  = __( 'Entrega', 'localpilot' );
				$new_columns['lclplt_location'] = __( 'Ubicación', 'localpilot' );
			}
		}
		return $new_columns;
	}

	/**
	 * Render columns content (HPOS screen).
	 *
	 * @param string   $column Column ID.
	 * @param WC_Order $order  Order object.
	 */
	public function render_columns_hpos( $column, $order ) {
		$this->render_column( $column, $order->get_id(), $order );
	}

	/**
	 * Render columns content (legacy screen).
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id Post/order ID.
	 */
	public function render_columns_legacy( $column, $post_id ) {
		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}
		$this->render_column( $column, $post_id, $order );
	}

	/**
	 * Render a single column value.
	 *
	 * @param string   $column  Column ID.
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	private function render_column( $column, $order_id, $order ) {
		switch ( $column ) {
			case 'lclplt_driver':
				$this->render_driver_column( $order );
				break;
			case 'lclplt_status':
				$this->render_status_column( $order );
				break;
			case 'lclplt_location':
				$this->render_location_column( $order );
				break;
		}
	}

	/**
	 * Render the Repartidor column.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function render_driver_column( $order ) {
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$driver_id = $meta->get_driver_id();

		if ( ! $driver_id ) {
			echo '<span class="na">—</span>';
			return;
		}

		$driver = get_userdata( $driver_id );
		if ( $driver ) {
			$edit_url = get_edit_user_link( $driver_id );
			echo '<a href="' . esc_url( $edit_url ) . '">' . esc_html( $driver->display_name ) . '</a>';
		} else {
			echo '<em>' . esc_html__( 'Usuario eliminado', 'localpilot' ) . '</em>';
		}
	}

	/**
	 * Render the Entrega column with a status badge.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function render_status_column( $order ) {
		$meta   = new Localpilot_Order_Delivery_Meta( $order );
		$status = $meta->get_delivery_status();

		if ( empty( $status ) || 'unassigned' === $status ) {
			echo '<span class="na">—</span>';
			return;
		}

		$label = Localpilot_Delivery_Status::label( $status );
		$class = Localpilot_Delivery_Status::status_class( $status );
		echo '<mark class="lclplt-badge lclplt-badge--' . esc_attr( $class ) . '">' . esc_html( $label ) . '</mark>';
	}

	/**
	 * Render the Ubicación column.
	 *
	 * @param WC_Order $order Order object.
	 */
	private function render_location_column( $order ) {
		$meta  = new Localpilot_Order_Delivery_Meta( $order );
		$addr  = $meta->get_geocoded_address();
		$lat   = $meta->get_latitude();
		$lng   = $meta->get_longitude();
		$gstatus = $meta->get_geocoding_status();

		if ( $addr ) {
			echo esc_html( mb_substr( $addr, 0, 60 ) );
		} elseif ( 'pending' === $gstatus ) {
			echo '<span style="color:#999;">' . esc_html__( 'Pendiente', 'localpilot' ) . '</span>';
		} elseif ( 'failed' === $gstatus ) {
			echo '<span style="color:#c00;">' . esc_html__( 'Error geocod.', 'localpilot' ) . '</span>';
		} elseif ( $lat && $lng ) {
			echo esc_html( $lat ) . ', ' . esc_html( $lng );
		} else {
			echo '<span class="na">—</span>';
		}
	}

	/*
	 * ------------------------------------------------------------------
	 * Filters — HPOS
	 * ------------------------------------------------------------------
	 */

	/**
	 * Render filter dropdowns on the HPOS orders list.
	 *
	 * @param string $order_type Order type.
	 * @param string $which      Top or bottom.
	 */
	public function render_filters_hpos( $order_type, $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$this->render_filter_driver();
		$this->render_filter_delivery_status();
	}

	/**
	 * Apply filters on the HPOS orders list.
	 *
	 * @param array $query_args Query arguments for wc_get_orders().
	 * @return array
	 */
	public function apply_filters_hpos( $query_args ) {
		$driver_id = $this->get_filter_driver_id();
		$dlv_status = $this->get_filter_delivery_status();

		if ( ! $driver_id && ! $dlv_status ) {
			return $query_args;
		}

		$order_ids = $this->get_order_ids_by_filters( $driver_id, $dlv_status );

		if ( empty( $order_ids ) ) {
			// Force zero results.
			$query_args['id'] = array( 0 );
			return $query_args;
		}

		$query_args['id'] = $order_ids;
		return $query_args;
	}

	/*
	 * ------------------------------------------------------------------
	 * Filters — Legacy (posts)
	 * ------------------------------------------------------------------
	 */

	/**
	 * Render filter dropdowns on the legacy orders list.
	 */
	public function render_filters_legacy() {
		global $typenow;
		if ( 'shop_order' !== $typenow ) {
			return;
		}
		$this->render_filter_driver();
		$this->render_filter_delivery_status();
	}

	/**
	 * Apply filters on the legacy (posts) orders list.
	 *
	 * @param array $query_vars WP_Query vars.
	 * @return array
	 */
	public function apply_filters_legacy( $query_vars ) {
		global $typenow;
		if ( 'shop_order' !== $typenow ) {
			return $query_vars;
		}

		$driver_id = $this->get_filter_driver_id();
		$dlv_status = $this->get_filter_delivery_status();

		if ( ! $driver_id && ! $dlv_status ) {
			return $query_vars;
		}

		$order_ids = $this->get_order_ids_by_filters( $driver_id, $dlv_status );

		if ( empty( $order_ids ) ) {
			$query_vars['post__in'] = array( 0 );
			return $query_vars;
		}

		$query_vars['post__in'] = $order_ids;
		return $query_vars;
	}

	/*
	 * ------------------------------------------------------------------
	 * Shared filter logic
	 * ------------------------------------------------------------------
	 */

	/**
	 * Render the driver filter dropdown.
	 */
	private function render_filter_driver() {
		if ( ! current_user_can( Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			return;
		}

		$drivers = Localpilot_Driver_Repository::get_active();
		$current = $this->get_filter_driver_id();
		?>
		<select name="lclplt_driver" id="lclplt-filter-by-driver">
			<option value=""><?php esc_html_e( 'Todos los repartidores', 'localpilot' ); ?></option>
			<?php foreach ( $drivers as $driver ) : ?>
				<option value="<?php echo esc_attr( $driver->ID ); ?>" <?php selected( (int) $current, $driver->ID ); ?>>
					<?php echo esc_html( $driver->display_name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Render the delivery status filter dropdown.
	 */
	private function render_filter_delivery_status() {
		if ( ! current_user_can( Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			return;
		}

		$statuses = array(
			''                 => __( 'Todos los estados', 'localpilot' ),
			'assigned'         => Localpilot_Delivery_Status::label( 'assigned' ),
			'accepted'         => Localpilot_Delivery_Status::label( 'accepted' ),
			'out_for_delivery' => Localpilot_Delivery_Status::label( 'out_for_delivery' ),
			'delivered'        => Localpilot_Delivery_Status::label( 'delivered' ),
			'failed'           => Localpilot_Delivery_Status::label( 'failed' ),
			'cancelled'        => Localpilot_Delivery_Status::label( 'cancelled' ),
		);

		$current = $this->get_filter_delivery_status();
		?>
		<select name="lclplt_delivery_status" id="lclplt-filter-by-delivery-status">
			<?php foreach ( $statuses as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	/**
	 * Get the selected driver ID from the filter request.
	 *
	 * @return int|null
	 */
	private function get_filter_driver_id() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['lclplt_driver'] ) && '' !== $_GET['lclplt_driver'] ) {
			return absint( $_GET['lclplt_driver'] );
		}
		return null;
	}

	/**
	 * Get the selected delivery status from the filter request.
	 *
	 * @return string|null
	 */
	private function get_filter_delivery_status() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['lclplt_delivery_status'] ) && '' !== $_GET['lclplt_delivery_status'] ) {
			return sanitize_key( $_GET['lclplt_delivery_status'] );
		}
		return null;
	}

	/**
	 * Get order IDs matching driver and/or delivery status filters.
	 *
	 * Queries the indexed lclplt_assignments table, not order tables.
	 *
	 * @param int|null    $driver_id  Driver user ID.
	 * @param string|null $dlv_status Delivery status.
	 * @return array Array of order IDs.
	 */
	private function get_order_ids_by_filters( $driver_id, $dlv_status ) {
		global $wpdb;

		$table  = Localpilot_DB_Schema::assignments_table();
		$where  = array( '1=1' );
		$params = array();

		if ( $driver_id ) {
			$where[]  = 'driver_id = %d';
			$params[] = (int) $driver_id;
		}

		if ( $dlv_status ) {
			if ( 'unassigned' === $dlv_status ) {
				// Orders with no active assignment at all.
				$subquery = $wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'SELECT DISTINCT order_id FROM ' . $table
				);
				$where[] = 'order_id NOT IN (' . $subquery . ')';
			} else {
				// Use a subquery to match the latest status per order.
				$where[] = 'a1.status = %s';
				$params[] = $dlv_status;
			}
		}

		$where_sql = implode( ' AND ', $where );

		if ( $dlv_status && 'unassigned' !== $dlv_status ) {
			// Subquery pattern: get orders whose latest assignment matches the status.
			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT a1.order_id FROM ' . $table . ' a1
				INNER JOIN (
					SELECT order_id, MAX(id) AS max_id
					FROM ' . $table . '
					GROUP BY order_id
				) a2 ON a1.id = a2.max_id
				WHERE ' . $where_sql,
				$params
			);
		} else {
			// Simple filter by driver (or unassigned via NOT IN).
			$sql = $wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'SELECT DISTINCT order_id FROM ' . $table . ' WHERE ' . $where_sql,
				$params
			);
		}

		$results = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( empty( $results ) ) {
			return array( 0 );
		}

		return array_map( 'absint', $results );
	}
}
