(function( $ ) {
	'use strict';

	/**
	 * LocalPilot admin JavaScript.
	 *
	 * - Prevents double-submit on delivery action buttons.
	 */

	$( function() {

		// Prevent double submit: disable the button AFTER the form submits.
		// We hook into the parent form's submit event instead of the button
		// click, so the button value is still sent with the request.
		$( '#localpilot_delivery' ).closest( 'form' ).on( 'submit', function() {
			var $btn = $( this ).find( 'button[name="lclplt_delivery_action"]' );
			if ( $btn.data( 'lclplt-clicked' ) ) {
				return false;
			}
			$btn.data( 'lclplt-clicked', true );
			$btn.prop( 'disabled', true );
		});

	});

})( jQuery );
