<?php
/**
 * LocalPilot base email class.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin/emails
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base email class for all LocalPilot notifications.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin/emails
 */
class Localpilot_Email extends WC_Email {

	/**
	 * Template path prefix (without .php).
	 *
	 * @var string
	 */
	protected $lclplt_template;

	/**
	 * How to resolve the recipient: 'driver', 'customer', 'admin', or 'custom'.
	 *
	 * @var string
	 */
	protected $recipient_type = 'admin';

	/**
	 * Optional option name to check before sending (e.g. lclplt_email_started_enabled).
	 *
	 * @var string
	 */
	protected $option_enabled = '';

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
	 *     @type string $title          Email title.
	 *     @type string $description    Description.
	 *     @type string $subject        Default subject.
	 *     @type string $heading        Default heading.
	 *     @type string $template       Template slug (without prefix).
	 *     @type string $recipient      Default recipient.
	 *     @type string $recipient_type 'driver', 'customer', 'admin', or 'custom'.
	 *     @type string $option_enabled Extra option to check before sending.
	 *     @type bool   $customer_email Whether this is sent to the customer.
	 * }
	 */
	public function __construct( $id, $params = array() ) {
		$this->id             = $id;
		$this->customer_email = ! empty( $params['customer_email'] );
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
		if ( isset( $params['recipient_type'] ) ) {
			$this->recipient_type = $params['recipient_type'];
		}
		if ( isset( $params['option_enabled'] ) ) {
			$this->option_enabled = $params['option_enabled'];
		}

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

		// Resolve recipient.
		switch ( $this->recipient_type ) {
			case 'driver':
				if ( ! empty( $extra['driver_email'] ) ) {
					$this->recipient = $extra['driver_email'];
				} else {
					$assignment = Localpilot_Assignment_Repository::get_active_by_order( $order_id );
					if ( $assignment && $assignment->driver_id ) {
						$driver = get_userdata( $assignment->driver_id );
						if ( $driver ) {
							$this->recipient = $driver->user_email;
						}
					}
				}
				break;

			case 'customer':
				$this->recipient = $order->get_billing_email();
				break;

			case 'admin':
				if ( ! $this->get_recipient() ) {
					$this->recipient = get_option( 'admin_email' );
				}
				break;
		}

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
	 * Check if the email is enabled, considering an extra option if set.
	 */
	public function is_enabled() {
		if ( $this->option_enabled && 'yes' !== get_option( $this->option_enabled, 'yes' ) ) {
			return false;
		}
		return parent::is_enabled();
	}

	/**
	 * Get content HTML.
	 *
	 * @return string
	 */
	public function get_content_html() {
		$order = wc_get_order( $this->order_id );
		if ( ! $order ) {
			return '<p>' . esc_html__( 'Para previsualizar este correo, asigna un pedido a un repartidor.', 'localpilot' ) . '</p>';
		}

		ob_start();
		wc_get_template(
			$this->template_html,
			array(
				'email'    => $this,
				'order'    => $order,
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
		$order = wc_get_order( $this->order_id );
		if ( ! $order ) {
			return esc_html__( 'Para previsualizar este correo, asigna un pedido a un repartidor.', 'localpilot' );
		}

		ob_start();
		wc_get_template(
			$this->template_plain,
			array(
				'email'    => $this,
				'order'    => $order,
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
	 * Trigger a LocalPilot email by ID.
	 *
	 * @param string $email_id Email ID (e.g. lclplt_email_assigned).
	 * @param int    $order_id Order ID.
	 * @param array  $extra    Extra data passed to trigger().
	 */
	public static function trigger_email( $email_id, $order_id, $extra = array() ) {
		$emails = WC_Emails::instance()->get_emails();
		if ( isset( $emails[ $email_id ] ) ) {
			$emails[ $email_id ]->trigger( $order_id, $extra );
		}
	}
}
