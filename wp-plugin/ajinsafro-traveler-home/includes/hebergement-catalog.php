<?php
/**
 * Normalized hebergement catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
		$query_args['posts_per_page'] = $limit;
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
			if ( $price === '' || $price === false ) {
				$price = get_post_meta( $post_id, 'price', true );
			}

			$location = get_post_meta( $post_id, 'address', true );
			$stars    = get_post_meta( $post_id, 'hotel_star', true );

			if ( ( $location === '' || $location === false || $price === '' || $price === false || $stars === '' || $stars === false ) && isset( $wpdb ) ) {
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT address, min_price, hotel_star FROM {$wpdb->prefix}st_hotel WHERE post_id = %d", $post_id ) );
				if ( is_object( $row ) ) {
					if ( ( $location === '' || $location === false ) && ! empty( $row->address ) ) {
						$location = $row->address;
					}
					if ( ( $price === '' || $price === false ) && isset( $row->min_price ) && $row->min_price !== '' ) {
						$price = $row->min_price;
					}
					if ( ( $stars === '' || $stars === false ) && isset( $row->hotel_star ) && $row->hotel_star !== '' ) {
						$stars = $row->hotel_star;
					}
				}
			}

			$terms = get_the_terms( $post_id, 'hotel_type' );
			$category = ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : 'Hôtel';

			$excerpt = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
			if ( $excerpt === '' ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 18, '…' );
			} else {
				$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), 18, '…' );
			}

			$items[] = array(
				'id'        => $post_id,
				'title'     => get_the_title(),
				'url'       => get_permalink(),
				'image_url' => function_exists( 'ajth_hebergement_catalog_card_image_url' ) ? ajth_hebergement_catalog_card_image_url( $post_id ) : '',
				'location'  => is_string( $location ) ? trim( $location ) : '',
				'category'  => $category,
				'stars'     => is_numeric( $stars ) ? (int) $stars : 0,
				'price'     => is_numeric( $price ) ? (float) $price : null,
				'excerpt'   => $excerpt,
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