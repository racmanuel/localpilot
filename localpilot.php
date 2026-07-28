<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress or ClassicPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://racmanuel.dev/
 * @since             1.0.0
 * @package           Localpilot
 *
 * @wordpress-plugin
 * Plugin Name:       LocalPilot
 * Plugin URI:       https://racmanuel.dev/localpilot/
 * Description:       LocalPilot – Local Delivery Drivers for WooCommerce. Asigna pedidos a repartidores locales, gestiona entregas desde Mi cuenta, registra evidencia con Mapbox y calcula rutas bajo demanda.
 * Version:           1.1.6-dev
 * Author:            racmanuel
 * Requires at least: 6.9
 * Requires PHP:      7.4
 * Tested up to:      7.0.2
 * Author URI:        https://racmanuel.dev/
 * WC requires at least: 10.9
 * WC tested up to: 10.9.4
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       localpilot
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'LOCALPILOT_VERSION', '1.1.6-dev' );

/**
 * CSS framework selected during generation.
 */
define( 'LOCALPILOT_CSS_FRAMEWORK', 'vanilla' );
define( 'LOCALPILOT_CSS_ENQUEUE_LOCATION', 'both' );

/**
 * Define the Plugin basename
 */
define( 'LOCALPILOT_BASE_NAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 *
 * This action is documented in includes/class-localpilot-activator.php
 * Full security checks are performed inside the class.
 */

function lclplt_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-localpilot-activator.php';
	Localpilot_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 *
 * This action is documented in includes/class-localpilot-deactivator.php
 * Full security checks are performed inside the class.
 */
function lclplt_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-localpilot-deactivator.php';
	Localpilot_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'lclplt_activate' );
register_deactivation_hook( __FILE__, 'lclplt_deactivate' );


/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */


require plugin_dir_path( __FILE__ ) . 'includes/class-localpilot.php';

/**
 * Declare compatibility with WooCommerce High-Performance Order Storage (HPOS).
 *
 * Must run before 'before_woocommerce_init' completes.
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
			'custom_order_tables',
			__FILE__,
			true
		);
	}
} );

/**
 * Check if WooCommerce is active and show an admin notice if not.
 *
 * Supports single-site and multisite (network-activated) installations.
 *
 * @return bool
 */
function lclplt_is_woocommerce_active() {
	if ( class_exists( 'WooCommerce' ) ) {
		return true;
	}

	if ( in_array(
		'woocommerce/woocommerce.php',
		(array) get_option( 'active_plugins', array() ),
		true
	) ) {
		return true;
	}

	if ( is_multisite() ) {
		$plugins = get_site_option( 'active_sitewide_plugins', array() );
		if ( isset( $plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		}
	}

	return false;
}

if ( ! lclplt_is_woocommerce_active() ) {
	add_action( 'admin_notices', function () {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$message = sprintf(
			/* translators: %s: plugin name */
			esc_html__( '%1$s requiere WooCommerce activo para funcionar. Instala y activa WooCommerce primero.', 'localpilot' ),
			'<strong>LocalPilot – Local Delivery Drivers for WooCommerce</strong>'
		);
		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} );
	return;
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function lclplt_run() {

	$plugin = new Localpilot();
	$plugin->run();

}
lclplt_run();

/**
 * Add a Settings link to the plugin action row.
 *
 * @param array  $links Plugin action links.
 * @param string $file  Plugin basename.
 * @return array
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	if ( ! lclplt_is_woocommerce_active() ) {
		return $links;
	}
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'admin.php?page=wc-settings&tab=localpilot' ) ),
		esc_html__( 'Ajustes', 'localpilot' )
	);
	array_unshift( $links, $settings_link );
	return $links;
} );
