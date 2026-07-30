<?php
/**
 * LocalPilot base email class.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base email class for all LocalPilot notifications.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/emails
 */
class Localpilot_Email extends WC_Email {

	/**
	 * Template path prefix (without .php).
	 *
	 * @var string
	 */
	protected $lclplt_template;

	/**
	 * Order ID passed to trigger().
	 *
	 * @var int
	 */
	public $order_id;

	/**
	 * Extra data passed to trigger().
	 *
	 * @var array
	 */
	public $extra = array();

	/**
	 * Constructor.
	 *
	 * @param string $id            Email ID.
	 * @param array  $params        {
	 *     Optional parameters.
	 *     @type string $title       Email title.
	 *     @type string $description Description.
	 *     @type string $subject     Default subject.
	 *     @type string $heading     Default heading.
	 *     @type string $template    Template slug (without prefix).
	 *     @type string $recipient   Default recipient.
	 * }
	 */
	public function __construct( $id, $params = array() ) {
		$this->id             = $id;
		$this->customer_email = false;
		$this->enabled        = $this->get_option( 'enabled', 'yes' );

		if ( isset( $params['title'] ) ) {
			$this->title = $params['title'];
		}
		if ( isset( $params['description'] ) ) {
			$this->description = $params['description'];
		}
		if ( isset( $params['subject'] ) ) {
			$this->subject = $this->get_option( 'subject', $params['subject'] );
		}
		if ( isset( $params['heading'] ) ) {
			$this->heading = $this->get_option( 'heading', $params['heading'] );
		}
		if ( isset( $params['template'] ) ) {
			$this->lclplt_template = $params['template'];
			$this->template_html   = 'emails/' . $params['template'] . '.php';
			$this->template_plain  = 'emails/plain/' . $params['template'] . '.php';
		}
		if ( isset( $params['recipient'] ) ) {
			$this->recipient = $params['recipient'];
		}

		// Use our plugin's template directory as fallback.
		$this->template_base = plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/';

		parent::__construct();
	}

	/**
	 * Trigger the email.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $extra    Extra data.
	 */
	public function trigger( $order_id, $extra = array() ) {
		$this->order_id = $order_id;
		$this->extra    = $extra;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$this->placeholders['{order_number}'] = $order->get_order_number();
		$this->placeholders['{order_date}']   = wc_format_datetime( $order->get_date_created() );

		if ( $this->is_enabled() && $this->get_recipient() ) {
			$this->send(
				$this->get_recipient(),
				$this->get_subject(),
				$this->get_content(),
				$this->get_headers(),
				$this->get_attachments()
			);
		}
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'email'    => $this,
				'order'    => wc_get_order( $this->order_id ),
				'order_id' => $this->order_id,
				'extra'    => $this->extra,
				'sent_to_admin' => false,
				'plain_text'    => false,
				'email_heading' => $this->get_heading(),
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Get content plain.
	 *
	 * @return string
	 */
	public function get_content_plain() {
		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'email'    => $this,
				'order'    => wc_get_order( $this->order_id ),
				'order_id' => $this->order_id,
				'extra'    => $this->extra,
				'sent_to_admin' => false,
				'plain_text'    => true,
				'email_heading' => $this->get_heading(),
			),
			'',
			$this->template_base
		);
		return ob_get_clean();
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'   => __( 'Habilitar', 'localpilot' ),
				'type'    => 'checkbox',
				'label'   => __( 'Habilitar esta notificación', 'localpilot' ),
				'default' => 'yes',
			),
			'subject' => array(
				'title'       => __( 'Asunto', 'localpilot' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Variables disponibles: %s.', 'localpilot' ), '<code>{order_number}</code>, <code>{order_date}</code>' ),
				'placeholder' => $this->subject,
				'default'     => '',
			),
			'heading' => array(
				'title'       => __( 'Encabezado', 'localpilot' ),
				'type'        => 'text',
				'description' => sprintf( __( 'Variables disponibles: %s.', 'localpilot' ), '<code>{order_number}</code>, <code>{order_date}</code>' ),
				'placeholder' => $this->heading,
				'default'     => '',
			),
			'recipient' => array(
				'title'       => __( 'Destinatario', 'localpilot' ),
				'type'        => 'text',
				'description' => __( 'Correo(s) separados por coma. Vacío usa el valor por defecto.', 'localpilot' ),
				'placeholder' => $this->recipient,
				'default'     => '',
			),
		);
	}

	/**
	 * Get the From name for the email.
	 *
	 * @param string $from_name Optional from name.
	 * @return string
	 */
	public function get_from_name( $from_name = '' ) {
		$name = get_option( 'woocommerce_email_from_name' );
		return $name ? $name : get_bloginfo( 'name' );
	}

	/**
	 * Get the From address for the email.
	 *
	 * @param string $from_address Optional from address.
	 * @return string
	 */
	public function get_from_address( $from_address = '' ) {
		$address = get_option( 'woocommerce_email_from_address' );
		return $address ? $address : get_option( 'admin_email' );
	}

	/**
	 * Register all LocalPilot email classes with WooCommerce.
	 *
	 * @param array $emails Existing email classes.
	 * @return array
	 */
	public static function register_emails( $emails ) {
		$emails['lclplt_email_assigned']   = new Localpilot_Email_Assigned();
		$emails['lclplt_email_unassigned'] = new Localpilot_Email_Unassigned();
		$emails['lclplt_email_started']    = new Localpilot_Email_Started();
		$emails['lclplt_email_completed']  = new Localpilot_Email_Completed();
		$emails['lclplt_email_failed']     = new Localpilot_Email_Failed();
		return $emails;
	}

	/**
	 * Get a WooCommerce email instance and trigger it.
	 *
	 * @param string $email_id Email ID.
	 * @param int    $order_id Order ID.
	 * @param array  $extra    Extra data.
	 */
	protected static function trigger_static( $email_id, $order_id, $extra = array() ) {
		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails[ $email_id ] ) ) {
			$emails[ $email_id ]->trigger( $order_id, $extra );
		}
	}

	/**
	 * Domain event for reassignment — notifies old and new driver.
	 *
	 * @param int $order_id         Order ID.
	 * @param int $old_driver_id    Previous driver ID.
	 * @param int $new_driver_id    New driver ID.
	 * @param int $new_assignment_id New assignment ID.
	 * @param int $event_id         Event ID.
	 */
	public static function on_delivery_reassigned( $order_id, $old_driver_id, $new_driver_id, $new_assignment_id, $event_id ) {
		Localpilot_Email_Unassigned::on_delivery_unassigned( $order_id, $old_driver_id, $event_id );

		$new_driver = get_userdata( $new_driver_id );
		if ( $new_driver && ! empty( $new_driver->user_email ) ) {
			self::trigger_static( 'lclplt_email_assigned', $order_id, array(
				'driver_email'  => $new_driver->user_email,
				'driver_name'   => $new_driver->display_name,
				'assignment_id' => $new_assignment_id,
				'event_id'      => $event_id,
			) );
		}
	}
}
