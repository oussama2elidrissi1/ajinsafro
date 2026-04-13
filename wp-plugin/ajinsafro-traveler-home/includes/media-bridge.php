<?php
/**
 * REST endpoints privés: upload médias via WordPress natif.
 *
 * Objectif: éviter les attachments "fantômes" créés par des écritures DB directes
 * quand Laravel n'écrit pas dans le même volume que WordPress.
 *
 * Auth: header X-Ajth-Secret identique à l'invalidation cache (AJTH_LARAVEL_INVALIDATE_SECRET
 * ou option ajth_laravel_invalidate_secret).
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permission callback (secret partagé).
 *
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function ajth_media_bridge_permission( $request ) {
	// Reuse invalidate secret helper (same shared secret).
	if ( ! function_exists( 'ajth_get_catalog_invalidate_secret' ) ) {
		return new WP_Error( 'ajth_media_bridge_missing_secret_helper', 'Secret helper is not loaded.', array( 'status' => 500 ) );
	}

	$expected = ajth_get_catalog_invalidate_secret();
	if ( $expected === '' ) {
		return new WP_Error( 'ajth_media_bridge_disabled', 'Media bridge is not configured.', array( 'status' => 403 ) );
	}

	$sent = $request->get_header( 'X-Ajth-Secret' );
	if ( ! is_string( $sent ) || $sent === '' ) {
		$sent = (string) $request->get_param( 'secret' );
	}
	if ( $sent === '' || ! hash_equals( $expected, $sent ) ) {
		return new WP_Error( 'ajth_media_bridge_forbidden', 'Invalid secret.', array( 'status' => 403 ) );
	}

	return true;
}

/**
 * POST /wp-json/ajth/v1/media-upload
 * Multipart param: file
 *
 * Uses: wp_upload_bits() + wp_insert_attachment() + wp_generate_attachment_metadata().
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function ajth_media_upload_rest( $request ) {
	$files = $request->get_file_params();
	$file  = isset( $files['file'] ) ? $files['file'] : null;
	if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
		return new WP_Error( 'bad_request', 'Missing file.', array( 'status' => 400 ) );
	}

	$parent_id = (int) $request->get_param( 'parent_post_id' );
	if ( $parent_id < 0 ) {
		$parent_id = 0;
	}

	$filename = sanitize_file_name( (string) $file['name'] );
	$tmp      = (string) $file['tmp_name'];
	if ( ! file_exists( $tmp ) || ! is_readable( $tmp ) ) {
		return new WP_Error( 'bad_request', 'Temp file is not readable.', array( 'status' => 400 ) );
	}

	// Allow only image file types.
	$filetype = wp_check_filetype( $filename );
	$mime     = isset( $filetype['type'] ) ? (string) $filetype['type'] : '';
	if ( $mime === '' || strpos( $mime, 'image/' ) !== 0 ) {
		return new WP_Error( 'unsupported_media_type', 'Only image uploads are allowed.', array( 'status' => 415 ) );
	}

	$contents = file_get_contents( $tmp );
	if ( $contents === false ) {
		return new WP_Error( 'bad_request', 'Failed to read temp file.', array( 'status' => 400 ) );
	}

	$upload = wp_upload_bits( $filename, null, $contents );
	if ( ! is_array( $upload ) || ! empty( $upload['error'] ) ) {
		return new WP_Error( 'upload_failed', (string) ( $upload['error'] ?? 'Unknown upload error.' ), array( 'status' => 500 ) );
	}

	if ( empty( $upload['file'] ) || ! file_exists( $upload['file'] ) ) {
		return new WP_Error( 'upload_failed', 'Upload succeeded but file is missing on disk.', array( 'status' => 500 ) );
	}

	$attachment = array(
		'post_mime_type' => $mime,
		'post_title'     => pathinfo( $filename, PATHINFO_FILENAME ),
		'post_content'   => '',
		'post_status'    => 'inherit',
		'post_parent'    => $parent_id,
	);

	$attachment_id = wp_insert_attachment( $attachment, $upload['file'], $parent_id );
	if ( is_wp_error( $attachment_id ) ) {
		return $attachment_id;
	}
	$attachment_id = (int) $attachment_id;

	require_once ABSPATH . 'wp-admin/includes/image.php';

	$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
	if ( is_wp_error( $metadata ) ) {
		return $metadata;
	}
	if ( is_array( $metadata ) ) {
		wp_update_attachment_metadata( $attachment_id, $metadata );
	}

	$attached_file = get_post_meta( $attachment_id, '_wp_attached_file', true );
	$full_url      = wp_get_attachment_image_url( $attachment_id, 'full' );
	$ml_url        = wp_get_attachment_image_url( $attachment_id, 'medium_large' );
	$abs_path      = get_attached_file( $attachment_id );

	return new WP_REST_Response(
		array(
			'ok'            => true,
			'attachment_id' => $attachment_id,
			'attached_file' => is_string( $attached_file ) ? $attached_file : '',
			'url'           => wp_get_attachment_url( $attachment_id ),
			'full'          => $full_url ? $full_url : '',
			'medium_large'  => $ml_url ? $ml_url : '',
			'abs_path'      => is_string( $abs_path ) ? $abs_path : '',
			'file_exists'   => (bool) ( $abs_path && file_exists( $abs_path ) ),
		),
		200
	);
}

/**
 * GET /wp-json/ajth/v1/media-validate?id=123
 *
 * @param WP_REST_Request $request
 * @return WP_REST_Response|WP_Error
 */
function ajth_media_validate_rest( $request ) {
	$id = (int) $request->get_param( 'id' );
	if ( $id <= 0 ) {
		return new WP_Error( 'bad_request', 'Missing id.', array( 'status' => 400 ) );
	}

	$post = get_post( $id );
	if ( ! $post || $post->post_type !== 'attachment' ) {
		return new WP_REST_Response(
			array(
				'ok'     => true,
				'valid'  => false,
				'reason' => 'attachment_not_found',
			),
			200
		);
	}

	$attached_file = get_post_meta( $id, '_wp_attached_file', true );
	if ( ! is_string( $attached_file ) || trim( $attached_file ) === '' ) {
		return new WP_REST_Response(
			array(
				'ok'     => true,
				'valid'  => false,
				'reason' => '_wp_attached_file_empty',
			),
			200
		);
	}

	$abs = get_attached_file( $id );
	$exists = is_string( $abs ) && $abs !== '' && file_exists( $abs );
	if ( ! $exists ) {
		return new WP_REST_Response(
			array(
				'ok'           => true,
				'valid'        => false,
				'reason'       => 'file_missing',
				'attached_file'=> $attached_file,
				'abs_path'     => is_string( $abs ) ? $abs : '',
			),
			200
		);
	}

	$full_url = wp_get_attachment_image_url( $id, 'full' );

	return new WP_REST_Response(
		array(
			'ok'            => true,
			'valid'         => true,
			'reason'        => 'file_exists',
			'attached_file' => $attached_file,
			'abs_path'      => is_string( $abs ) ? $abs : '',
			'full'          => $full_url ? $full_url : '',
		),
		200
	);
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'ajth/v1',
			'/media-upload',
			array(
				'methods'             => 'POST',
				'callback'            => 'ajth_media_upload_rest',
				'permission_callback' => 'ajth_media_bridge_permission',
			)
		);

		register_rest_route(
			'ajth/v1',
			'/media-validate',
			array(
				'methods'             => 'GET',
				'callback'            => 'ajth_media_validate_rest',
				'permission_callback' => 'ajth_media_bridge_permission',
			)
		);
	}
);

