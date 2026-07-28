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

		if ( isNaN( lat ) || isNaN( lng ) || ! token ) {
			return;
		}

		mapboxgl.accessToken = token;

		var map = new mapboxgl.Map({
			container: 'lclplt-map',
			style: 'mapbox://styles/mapbox/' + style,
			center: [ lng, lat ],
			zoom: zoom
		});

		// Add navigation controls.
		map.addControl( new mapboxgl.NavigationControl(), 'top-right' );

		// Add marker.
		new mapboxgl.Marker()
			.setLngLat( [ lng, lat ] )
			.addTo( map );

	});

})( jQuery );
