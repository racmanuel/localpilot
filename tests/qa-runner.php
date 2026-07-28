<?php
/**
 * QA test runner — executed via CLI.
 */
require_once dirname( dirname( dirname( dirname( __DIR__ ) ) ) ) . '/wp-load.php';

$tests = array();

// FSM-01: assigned -> delivered (should reject)
$order = wc_get_order( 1664 );
if ( $order ) {
	$meta = new Localpilot_Order_Delivery_Meta( $order );
	$tests['FSM-01 assigned->delivered'] = array(
		'status_before' => $meta->get_delivery_status(),
	);
	$result = Localpilot_Delivery_Transition_Service::transition( 1664, 'delivered', 46 );
	$tests['FSM-01 assigned->delivered']['result'] = is_wp_error( $result )
		? 'RECHAZADO: ' . $result->get_error_message()
		: 'ACEPTADO (debió rechazar)';
	$meta2 = new Localpilot_Order_Delivery_Meta( wc_get_order( 1664 ) );
	$tests['FSM-01 assigned->delivered']['status_after'] = $meta2->get_delivery_status();
}

// ASG-01: Double assignment (order already has active assignment)
// Order 1564 should have an active or completed assignment
$assign2 = Localpilot_Assignment_Service::assign( 1664, 46, 1 );
$tests['ASG-01 doble asignación'] = is_wp_error( $assign2 )
	? 'RECHAZADO: ' . $assign2->get_error_message()
	: 'ACEPTADO ID: ' . $assign2;

// MAP-02: Test geocoding status on order 1664
$order4 = wc_get_order( 1664 );
if ( $order4 ) {
	$meta4 = new Localpilot_Order_Delivery_Meta( $order4 );
	$tests['MAP-01 geocodificación'] = $meta4->get_geocoding_status()
		? 'Estado: ' . $meta4->get_geocoding_status()
		: 'Sin geocodificar';
}

// Check events for a completed order (1662)
$events = Localpilot_Event_Repository::get_by_assignment( 4, 10 );
$tests['EVT-01 eventos orden 1664'] = count( $events ) . ' eventos: '
	. implode( ', ', array_map( function( $e ) { return $e->event_type; }, $events ) );

// ACL-01: Verify driver role capabilities
$driver = get_userdata( 46 );
$tests['ACL-01 driver capabilities'] = $driver
	? 'Rol: ' . implode( ', ', $driver->roles )
	: 'Driver no encontrado';

echo json_encode( $tests, JSON_PRETTY_PRINT );

// === Additional inline tests ===

// MAIL-01: Email registration
$emails = WC_Emails::instance()->get_emails();
$lclplt_emails = array();
foreach ( $emails as $k => $v ) {
	if ( str_starts_with( $k, 'lclplt' ) ) {
		$lclplt_emails[] = $k;
	}
}
echo PHP_EOL . PHP_EOL . 'MAIL-01: ' . count( $lclplt_emails ) . ' emails: ' . implode( ', ', $lclplt_emails ) . PHP_EOL;

// ASG-02 + ACL-02: Create fresh order, assign, unassign
$new_order = new WC_Order();
$new_order->set_status( 'processing' );
$new_order->set_billing_email( 'qa@test.com' );
$new_order->save();
$new_oid = $new_order->get_id();

$ass = Localpilot_Assignment_Service::assign( $new_oid, 46, 1 );
echo 'ASG-02 asignar: ' . ( is_wp_error( $ass ) ? 'FALLO: ' . $ass->get_error_message() : 'OK ID=' . $ass ) . PHP_EOL;

$uas = Localpilot_Assignment_Service::unassign( $new_oid, 1 );
echo 'UNASG retirar: ' . ( is_wp_error( $uas ) ? 'FALLO: ' . $uas->get_error_message() : 'OK' ) . PHP_EOL;

$active_check = Localpilot_Assignment_Repository::get_active_by_order( $new_oid );
echo 'ACL-02 acceso tras retiro: ' . ( $active_check ? 'ERROR — sigue activa' : 'OK — inactiva' ) . PHP_EOL;

// PRF-02: MIME validation
$uploads = wp_upload_dir();
$evil_file = $uploads['basedir'] . '/evil.jpg.php';
$finfo = finfo_open( FILEINFO_MIME_TYPE );
$evil_mime = finfo_file( $finfo, $evil_file );
finfo_close( $finfo );
echo 'PRF-02 MIME real: ' . $evil_mime . ' — Permitido: ' . ( isset( Localpilot_Proof_Service::ALLOWED_MIMES[ $evil_mime ] ) ? 'SI (ERROR)' : 'NO (OK)' ) . PHP_EOL;

// Clean up
wp_delete_file( $evil_file );
