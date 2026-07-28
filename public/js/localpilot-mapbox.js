(function( $ ) {
	'use strict';

	/**
	 * LocalPilot Mapbox — public map initialization.
	 *
	 * Renders a Mapbox GL map on the delivery detail page.
	 * The map container must have id="lclplt-map" and data-* attributes set.
	 */

	$( function() {

		var $mapEl = $( '#lclplt-map' );

		if ( ! $mapEl.length || typeof mapboxgl === 'undefined' ) {
			return;
		}

		var lat = parseFloat( $mapEl.data( 'lat' ) );
		var lng = parseFloat( $mapEl.data( 'lng' ) );
		var token = $mapEl.data( 'token' );
		var style = $mapEl.data( 'style' ) || 'streets-v12';
		var zoom  = parseInt( $mapEl.data( 'zoom' ), 10 ) || 14;
		var destinationLabel = $mapEl.data( 'destination-label' ) || 'Destino de entrega';
		var loadingLabel = $mapEl.data( 'loading-label' ) || 'Cargando mapa…';
		var errorLabel = $mapEl.data( 'error-label' ) || 'No se pudo cargar el mapa.';
		var $status = $( '[data-lclplt-map-status]' ).first();

		function showError() {
			$mapEl.removeClass( 'is-loading is-ready' ).addClass( 'is-error' );
			$status.text( errorLabel );
		}

		if ( isNaN( lat ) || isNaN( lng ) || ! token ) {
			showError();
			return;
		}

		mapboxgl.accessToken = token;
		$mapEl.addClass( 'is-loading' );
		$status.text( loadingLabel );

		try {
			var map = new mapboxgl.Map({
				container: 'lclplt-map',
				style: 'mapbox://styles/mapbox/' + style,
				center: [ lng, lat ],
				zoom: zoom
			});

			map.addControl( new mapboxgl.NavigationControl(), 'top-right' );

			var popup = new mapboxgl.Popup({ offset: 24 }).setText( destinationLabel );
			var marker = new mapboxgl.Marker()
				.setLngLat( [ lng, lat ] )
				.setPopup( popup )
				.addTo( map );

			marker.getElement().setAttribute( 'aria-label', destinationLabel );
			marker.getElement().setAttribute( 'title', destinationLabel );

			map.once( 'load', function() {
				$mapEl.removeClass( 'is-loading is-error' ).addClass( 'is-ready' );
				$status.text( '' );
				map.resize();
			} );

			map.on( 'error', function() {
				showError();
			} );
		} catch ( error ) {
			showError();
		}

	});

})( jQuery );
