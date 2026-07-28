<?php
/**
 * LocalPilot — WooCommerce Settings page.
 *
 * Registers a native WooCommerce settings tab with sections for
 * delivery flow configuration, general options, and data/privacy.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */

/**
 * LocalPilot settings tab for WooCommerce.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/integrations
 */
class Localpilot_Settings extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'localpilot';
		$this->label = __( 'LocalPilot', 'localpilot' );

		parent::__construct();
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			''           => __( 'General', 'localpilot' ),
			'flow'       => __( 'Flujo de entrega', 'localpilot' ),
			'mapbox'     => __( 'Mapbox', 'localpilot' ),
			'data'       => __( 'Datos y privacidad', 'localpilot' ),
		);
	}

	/**
	 * Get settings for the default (General) section.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'LocalPilot — General', 'localpilot' ),
				'type'  => 'title',
				'desc'  => $this->section_description_general(),
				'id'    => 'lclplt_general_options',
			),

			array(
				'title'    => __( 'Estados de pedido elegibles', 'localpilot' ),
				'desc'     => __( 'Pedidos en estos estados pueden asignarse a un repartidor. Los estados no seleccionados no aparecerán como disponibles para asignación.', 'localpilot' ),
				'desc_tip' => __( 'Selecciona uno o más estados de WooCommerce. Por defecto: Processing y On-hold.', 'localpilot' ),
				'id'       => 'lclplt_eligible_order_statuses',
				'type'     => 'multiselect',
				'class'    => 'wc-enhanced-select',
				'default'  => array( 'processing', 'on-hold' ),
				'options'  => $this->get_order_status_options(),
			),

			array(
				'title'    => __( 'Mostrar total del pedido', 'localpilot' ),
				'desc'     => __( 'Mostrar el monto total del pedido al repartidor en la pantalla de Mis entregas.', 'localpilot' ),
				'desc_tip' => __( 'Actívalo solo si los repartidores deben conocer el valor de lo que entregan.', 'localpilot' ),
				'id'       => 'lclplt_show_order_total',
				'type'     => 'checkbox',
				'default'  => 'no',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'lclplt_general_options',
			),
		);
	}

	/**
	 * Get settings for the "Flujo de entrega" section.
	 *
	 * @return array
	 */
	protected function get_settings_for_flow_section() {
		return array(
			array(
				'title' => __( 'LocalPilot — Flujo de entrega', 'localpilot' ),
				'type'  => 'title',
				'desc'  => $this->section_description_flow(),
				'id'    => 'lclplt_flow_options',
			),

			array(
				'title'    => __( 'Aceptación obligatoria', 'localpilot' ),
				'desc'     => __( 'El repartidor debe aceptar la entrega antes de poder iniciar el reparto. Si se desactiva, puede pasar directamente de Asignado a En reparto.', 'localpilot' ),
				'id'       => 'lclplt_require_acceptance',
				'type'     => 'checkbox',
				'default'  => 'yes',
			),

			array(
				'title'    => __( 'Receptor obligatorio', 'localpilot' ),
				'desc'     => __( 'Exigir el nombre de quien recibe el pedido para poder completar la entrega.', 'localpilot' ),
				'id'       => 'lclplt_require_received_by',
				'type'     => 'checkbox',
				'default'  => 'yes',
			),

			array(
				'title'    => __( 'Evidencia obligatoria', 'localpilot' ),
				'desc'     => __( 'Exigir una foto o archivo de evidencia para poder completar la entrega.', 'localpilot' ),
				'desc_tip' => __( 'Requiere que el servidor permita subida de archivos. Se validan formato (JPG, PNG, WebP) y tamaño máximo configurado.', 'localpilot' ),
				'id'       => 'lclplt_require_proof',
				'type'     => 'checkbox',
				'default'  => 'yes',
			),

			array(
				'title'    => __( 'Tamaño máximo de evidencia', 'localpilot' ),
				'desc'     => __( 'Tamaño máximo en megabytes (MB) para cada archivo de evidencia subido.', 'localpilot' ),
				'desc_tip' => __( 'Valor predeterminado: 5 MB. Límite recomendado: 10 MB.', 'localpilot' ),
				'id'       => 'lclplt_max_proof_size',
				'type'     => 'number',
				'default'  => 5,
				'custom_attributes' => array(
					'min'  => 1,
					'max'  => 50,
					'step' => 1,
				),
			),

			array(
				'title'    => __( 'Estado al completar entrega', 'localpilot' ),
				'desc'     => __( 'Estado de WooCommerce que se asignará al pedido cuando la entrega se marque como completada.', 'localpilot' ),
				'desc_tip' => __( 'Predeterminado: Completed. El pedido avanzará a este estado automáticamente.', 'localpilot' ),
				'id'       => 'lclplt_completed_order_status',
				'type'     => 'select',
				'default'  => 'completed',
				'options'  => $this->get_order_status_options(),
			),

			array(
				'title'    => __( 'Estado al fallar entrega', 'localpilot' ),
				'desc'     => __( 'Estado de WooCommerce que se asignará al pedido cuando la entrega se marque como fallida.', 'localpilot' ),
				'desc_tip' => __( 'Predeterminado: Failed.', 'localpilot' ),
				'id'       => 'lclplt_failed_order_status',
				'type'     => 'select',
				'default'  => 'failed',
				'options'  => $this->get_order_status_options(),
			),

			array(
				'type' => 'sectionend',
				'id'   => 'lclplt_flow_options',
			),
		);
	}

	/**
	 * Get settings for the "Datos y privacidad" section.
	 *
	 * @return array
	 */
	protected function get_settings_for_data_section() {
		return array(
			array(
				'title' => __( 'LocalPilot — Datos y privacidad', 'localpilot' ),
				'type'  => 'title',
				'desc'  => $this->section_description_data(),
				'id'    => 'lclplt_data_options',
			),

			array(
				'title'    => __( 'Eliminar datos al desinstalar', 'localpilot' ),
				'desc'     => __( 'Si está activado, al desinstalar el plugin se eliminarán todas las tablas, eventos, asignaciones, opciones y metadatos de usuario creados por LocalPilot. Por defecto los datos se conservan al desinstalar.', 'localpilot' ),
				'desc_tip' => __( 'Recomendación: mantenlo desactivado a menos que estés seguro de querer eliminar todos los registros de entregas.', 'localpilot' ),
				'id'       => 'lclplt_delete_data_on_uninstall',
				'type'     => 'checkbox',
				'default'  => 'no',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'lclplt_data_options',
			),
		);
	}

	/**
	 * HTML description for the General section.
	 *
	 * @return string
	 */
	private function section_description_general() {
		$desc  = '<p>';
		$desc .= esc_html__( 'Configuración general de LocalPilot. Los ajustes aquí definidos afectan qué pedidos pueden asignarse y qué información ven los repartidores.', 'localpilot' );
		$desc .= '</p><p><strong>';
		$desc .= esc_html__( 'Nota:', 'localpilot' );
		$desc .= '</strong> ';
		$desc .= esc_html__( 'Las configuraciones de Mapbox, correos electrónicos y evidencias se encuentran en sus respectivas secciones una vez que los módulos correspondientes estén activos.', 'localpilot' );
		$desc .= '</p>';
		return $desc;
	}

	/**
	 * HTML description for the Flow section.
	 *
	 * @return string
	 */
	private function section_description_flow() {
		$desc  = '<p>';
		$desc .= esc_html__( 'Controla el comportamiento del flujo de entregas: qué pasos son obligatorios, qué estados de WooCommerce se asignan automáticamente y cómo se comporta el sistema ante cada evento.', 'localpilot' );
		$desc .= '</p><p><strong>';
		$desc .= esc_html__( 'Transiciones disponibles:', 'localpilot' );
		$desc .= '</strong> ';
		$desc .= esc_html__( 'Sin asignar → Asignado → Aceptado (si aplica) → En reparto → Entregado / Fallido / Cancelado.', 'localpilot' );
		$desc .= '</p>';
		return $desc;
	}

	/**
	 * Get settings for the "Mapbox" section.
	 *
	 * @return array
	 */
	protected function get_settings_for_mapbox_section() {
		return array(
			array(
				'title' => __( 'LocalPilot — Mapbox', 'localpilot' ),
				'type'  => 'title',
				'desc'  => $this->section_description_mapbox(),
				'id'    => 'lclplt_mapbox_options',
			),

			array(
				'title'   => __( 'Activar Mapbox', 'localpilot' ),
				'desc'    => __( 'Habilitar integración con Mapbox para geocodificar direcciones y mostrar mapas.', 'localpilot' ),
				'id'      => 'lclplt_enable_mapbox',
				'type'    => 'checkbox',
				'default' => 'no',
			),

			array(
				'title'       => __( 'Access Token público', 'localpilot' ),
				'desc'        => __( 'Token público de Mapbox. Se recomienda restringirlo por URL desde el panel de Mapbox.', 'localpilot' ),
				'desc_tip'    => __( 'Este token se usará tanto en el servidor para geocodificar como en el frontend para cargar los mapas.', 'localpilot' ),
				'id'          => 'lclplt_mapbox_token',
				'type'        => 'text',
				'placeholder' => 'pk.eyJ1Ijoi...',
				'default'     => '',
			),

			array(
				'title'   => __( 'Estilo de mapa', 'localpilot' ),
				'desc'    => __( 'Estilo visual del mapa. Predeterminado: streets-v12.', 'localpilot' ),
				'desc_tip'=> __( 'Usa el ID del estilo (ej. streets-v12, light-v11, dark-v11, satellite-v9).', 'localpilot' ),
				'id'      => 'lclplt_mapbox_style',
				'type'    => 'text',
				'default' => 'streets-v12',
			),

			array(
				'title'   => __( 'Zoom predeterminado', 'localpilot' ),
				'id'      => 'lclplt_mapbox_zoom',
				'type'    => 'number',
				'default' => 14,
				'custom_attributes' => array(
					'min'  => 1,
					'max'  => 22,
					'step' => 1,
				),
			),

			array(
				'title'   => __( 'País', 'localpilot' ),
				'desc'    => __( 'Código ISO 3166-1 alpha-2 para sesgar resultados de geocodificación (ej. MX, US, ES). Vacío = sin sesgo.', 'localpilot' ),
				'id'      => 'lclplt_mapbox_country',
				'type'    => 'text',
				'default' => 'mx',
				'placeholder' => 'mx',
			),

			array(
				'title'   => __( 'Idioma', 'localpilot' ),
				'desc'    => __( 'Código de idioma BCP 47 para resultados (ej. es, en, fr).', 'localpilot' ),
				'id'      => 'lclplt_mapbox_language',
				'type'    => 'text',
				'default' => 'es',
				'placeholder' => 'es',
			),

			array(
				'title'   => __( 'Geocodificación automática', 'localpilot' ),
				'desc'    => __( 'Geocodificar automáticamente la dirección cuando se asigna un pedido.', 'localpilot' ),
				'id'      => 'lclplt_auto_geocode',
				'type'    => 'checkbox',
				'default' => 'yes',
			),

			array(
				'type' => 'sectionend',
				'id'   => 'lclplt_mapbox_options',
			),
		);
	}

	/**
	 * HTML description for the Mapbox section.
	 *
	 * @return string
	 */
	private function section_description_mapbox() {
		$desc  = '<p>';
		$desc .= esc_html__( 'Configura la integración con Mapbox para geocodificar direcciones de pedidos y mostrar mapas interactivos tanto en el panel de administración como en Mi cuenta del repartidor.', 'localpilot' );
		$desc .= '</p><p><strong>';
		$desc .= esc_html__( 'Token requerido:', 'localpilot' );
		$desc .= '</strong> ';
		$desc .= esc_html__( 'Necesitas un Access Token público desde tu cuenta de Mapbox. Se recomienda restringir el token por URL (origen HTTP) para evitar su uso no autorizado.', 'localpilot' );
		$desc .= ' <a href="https://account.mapbox.com/access-tokens/" target="_blank" rel="noopener">';
		$desc .= esc_html__( 'Crear token en Mapbox', 'localpilot' );
		$desc .= '</a>.</p><p>';
		$desc .= esc_html__( 'Atribución requerida por Mapbox: © Mapbox, © OpenStreetMap.', 'localpilot' );
		$desc .= '</p>';
		return $desc;
	}

	/**
	 * HTML description for the Data section.
	 *
	 * @return string
	 */
	private function section_description_data() {
		$desc  = '<p>';
		$desc .= esc_html__( 'Políticas de retención y privacidad de datos. Por defecto, LocalPilot conserva todos los datos al ser desinstalado para evitar pérdida accidental de registros de entregas.', 'localpilot' );
		$desc .= '</p><p>';
		$desc .= esc_html__( 'Los datos que almacena LocalPilot incluyen: asignaciones, eventos del ciclo de entrega, metadatos en pedidos (coordenadas, receptor, evidencia) y metadatos de perfil de repartidores.', 'localpilot' );
		$desc .= '</p>';
		return $desc;
	}

	/**
	 * Get WooCommerce order statuses as an associative array for settings.
	 *
	 * @return array
	 */
	private function get_order_status_options() {
		$statuses = wc_get_order_statuses();
		$options  = array();
		foreach ( $statuses as $slug => $label ) {
			$status = str_replace( 'wc-', '', $slug );
			$options[ $status ] = $label;
		}
		return $options;
	}
}
