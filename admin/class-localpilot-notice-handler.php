<?php
/**
 * Admin notice handler — delivery action feedback.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * Admin notice callbacks.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */
class Localpilot_Notice_Handler {

	/**
	 * Show admin notice after a successful delivery action redirect.
	 */
	public static function show_delivery_notice() {
		if ( ! function_exists( 'lclplt_is_woocommerce_active' ) || ! lclplt_is_woocommerce_active() ) {
			return;
		}
		$user_id = get_current_user_id();
		$notice  = get_transient( 'lclplt_admin_notice_' . $user_id );
		if ( $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
		}
	}

	/**
	 * Clean the delivery notice transient after display.
	 */
	public static function clean_delivery_notice() {
		$user_id = get_current_user_id();
		delete_transient( 'lclplt_admin_notice_' . $user_id );
	}
}
