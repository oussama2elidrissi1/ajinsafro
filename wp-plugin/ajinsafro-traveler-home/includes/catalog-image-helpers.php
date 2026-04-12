<?php
/**
 * Images catalogue (hébergement / activités / transferts).
 *
 * Source principale : meta WordPress _thumbnail_id (featured image) → attachment.
 * Secours : première image valide des métas st_gallery, gallery, _gallery (IDs séparés par des virgules).
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vérifie que l’attachment image existe sur le disque (évite img cassées / src vides).
 *
 * @param int $attachment_id ID attachment (wp_posts).
 * @return bool
 */
function ajth_attachment_image_is_displayable( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return false;
	}
	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}
	$path = get_attached_file( $attachment_id );

	return $path && is_readable( $path );
}

/**
 * Premier ID d’attachment valide dans une méta « liste d’IDs » (galerie Traveler).
 *
 * @param int    $post_id  Post catalogue.
 * @param string $meta_key Clé postmeta.
 * @return int 0 si aucun.
 */
function ajth_first_valid_gallery_attachment_id( $post_id, $meta_key ) {
	$raw = get_post_meta( (int) $post_id, $meta_key, true );
	if ( ! is_string( $raw ) || trim( $raw ) === '' ) {
		return 0;
	}
	$ids = array_filter( array_map( 'intval', explode( ',', $raw ) ) );
	foreach ( $ids as $att_id ) {
		if ( ajth_attachment_image_is_displayable( $att_id ) ) {
			return $att_id;
		}
	}

	return 0;
}

/**
 * Affiche l’image de carte catalogue : une à la une WP si valide, sinon première image de galerie, sinon fallback CSS.
 *
 * @param int $post_id ID du post (st_hotel, st_activity, st_cars…).
 * @return void
 */
function ajth_render_catalog_card_image( $post_id ) {
	$post_id = (int) $post_id;
	if ( $post_id <= 0 ) {
		echo '<div class="aj-voyages-image-fallback aj-voyages-image-fallback--empty" aria-hidden="true"></div>';

		return;
	}

	$thumb_id = (int) get_post_thumbnail_id( $post_id );
	if ( $thumb_id && ajth_attachment_image_is_displayable( $thumb_id ) ) {
		echo wp_get_attachment_image(
			$thumb_id,
			'large',
			false,
			array(
				'loading'    => 'lazy',
				'decoding'   => 'async',
				'class'      => 'aj-catalog-card__img',
				'sizes'      => '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
			)
		);

		return;
	}

	foreach ( array( 'st_gallery', 'gallery', '_gallery' ) as $gkey ) {
		$gid = ajth_first_valid_gallery_attachment_id( $post_id, $gkey );
		if ( $gid > 0 ) {
			echo wp_get_attachment_image(
				$gid,
				'large',
				false,
				array(
					'loading'    => 'lazy',
					'decoding'   => 'async',
					'class'      => 'aj-catalog-card__img aj-catalog-card__img--gallery',
					'sizes'      => '(max-width: 768px) 100vw, (max-width: 1200px) 50vw, 33vw',
				)
			);

			return;
		}
	}

	echo '<div class="aj-voyages-image-fallback aj-voyages-image-fallback--empty" aria-hidden="true"></div>';
}
