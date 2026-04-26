<?php
/**
 * Normalized activites catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_activity_parse_duration_hours' ) ) {
	/**
	 * Convertit une duree brute en heures approximatives.
	 *
	 * @param mixed $raw Valeur brute.
	 * @return int
	 */
	function ajth_activity_parse_duration_hours( $raw ) {
		if ( is_numeric( $raw ) ) {
			$value = (float) $raw;
			return $value > 0 ? (int) round( $value ) : 0;
		}

		$text = strtolower( trim( (string) $raw ) );
		if ( '' === $text ) {
			return 0;
		}

		if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*(jour|jours|day|days)/u', $text, $matches ) ) {
			return max( 1, (int) round( (float) str_replace( ',', '.', $matches[1] ) * 24 ) );
		}

		if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*(heure|heures|hour|hours|hr|hrs|h)\b/u', $text, $matches ) ) {
			return max( 1, (int) round( (float) str_replace( ',', '.', $matches[1] ) ) );
		}

		if ( preg_match( '/(\d+(?:[.,]\d+)?)/', $text, $matches ) ) {
			return max( 1, (int) round( (float) str_replace( ',', '.', $matches[1] ) ) );
		}

		return 0;
	}
}

if ( ! function_exists( 'ajth_get_activities' ) ) {
	/**
	 * Returns a normalized list of activity cards for the Ajinsafro catalog.
	 *
	 * @param int   $limit Number of items to return.
	 * @param array $args  Optional WP_Query overrides.
	 * @return array<int, array<string, mixed>>
	 */
	function ajth_get_activities( $limit = 12, array $args = array() ) {
		$limit = max( 1, (int) $limit );

		$query_args = wp_parse_args(
			$args,
			array(
				'post_type'           => 'st_activity',
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

		$query_args = apply_filters( 'ajth_activities_query_args', $query_args, $limit, $args );

		$query = new WP_Query( $query_args );
		if ( ! $query->have_posts() ) {
			return array();
		}

		global $wpdb;

		$items = array();
		while ( $query->have_posts() ) {
			$query->the_post();

			$post_id       = (int) get_the_ID();
			$title         = get_the_title();
			$price         = get_post_meta( $post_id, 'min_price', true );
			$location      = get_post_meta( $post_id, 'address', true );
			$duration      = get_post_meta( $post_id, 'duration', true );
			$rating        = get_post_meta( $post_id, 'rate_review', true );
			$max_people    = get_post_meta( $post_id, 'max_people', true );
			$category      = (string) get_post_meta( $post_id, 'aj_activity_category', true );
			$place_text    = (string) get_post_meta( $post_id, 'aj_activity_place_text', true );
			$min_age       = get_post_meta( $post_id, 'aj_activity_min_age', true );
			$adult_price   = '';
			$sale_price    = '';
			$discount      = '';
			$type_activity = '';
			$is_featured   = '';

			if ( '' === $price || false === $price ) {
				$price = get_post_meta( $post_id, 'price', true );
			}

			if ( isset( $wpdb ) ) {
				$row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT address, adult_price, min_price, sale_price, type_activity, duration, max_people, rate_review, is_featured, discount
						 FROM {$wpdb->prefix}st_activity
						 WHERE post_id = %d",
						$post_id
					)
				);

				if ( is_object( $row ) ) {
					if ( ( '' === $location || false === $location ) && ! empty( $row->address ) ) {
						$location = $row->address;
					}
					if ( ( '' === $price || false === $price ) && isset( $row->min_price ) && '' !== $row->min_price ) {
						$price = $row->min_price;
					}
					if ( ( '' === $duration || false === $duration ) && isset( $row->duration ) && '' !== $row->duration ) {
						$duration = $row->duration;
					}
					if ( ( '' === $rating || false === $rating ) && isset( $row->rate_review ) && '' !== $row->rate_review ) {
						$rating = $row->rate_review;
					}
					if ( ( '' === $max_people || false === $max_people ) && isset( $row->max_people ) && '' !== $row->max_people ) {
						$max_people = $row->max_people;
					}

					$adult_price   = $row->adult_price ?? '';
					$sale_price    = $row->sale_price ?? '';
					$type_activity = $row->type_activity ?? '';
					$is_featured   = $row->is_featured ?? '';
					$discount      = $row->discount ?? '';
				}
			}

			if ( '' === $location && '' !== $place_text ) {
				$location = $place_text;
			}
			if ( '' === $category ) {
				$category = '' !== $type_activity ? $type_activity : 'Activite';
			}

			$excerpt = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
			if ( '' === $excerpt ) {
				$excerpt = wp_trim_words( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ), 20, '...' );
			} else {
				$excerpt = wp_trim_words( wp_strip_all_tags( $excerpt ), 20, '...' );
			}

			$resolved_price = null;
			if ( is_numeric( $sale_price ) && (float) $sale_price > 0 ) {
				$resolved_price = (float) $sale_price;
			} elseif ( is_numeric( $adult_price ) && (float) $adult_price > 0 ) {
				$resolved_price = (float) $adult_price;
			} elseif ( is_numeric( $price ) ) {
				$resolved_price = (float) $price;
			}

			$old_price = null;
			if ( is_numeric( $adult_price ) && null !== $resolved_price && (float) $adult_price > (float) $resolved_price ) {
				$old_price = (float) $adult_price;
			}

			$discount_value = 0;
			if ( is_numeric( $discount ) && (float) $discount > 0 ) {
				$discount_value = (int) round( (float) $discount );
			} elseif ( null !== $old_price && null !== $resolved_price && $old_price > 0 && $old_price > $resolved_price ) {
				$discount_value = (int) round( ( ( $old_price - $resolved_price ) / $old_price ) * 100 );
			}

			$image_url  = function_exists( 'ajth_hebergement_catalog_card_image_url' ) ? ajth_hebergement_catalog_card_image_url( $post_id ) : '';
			$is_popular = '1' === (string) $is_featured || 'on' === (string) get_post_meta( $post_id, 'is_featured', true );
			$badges     = array();

			if ( $is_popular ) {
				$badges[] = 'A la une';
			} elseif ( $discount_value > 0 ) {
				$badges[] = 'Promotion';
			}

			$items[] = array(
				'id'             => $post_id,
				'title'          => $title,
				'name'           => $title,
				'url'            => get_permalink(),
				'image_url'      => $image_url,
				'image'          => $image_url,
				'city'           => is_string( $location ) ? trim( $location ) : '',
				'location'       => is_string( $location ) ? trim( $location ) : '',
				'category'       => sanitize_title( $category ) !== '' ? sanitize_title( $category ) : 'activite',
				'category_label' => $category !== '' ? $category : 'Activite',
				'type'           => $type_activity !== '' ? sanitize_title( $type_activity ) : '',
				'rating'         => is_numeric( $rating ) ? (float) $rating : null,
				'reviews'        => 0,
				'duration'       => ajth_activity_parse_duration_hours( $duration ),
				'duration_label' => is_scalar( $duration ) ? trim( (string) $duration ) : '',
				'price'          => $resolved_price,
				'oldPrice'       => $old_price,
				'discount'       => $discount_value,
				'badges'         => $badges,
				'languages'      => array(),
				'features'       => array(),
				'description'    => $excerpt,
				'popular'        => (bool) $is_popular,
				'available'      => true,
				'max_people'     => is_numeric( $max_people ) ? (int) $max_people : 0,
				'min_age'        => is_numeric( $min_age ) ? (int) $min_age : 0,
			);
		}

		wp_reset_postdata();

		return apply_filters( 'ajth_activities_items', $items, $query_args );
	}
}
