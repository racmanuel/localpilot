(function( $ ) {
	'use strict';

	/**
	 * LocalPilot Mapbox — delivery destination and on-demand routes.
	 *
	 * A route uses one browser geolocation request and lives only in memory.
	 * No origin, geometry, distance, or duration is sent to WordPress.
	 */

	$( function() {
		var $mapEl = $( '#lclplt-map' );

		if ( ! $mapEl.length || typeof mapboxgl === 'undefined' ) {
			return;
		}

		var destination = [ parseFloat( $mapEl.data( 'lng' ) ), parseFloat( $mapEl.data( 'lat' ) ) ];
		var token = $mapEl.data( 'token' );
		var style = $mapEl.data( 'style' ) || 'streets-v12';
		var zoom = parseInt( $mapEl.data( 'zoom' ), 10 ) || 14;
		var destinationLabel = $mapEl.data( 'destination-label' ) || 'Destino de entrega';
		var loadingLabel = $mapEl.data( 'loading-label' ) || 'Cargando mapa…';
		var errorLabel = $mapEl.data( 'error-label' ) || 'No se pudo cargar el mapa.';
		var $mapStatus = $( '[data-lclplt-map-status]' ).first();
		var $planner = $( '[data-lclplt-route-planner="1"]' ).first();
		var map = null;
		var mapLoaded = false;
		var originMarker = null;
		var routeBounds = null;
		var requestController = null;
		var requestSerial = 0;
		var routeSourceId = 'lclplt-route';
		var routeOutlineLayerId = 'lclplt-route-outline';
		var routeLayerId = 'lclplt-route-line';
		var allowedProfiles = [ 'driving-traffic', 'driving', 'cycling', 'walking' ];

		var $routeButton = $planner.find( '[data-lclplt-route-calculate]' );
		var $routeButtonText = $planner.find( '[data-lclplt-route-button-text]' );
		var $routeProfile = $planner.find( '[data-lclplt-route-profile]' );
		var $routeStatus = $planner.find( '[data-lclplt-route-status]' );
		var $routeSummary = $planner.find( '[data-lclplt-route-summary]' );
		var $routeFit = $planner.find( '[data-lclplt-route-fit]' );

		function plannerText( key, fallback ) {
			return $planner.attr( 'data-' + key ) || fallback;
		}

		function routeError( code ) {
			var error = new Error( code );
			error.lclpltCode = code;
			return error;
		}

		function isCoordinate( coordinate ) {
			return Array.isArray( coordinate ) &&
				2 <= coordinate.length &&
				Number.isFinite( Number( coordinate[0] ) ) &&
				Number.isFinite( Number( coordinate[1] ) ) &&
				-180 <= Number( coordinate[0] ) &&
				180 >= Number( coordinate[0] ) &&
				-90 <= Number( coordinate[1] ) &&
				90 >= Number( coordinate[1] );
		}

		function getProfile() {
			var profile = $routeProfile.length ? String( $routeProfile.val() || '' ) : plannerText( 'default-profile', 'driving-traffic' );
			return -1 !== allowedProfiles.indexOf( profile ) ? profile : 'driving-traffic';
		}

		function getProfileLabel( profile ) {
			return plannerText( 'profile-' + profile, profile );
		}

		function setPlannerState( state, message ) {
			if ( ! $planner.length ) {
				return;
			}
			$planner.removeClass( 'is-idle is-requesting is-calculating is-success is-error' ).addClass( 'is-' + state );
			$routeStatus.text( message || '' );
		}

		function setPlannerBusy( busy ) {
			$routeButton.prop( 'disabled', busy || ! mapLoaded );
			$routeProfile.prop( 'disabled', busy );
		}

		function setButtonLabel( hasRoute ) {
			$routeButtonText.text( hasRoute ? plannerText( 'button-update', 'Actualizar ruta' ) : plannerText( 'button-calculate', 'Calcular ruta desde mi ubicación' ) );
		}

		function emptyRouteData() {
			return {
				type: 'FeatureCollection',
				features: []
			};
		}

		function clearRoute() {
			if ( originMarker ) {
				originMarker.remove();
				originMarker = null;
			}

			if ( map && map.getSource( routeSourceId ) ) {
				map.getSource( routeSourceId ).setData( emptyRouteData() );
			}

			routeBounds = null;
			$routeSummary.prop( 'hidden', true );
			$routeFit.prop( 'hidden', true );
			setButtonLabel( false );
		}

		function formatDistance( metres ) {
			if ( 1000 > metres ) {
				return Math.round( metres ) + ' m';
			}
			return ( metres / 1000 ).toLocaleString( undefined, { maximumFractionDigits: 1, minimumFractionDigits: 1 } ) + ' km';
		}

		function formatDuration( seconds ) {
			var minutes = Math.max( 1, Math.round( seconds / 60 ) );
			var hours = Math.floor( minutes / 60 );
			var remaining = minutes % 60;

			if ( 0 === hours ) {
				return minutes + ' min';
			}
			return hours + ' h' + ( remaining ? ' ' + remaining + ' min' : '' );
		}

		function fitRoute() {
			if ( ! map || ! routeBounds || routeBounds.isEmpty() ) {
				return;
			}

			map.fitBounds( routeBounds, {
				padding: window.innerWidth <= 480 ? 42 : 70,
				maxZoom: 16,
				duration: 700
			} );
		}

		function addRouteLayers( geometry ) {
			var feature = {
				type: 'Feature',
				properties: {},
				geometry: geometry
			};

			if ( map.getSource( routeSourceId ) ) {
				map.getSource( routeSourceId ).setData( feature );
			} else {
				map.addSource( routeSourceId, {
					type: 'geojson',
					data: feature
				} );
			}

			if ( ! map.getLayer( routeOutlineLayerId ) ) {
				map.addLayer( {
					id: routeOutlineLayerId,
					type: 'line',
					source: routeSourceId,
					layout: {
						'line-join': 'round',
						'line-cap': 'round'
					},
					paint: {
						'line-color': '#ffffff',
						'line-width': 8,
						'line-opacity': 0.92
					}
				} );
			}

			if ( ! map.getLayer( routeLayerId ) ) {
				map.addLayer( {
					id: routeLayerId,
					type: 'line',
					source: routeSourceId,
					layout: {
						'line-join': 'round',
						'line-cap': 'round'
					},
					paint: {
						'line-color': '#2872fa',
						'line-width': 5,
						'line-opacity': 0.95
					}
				} );
			}
		}

		function renderRoute( origin, route, profile ) {
			var originElement = document.createElement( 'span' );
			var originLabel = plannerText( 'origin-label', 'Tu ubicación al calcular' );
			originElement.className = 'lclplt-route-origin-marker';
			originElement.setAttribute( 'role', 'img' );
			originElement.setAttribute( 'aria-label', originLabel );
			originElement.setAttribute( 'title', originLabel );

			originMarker = new mapboxgl.Marker( { element: originElement } )
				.setLngLat( origin )
				.setPopup( new mapboxgl.Popup( { offset: 22 } ).setText( originLabel ) )
				.addTo( map );

			addRouteLayers( route.geometry );

			routeBounds = new mapboxgl.LngLatBounds();
			route.geometry.coordinates.forEach( function( coordinate ) {
				routeBounds.extend( coordinate );
			} );
			routeBounds.extend( destination );
			routeBounds.extend( origin );
			fitRoute();

			$routeSummary.find( '[data-lclplt-route-distance]' ).text( formatDistance( route.distance ) );
			$routeSummary.find( '[data-lclplt-route-duration]' ).text( formatDuration( route.duration ) );
			$routeSummary.find( '[data-lclplt-route-profile-label]' ).text( getProfileLabel( profile ) );
			$routeSummary.prop( 'hidden', false );
			$routeFit.prop( 'hidden', false );
			setButtonLabel( true );
		}

		function capturePosition() {
			return new Promise( function( resolve, reject ) {
				if ( ! navigator.geolocation ) {
					reject( routeError( 'unavailable' ) );
					return;
				}

				navigator.geolocation.getCurrentPosition( resolve, function( error ) {
					if ( 1 === error.code ) {
						reject( routeError( 'permission' ) );
					} else if ( 3 === error.code ) {
						reject( routeError( 'timeout' ) );
					} else {
						reject( routeError( 'unavailable' ) );
					}
				}, {
					enableHighAccuracy: true,
					timeout: 15000,
					maximumAge: 0
				} );
			} );
		}

		function fetchRoute( origin, profile, signal ) {
			var coordinatePath = origin[0] + ',' + origin[1] + ';' + destination[0] + ',' + destination[1];
			var url = new URL( 'https://api.mapbox.com/directions/v5/mapbox/' + profile + '/' + coordinatePath );
			url.searchParams.set( 'alternatives', 'false' );
			url.searchParams.set( 'geometries', 'geojson' );
			url.searchParams.set( 'overview', 'full' );
			url.searchParams.set( 'steps', 'false' );
			url.searchParams.set( 'language', plannerText( 'language', 'es' ) );
			url.searchParams.set( 'access_token', token );

			return fetch( url.toString(), {
				method: 'GET',
				signal: signal,
				credentials: 'omit',
				referrerPolicy: 'strict-origin-when-cross-origin'
			} ).then( function( response ) {
				if ( 401 === response.status || 403 === response.status ) {
					throw routeError( 'auth' );
				}
				if ( 429 === response.status ) {
					throw routeError( 'rate' );
				}
				if ( ! response.ok ) {
					throw routeError( 'network' );
				}
				return response.json();
			} ).then( function( payload ) {
				if ( 'NoRoute' === payload.code ) {
					throw routeError( 'no-route' );
				}
				if ( 'NoSegment' === payload.code ) {
					throw routeError( 'no-segment' );
				}
				if ( 'Ok' !== payload.code || ! Array.isArray( payload.routes ) || ! payload.routes.length ) {
					throw routeError( 'invalid' );
				}

				var route = payload.routes[0];
				if ( ! route.geometry || 'LineString' !== route.geometry.type ||
					! Array.isArray( route.geometry.coordinates ) || 2 > route.geometry.coordinates.length ||
					! route.geometry.coordinates.every( isCoordinate ) ||
					! Number.isFinite( Number( route.distance ) ) || 0 > Number( route.distance ) ||
					! Number.isFinite( Number( route.duration ) ) || 0 > Number( route.duration ) ) {
					throw routeError( 'invalid' );
				}

				return {
					geometry: route.geometry,
					distance: Number( route.distance ),
					duration: Number( route.duration )
				};
			} );
		}

		function errorMessage( code ) {
			var messages = {
				permission: plannerText( 'message-permission', 'Permiso de ubicación denegado.' ),
				unavailable: plannerText( 'message-unavailable', 'No se pudo obtener tu ubicación.' ),
				timeout: plannerText( 'message-timeout', 'La ubicación tardó demasiado.' ),
				'no-route': plannerText( 'message-no-route', 'No se encontró una ruta.' ),
				'no-segment': plannerText( 'message-no-segment', 'No se encontró una vía cercana.' ),
				auth: plannerText( 'message-auth', 'El token no está autorizado.' ),
				rate: plannerText( 'message-rate', 'Se alcanzó el límite de solicitudes.' ),
				invalid: plannerText( 'message-invalid', 'La ruta no es válida.' ),
				map: plannerText( 'message-map', 'El mapa no está disponible.' ),
				network: plannerText( 'message-network', 'No se pudo conectar con Mapbox.' )
			};
			return messages[ code ] || messages.network;
		}

		function calculateRoute() {
			if ( ! mapLoaded || $routeButton.prop( 'disabled' ) ) {
				return;
			}

			var serial = ++requestSerial;
			var profile = getProfile();
			clearRoute();
			setPlannerBusy( true );
			setPlannerState( 'requesting', plannerText( 'message-gps', 'Solicitando tu ubicación actual…' ) );

			if ( requestController ) {
				requestController.abort();
			}
			requestController = new AbortController();

			capturePosition().then( function( position ) {
				if ( serial !== requestSerial ) {
					throw routeError( 'aborted' );
				}

				var origin = [ Number( position.coords.longitude ), Number( position.coords.latitude ) ];
				if ( ! isCoordinate( origin ) ) {
					throw routeError( 'unavailable' );
				}

				setPlannerState( 'calculating', plannerText( 'message-calculating', 'Calculando la ruta…' ) );
				return fetchRoute( origin, profile, requestController.signal ).then( function( route ) {
					return { origin: origin, route: route };
				} );
			} ).then( function( result ) {
				if ( serial !== requestSerial ) {
					return;
				}
				renderRoute( result.origin, result.route, profile );
				setPlannerState( 'success', plannerText( 'message-success', 'Ruta calculada.' ) );
			} ).catch( function( error ) {
				if ( 'AbortError' === error.name || 'aborted' === error.lclpltCode || serial !== requestSerial ) {
					return;
				}
				clearRoute();
				setPlannerState( 'error', errorMessage( error.lclpltCode || 'network' ) );
			} ).finally( function() {
				if ( serial === requestSerial ) {
					requestController = null;
					setPlannerBusy( false );
				}
			} );
		}

		function showMapError() {
			$mapEl.removeClass( 'is-loading is-ready' ).addClass( 'is-error' );
			$mapStatus.text( errorLabel );
			mapLoaded = false;
			setPlannerBusy( false );
			setPlannerState( 'error', errorMessage( 'map' ) );
		}

		function markMapReady() {
			if ( mapLoaded || ! map || ! map.isStyleLoaded() ) {
				return;
			}

			mapLoaded = true;
			$mapEl.removeClass( 'is-loading is-error' ).addClass( 'is-ready' );
			$mapStatus.text( '' );
			map.resize();
			setPlannerBusy( false );
			setPlannerState( 'idle', plannerText( 'message-ready', 'El mapa está listo.' ) );
		}

		if ( ! isCoordinate( destination ) || ! token ) {
			showMapError();
			return;
		}

		if ( $planner.length ) {
			$routeButton.on( 'click', calculateRoute );
			$routeFit.on( 'click', fitRoute );
			$routeProfile.on( 'change', function() {
				requestSerial++;
				if ( requestController ) {
					requestController.abort();
					requestController = null;
				}
				clearRoute();
				setPlannerBusy( false );
				setPlannerState( 'idle', plannerText( 'message-profile', 'Perfil actualizado. Calcula una nueva ruta.' ) );
			} );
		}

		mapboxgl.accessToken = token;
		$mapEl.addClass( 'is-loading' );
		$mapStatus.text( loadingLabel );

		try {
			map = new mapboxgl.Map( {
				container: 'lclplt-map',
				style: 'mapbox://styles/mapbox/' + style,
				center: destination,
				zoom: zoom
			} );

			map.addControl( new mapboxgl.NavigationControl(), 'top-right' );

			var popup = new mapboxgl.Popup( { offset: 24 } ).setText( destinationLabel );
			var marker = new mapboxgl.Marker()
				.setLngLat( destination )
				.setPopup( popup )
				.addTo( map );

			marker.getElement().setAttribute( 'aria-label', destinationLabel );
			marker.getElement().setAttribute( 'title', destinationLabel );

			map.on( 'styledata', markMapReady );
			map.once( 'load', markMapReady );

			map.on( 'error', function() {
				if ( ! mapLoaded ) {
					showMapError();
				}
			} );
		} catch ( error ) {
			showMapError();
		}
	} );

})( jQuery );
