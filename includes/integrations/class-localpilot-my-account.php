<?php
/**
 * My Account — Mis entregas endpoint.
 *
 * Registers the mis-entregas endpoint, adds a menu item in My Account,
 * renders a paginated list of deliveries for the current driver, and
 * processes accept/start/complete/fail transitions.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */

/**
 * My Account — Mis entregas endpoint.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */
class Localpilot_My_Account {

	/**
	 * Endpoint slug.
	 *
	 * @var string
	 */
	const ENDPOINT = 'mis-entregas';

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'lclplt_delivery_action';

	/**
	 * Register endpoint, menu, and content callback.
	 */
	public static function register() {
		// Register rewrite endpoint.
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );

		// Add menu item.
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'add_menu_item' ), 40 );

		// Render endpoint content.
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( __CLASS__, 'render' ) );
	}

	/**
	 * Add or remove the menu item based on capabilities.
	 *
	 * @param array $items Menu items.
	 * @return array
	 */
	public static function add_menu_item( $items ) {
		if ( ! current_user_can( Localpilot_Capabilities::VIEW_ASSIGNED_DELIVERIES ) ) {
			unset( $items[ self::ENDPOINT ] );
			return $items;
		}

		// Insert after 'orders' if it exists, otherwise add at end.
		$new_items = array();
		foreach ( $items as $slug => $label ) {
			$new_items[ $slug ] = $label;
			if ( 'orders' === $slug ) {
				$new_items[ self::ENDPOINT ] = __( 'Mis entregas', 'localpilot' );
			}
		}
		if ( ! isset( $new_items[ self::ENDPOINT ] ) ) {
			$new_items[ self::ENDPOINT ] = __( 'Mis entregas', 'localpilot' );
		}

		return $new_items;
	}

	/**
	 * Render the endpoint content.
	 */
	public static function render() {
		if ( ! is_user_logged_in() ) {
			echo '<p>' . esc_html__( 'Debes iniciar sesión para ver tus entregas.', 'localpilot' ) . '</p>';
			return;
		}

		$driver_id   = get_current_user_id();
		$query_var   = get_query_var( self::ENDPOINT, '' );
		$nonce_field = wp_nonce_field( self::NONCE_ACTION, 'lclplt_delivery_nonce', true, false );

		// Process POST actions before rendering.
		self::handle_post( $driver_id );

		if ( is_numeric( $query_var ) && (int) $query_var > 0 ) {
			self::render_detail( $driver_id, (int) $query_var, $nonce_field );
		} else {
			self::render_list( $driver_id, $nonce_field );
		}
	}

	/**
	 * Render the paginated list of deliveries.
	 *
	 * @param int    $driver_id   Current user ID.
	 * @param string $nonce_field Nonce field HTML.
	 */
	private static function render_list( $driver_id, $nonce_field ) {
		$filter  = isset( $_GET['filter'] ) ? sanitize_key( $_GET['filter'] ) : 'pendientes'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$paged   = isset( $_GET['pag'] ) ? max( 1, (int) $_GET['pag'] ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$per_page = apply_filters( 'lclplt_deliveries_per_page', 20 );

		$statuses = self::get_statuses_for_filter( $filter );
		$result   = Localpilot_Delivery_Query::get_for_driver( $driver_id, array(
			'statuses' => $statuses,
			'page'     => $paged,
			'per_page' => $per_page,
		) );

		wc_get_template(
			'my-account/deliveries/list.php',
			array(
				'deliveries'  => $result['items'],
				'total'       => $result['total'],
				'total_pages' => $result['total_pages'],
				'current_page' => $result['page'],
				'per_page'    => $per_page,
				'filter'      => $filter,
				'nonce_field' => $nonce_field,
				'driver_id'   => $driver_id,
			),
			'',
			plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/'
		);
	}

	/**
	 * Render the detail view for a single delivery.
	 *
	 * @param int    $driver_id   Current user ID.
	 * @param int    $assignment_id Assignment ID.
	 * @param string $nonce_field  Nonce field HTML.
	 */
	private static function render_detail( $driver_id, $assignment_id, $nonce_field ) {
		$assignment = Localpilot_Assignment_Repository::get( $assignment_id );

		if ( ! $assignment || (int) $assignment->driver_id !== $driver_id ) {
			wc_add_notice( __( 'No tienes acceso a esta entrega.', 'localpilot' ), 'error' );
			wc_get_template(
				'my-account/deliveries/list.php',
				array( 'deliveries' => array(), 'total' => 0, 'total_pages' => 0, 'current_page' => 1, 'per_page' => 20, 'filter' => 'pendientes', 'nonce_field' => $nonce_field, 'driver_id' => $driver_id ),
				'',
				plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/'
			);
			return;
		}

		$order      = wc_get_order( $assignment->order_id );
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Pedido no encontrado.', 'localpilot' ) . '</p>';
			return;
		}

		$meta        = new Localpilot_Order_Delivery_Meta( $order );
		$status      = $assignment->status;
		$transitions = Localpilot_Delivery_Status::allowed_transitions( $status );
		$events      = Localpilot_Event_Repository::get_by_assignment( $assignment_id, 10 );
		$show_total  = 'yes' === get_option( 'lclplt_show_order_total', 'no' );

		$can_accept  = isset( $transitions['accepted'] ) && 'driver' === $transitions['accepted'];
		$can_start   = isset( $transitions['out_for_delivery'] ) && 'driver' === $transitions['out_for_delivery']
			&& ( $status !== 'assigned' || 'yes' !== get_option( 'lclplt_require_acceptance', 'yes' ) );
		$can_complete = isset( $transitions['delivered'] ) && 'driver' === $transitions['delivered'];
		$can_fail    = isset( $transitions['failed'] ) && 'driver' === $transitions['failed'];

		wc_get_template(
			'my-account/deliveries/detail.php',
			array(
				'assignment'   => $assignment,
				'order'        => $order,
				'meta'         => $meta,
				'status'       => $status,
				'events'       => $events,
				'show_total'   => $show_total,
				'can_accept'   => $can_accept,
				'can_start'    => $can_start,
				'can_complete' => $can_complete,
				'can_fail'     => $can_fail,
				'nonce_field'  => $nonce_field,
				'driver_id'    => $driver_id,
			),
			'',
			plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/'
		);
	}

	/**
	 * Process POST delivery actions.
	 *
	 * @param int $driver_id Current user ID.
	 */
	private static function handle_post( $driver_id ) {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}
		if ( ! isset( $_POST['lclplt_delivery_action'] ) || ! isset( $_POST['lclplt_delivery_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( $_POST['lclplt_delivery_nonce'] ), self::NONCE_ACTION ) ) {
			wc_add_notice( __( 'Error de seguridad. Intenta de nuevo.', 'localpilot' ), 'error' );
			return;
		}

		if ( ! user_can( $driver_id, Localpilot_Capabilities::VIEW_ASSIGNED_DELIVERIES ) ) {
			wc_add_notice( __( 'No tienes permiso para realizar esta acción.', 'localpilot' ), 'error' );
			return;
		}

		$action = sanitize_key( $_POST['lclplt_delivery_action'] );
		$allowed_actions = array( 'accept', 'start', 'complete', 'fail' );
		if ( ! in_array( $action, $allowed_actions, true ) ) {
			wc_add_notice( __( 'Acción no válida.', 'localpilot' ), 'error' );
			return;
		}

		$assignment_id = isset( $_POST['lclplt_assignment_id'] ) ? absint( $_POST['lclplt_assignment_id'] ) : 0;
		$assignment    = Localpilot_Assignment_Repository::get( $assignment_id );
		if ( ! $assignment || (int) $assignment->driver_id !== $driver_id ) {
			wc_add_notice( __( 'No tienes acceso a esta entrega.', 'localpilot' ), 'error' );
			return;
		}

		$order_id = (int) $assignment->order_id;
		$extra    = array();

		switch ( $action ) {
			case 'accept':
				$target = 'accepted';
				break;
			case 'start':
				$target = 'out_for_delivery';
				break;
			case 'complete':
				$target = 'delivered';
				$extra['received_by']   = isset( $_POST['lclplt_received_by'] ) ? sanitize_text_field( wp_unslash( $_POST['lclplt_received_by'] ) ) : '';
				$extra['delivery_notes'] = isset( $_POST['lclplt_delivery_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lclplt_delivery_notes'] ) ) : '';
				break;
			case 'fail':
				$target = 'failed';
				$extra['failed_reason']  = isset( $_POST['lclplt_failed_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['lclplt_failed_reason'] ) ) : '';
				$extra['delivery_notes'] = isset( $_POST['lclplt_delivery_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['lclplt_delivery_notes'] ) ) : '';
				break;
			default:
				return;
		}

		// Validate one browser location at the moment of completion. This is
		// intentionally before proof upload so a rejected request cannot leave
		// an orphan attachment behind.
		if ( 'complete' === $action ) {
			$location_validation = Localpilot_Location_Validation_Service::validate(
				$order_id,
				isset( $_POST['lclplt_location_latitude'] ) ? wp_unslash( $_POST['lclplt_location_latitude'] ) : '',
				isset( $_POST['lclplt_location_longitude'] ) ? wp_unslash( $_POST['lclplt_location_longitude'] ) : '',
				isset( $_POST['lclplt_location_accuracy'] ) ? wp_unslash( $_POST['lclplt_location_accuracy'] ) : '',
				isset( $_POST['lclplt_location_timestamp'] ) ? wp_unslash( $_POST['lclplt_location_timestamp'] ) : '',
				isset( $_POST['lclplt_location_status'] ) ? wp_unslash( $_POST['lclplt_location_status'] ) : ''
			);

			$extra['location_validation'] = $location_validation;
			$extra['location_driver_lat'] = isset( $_POST['lclplt_location_latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['lclplt_location_latitude'] ) ) : '';
			$extra['location_driver_lng'] = isset( $_POST['lclplt_location_longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['lclplt_location_longitude'] ) ) : '';
			if ( ! $location_validation['allowed'] ) {
				wc_add_notice( Localpilot_Location_Validation_Service::get_error_message( $location_validation['status'] ), 'error' );
				return;
			}
		}

		// Process proof upload for complete/fail actions.
		if ( in_array( $action, array( 'complete', 'fail' ), true ) ) {
			$require_proof = 'yes' === get_option( 'lclplt_require_proof', 'yes' );
			$has_file      = ! empty( $_FILES['lclplt_proof'] ) && ! empty( $_FILES['lclplt_proof']['name'] );

			if ( $has_file ) {
				$attachment_id = Localpilot_Proof_Service::handle_upload( $order_id, 'lclplt_proof', $driver_id );
				if ( is_wp_error( $attachment_id ) ) {
					wc_add_notice( $attachment_id->get_error_message(), 'error' );
					return;
				}
				$extra['proof_attachment_id'] = $attachment_id;
			} elseif ( $require_proof && 'complete' === $action ) {
				wc_add_notice( __( 'La evidencia (foto) es obligatoria para completar la entrega.', 'localpilot' ), 'error' );
				return;
			}
		}

		$result = Localpilot_Delivery_Transition_Service::transition( $order_id, $target, $driver_id, $extra );

		if ( is_wp_error( $result ) ) {
			// Clean up orphan proof if upload succeeded but transition failed.
			if ( ! empty( $extra['proof_attachment_id'] ) ) {
				Localpilot_Proof_Service::delete_orphan( $extra['proof_attachment_id'] );
			}
			wc_add_notice( $result->get_error_message(), 'error' );
		} else {
			wc_add_notice( __( 'Entrega actualizada correctamente.', 'localpilot' ), 'success' );
		}

		// Redirect back to avoid re-POST.
		wp_safe_redirect( wc_get_account_endpoint_url( self::ENDPOINT ) );
		exit;
	}

	/**
	 * Map a filter key to a list of delivery statuses.
	 *
	 * @param string $filter Filter key.
	 * @return array
	 */
	private static function get_statuses_for_filter( $filter ) {
		$map = array(
			'pendientes'  => array( 'assigned', 'accepted' ),
			'en_reparto'  => array( 'out_for_delivery' ),
			'entregadas'  => array( 'delivered' ),
			'fallidas'    => array( 'failed' ),
			'todas'       => array( 'assigned', 'accepted', 'out_for_delivery', 'delivered', 'failed', 'cancelled' ),
		);
		return isset( $map[ $filter ] ) ? $map[ $filter ] : $map['pendientes'];
	}
}
