<?php
/**
 * Schema upgrade handler — triggered on admin_init.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * Schema upgrade callback.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */
class Localpilot_Schema_Handler {

	/**
	 * Run schema upgrade if the stored version is behind.
	 */
	public static function maybe_upgrade() {
		if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
			return;
		}
		Localpilot_DB_Schema::maybe_upgrade();
	}
}
