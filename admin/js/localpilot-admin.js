(function( $ ) {
	'use strict';

	/**
	 * LocalPilot admin JavaScript.
	 *
	 * Handles contextual delivery actions and prevents duplicate submissions.
	 */

	$( function() {
		var $panel = $( '#localpilot_delivery' );
		var $form = $panel.closest( 'form' );
		var $actionInput = $( '#lclplt_delivery_action_input' );

		$panel.on( 'click', '[data-lclplt-action]', function( event ) {
			var $button = $( this );
			var action = $button.data( 'lclplt-action' );
			var confirmation = $button.data( 'lclplt-confirm' );

			if ( confirmation && ! window.confirm( confirmation ) ) {
				event.preventDefault();
				$actionInput.val( '' );
				return;
			}

			$actionInput.val( action );
		});

		$form.on( 'submit', function() {
			if ( ! $actionInput.val() ) {
				return true;
			}

			if ( $form.data( 'lclplt-submitting' ) ) {
				return false;
			}

			$form.data( 'lclplt-submitting', true );
			$panel.find( '[data-lclplt-action]' ).prop( 'disabled', true );
			return true;
		});

		$panel.on( 'click', '[data-lclplt-copy-coords]', function() {
			var $button = $( this );
			var coordinates = String( $button.data( 'lclplt-copy-coords' ) || '' );
			var successMessage = $button.data( 'lclplt-copied-label' ) || '';
			var errorMessage = $button.data( 'lclplt-copy-error-label' ) || '';
			var $status = $( '#lclplt-map-status' );

			function reportSuccess() {
				$status.removeClass( 'is-error' ).addClass( 'is-success' ).text( successMessage );
			}

			function reportError() {
				$status.removeClass( 'is-success' ).addClass( 'is-error' ).text( errorMessage );
			}

			if ( navigator.clipboard && window.isSecureContext ) {
				navigator.clipboard.writeText( coordinates ).then( reportSuccess, reportError );
				return;
			}

			var $fallback = $( '<textarea />' ).val( coordinates ).attr( 'readonly', true ).addClass( 'lclplt-clipboard-fallback' ).appendTo( 'body' );
			$fallback[0].select();
			try {
				document.execCommand( 'copy' ) ? reportSuccess() : reportError();
			} catch ( error ) {
				reportError();
			}
			$fallback.remove();
		});
	});

})( jQuery );
