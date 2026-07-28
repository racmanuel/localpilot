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

		// Draggable marker — destination / geocoded address.
		var destMarker = new mapboxgl.Marker({
			draggable: true,
			color: '#dc3545'
		})
			.setLngLat( [ lng, lat ] )
			.setPopup( new mapboxgl.Popup({ offset: 25 }).setText( 'Destino' ) )
			.addTo( map );

		// Draw a radius circle around the destination (validation zone).
		var radiusMeters = parseInt( $mapEl.data( 'validation-radius' ), 10 );
		if ( radiusMeters > 0 ) {
			var circleCoords = [];
			var steps = 64;
			var radiusDeg = radiusMeters / 111320; // Approx degrees per meter at equator
			for ( var i = 0; i <= steps; i++ ) {
				var bearing = ( i / steps ) * 360;
				var rad = bearing * Math.PI / 180;
				// Adjust longitude stretching by latitude.
				var latAdj = radiusDeg * Math.cos( rad );
				var lngAdj = radiusDeg * Math.sin( rad ) / Math.cos( lat * Math.PI / 180 );
				circleCoords.push( [ lng + lngAdj, lat + latAdj ] );
			}

			map.on( 'load', function() {
				map.addSource( 'validation-radius', {
					'type': 'geojson',
					'data': {
						'type': 'Feature',
						'geometry': {
							'type': 'Polygon',
							'coordinates': [ circleCoords ]
						}
					}
				});
				map.addLayer({
					'id': 'validation-radius-fill',
					'type': 'fill',
					'source': 'validation-radius',
					'layout': {},
					'paint': {
						'fill-color': '#28a745',
						'fill-opacity': 0.08
					}
				});
				map.addLayer({
					'id': 'validation-radius-outline',
					'type': 'line',
					'source': 'validation-radius',
					'layout': {},
					'paint': {
						'line-color': '#28a745',
						'line-width': 2,
						'line-opacity': 0.5,
						'line-dasharray': [ 4, 3 ]
					}
				});
			});
		}

		// Non-draggable marker — actual delivery GPS location (if available).
		var deliveryLat = parseFloat( $mapEl.data( 'delivery-lat' ) );
		var deliveryLng = parseFloat( $mapEl.data( 'delivery-lng' ) );
		if ( ! isNaN( deliveryLat ) && ! isNaN( deliveryLng ) ) {
			new mapboxgl.Marker({
				draggable: false,
				color: '#28a745',
				scale: 0.8
			})
				.setLngLat( [ deliveryLng, deliveryLat ] )
				.setPopup( new mapboxgl.Popup({ offset: 25 }).setText( 'Entrega registrada aquí' ) )
				.addTo( map );
		}

		// Update hidden inputs when marker is dragged.
		function updateCoords( lngLat ) {
			$( '#lclplt_correction_lat' ).val( lngLat.lat.toFixed(6) );
			$( '#lclplt_correction_lng' ).val( lngLat.lng.toFixed(6) );
		}

		destMarker.on( 'dragend', function() {
			updateCoords( destMarker.getLngLat() );
		});

		// Also update on initial load so values are ready.
		updateCoords( destMarker.getLngLat() );

	});

})( jQuery );
