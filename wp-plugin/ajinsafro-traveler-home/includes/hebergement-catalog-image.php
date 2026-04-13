<?php
/**
 * Images catalogue hébergement : résolution fiable (featured + galerie) et alignement domaine / uploads.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enregistre le correctif d’URL pour tous les attachments (évite localhost / ancien domaine en base).
 */
function ajth_register_attachment_url_normalizer() {
	add_filter( 'wp_get_attachment_url', 'ajth_normalize_attachment_url_to_current_site', 99, 2 );
}
add_action( 'plugins_loaded', 'ajth_register_attachment_url_normalizer', 5 );

/**
 * Réécrit les URLs d’uploads vers le host actuel (home_url), même si le guid / options WP pointaient ailleurs.
 *
 * @param string|false $url URL calculée par WordPress.
 * @param int          $post_id Attachment ID.
 * @return string|false
 */
function ajth_normalize_attachment_url_to_current_site( $url, $post_id ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return $url;
	}
	if ( strpos( $url, '/wp-content/uploads/' ) === false ) {
		return $url;
	}
	$fixed = ajth_normalize_upload_image_url( $url );
	return $fixed !== '' ? $fixed : $url;
}

/**
 * Aligne host/port/schéma de l’URL sur ceux de home_url() pour les chemins uploads.
 *
 * @param string $url URL absolue.
 * @return string
 */
function ajth_normalize_upload_image_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return '';
	}
	if ( strpos( $url, '/wp-content/uploads/' ) === false ) {
		return $url;
	}
	$home = wp_parse_url( home_url( '/' ) );
	$u    = wp_parse_url( $url );
	if ( empty( $home['host'] ) || empty( $u['host'] ) ) {
		return $url;
	}
	if ( $u['host'] === $home['host'] && ( isset( $u['port'] ) ? (int) $u['port'] : null ) === ( isset( $home['port'] ) ? (int) $home['port'] : null ) ) {
		return $url;
	}
	$scheme = isset( $home['scheme'] ) ? $home['scheme'] : 'https';
	$host   = $home['host'];
	$port   = isset( $home['port'] ) ? ':' . (int) $home['port'] : '';
	$path   = isset( $u['path'] ) ? $u['path'] : '';
	$query  = isset( $u['query'] ) ? '?' . $u['query'] : '';
	$frag   = isset( $u['fragment'] ) ? '#' . $u['fragment'] : '';

	return $scheme . '://' . $host . $port . $path . $query . $frag;
}

/**
 * URL http(s) absolue exploitable par le navigateur (pas file:, pas chemin local).
 *
 * @param string $url URL.
 * @return bool
 */
function ajth_is_browser_safe_image_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}
	$p = wp_parse_url( $url );
	if ( empty( $p['scheme'] ) || empty( $p['host'] ) ) {
		return false;
	}
	if ( ! in_array( strtolower( $p['scheme'] ), array( 'http', 'https' ), true ) ) {
		return false;
	}
	$host = strtolower( $p['host'] );
	if ( $host === 'localhost' || $host === '127.0.0.1' || $host === '::1' ) {
		$home = wp_parse_url( home_url( '/' ) );
		if ( ! empty( $home['host'] ) && strtolower( $home['host'] ) !== $host ) {
			return false;
		}
	}

	return true;
}

/**
 * Image par défaut Ajinsafro (plugin), toujours une URL HTTP valide.
 *
 * @return string
 */
function ajth_hebergement_default_card_image_url() {
	if ( defined( 'AJTH_FILE' ) ) {
		return plugins_url( 'assets/images/default-hotel.svg', AJTH_FILE );
	}
	return '';
}

/**
 * Résout l’URL d’image pour une carte hébergement : _thumbnail_id (via get_post_thumbnail_id),
 * puis wp_get_attachment_image_url (large / medium_large / …), puis galerie Traveler.
 *
 * @param int $post_id Post st_hotel.
 * @return string URL absolue sûre ou image par défaut plugin.
 */
function ajth_hebergement_catalog_card_image_url( $post_id ) {
	$fallback = ajth_hebergement_default_card_image_url();
	$post_id  = (int) $post_id;
	if ( $post_id <= 0 ) {
		return $fallback;
	}

	$attachment_ids = array();
	$thumb_id         = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb_id > 0 ) {
		$attachment_ids[] = $thumb_id;
	}
	foreach ( array( 'st_gallery', 'gallery', '_gallery' ) as $meta_key ) {
		$raw = get_post_meta( $post_id, $meta_key, true );
		if ( ! is_string( $raw ) || trim( $raw ) === '' ) {
			continue;
		}
		foreach ( array_filter( array_map( 'intval', explode( ',', $raw ) ) ) as $aid ) {
			if ( $aid > 0 && ! in_array( $aid, $attachment_ids, true ) ) {
				$attachment_ids[] = $aid;
			}
		}
	}

	$sizes = array( 'large', 'medium_large', 'full', 'medium' );

	foreach ( $attachment_ids as $att_id ) {
		$att = get_post( $att_id );
		if ( ! $att || $att->post_type !== 'attachment' || ! wp_attachment_is_image( $att_id ) ) {
			continue;
		}
		foreach ( $sizes as $size ) {
			$url = wp_get_attachment_image_url( $att_id, $size );
			if ( ! $url ) {
				continue;
			}
			$url = ajth_normalize_upload_image_url( $url );
			if ( ! ajth_is_browser_safe_image_url( $url ) ) {
				continue;
			}
			$path = get_attached_file( $att_id );
			if ( $path && is_readable( $path ) ) {
				return $url;
			}
			// Fichier absent du FS local (CDN, autre serveur) : garder l’URL si WordPress la valide.
			if ( function_exists( 'wp_http_validate_url' ) && wp_http_validate_url( $url ) ) {
				return $url;
			}
		}
	}

	return $fallback;
}
