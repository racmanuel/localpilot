<?php
/**
 * Order editor meta box and handler.
 *
 * Adds a LocalPilot meta box to the WooCommerce order edit screen
 * and processes assign/reassign/unassign actions.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */

/**
 * Order editor meta box and action handler.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */
class Localpilot_Order_Editor {

	/**
	 * Nonce action for delivery actions.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'lclplt_order_delivery';

	/**
	 * Register hooks.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'handle_actions' ), 50, 2 );

		// Force the LocalPilot meta box to the normal (main) area, even if
		// HPOS saved a user preference for the sidebar.
		add_filter( "get_user_option_meta-box-order_{$this->get_order_screen()}", array( $this, 'force_normal_context' ), 100 );
	}

	/**
	 * Get the order edit screen ID (HPOS-compatible).
	 *
	 * @return string
	 */
	private function get_order_screen() {
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			return wc_get_page_screen_id( 'shop-order' );
		}
		return 'shop_order';
	}

	/**
	 * Force the LocalPilot meta box to the normal (main) area.
	 *
	 * @param mixed $result Saved meta box order.
	 * @return array
	 */
	public function force_normal_context( $result ) {
		if ( ! is_array( $result ) ) {
			$result = array(
				'normal' => '',
				'side'   => '',
				'column3' => '',
			);
		}

		// Remove LocalPilot from 'side' if it's there, ensure it's in 'normal'.
		foreach ( array( 'side', 'column3', 'column4' ) as $context ) {
			if ( isset( $result[ $context ] ) ) {
				$boxes = explode( ',', $result[ $context ] );
				$boxes = array_filter( $boxes, function( $id ) {
					return 'localpilot_delivery' !== trim( $id );
				} );
				$result[ $context ] = implode( ',', $boxes );
			}
		}

		// Add to normal if not already there.
		$normal = isset( $result['normal'] ) ? explode( ',', $result['normal'] ) : array();
		if ( ! in_array( 'localpilot_delivery', $normal, true ) ) {
			$normal[] = 'localpilot_delivery';
		}
		$result['normal'] = implode( ',', array_filter( $normal ) );

		return $result;
	}

	/**
	 * Register the LocalPilot meta box on the order edit screen.
	 * Works with both HPOS and legacy (posts) order screens.
	 */
	public function add_meta_box() {
		$screen = wc_get_page_screen_id( 'shop-order' );

		add_meta_box(
			'localpilot_delivery',
			__( 'LocalPilot', 'localpilot' ),
			array( $this, 'render_meta_box' ),
			$screen,
			'normal',
			'high'
		);
	}

	/**
	 * Render the meta box content.
	 *
	 * @param WP_Post|WC_Order $post_or_order Post or order object.
	 */
	public function render_meta_box( $post_or_order ) {
		$order = ( $post_or_order instanceof WP_Post ) ? wc_get_order( $post_or_order->ID ) : $post_or_order;
		if ( ! $order ) {
			echo '<p>' . esc_html__( 'Pedido no encontrado.', 'localpilot' ) . '</p>';
			return;
		}

		$meta      = new Localpilot_Order_Delivery_Meta( $order );
		$status    = $meta->get_delivery_status();
		$driver_id = $meta->get_driver_id();
		$assignment_id = $meta->get_assignment_id();

		$can_manage = current_user_can( Localpilot_Capabilities::MANAGE_DELIVERIES );

		// Load the partial view.
		$panel_path = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'admin/partials/localpilot-order-delivery-panel.php';
		if ( file_exists( $panel_path ) ) {
			include $panel_path;
		}
	}

	/**
	 * Get a human-readable event label for the admin timeline.
	 *
	 * @param string $event_type Event type.
	 * @return string
	 */
	public static function event_label( $event_type ) {
		$labels = array(
			'delivery_assigned'         => __( 'Repartidor asignado', 'localpilot' ),
			'delivery_reassigned'       => __( 'Repartidor reasignado', 'localpilot' ),
			'delivery_unassigned'       => __( 'Asignación retirada', 'localpilot' ),
			'delivery_accepted'         => __( 'Entrega aceptada', 'localpilot' ),
			'delivery_started'          => __( 'Reparto iniciado', 'localpilot' ),
			'delivery_completed'        => __( 'Entrega completada', 'localpilot' ),
			'delivery_failed'           => __( 'Entrega fallida', 'localpilot' ),
			'delivery_cancelled'        => __( 'Entrega cancelada', 'localpilot' ),
			'delivery_geocoded'         => __( 'Destino geocodificado', 'localpilot' ),
			'delivery_location_updated' => __( 'Destino corregido', 'localpilot' ),
		);

		return isset( $labels[ $event_type ] ) ? $labels[ $event_type ] : __( 'Actividad de entrega', 'localpilot' );
	}

	/**
	 * Get a Dashicon class for an event type.
	 *
	 * @param string $event_type Event type.
	 * @return string
	 */
	public static function event_icon( $event_type ) {
		$icons = array(
			'delivery_assigned'         => 'dashicons-admin-users',
			'delivery_reassigned'       => 'dashicons-update',
			'delivery_unassigned'       => 'dashicons-dismiss',
			'delivery_accepted'         => 'dashicons-yes-alt',
			'delivery_started'          => 'dashicons-location-alt',
			'delivery_completed'        => 'dashicons-saved',
			'delivery_failed'           => 'dashicons-warning',
			'delivery_cancelled'        => 'dashicons-no-alt',
			'delivery_geocoded'         => 'dashicons-location',
			'delivery_location_updated' => 'dashicons-edit-location',
		);

		return isset( $icons[ $event_type ] ) ? $icons[ $event_type ] : 'dashicons-info-outline';
	}

	/**
	 * Handle delivery actions from the meta box.
	 *
	 * @param int      $order_id Order ID.
	 * @param WC_Order $order    Order object.
	 */
	public function handle_actions( $order_id, $order ) {
		if ( ! isset( $_POST['lclplt_delivery_action'] ) ) {
			return;
		}

		$action = sanitize_key( wp_unslash( $_POST['lclplt_delivery_action'] ) );
		if ( '' === $action ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['lclplt_delivery_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['lclplt_delivery_nonce'] ), self::NONCE_ACTION )
		) {
			WC_Admin_Meta_Boxes::add_error( __( 'Error de seguridad. Intenta de nuevo.', 'localpilot' ) );
			return;
		}

		if ( ! current_user_can( Localpilot_Capabilities::MANAGE_DELIVERIES ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'No tienes permiso para realizar esta acción.', 'localpilot' ) );
			return;
		}

		$allowed = array( 'assign', 'reassign', 'unassign', 'update_location' );

		if ( ! in_array( $action, $allowed, true ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'Acción no válida.', 'localpilot' ) );
			return;
		}

		$actor_id  = get_current_user_id();
		$order_obj = wc_get_order( $order_id );
		if ( ! $order_obj ) {
			WC_Admin_Meta_Boxes::add_error( __( 'Pedido no encontrado.', 'localpilot' ) );
			return;
		}

		$meta_obj = new Localpilot_Order_Delivery_Meta( $order_obj );
		if ( Localpilot_Delivery_Status::is_terminal( $meta_obj->get_delivery_status() ) ) {
			WC_Admin_Meta_Boxes::add_error( __( 'La entrega está cerrada y no se puede modificar.', 'localpilot' ) );
			return;
		}

		switch ( $action ) {
			case 'assign':
				$driver_id = isset( $_POST['lclplt_driver_id'] ) ? absint( $_POST['lclplt_driver_id'] ) : 0;
				if ( ! $driver_id ) {
					WC_Admin_Meta_Boxes::add_error( __( 'Selecciona un repartidor.', 'localpilot' ) );
					return;
				}
				$result = Localpilot_Assignment_Service::assign( $order_id, $driver_id, $actor_id );
				break;

			case 'reassign':
				$driver_id = isset( $_POST['lclplt_driver_id'] ) ? absint( $_POST['lclplt_driver_id'] ) : 0;
				if ( ! $driver_id ) {
					WC_Admin_Meta_Boxes::add_error( __( 'Selecciona un repartidor.', 'localpilot' ) );
					return;
				}
				$result = Localpilot_Assignment_Service::reassign( $order_id, $driver_id, $actor_id );
				break;

			case 'unassign':
				$result = Localpilot_Assignment_Service::unassign( $order_id, $actor_id );
				break;

			case 'update_location':
				$lat = isset( $_POST['lclplt_correction_lat'] ) ? sanitize_text_field( wp_unslash( $_POST['lclplt_correction_lat'] ) ) : '';
				$lng = isset( $_POST['lclplt_correction_lng'] ) ? sanitize_text_field( wp_unslash( $_POST['lclplt_correction_lng'] ) ) : '';

				if ( ! is_numeric( $lat ) || ! is_numeric( $lng )
					|| (float) $lat < -90 || (float) $lat > 90
					|| (float) $lng < -180 || (float) $lng > 180
				) {
					WC_Admin_Meta_Boxes::add_error( __( 'Coordenadas no válidas.', 'localpilot' ) );
					return;
				}

				$meta_obj->set_bulk( array(
					Localpilot_Order_Delivery_Meta::DELIVERY_LATITUDE  => (float) $lat,
					Localpilot_Order_Delivery_Meta::DELIVERY_LONGITUDE => (float) $lng,
					Localpilot_Order_Delivery_Meta::GEOCODING_STATUS   => 'manual',
				) )->save();

				// Get current assignment for event.
				$assignment    = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
				$assignment_id = $assignment ? (int) $assignment->id : 0;
				$driver_id     = $assignment ? (int) $assignment->driver_id : 0;

				Localpilot_Event_Repository::insert( array(
					'order_id'       => $order_id,
					'assignment_id'  => $assignment_id,
					'driver_id'      => $driver_id,
					'user_id'        => $actor_id,
					'event_type'     => 'delivery_location_updated',
					'event_data'     => array(
						'source' => 'manual',
					),
				) );

				$result = true;
				break;

			default:
				return;
		}

		if ( is_wp_error( $result ) ) {
			WC_Admin_Meta_Boxes::add_error( $result->get_error_message() );
		} else {
			// Store a transient for the redirect notice.
			set_transient( 'lclplt_admin_notice_' . $actor_id, __( 'Operación de entrega exitosa.', 'localpilot' ), 30 );
		}
	}
}
