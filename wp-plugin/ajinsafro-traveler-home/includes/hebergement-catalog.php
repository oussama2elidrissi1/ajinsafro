<?php
/**
 * Normalized hebergement catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_hebergement_normalize_meta_list' ) ) {
	/**
	 * Normalise une meta texte / serialisée vers une liste simple.
	 *
	 * @param mixed $raw Valeur brute.
	 * @return array<int, string>
	 */
	function ajth_hebergement_normalize_meta_list( $raw ) {
		if ( is_array( $raw ) ) {
			$values = $raw;
		} elseif ( is_string( $raw ) && $raw !== '' ) {
			$maybe = maybe_unserialize( $raw );
			if ( is_array( $maybe ) ) {
				$values = $maybe;
			} else {
				$values = preg_split( '/[\n,;|]+/', $raw ) ?: array();
			}
		} else {
			$values = array();
		}

		$values = array_map(
			static function ( $value ) {
				return sanitize_title( is_scalar( $value ) ? (string) $value : '' );
			},
			$values
		);

		return array_values( array_filter( array_unique( $values ) ) );
	}
}

if ( ! function_exists( 'getAjinsafroHebergements' ) ) {
	/**
	 * Returns a normalized list of hotel cards for the Ajinsafro catalog.
	 *
	 * @param int   $limit Number of items to return.
	 * @param array $args  Optional WP_Query overrides.
	 * @return array<int, array<string, mixed>>
	 */
	function getAjinsafroHebergements( $limit = 4, array $args = array() ) {
		$limit = max( 1, (int) $limit );

		$query_args = wp_parse_args(
			$args,
			array(
				'post_type'           => 'st_hotel',
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			)
		);

		if ( ! isset( $args['posts_per_page'] ) ) {
			$query_args['posts_per_page'] = $limit;
		}

		$query_args = apply_filters( 'ajth_hebergements_query_args', $query_args, $limit, $args );

		$query = new WP_Query( $query_args );
		if ( ! $query->have_posts() ) {
			return array();
		}

		global $wpdb;

		$items = array();
		while ( $query->have_posts() ) {
			$query->the_post();

			$post_id = (int) get_the_ID();
			$price   = get_post_meta( $post_id, 'min_price', true );
			if ( '' === $price || false === $price ) {
				$price = get_post_meta( $post_id, 'price', true );
			}

			$location = get_post_meta( $post_id, 'address', true );
			$stars    = get_post_meta( $post_id, 'hotel_star', true );

			if ( ( '' === $location || false === $location || '' === $price || false === $price || '' === $stars || false === $stars ) && isset( $wpdb ) ) {
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT address, min_price, hotel_star, is_featured FROM {$wpdb->prefix}st_hotel WHERE post_id = %d", $post_id ) );
				if ( is_object( $row ) ) {
					if ( ( '' === $location || false === $location ) && ! empty( $row->address ) ) {
						$location = $row->address;
					}
					if ( ( '' === $price || false === $price ) && isset( $row->min_price ) && '' !== $row->min_price ) {
						$price = $row->min_price;
					}
					if ( ( '' === $stars || false === $stars ) && isset( $row->hotel_star ) && '' !== $row->hotel_star ) {
						$stars = $row->hotel_star;
					}
				}
			}

			$terms    = get_the_terms( $post_id, 'hotel_type' );
			$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Hotel';
			$type     = ( $terms && ! is_wp_error( $terms ) ) ? sanitize_title( $terms[0]->slug ) : 'hotel';

			$excerpt = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
			if ( '' === $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 18, '...' );
			} else {
				$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), 18, '...' );
			}

			$amenities = ajth_hebergement_normalize_meta_list( get_post_meta( $post_id, 'hotel_amenities', true ) );
			$featured  = get_post_meta( $post_id, '_is_featured', true );
			$is_popular = '1' === (string) $featured || 'on' === (string) get_post_meta( $post_id, 'is_featured', true );
			$image_url = function_exists( 'ajth_hebergement_catalog_card_image_url' ) ? ajth_hebergement_catalog_card_image_url( $post_id ) : '';

			$items[] = array(
				'id'          => $post_id,
				'title'       => get_the_title(),
				'name'        => get_the_title(),
				'url'         => get_permalink(),
				'image_url'   => $image_url,
				'image'       => $image_url,
				'location'    => is_string( $location ) ? trim( $location ) : '',
				'category'    => $category,
				'type'        => $type !== '' ? $type : 'hotel',
				'stars'       => is_numeric( $stars ) ? (int) $stars : 0,
				'price'       => is_numeric( $price ) ? (float) $price : null,
				'excerpt'     => $excerpt,
				'description' => $excerpt,
				'amenities'   => $amenities,
				'popular'     => (bool) $is_popular,
				'available'   => true,
				'discount'    => 0,
				'oldPrice'    => null,
				'rating'      => null,
				'reviews'     => 0,
				'board'       => '',
			);
		}

		wp_reset_postdata();

		return apply_filters( 'ajth_hebergements_items', $items, $query_args );
	}
}

if ( ! function_exists( 'ajth_get_hebergements' ) ) {
	function ajth_get_hebergements( $limit = 4, array $args = array() ) {
		return getAjinsafroHebergements( $limit, $args );
	}
}
