(function( $ ) {
	'use strict';

	/**
	 * LocalPilot Mapbox admin audit map.
	 *
	 * Shows the destination, the point-in-time delivery GPS and the historical
	 * validation radius. Active deliveries allow destination correction.
	 */

	$( function() {
		var $mapEl = $( '#lclplt-admin-map' );

		if ( ! $mapEl.length || typeof mapboxgl === 'undefined' ) {
			return;
		}

		var lat = parseFloat( $mapEl.data( 'lat' ) );
		var lng = parseFloat( $mapEl.data( 'lng' ) );
		var deliveryLat = parseFloat( $mapEl.data( 'delivery-lat' ) );
		var deliveryLng = parseFloat( $mapEl.data( 'delivery-lng' ) );
		var radiusMeters = parseInt( $mapEl.data( 'validation-radius' ), 10 );
		var token = $mapEl.data( 'token' );
		var style = $mapEl.data( 'style' ) || 'streets-v12';
		var zoom = parseInt( $mapEl.data( 'zoom' ), 10 ) || 14;
		var isReadonly = String( $mapEl.data( 'readonly' ) ) === '1';
		var destinationLabel = $mapEl.data( 'destination-label' ) || 'Destino';
		var deliveryLabel = $mapEl.data( 'delivery-label' ) || 'Entrega registrada aquí';
		var errorLabel = $mapEl.data( 'error-label' ) || '';
		var hasDeliveryPoint = ! isNaN( deliveryLat ) && ! isNaN( deliveryLng );
		var $status = $( '#lclplt-map-status' );
		var circleCoordinates = [];

		if ( isNaN( lat ) || isNaN( lng ) || ! token ) {
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

		function formatCoordinates( pointLat, pointLng ) {
			return pointLat.toFixed( 6 ) + ', ' + pointLng.toFixed( 6 );
		}

		function destinationPoint( centerLat, centerLng, distance, bearing ) {
			var earthRadius = 6371000;
			var angularDistance = distance / earthRadius;
			var bearingRadians = bearing * Math.PI / 180;
			var latitudeRadians = centerLat * Math.PI / 180;
			var longitudeRadians = centerLng * Math.PI / 180;
			var resultLatitude = Math.asin(
				Math.sin( latitudeRadians ) * Math.cos( angularDistance ) +
				Math.cos( latitudeRadians ) * Math.sin( angularDistance ) * Math.cos( bearingRadians )
			);
			var resultLongitude = longitudeRadians + Math.atan2(
				Math.sin( bearingRadians ) * Math.sin( angularDistance ) * Math.cos( latitudeRadians ),
				Math.cos( angularDistance ) - Math.sin( latitudeRadians ) * Math.sin( resultLatitude )
			);

			return [
				( ( resultLongitude * 180 / Math.PI + 540 ) % 360 ) - 180,
				resultLatitude * 180 / Math.PI
			];
		}

		function buildCircle( centerLat, centerLng ) {
			var coordinates = [];
			var steps = 64;

			if ( radiusMeters <= 0 ) {
				return coordinates;
			}

			for ( var index = 0; index <= steps; index++ ) {
				coordinates.push( destinationPoint( centerLat, centerLng, radiusMeters, ( index / steps ) * 360 ) );
			}

			return coordinates;
		}

		function circleFeature( coordinates ) {
			return {
				'type': 'Feature',
				'properties': {},
				'geometry': {
					'type': 'Polygon',
					'coordinates': [ coordinates ]
				}
			};
		}

		function lineFeature( destinationLng, destinationLat ) {
			return {
				'type': 'Feature',
				'properties': {},
				'geometry': {
					'type': 'LineString',
					'coordinates': [ [ destinationLng, destinationLat ], [ deliveryLng, deliveryLat ] ]
				}
			};
		}

		function updateInputs( lngLat ) {
			$( '#lclplt_correction_lat' ).val( lngLat.lat.toFixed( 6 ) );
			$( '#lclplt_correction_lng' ).val( lngLat.lng.toFixed( 6 ) );
		}

		function updateGeometry( lngLat ) {
			circleCoordinates = buildCircle( lngLat.lat, lngLat.lng );

			if ( map.getSource( 'lclplt-validation-radius' ) && circleCoordinates.length ) {
				map.getSource( 'lclplt-validation-radius' ).setData( circleFeature( circleCoordinates ) );
			}

			if ( map.getSource( 'lclplt-delivery-distance' ) && hasDeliveryPoint ) {
				map.getSource( 'lclplt-delivery-distance' ).setData( lineFeature( lngLat.lng, lngLat.lat ) );
			}
		}

		function fitAuditArea() {
			var destination = destMarker.getLngLat();
			var bounds = new mapboxgl.LngLatBounds();
			bounds.extend( [ destination.lng, destination.lat ] );

			circleCoordinates.forEach( function( coordinate ) {
				bounds.extend( coordinate );
			});

			if ( hasDeliveryPoint ) {
				bounds.extend( [ deliveryLng, deliveryLat ] );
			}

			map.fitBounds( bounds, {
				padding: 56,
				maxZoom: 16,
				duration: 500
			});
		}

		var destMarker = new mapboxgl.Marker({
			draggable: ! isReadonly,
			color: '#d63638'
		})
			.setLngLat( [ lng, lat ] )
			.setPopup( new mapboxgl.Popup({ offset: 25 }).setText( destinationLabel + ': ' + formatCoordinates( lat, lng ) ) )
			.addTo( map );

		destMarker.getElement().setAttribute( 'aria-label', destinationLabel );

		var deliveryMarker = null;
		if ( hasDeliveryPoint ) {
			deliveryMarker = new mapboxgl.Marker({
				draggable: false,
				color: '#008a20',
				scale: 0.85
			})
				.setLngLat( [ deliveryLng, deliveryLat ] )
				.setPopup( new mapboxgl.Popup({ offset: 25 }).setText( deliveryLabel + ': ' + formatCoordinates( deliveryLat, deliveryLng ) ) )
				.addTo( map );

			deliveryMarker.getElement().setAttribute( 'aria-label', deliveryLabel );
		}

		circleCoordinates = buildCircle( lat, lng );
		updateInputs( destMarker.getLngLat() );

		map.on( 'load', function() {
			if ( circleCoordinates.length ) {
				map.addSource( 'lclplt-validation-radius', {
					'type': 'geojson',
					'data': circleFeature( circleCoordinates )
				});
				map.addLayer({
					'id': 'lclplt-validation-radius-fill',
					'type': 'fill',
					'source': 'lclplt-validation-radius',
					'paint': {
						'fill-color': '#008a20',
						'fill-opacity': 0.1
					}
				});
				map.addLayer({
					'id': 'lclplt-validation-radius-outline',
					'type': 'line',
					'source': 'lclplt-validation-radius',
					'paint': {
						'line-color': '#008a20',
						'line-width': 2,
						'line-opacity': 0.65,
						'line-dasharray': [ 3, 2 ]
					}
				});
			}

			if ( hasDeliveryPoint ) {
				map.addSource( 'lclplt-delivery-distance', {
					'type': 'geojson',
					'data': lineFeature( lng, lat )
				});
				map.addLayer({
					'id': 'lclplt-delivery-distance-line',
					'type': 'line',
					'source': 'lclplt-delivery-distance',
					'paint': {
						'line-color': '#50575e',
						'line-width': 1.5,
						'line-opacity': 0.7,
						'line-dasharray': [ 2, 2 ]
					}
				});
			}

			fitAuditArea();
		});

		map.once( 'error', function() {
			$status.removeClass( 'is-success' ).addClass( 'is-error' ).text( errorLabel );
		});

		if ( ! isReadonly ) {
			destMarker.on( 'drag', function() {
				var position = destMarker.getLngLat();
				updateInputs( position );
				updateGeometry( position );
			});

			destMarker.on( 'dragend', function() {
				var position = destMarker.getLngLat();
				destMarker.setPopup( new mapboxgl.Popup({ offset: 25 }).setText( destinationLabel + ': ' + formatCoordinates( position.lat, position.lng ) ) );
				fitAuditArea();
			});
		}

		$( '[data-lclplt-map-action]' ).on( 'click', function() {
			var action = $( this ).data( 'lclplt-map-action' );

			if ( 'fit' === action ) {
				fitAuditArea();
			} else if ( 'destination' === action ) {
				map.flyTo({ center: destMarker.getLngLat(), zoom: Math.max( zoom, 15 ) });
			} else if ( 'delivery' === action && deliveryMarker ) {
				map.flyTo({ center: deliveryMarker.getLngLat(), zoom: Math.max( zoom, 15 ) });
			}
		});
	});

})( jQuery );
