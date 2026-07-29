<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two hooks to
 * enqueue the public-facing stylesheet and JavaScript.
 * As you add hooks and methods, update this description.
 *
 * @package    Localpilot
 * @subpackage Localpilot/public
 * @author     racmanuel <developer@racmanuel.dev>
 */
class Localpilot_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The unique prefix of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_prefix    The string used to uniquely prefix technical functions of this plugin.
	 */
	private $plugin_prefix;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name      The name of the plugin.
	 * @param      string $plugin_prefix          The unique prefix of this plugin.
	 * @param      string $version          The version of this plugin.
	 */
	public function __construct( $plugin_name, $plugin_prefix, $version ) {

		$this->plugin_name   = $plugin_name;
		$this->plugin_prefix = $plugin_prefix;
		$this->version = $version;

	}

	/**
	 * Check if we are on the LocalPilot My Account endpoint.
	 *
	 * @return bool
	 */
	private function is_my_account_endpoint() {
		global $wp_query;
		return isset( $wp_query->query_vars['mis-entregas'] ) || isset( $wp_query->query_vars['lclplt_deliveries'] );
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if ( ! $this->is_my_account_endpoint() ) {
			return;
		}

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/localpilot-public.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'dashicons' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! $this->is_my_account_endpoint() ) {
			return;
		}

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/localpilot-public.js', array( 'jquery' ), $this->version, true );

	}

	/**
	 * Enqueue Mapbox GL JS and custom map script on the delivery detail page.
	 *
	 * Only loads when viewing a single delivery with valid coordinates.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_mapbox_scripts() {
		if ( 'yes' !== get_option( 'lclplt_enable_mapbox', 'no' ) ) {
			return;
		}

		if ( ! $this->is_my_account_endpoint() ) {
			return;
		}

		$assignment_id = (int) get_query_var( 'mis-entregas', 0 );
		if ( $assignment_id <= 0 ) {
			return;
		}

		$assignment = Localpilot_Assignment_Repository::get( $assignment_id );
		if ( ! $assignment || (int) $assignment->driver_id !== get_current_user_id() ) {
			return;
		}

		if ( ! current_user_can( Localpilot_Capabilities::VIEW_ASSIGNED_DELIVERIES ) ) {
			return;
		}

		$order = wc_get_order( $assignment->order_id );
		if ( ! $order ) {
			return;
		}

		$meta  = new Localpilot_Order_Delivery_Meta( $order );
		$lat   = $meta->get_latitude();
		$lng   = $meta->get_longitude();

		if ( empty( $lat ) || empty( $lng ) ) {
			return;
		}

		$token = get_option( 'lclplt_mapbox_token', '' );
		if ( empty( $token ) ) {
			return;
		}

		// Mapbox GL JS — from CDN.
		wp_enqueue_style( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.css', array(), '3.9.4' );
		wp_enqueue_script( 'mapbox-gl', 'https://api.mapbox.com/mapbox-gl-js/v3.9.4/mapbox-gl.js', array(), '3.9.4', true );

		// Our custom map init script.
		wp_enqueue_script(
			$this->plugin_name . '-mapbox',
			plugin_dir_url( __FILE__ ) . 'js/localpilot-mapbox.js',
			array( 'jquery', 'mapbox-gl' ),
			$this->version,
			true
		);
	}

	/**
	 * Example of Shortcode processing function.
	 *
	 * Shortcode can take attributes like [localpilot-shortcode attribute='123']
	 * Shortcodes can be enclosing content [localpilot-shortcode attribute='123']custom content[/localpilot-shortcode].
	 *
	 * @see https://developer.wordpress.org/plugins/shortcodes/enclosing-shortcodes/
	 *
	 * @since    1.0.0
	 * @param    array  $atts    ShortCode Attributes.
	 * @param    mixed  $content ShortCode enclosed content.
	 * @param    string $tag    The Shortcode tag.
	 */
	public function lclplt_shortcode_func( $atts ) {

		/**
		 * Combine user attributes with known attributes.
		 *
		 * @see https://developer.wordpress.org/reference/functions/shortcode_atts/
		 *
		 * Pass third paramter $shortcode to enable ShortCode Attribute Filtering.
		 * @see https://developer.wordpress.org/reference/hooks/shortcode_atts_shortcode/
		 */
		$atts = shortcode_atts(
			array(
				'attribute' => 123,
			),
			$atts,
			$this->plugin_prefix . 'shortcode'
		);

		/**
		 * Build our ShortCode output.
		 * Remember to sanitize all user input.
		 * In this case, we expect a integer value to be passed to the ShortCode attribute.
		 *
		 * @see https://developer.wordpress.org/themes/theme-security/data-sanitization-escaping/
		 */
		$out = intval( $atts['attribute'] );

		/**
		 * If the shortcode is enclosing, we may want to do something with $content
		 */
		if ( ! is_null( $content ) && ! empty( $content ) ) {
			$out = do_shortcode( $content );// We can parse shortcodes inside $content.
			$out = intval( $atts['attribute'] ) . ' ' . sanitize_text_field( $out );// Remember to sanitize your user input.
		}

		// ShortCodes are filters and should always return, never echo.
		return $out;

	}

}
