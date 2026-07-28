<?php
/**
 * Order delivery meta adapter.
 *
 * Centralises all _lclplt_* meta keys and provides read/write access
 * exclusively through WC_Order CRUD methods.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Order delivery meta adapter.
 *
 * Encapsulates reading and writing delivery-related metadata on WC_Order
 * objects. All keys use the _lclplt_ private prefix. No direct postmeta
 * or HPOS table access outside this class.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Order_Delivery_Meta {

	/*
	 * ------------------------------------------------------------------
	 * Meta key constants
	 * ------------------------------------------------------------------
	 */

	const DRIVER_ID             = '_lclplt_driver_id';
	const DELIVERY_STATUS       = '_lclplt_delivery_status';
	const ASSIGNMENT_ID         = '_lclplt_assignment_id';
	const ASSIGNED_AT           = '_lclplt_assigned_at';
	const ACCEPTED_AT           = '_lclplt_accepted_at';
	const OUT_FOR_DELIVERY_AT   = '_lclplt_out_for_delivery_at';
	const DELIVERED_AT          = '_lclplt_delivered_at';
	const FAILED_AT             = '_lclplt_failed_at';
	const FAILED_REASON         = '_lclplt_failed_reason';
	const RECEIVED_BY           = '_lclplt_received_by';
	const PROOF_ATTACHMENT_ID   = '_lclplt_proof_attachment_id';
	const DELIVERY_NOTES        = '_lclplt_delivery_notes';
	const DELIVERY_LATITUDE     = '_lclplt_delivery_latitude';
	const DELIVERY_LONGITUDE    = '_lclplt_delivery_longitude';
	const MAPBOX_PLACE_ID       = '_lclplt_mapbox_place_id';
	const GEOCODED_ADDRESS      = '_lclplt_geocoded_address';
	const GEOCODED_AT           = '_lclplt_geocoded_at';
	const GEOCODING_STATUS      = '_lclplt_geocoding_status';

	/**
	 * All meta keys for iteration or bulk operations.
	 *
	 * @return array
	 */
	public static function all_keys() {
		return array(
			self::DRIVER_ID,
			self::DELIVERY_STATUS,
			self::ASSIGNMENT_ID,
			self::ASSIGNED_AT,
			self::ACCEPTED_AT,
			self::OUT_FOR_DELIVERY_AT,
			self::DELIVERED_AT,
			self::FAILED_AT,
			self::FAILED_REASON,
			self::RECEIVED_BY,
			self::PROOF_ATTACHMENT_ID,
			self::DELIVERY_NOTES,
			self::DELIVERY_LATITUDE,
			self::DELIVERY_LONGITUDE,
			self::MAPBOX_PLACE_ID,
			self::GEOCODED_ADDRESS,
			self::GEOCODED_AT,
			self::GEOCODING_STATUS,
		);
	}

	/**
	 * Order instance.
	 *
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Constructor.
	 *
	 * @param WC_Order $order WooCommerce order object.
	 */
	public function __construct( $order ) {
		$this->order = $order;
	}

	/**
	 * Get the underlying WC_Order.
	 *
	 * @return WC_Order
	 */
	public function get_order() {
		return $this->order;
	}

	/**
	 * Get a delivery meta value.
	 *
	 * @param string $key Meta key constant.
	 * @param mixed  $default Default value if not found.
	 * @return mixed
	 */
	public function get( $key, $default = null ) {
		$value = $this->order->get_meta( $key, true );
		return '' !== $value ? $value : $default;
	}

	/**
	 * Set a delivery meta value and persist.
	 *
	 * @param string $key   Meta key constant.
	 * @param mixed  $value Value to set.
	 * @return self
	 */
	public function set( $key, $value ) {
		$this->order->update_meta_data( $key, $value );
		return $this;
	}

	/**
	 * Delete a delivery meta key.
	 *
	 * @param string $key Meta key constant.
	 * @return self
	 */
	public function delete( $key ) {
		$this->order->delete_meta_data( $key );
		return $this;
	}

	/**
	 * Persist all pending meta changes to the database.
	 *
	 * @return self
	 */
	public function save() {
		$this->order->save();
		return $this;
	}

	/**
	 * Convenience: set one or more meta values and save.
	 *
	 * @param array $pairs Associative array of key => value.
	 * @return self
	 */
	public function set_bulk( array $pairs ) {
		foreach ( $pairs as $key => $value ) {
			$this->set( $key, $value );
		}
		return $this;
	}

	/*
	 * ------------------------------------------------------------------
	 * Individual getters
	 * ------------------------------------------------------------------
	 */

	public function get_driver_id() {
		return (int) $this->get( self::DRIVER_ID, 0 );
	}

	public function get_delivery_status() {
		return $this->get( self::DELIVERY_STATUS, 'unassigned' );
	}

	public function get_assignment_id() {
		return (int) $this->get( self::ASSIGNMENT_ID, 0 );
	}

	public function get_assigned_at() {
		return $this->get( self::ASSIGNED_AT );
	}

	public function get_accepted_at() {
		return $this->get( self::ACCEPTED_AT );
	}

	public function get_out_for_delivery_at() {
		return $this->get( self::OUT_FOR_DELIVERY_AT );
	}

	public function get_delivered_at() {
		return $this->get( self::DELIVERED_AT );
	}

	public function get_failed_at() {
		return $this->get( self::FAILED_AT );
	}

	public function get_failed_reason() {
		return $this->get( self::FAILED_REASON );
	}

	public function get_received_by() {
		return $this->get( self::RECEIVED_BY );
	}

	public function get_proof_attachment_id() {
		return (int) $this->get( self::PROOF_ATTACHMENT_ID, 0 );
	}

	public function get_delivery_notes() {
		return $this->get( self::DELIVERY_NOTES );
	}

	public function get_latitude() {
		return $this->get( self::DELIVERY_LATITUDE );
	}

	public function get_longitude() {
		return $this->get( self::DELIVERY_LONGITUDE );
	}

	public function get_mapbox_place_id() {
		return $this->get( self::MAPBOX_PLACE_ID );
	}

	public function get_geocoded_address() {
		return $this->get( self::GEOCODED_ADDRESS );
	}

	public function get_geocoded_at() {
		return $this->get( self::GEOCODED_AT );
	}

	public function get_geocoding_status() {
		return $this->get( self::GEOCODING_STATUS );
	}

	/**
	 * Clear all delivery meta from the order.
	 *
	 * @return self
	 */
	public function clear_all() {
		foreach ( self::all_keys() as $key ) {
			$this->order->delete_meta_data( $key );
		}
		return $this;
	}
}
