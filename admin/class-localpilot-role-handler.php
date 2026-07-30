<?php
/**
 * Driver role handler — registers the role on admin_init.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * Driver role registration callback.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */
class Localpilot_Role_Handler {

	/**
	 * Register the driver role and capabilities idempotently.
	 */
	public static function register() {
		if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
			return;
		}
		Localpilot_Driver_Role::register();
	}
}
