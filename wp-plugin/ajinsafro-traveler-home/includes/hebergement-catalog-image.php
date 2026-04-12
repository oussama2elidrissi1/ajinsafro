<?php
/**
 * Images catalogue hébergement : résolution stricte (aucune URL « valide » mais 404).
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
 * URL absolue http(s), extension image cohérente, pas de schéma bizarre.
 *
 * @param string $url URL.
 * @return bool
 */
function ajth_hebergement_url_passes_format_checks( $url ) {
	if ( ! is_string( $url ) || trim( $url ) === '' ) {
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
	$path = isset( $p['path'] ) ? $p['path'] : '';
	if ( $path === '' || strpos( $path, '//' ) !== false ) {
		return false;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( $ext === '' ) {
		return false;
	}
	$allowed = array( 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg' );

	return in_array( $ext, $allowed, true );
}

/**
 * Fichier local : présent, lisible, taille minimale, contenu image réel (pas un HTML d’erreur renommé).
 *
 * @param string $path Chemin absolu.
 * @return bool
 */
function ajth_hebergement_local_path_is_valid_image( $path ) {
	if ( ! is_string( $path ) || $path === '' ) {
		return false;
	}
	if ( ! is_readable( $path ) ) {
		return false;
	}
	$size = @filesize( $path );
	if ( $size === false || $size < 32 ) {
		return false;
	}
	$ext = strtolower( pathinfo( $path, PATHINFO_EXTENSION ) );
	if ( $ext === 'svg' ) {
		$head = @file_get_contents( $path, false, null, 0, 512 );
		if ( ! is_string( $head ) ) {
			return false;
		}
		return ( strpos( $head, '<svg' ) !== false || strpos( $head, '<?xml' ) !== false );
	}
	if ( function_exists( 'getimagesize' ) ) {
		$info = @getimagesize( $path );
		if ( $info !== false && ! empty( $info[0] ) && ! empty( $info[1] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Vérifie que l’URL répond avec une image (HEAD puis repli GET court). Résultat mis en transient.
 *
 * @param string $url URL publique.
 * @return bool
 */
function ajth_hebergement_remote_image_is_reachable( $url ) {
	if ( ! ajth_hebergement_url_passes_format_checks( $url ) ) {
		return false;
	}
	$key = 'ajth_imgreach_' . md5( $url );
	$hit = get_transient( $key );
	if ( $hit === 'ok' ) {
		return true;
	}
	if ( $hit === 'fail' ) {
		return false;
	}

	$r = wp_remote_head(
		$url,
		array(
			'timeout'     => 3,
			'redirection' => 2,
			'sslverify'   => true,
		)
	);
	if ( ! is_wp_error( $r ) && (int) wp_remote_retrieve_response_code( $r ) === 200 ) {
		$ct = wp_remote_retrieve_header( $r, 'content-type' );
		if ( is_string( $ct ) && strpos( strtolower( $ct ), 'image/' ) === 0 ) {
			set_transient( $key, 'ok', 12 * HOUR_IN_SECONDS );
			return true;
		}
	}

	$r2 = wp_remote_get(
		$url,
		array(
			'timeout'     => 5,
			'redirection' => 2,
			'sslverify'   => true,
			'headers'     => array( 'Range' => 'bytes=0-2047' ),
		)
	);
	$code = is_wp_error( $r2 ) ? 0 : (int) wp_remote_retrieve_response_code( $r2 );
	if ( $code < 200 || $code >= 400 ) {
		set_transient( $key, 'fail', 2 * HOUR_IN_SECONDS );
		return false;
	}
	$body = wp_remote_retrieve_body( $r2 );
	if ( ! is_string( $body ) || strlen( $body ) < 4 ) {
		set_transient( $key, 'fail', 2 * HOUR_IN_SECONDS );
		return false;
	}
	$ct = wp_remote_retrieve_header( $r2, 'content-type' );
	if ( is_string( $ct ) && strpos( strtolower( $ct ), 'image/' ) === 0 ) {
		set_transient( $key, 'ok', 12 * HOUR_IN_SECONDS );
		return true;
	}
	// Magic bytes (JPEG, PNG, GIF, WebP).
	$b0 = ord( $body[0] );
	$b1 = ord( $body[1] );
	if ( $b0 === 0xff && $b1 === 0xd8 ) {
		set_transient( $key, 'ok', 12 * HOUR_IN_SECONDS );
		return true;
	}
	if ( $b0 === 0x89 && $b1 === 0x50 && strlen( $body ) > 4 && substr( $body, 1, 3 ) === 'PNG' ) {
		set_transient( $key, 'ok', 12 * HOUR_IN_SECONDS );
		return true;
	}
	$gif = strlen( $body ) >= 6 ? substr( $body, 0, 6 ) : '';
	if ( $gif === 'GIF87a' || $gif === 'GIF89a' ) {
		set_transient( $key, 'ok', 12 * HOUR_IN_SECONDS );
		return true;
	}
	if ( substr( $body, 0, 4 ) === 'RIFF' && strlen( $body ) > 12 && substr( $body, 8, 4 ) === 'WEBP' ) {
		set_transient( $key, 'ok', 12 * HOUR_IN_SECONDS );
		return true;
	}

	set_transient( $key, 'fail', 2 * HOUR_IN_SECONDS );
	return false;
}

/**
 * Journalisation si WP_DEBUG (diagnostic : _thumbnail_id, attachment, URL générée, raison).
 *
 * @param int    $post_id Post hôtel.
 * @param int    $att_id  Attachment ID.
 * @param string $url     URL testée.
 * @param string $reason  Code court.
 */
function ajth_hebergement_debug_log_reject( $post_id, $att_id, $url, $reason ) {
	if ( ! ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ) {
		return;
	}
	$thumb_meta = get_post_meta( (int) $post_id, '_thumbnail_id', true );
	error_log(
		'[ajth-hebergement-image] post_id=' . (int) $post_id .
		' meta__thumbnail_id=' . ( is_scalar( $thumb_meta ) ? (string) $thumb_meta : '?' ) .
		' att_id=' . (int) $att_id .
		' reason=' . $reason .
		' url=' . $url
	);
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
 * Résout l’URL d’image pour une carte hébergement : featured puis galerie.
 * Ne retourne une URL que si elle est réellement exploitable (fichier local valide ou test HTTP OK).
 *
 * @param int $post_id Post st_hotel.
 * @return string
 */
function ajth_hebergement_catalog_card_image_url( $post_id ) {
	$fallback = ajth_hebergement_default_card_image_url();
	if ( $fallback === '' ) {
		return '';
	}
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		return $fallback;
	}

	$attachment_ids = array();
	$thumb_id       = (int) get_post_thumbnail_id( $post_id );
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
		if ( ! $att || $att->post_type !== 'attachment' ) {
			continue;
		}
		if ( ! wp_attachment_is_image( $att_id ) ) {
			continue;
		}

		$path     = get_attached_file( $att_id );
		$local_ok = $path && ajth_hebergement_local_path_is_valid_image( $path );
		$last_url = '';

		foreach ( $sizes as $size ) {
			$url = wp_get_attachment_image_url( $att_id, $size );
			if ( ! $url ) {
				continue;
			}
			$url = ajth_normalize_upload_image_url( $url );
			if ( ! ajth_hebergement_url_passes_format_checks( $url ) ) {
				$last_url = $url;
				continue;
			}

			if ( $local_ok ) {
				return $url;
			}

			if ( ajth_hebergement_remote_image_is_reachable( $url ) ) {
				return $url;
			}
			$last_url = $url;
		}

		if ( $last_url !== '' ) {
			ajth_hebergement_debug_log_reject( $post_id, $att_id, $last_url, 'all_sizes_rejected_use_fallback' );
		}
	}

	return $fallback;
}
