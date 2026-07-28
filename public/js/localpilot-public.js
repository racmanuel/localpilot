(function( $ ) {
	'use strict';

	/**
	 * LocalPilot public JavaScript.
	 *
	 * - Prevents double-submit on delivery action forms.
	 */

	$( function() {

		$( '.lclplt-action-form' ).on( 'submit', function() {
			var $form = $( this );
			var $btn  = $form.find( 'button[type="submit"]' );
			if ( $btn.data( 'lclplt-clicked' ) ) {
				return false;
			}
			$btn.data( 'lclplt-clicked', true );
			$btn.prop( 'disabled', true );
		});

	});

})( jQuery );
