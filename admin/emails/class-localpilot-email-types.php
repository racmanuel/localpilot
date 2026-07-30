<?php
/**
 * Concrete LocalPilot email types.
 *
 * WooCommerce identifies emails by PHP class when generating a preview. These
 * thin classes preserve the shared Localpilot_Email implementation while giving
 * each notification a unique, previewable type.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin/emails
 */

defined( 'ABSPATH' ) || exit;

class Localpilot_Email_Assigned extends Localpilot_Email {}
class Localpilot_Email_Unassigned extends Localpilot_Email {}
class Localpilot_Email_Started extends Localpilot_Email {}
class Localpilot_Email_Completed extends Localpilot_Email {}
class Localpilot_Email_Failed extends Localpilot_Email {}
