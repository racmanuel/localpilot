<?php
/**
 * Proof (evidence) upload service.
 *
 * Handles file upload, MIME validation, WordPress attachment creation,
 * and orphan cleanup for delivery evidence images.
 *
 * @link       https://racmanuel.dev/
 * @since      1.0.0
 *
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */

/**
 * Proof (evidence) upload service.
 *
 * @since      1.0.0
 * @package    Localpilot
 * @subpackage Localpilot/includes/deliveries
 */
class Localpilot_Proof_Service {

	/**
	 * Allowed MIME types.
	 *
	 * @var array
	 */
	const ALLOWED_MIMES = array(
		'image/jpeg' => 'jpg',
		'image/png'  => 'png',
		'image/webp' => 'webp',
	);

	/**
	 * Process a proof upload from $_FILES.
	 *
	 * @param int    $order_id   Order ID.
	 * @param string $file_key   Key in $_FILES array (default 'lclplt_proof').
	 * @param int    $actor_id   User ID performing the upload.
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function handle_upload( $order_id, $file_key = 'lclplt_proof', $actor_id = 0 ) {
		if ( ! $actor_id ) {
			$actor_id = get_current_user_id();
		}

		// Check that a file was uploaded.
		if ( empty( $_FILES[ $file_key ] ) || empty( $_FILES[ $file_key ]['name'] ) ) {
			return new WP_Error(
				'lclplt_proof_no_file',
				__( 'No se ha seleccionado ningún archivo.', 'localpilot' )
			);
		}

		$file = $_FILES[ $file_key ]; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		// Validate upload error code.
		if ( UPLOAD_ERR_OK !== $file['error'] ) {
			$messages = array(
				UPLOAD_ERR_INI_SIZE   => __( 'El archivo supera el tamaño máximo permitido por el servidor.', 'localpilot' ),
				UPLOAD_ERR_FORM_SIZE  => __( 'El archivo supera el tamaño máximo permitido.', 'localpilot' ),
				UPLOAD_ERR_PARTIAL    => __( 'El archivo se subió parcialmente.', 'localpilot' ),
				UPLOAD_ERR_NO_FILE    => __( 'No se subió ningún archivo.', 'localpilot' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Falta la carpeta temporal de subida.', 'localpilot' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Error al escribir el archivo en el disco.', 'localpilot' ),
				UPLOAD_ERR_EXTENSION  => __( 'Una extensión bloqueó la subida del archivo.', 'localpilot' ),
			);
			$msg = isset( $messages[ $file['error'] ] )
				? $messages[ $file['error'] ]
				: __( 'Error desconocido al subir el archivo.', 'localpilot' );
			return new WP_Error( 'lclplt_proof_upload_error', $msg );
		}

		// Validate file size.
		$max_size = (int) get_option( 'lclplt_max_proof_size', 5 ) * 1024 * 1024;
		if ( $file['size'] > $max_size ) {
			return new WP_Error(
				'lclplt_proof_too_large',
				sprintf(
					/* translators: %d: maximum file size in MB */
					__( 'El archivo supera el tamaño máximo de %d MB.', 'localpilot' ),
					(int) get_option( 'lclplt_max_proof_size', 5 )
				)
			);
		}

		// Validate MIME type via finfo (real content, not extension).
		$finfo    = finfo_open( FILEINFO_MIME_TYPE );
		$real_mime = finfo_file( $finfo, $file['tmp_name'] );
		finfo_close( $finfo );

		if ( ! isset( self::ALLOWED_MIMES[ $real_mime ] ) ) {
			return new WP_Error(
				'lclplt_proof_invalid_type',
				sprintf(
					/* translators: %s: allowed MIME types */
					__( 'Tipo de archivo no permitido. Solo se aceptan: %s.', 'localpilot' ),
					implode( ', ', array_keys( self::ALLOWED_MIMES ) )
				)
			);
		}

		// Validate extension matches real MIME.
		$extension = self::ALLOWED_MIMES[ $real_mime ];
		$file_ext  = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
		if ( $file_ext !== $extension ) {
			return new WP_Error(
				'lclplt_proof_extension_mismatch',
				__( 'La extensión del archivo no coincide con su tipo real.', 'localpilot' )
			);
		}

		// Handle the upload using WordPress.
		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( isset( $upload['error'] ) ) {
			return new WP_Error(
				'lclplt_proof_handle_error',
				$upload['error']
			);
		}

		$title = sprintf(
			/* translators: 1: order number, 2: date */
			__( 'Evidencia — Pedido #%1$s — %2$s', 'localpilot' ),
			$order_id,
			wp_date( get_option( 'date_format' ) . ' H:i' )
		);

		$filetype = wp_check_filetype( $upload['file'] );

		$attachment = array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
			'post_author'    => $actor_id,
		);

		$attachment_id = wp_insert_attachment( $attachment, $upload['file'], 0, true );

		if ( is_wp_error( $attachment_id ) ) {
			// Clean up the uploaded file.
			wp_delete_file( $upload['file'] );
			return $attachment_id;
		}

		// Generate attachment metadata and update.
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $attach_data );

		// Store private meta linking attachment to order.
		update_post_meta( $attachment_id, '_lclplt_proof_order', $order_id );

		return $attachment_id;
	}

	/**
	 * Delete an orphan proof attachment.
	 *
	 * Should be called when a delivery transition fails after upload.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	public static function delete_orphan( $attachment_id ) {
		if ( ! $attachment_id ) {
			return false;
		}
		return wp_delete_attachment( $attachment_id, true ) ? true : false;
	}

	/**
	 * Get the proof attachment URL for an order.
	 *
	 * @param int $order_id Order ID.
	 * @return string|false URL or false if not found.
	 */
	public static function get_proof_url( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$id   = $meta->get_proof_attachment_id();
		if ( ! $id ) {
			return false;
		}
		$url = wp_get_attachment_url( $id );
		return $url ? $url : false;
	}

	/**
	 * Get proof attachment image HTML (thumbnail) for admin.
	 *
	 * @param int $order_id Order ID.
	 * @param int $width    Thumbnail width.
	 * @return string HTML or empty string.
	 */
	public static function get_proof_thumbnail( $order_id, $width = 150 ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return '';
		}
		$meta = new Localpilot_Order_Delivery_Meta( $order );
		$id   = $meta->get_proof_attachment_id();
		if ( ! $id ) {
			return '';
		}
		$src = wp_get_attachment_image_url( $id, array( $width, $width ) );
		if ( ! $src ) {
			return '';
		}
		return sprintf(
			'<a href="%s" target="_blank"><img src="%s" alt="%s" style="max-width:%dpx;height:auto;border:1px solid #ddd;border-radius:4px;" /></a>',
			esc_url( wp_get_attachment_url( $id ) ),
			esc_url( $src ),
			esc_attr__( 'Evidencia de entrega', 'localpilot' ),
			(int) $width
		);
	}
}
