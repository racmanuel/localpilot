<?php
/**
 * User profile fields for drivers.
 *
 * Adds LocalPilot-specific fields to the WordPress user profile
 * for users with the localpilot_driver role.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */

/**
 * User profile fields for drivers.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/admin
 */
class Localpilot_User_Profile {

	/*
	 * ------------------------------------------------------------------
	 * User meta key constants
	 * ------------------------------------------------------------------
	 */

	const PHONE         = '_lclplt_driver_phone';
	const ACTIVE        = '_lclplt_driver_active';
	const VEHICLE_TYPE  = '_lclplt_driver_vehicle_type';
	const VEHICLE_PLATE = '_lclplt_driver_vehicle_plate';
	const CAPACITY      = '_lclplt_driver_capacity';
	const NOTES         = '_lclplt_driver_notes';

	/**
	 * Check if a user should see/save driver fields.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private function is_driver_user( $user_id ) {
		return Localpilot_Driver_Role::is_driver( $user_id );
	}

	/**
	 * Render driver fields on the user profile.
	 *
	 * @param WP_User $user User object.
	 */
	public function render_fields( $user ) {
		if ( ! $this->is_driver_user( $user->ID ) ) {
			return;
		}

		// Only users who can edit this profile.
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$phone         = get_user_meta( $user->ID, self::PHONE, true );
		$active        = get_user_meta( $user->ID, self::ACTIVE, true );
		$vehicle_type  = get_user_meta( $user->ID, self::VEHICLE_TYPE, true );
		$vehicle_plate = get_user_meta( $user->ID, self::VEHICLE_PLATE, true );
		$capacity      = get_user_meta( $user->ID, self::CAPACITY, true );
		$notes         = get_user_meta( $user->ID, self::NOTES, true );

		$active         = in_array( $active, array( '1', 1, true ), true );
		$active_count   = Localpilot_Driver_Repository::get_active_assignment_count( $user->ID );
		?>
		<h2><?php esc_html_e( 'LocalPilot — Datos del repartidor', 'localpilot' ); ?></h2>

		<?php wp_nonce_field( 'lclplt_driver_profile', 'lclplt_driver_profile_nonce' ); ?>

		<table class="form-table">
			<tr>
				<th><label for="lclplt_driver_phone"><?php esc_html_e( 'Teléfono', 'localpilot' ); ?></label></th>
				<td>
					<input type="text"
						name="lclplt_driver_phone"
						id="lclplt_driver_phone"
						value="<?php echo esc_attr( $phone ); ?>"
						class="regular-text"
						maxlength="20" />
				</td>
			</tr>
			<tr>
				<th><label for="lclplt_driver_vehicle_type"><?php esc_html_e( 'Tipo de vehículo', 'localpilot' ); ?></label></th>
				<td>
					<input type="text"
						name="lclplt_driver_vehicle_type"
						id="lclplt_driver_vehicle_type"
						value="<?php echo esc_attr( $vehicle_type ); ?>"
						class="regular-text"
						maxlength="100"
						placeholder="<?php esc_attr_e( 'Ej: Bicicleta, Moto, Auto', 'localpilot' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="lclplt_driver_vehicle_plate"><?php esc_html_e( 'Patente / Placa', 'localpilot' ); ?></label></th>
				<td>
					<input type="text"
						name="lclplt_driver_vehicle_plate"
						id="lclplt_driver_vehicle_plate"
						value="<?php echo esc_attr( $vehicle_plate ); ?>"
						class="regular-text"
						maxlength="20" />
				</td>
			</tr>
			<tr>
				<th><label for="lclplt_driver_capacity"><?php esc_html_e( 'Capacidad de carga', 'localpilot' ); ?></label></th>
				<td>
					<input type="text"
						name="lclplt_driver_capacity"
						id="lclplt_driver_capacity"
						value="<?php echo esc_attr( $capacity ); ?>"
						class="regular-text"
						maxlength="100"
						placeholder="<?php esc_attr_e( 'Ej: 20 kg, 10 pedidos', 'localpilot' ); ?>" />
				</td>
			</tr>
			<tr>
				<th><label for="lclplt_driver_active"><?php esc_html_e( 'Activo', 'localpilot' ); ?></label></th>
				<td>
					<label for="lclplt_driver_active">
						<input type="checkbox"
							name="lclplt_driver_active"
							id="lclplt_driver_active"
							value="1"
							<?php checked( $active ); ?> />
						<?php esc_html_e( 'Repartidor disponible para asignaciones', 'localpilot' ); ?>
					</label>
					<?php if ( $active_count > 0 ) : ?>
						<p class="description">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %d: number of active deliveries */
									_n(
										'Tiene %d entrega activa. Desactivar no cancela las asignaciones existentes.',
										'Tiene %d entregas activas. Desactivar no cancela las asignaciones existentes.',
										$active_count,
										'localpilot'
									),
									$active_count
								)
							);
							?>
						</p>
					<?php endif; ?>
				</td>
			</tr>
			<tr>
				<th><label for="lclplt_driver_notes"><?php esc_html_e( 'Notas internas', 'localpilot' ); ?></label></th>
				<td>
					<textarea name="lclplt_driver_notes"
						id="lclplt_driver_notes"
						class="regular-text"
						rows="3"
						maxlength="500"><?php echo esc_textarea( $notes ); ?></textarea>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save driver profile fields.
	 *
	 * @param int $user_id User ID.
	 */
	public function save_fields( $user_id ) {
		if ( ! $this->is_driver_user( $user_id ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['lclplt_driver_profile_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['lclplt_driver_profile_nonce'] ), 'lclplt_driver_profile' )
		) {
			return;
		}

		// Check capability.
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		// Phone.
		if ( isset( $_POST['lclplt_driver_phone'] ) ) {
			update_user_meta(
				$user_id,
				self::PHONE,
				sanitize_text_field( wp_unslash( $_POST['lclplt_driver_phone'] ) )
			);
		}

		// Vehicle type.
		if ( isset( $_POST['lclplt_driver_vehicle_type'] ) ) {
			update_user_meta(
				$user_id,
				self::VEHICLE_TYPE,
				sanitize_text_field( wp_unslash( $_POST['lclplt_driver_vehicle_type'] ) )
			);
		}

		// Vehicle plate.
		if ( isset( $_POST['lclplt_driver_vehicle_plate'] ) ) {
			update_user_meta(
				$user_id,
				self::VEHICLE_PLATE,
				sanitize_text_field( wp_unslash( $_POST['lclplt_driver_vehicle_plate'] ) )
			);
		}

		// Capacity.
		if ( isset( $_POST['lclplt_driver_capacity'] ) ) {
			update_user_meta(
				$user_id,
				self::CAPACITY,
				sanitize_text_field( wp_unslash( $_POST['lclplt_driver_capacity'] ) )
			);
		}

		// Active (checkbox).
		$active = isset( $_POST['lclplt_driver_active'] ) && '1' === $_POST['lclplt_driver_active'];
		update_user_meta( $user_id, self::ACTIVE, $active ? '1' : '0' );

		// Notes.
		if ( isset( $_POST['lclplt_driver_notes'] ) ) {
			update_user_meta(
				$user_id,
				self::NOTES,
				sanitize_textarea_field( wp_unslash( $_POST['lclplt_driver_notes'] ) )
			);
		}
	}

	/**
	 * Add driver columns to the Users list.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_columns( $columns ) {
		$columns['lclplt_driver_status']  = __( 'Estado', 'localpilot' );
		$columns['lclplt_driver_vehicle'] = __( 'Vehículo', 'localpilot' );
		$columns['lclplt_driver_active_assignments'] = __( 'Entregas activas', 'localpilot' );
		return $columns;
	}

	/**
	 * Render driver column content.
	 *
	 * @param string $output      Column output.
	 * @param string $column_name Column name.
	 * @param int    $user_id     User ID.
	 * @return string
	 */
	public function render_column( $output, $column_name, $user_id ) {
		if ( ! $this->is_driver_user( $user_id ) ) {
			return $output;
		}

		switch ( $column_name ) {
			case 'lclplt_driver_status':
				$active = Localpilot_Driver_Repository::is_active( $user_id );
				return $active
					? '<span style="color:#46b450;">' . esc_html__( 'Activo', 'localpilot' ) . '</span>'
					: '<span style="color:#dc3232;">' . esc_html__( 'Inactivo', 'localpilot' ) . '</span>';

			case 'lclplt_driver_vehicle':
				$vehicle = get_user_meta( $user_id, self::VEHICLE_TYPE, true );
				return $vehicle ? esc_html( $vehicle ) : '—';

			case 'lclplt_driver_active_assignments':
				$count = Localpilot_Driver_Repository::get_active_assignment_count( $user_id );
				return (string) $count;
		}

		return $output;
	}
}
