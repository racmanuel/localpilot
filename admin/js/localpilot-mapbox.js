(function( $ ) {
	'use strict';

	/**
	 * LocalPilot Mapbox — admin map with draggable marker.
	 *
	 * Allows the shop manager to view and manually correct
	 * geocoding coordinates by dragging a marker.
	 */

	$( function() {

		var $mapEl = $( '#lclplt-admin-map' );

		if ( ! $mapEl.length || typeof mapboxgl === 'undefined' ) {
			return;
		}

		var lat   = parseFloat( $mapEl.data( 'lat' ) );
		var lng   = parseFloat( $mapEl.data( 'lng' ) );
		var token = $mapEl.data( 'token' );
		var style = $mapEl.data( 'style' ) || 'streets-v12';
		var zoom  = parseInt( $mapEl.data( 'zoom' ), 10 ) || 14;

		// Fallback center if no coordinates yet.
		if ( isNaN( lat ) || isNaN( lng ) ) {
			lat = 19.4326;  // Mexico City (approximate center)
			lng = -99.1332;
		}

		if ( ! token ) {
			return;
		}

		mapboxgl.accessToken = token;

		var map = new mapboxgl.Map({
			container: 'lclplt-admin-map',
			style: 'mapbox://styles/mapbox/' + style,
			center: [ lng, lat ],
			zoom: zoom
		});

		map.addControl( new mapboxgl.NavigationControl(), 'top-right' );

		// Draggable marker.
		var marker = new mapboxgl.Marker({
			draggable: true
		})
			.setLngLat( [ lng, lat ] )
			.addTo( map );

		// Update hidden inputs when marker is dragged.
		function updateCoords( lngLat ) {
			$( '#lclplt_correction_lat' ).val( lngLat.lat.toFixed(6) );
			$( '#lclplt_correction_lng' ).val( lngLat.lng.toFixed(6) );
		}

		marker.on( 'dragend', function() {
			updateCoords( marker.getLngLat() );
		});

		// Also update on initial load so values are ready.
		updateCoords( marker.getLngLat() );

	});

})( jQuery );
