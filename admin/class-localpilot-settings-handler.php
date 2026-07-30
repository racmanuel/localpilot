<?php
/**
 * Settings page handler — lazy-loads the WC_Settings_Page subclass.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * Settings page registration callback.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */
class Localpilot_Settings_Handler {

	/**
	 * Register the LocalPilot settings page with WooCommerce.
	 *
	 * @param array $pages Existing settings pages.
	 * @return array
	 */
	public static function register_settings_page( $pages ) {
		require_once plugin_dir_path( __FILE__ ) . 'class-localpilot-settings.php';
		$pages[] = new Localpilot_Settings();
		return $pages;
	}
}
