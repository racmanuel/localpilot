(function( $ ) {
	'use strict';

	/**
	 * LocalPilot public JavaScript.
	 *
	 * - Captures one GPS position when completing a delivery.
	 * - Provides a permission retry and warning fallback.
	 * - Prevents double-submit on delivery action forms.
	 */

	$( function() {

		$( '[data-lclplt-location-validation="1"]' ).each( function() {
			var $location = $( this );
			var $form = $location.closest( 'form' );
			var $message = $location.find( '.lclplt-location-validation__message' );
			var $retry = $location.find( '.lclplt-location-retry' );
			var $continue = $location.find( '.lclplt-location-continue' );

			function text( key, fallback ) {
				return $location.attr( 'data-message-' + key ) || fallback;
			}

			function submitWithLocation() {
				$form.data( 'lclplt-location-ready', true );
				$form.find( '.lclplt-complete-submit' ).prop( 'disabled', true );
				// Native submit avoids recursively triggering this capture handler.
				HTMLFormElement.prototype.submit.call( $form.get( 0 ) );
			}

			function showError( code ) {
				var message = text( 'unavailable', 'No se pudo obtener tu ubicación.' );
				if ( 1 === code ) {
					message = text( 'permission', 'Debes permitir el acceso a tu ubicación.' );
				} else if ( 3 === code ) {
					message = text( 'timeout', 'La solicitud de ubicación tardó demasiado.' );
				}
				$message.text( message ).css( 'color', '#a00' );
				$retry.show();
				$continue.show();
			}

			function captureLocation() {
				$retry.hide();
				$continue.hide();
				$message.text( text( 'request', 'Obteniendo tu ubicación actual…' ) ).css( 'color', '#666' );

				if ( ! navigator.geolocation ) {
					showError( 2 );
					return;
				}

				navigator.geolocation.getCurrentPosition( function( position ) {
					var coords = position.coords || {};
					$location.find( '[name="lclplt_location_latitude"]' ).val( coords.latitude || '' );
					$location.find( '[name="lclplt_location_longitude"]' ).val( coords.longitude || '' );
					$location.find( '[name="lclplt_location_accuracy"]' ).val( coords.accuracy || '' );
					$location.find( '[name="lclplt_location_timestamp"]' ).val( position.timestamp || Date.now() );
					$location.find( '[name="lclplt_location_status"]' ).val( 'success' );
					$message.text( text( 'success', 'Ubicación obtenida.' ) ).css( 'color', '#2271b1' );
					submitWithLocation();
				}, function( error ) {
					$location.find( '[name="lclplt_location_status"]' ).val( 1 === error.code ? 'permission_denied' : ( 3 === error.code ? 'timeout' : 'unavailable' ) );
					showError( error.code );
				}, {
					enableHighAccuracy: true,
					timeout: 15000,
					maximumAge: 0
				} );
			}

			$form.on( 'submit', function( event ) {
				if ( $form.data( 'lclplt-location-ready' ) ) {
					return;
				}

				if ( ! $form.get( 0 ).checkValidity() ) {
					return;
				}

				event.preventDefault();
				captureLocation();
			} );

			$retry.on( 'click', function() {
				captureLocation();
			} );

			$continue.on( 'click', function() {
				$location.find( '[name="lclplt_location_status"]' ).val( 'unavailable' );
				submitWithLocation();
			} );
		} );

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
