<?php
/**
 * Mapbox Geocoding API client.
 *
 * Wraps the Mapbox Geocoding API (v5) via the WordPress HTTP API.
 * No frontend SDK tokens are handled here — only server-side requests.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/maps
 */

/**
 * Mapbox Geocoding API client.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/maps
 */
class Localpilot_Mapbox_Client {

	/**
	 * Base URL for the Mapbox Geocoding API.
	 */
	const API_BASE = 'https://api.mapbox.com/geocoding/v5/mapbox.places/';

	/**
	 * Access token.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Country code (ISO 3166-1 alpha-2) to bias results.
	 *
	 * @var string
	 */
	private $country;

	/**
	 * Language tag (BCP 47) for results.
	 *
	 * @var string
	 */
	private $language;

	/**
	 * Constructor.
	 *
	 * @param string $token    Mapbox access token.
	 * @param string $country  Optional. Country code for biasing.
	 * @param string $language Optional. Language tag.
	 */
	public function __construct( $token, $country = '', $language = 'es' ) {
		$this->token    = $token;
		$this->country  = $country;
		$this->language = $language;
	}

	/**
	 * Geocode a free-form address string.
	 *
	 * @param string $address Address to geocode.
	 * @return array|WP_Error {
	 *     Success response with keys:
	 *     @type float  $latitude           Latitude.
	 *     @type float  $longitude          Longitude.
	 *     @type string $place_id           Mapbox place ID.
	 *     @type string $formatted_address  Formatted address from Mapbox.
	 *     @type float  $relevance          Relevance score (0-1).
	 * }
	 */
	public function geocode( $address ) {
		if ( empty( $this->token ) ) {
			return new WP_Error(
				'lclplt_mapbox_no_token',
				__( 'Mapbox access token is not configured.', 'localpilot' )
			);
		}

		$address = trim( $address );
		if ( empty( $address ) ) {
			return new WP_Error(
				'lclplt_mapbox_empty_address',
				__( 'Cannot geocode an empty address.', 'localpilot' )
			);
		}

		$query = array(
			'access_token' => $this->token,
			'limit'        => 1,
		);

		if ( ! empty( $this->country ) ) {
			$query['country'] = $this->country;
		}

		if ( ! empty( $this->language ) ) {
			$query['language'] = $this->language;
		}

		$encoded = rawurlencode( $address );
		$url     = self::API_BASE . $encoded . '.json?' . http_build_query( $query );

		$response = wp_remote_get( $url, array(
			'timeout'  => 10,
			'headers'  => array(
				'Accept' => 'application/json',
			),
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'lclplt_mapbox_http_error',
				sprintf(
					/* translators: %s: error message from HTTP API */
					__( 'Mapbox HTTP error: %s', 'localpilot' ),
					$response->get_error_message()
				)
			);
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body        = wp_remote_retrieve_body( $response );
		$data        = json_decode( $body, true );

		if ( 200 !== $status_code || ! $data || ! isset( $data['features'] ) ) {
			$error_msg = isset( $data['message'] ) ? $data['message'] : sprintf(
				/* translators: %d: HTTP status code */
				__( 'Mapbox returned HTTP %d', 'localpilot' ),
				$status_code
			);
			return new WP_Error(
				'lclplt_mapbox_api_error',
				sprintf(
					/* translators: %s: error message from Mapbox */
					__( 'Mapbox API error: %s', 'localpilot' ),
					$error_msg
				)
			);
		}

		if ( empty( $data['features'] ) ) {
			return new WP_Error(
				'lclplt_mapbox_no_results',
				__( 'No geocoding results found for the address.', 'localpilot' )
			);
		}

		$feature = $data['features'][0];
		$coords  = $feature['geometry']['coordinates'];

		return array(
			'longitude'         => (float) $coords[0],
			'latitude'          => (float) $coords[1],
			'place_id'          => isset( $feature['id'] ) ? sanitize_text_field( $feature['id'] ) : '',
			'formatted_address' => isset( $feature['place_name'] ) ? sanitize_text_field( $feature['place_name'] ) : '',
			'relevance'         => isset( $feature['relevance'] ) ? (float) $feature['relevance'] : 0,
		);
	}
}
