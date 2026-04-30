<?php
/**
 * Normalized hebergement catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_laravel_api_base_url' ) ) {
	/**
	 * Returns the Laravel API base URL used by catalog pages.
	 *
	 * Accepts either a root URL like http://127.0.0.1:8000
	 * or an API URL like http://127.0.0.1:8000/api.
	 *
	 * @return string
	 */
	function ajth_laravel_api_base_url() {
		$url = '';

		if ( defined( 'AJTH_LARAVEL_API_URL' ) && is_string( AJTH_LARAVEL_API_URL ) && AJTH_LARAVEL_API_URL !== '' ) {
			$url = AJTH_LARAVEL_API_URL;
		} elseif ( defined( 'AJTB_LARAVEL_API_URL' ) && is_string( AJTB_LARAVEL_API_URL ) && AJTB_LARAVEL_API_URL !== '' ) {
			$url = AJTB_LARAVEL_API_URL;
		} else {
			$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
			$home_host = is_string( $home_host ) ? strtolower( $home_host ) : '';

			if ( '' !== $home_host ) {
				if ( false !== strpos( $home_host, 'ajinsafro.net' ) ) {
					$url = 'https://booking.ajinsafro.net/api';
				} elseif ( in_array( $home_host, array( '127.0.0.1', 'localhost' ), true ) ) {
					$url = 'http://127.0.0.1:8000/api';
				}
			}
		}

		$url = untrailingslashit( (string) apply_filters( 'ajth_laravel_api_base_url', $url ) );

		if ( '' === $url ) {
			return '';
		}

		return preg_match( '#/api$#', $url ) ? $url : $url . '/api';
	}
}

if ( ! function_exists( 'ajth_fetch_laravel_catalog_json' ) ) {
	/**
	 * Fetches a JSON payload from Laravel and caches it briefly.
	 *
	 * @param string $path      API path beginning with a slash.
	 * @param string $cache_key Unique transient key.
	 * @param int    $ttl       Cache TTL in seconds.
	 * @return array<string, mixed>
	 */
	function ajth_fetch_laravel_catalog_json( $path, $cache_key, $ttl = 300 ) {
		$cached = get_transient( $cache_key );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$base_url = ajth_laravel_api_base_url();
		if ( '' === $base_url ) {
			return array();
		}

		$path = '/' . ltrim( (string) $path, '/' );

		$response = wp_remote_get(
			$base_url . $path,
			array(
				'timeout' => 10,
				'headers' => array(
					'Accept' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( $status < 200 || $status >= 300 ) {
			return array();
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( (string) $body, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		set_transient( $cache_key, $data, $ttl );

		return $data;
	}
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

if ( ! function_exists( 'ajth_hebergement_extract_first_location_id' ) ) {
	/**
	 * Extract the first Traveler location id from a scalar / multi_location value.
	 *
	 * @param mixed $value Raw meta value.
	 * @return int
	 */
	function ajth_hebergement_extract_first_location_id( $value ) {
		if ( is_numeric( $value ) ) {
			return max( 0, (int) $value );
		}

		if ( ! is_string( $value ) ) {
			return 0;
		}

		$value = trim( $value );
		if ( '' === $value ) {
			return 0;
		}

		if ( preg_match( '/_(\d+)_/', $value, $matches ) ) {
			return (int) ( $matches[1] ?? 0 );
		}

		if ( preg_match( '/\d+/', $value, $matches ) ) {
			return (int) ( $matches[0] ?? 0 );
		}

		return 0;
	}
}

if ( ! function_exists( 'ajth_hebergement_resolve_location_context' ) ) {
	/**
	 * Resolve Traveler city / country labels from hotel meta / location posts.
	 *
	 * @param int   $post_id Hotel post id.
	 * @param array $detail  Optional st_hotel row as array.
	 * @return array<string, string>
	 */
	function ajth_hebergement_resolve_location_context( $post_id, array $detail = array() ) {
		$post_id      = (int) $post_id;
		$location_id  = ajth_hebergement_extract_first_location_id( $detail['id_location'] ?? get_post_meta( $post_id, 'location_id', true ) );
		$multi_id     = ajth_hebergement_extract_first_location_id( $detail['multi_location'] ?? get_post_meta( $post_id, 'multi_location', true ) );
		$location_id  = $location_id > 0 ? $location_id : $multi_id;
		$city         = '';
		$country      = '';
		$destination  = '';

		if ( $location_id > 0 ) {
			$location = get_post( $location_id );
			if ( $location instanceof WP_Post && 'location' === $location->post_type ) {
				$destination = trim( (string) $location->post_title );
				if ( $location->post_parent > 0 ) {
					$parent = get_post( (int) $location->post_parent );
					if ( $parent instanceof WP_Post && 'location' === $parent->post_type ) {
						$country = trim( (string) $parent->post_title );
					}
				}
			}
		}

		$city = trim(
			(string) (
				get_post_meta( $post_id, 'city', true )
				?: get_post_meta( $post_id, 'location', true )
				?: $destination
			)
		);

		$country = trim(
			(string) (
				get_post_meta( $post_id, 'country', true )
				?: $country
			)
		);

		return array(
			'city'        => $city,
			'destination' => $destination !== '' ? $destination : $city,
			'country'     => $country,
		);
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

			$address  = get_post_meta( $post_id, 'address', true );
			$stars    = get_post_meta( $post_id, 'hotel_star', true );
			$detail   = array();

			if ( ( '' === $address || false === $address || '' === $price || false === $price || '' === $stars || false === $stars ) && isset( $wpdb ) ) {
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT address, min_price, hotel_star, is_featured, id_location, multi_location FROM {$wpdb->prefix}st_hotel WHERE post_id = %d", $post_id ), ARRAY_A );
				if ( is_array( $row ) ) {
					$detail = $row;
					if ( ( '' === $address || false === $address ) && ! empty( $row['address'] ) ) {
						$address = $row['address'];
					}
					if ( ( '' === $price || false === $price ) && isset( $row['min_price'] ) && '' !== $row['min_price'] ) {
						$price = $row['min_price'];
					}
					if ( ( '' === $stars || false === $stars ) && isset( $row['hotel_star'] ) && '' !== $row['hotel_star'] ) {
						$stars = $row['hotel_star'];
					}
				}
			}

			$location_context = ajth_hebergement_resolve_location_context( $post_id, $detail );
			$city             = trim( (string) ( $location_context['city'] ?? '' ) );
			$destination      = trim( (string) ( $location_context['destination'] ?? '' ) );
			$country          = trim( (string) ( $location_context['country'] ?? '' ) );
			$location_parts   = array_values( array_filter( array( $city, $destination, $country ) ) );
			$location_label   = '';
			if ( ! empty( $location_parts ) ) {
				$location_parts = array_values( array_unique( $location_parts ) );
				$location_label = implode( ', ', $location_parts );
			} elseif ( is_string( $address ) ) {
				$location_label = trim( $address );
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
				'kind'        => 'hotel',
				'title'       => get_the_title(),
				'name'        => get_the_title(),
				'url'         => get_permalink(),
				'image_url'   => $image_url,
				'image'       => $image_url,
				'location'    => $location_label,
				'city'        => $city,
				'destination' => $destination,
				'country'     => $country,
				'address'     => is_string( $address ) ? trim( $address ) : '',
				'category'    => $category,
				'type_label'  => $category,
				'type'        => $type !== '' ? $type : 'hotel',
				'stars'       => is_numeric( $stars ) ? (int) $stars : 0,
				'price'       => is_numeric( $price ) ? (float) $price : null,
				'excerpt'     => $excerpt,
				'description' => $excerpt,
				'amenities'   => $amenities,
				'popular'     => (bool) $is_popular,
				'available'   => true,
				'availability_label' => 'Disponible',
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

if ( ! function_exists( 'ajth_hebergement_slugify' ) ) {
	/**
	 * Lightweight slugifier for JS-facing payloads.
	 *
	 * @param string $value Raw label.
	 * @return string
	 */
	function ajth_hebergement_slugify( $value ) {
		$value = remove_accents( strtolower( trim( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );

		return trim( (string) $value, '-' );
	}
}

if ( ! function_exists( 'ajth_map_pension_to_key' ) ) {
	/**
	 * Maps a pension label to the JS filter key.
	 *
	 * @param string $label Pension label.
	 * @return string
	 */
	function ajth_map_pension_to_key( $label ) {
		$normalized = ajth_hebergement_slugify( $label );

		$map = array(
			'petit-dejeuner-inclus' => 'breakfast',
			'petit-dejeuner'        => 'breakfast',
			'demi-pension'          => 'half_board',
			'pension-complete'      => 'full_board',
			'sans-repas'            => 'room_only',
		);

		return $map[ $normalized ] ?? 'room_only';
	}
}

if ( ! function_exists( 'ajth_map_accommodation_type_to_key' ) ) {
	/**
	 * Maps accommodation types to JS filter keys.
	 *
	 * @param string $label Type label.
	 * @return string
	 */
	function ajth_map_accommodation_type_to_key( $label ) {
		$normalized = ajth_hebergement_slugify( $label );

		$map = array(
			'hotel'           => 'hotel',
			'riad'            => 'riad',
			'appartement'     => 'apartment',
			'villa'           => 'villa',
			'maison-d-hotes'  => 'guest-house',
			'maison-dhotes'   => 'guest-house',
			'maison-d-hotes-' => 'guest-house',
		);

		return $map[ $normalized ] ?? 'hotel';
	}
}

if ( ! function_exists( 'ajth_map_package_include_to_key' ) ) {
	/**
	 * Maps include labels to existing front-end amenity keys.
	 *
	 * @param string $label Include label.
	 * @return string
	 */
	function ajth_map_package_include_to_key( $label ) {
		$normalized = ajth_hebergement_slugify( $label );

		$map = array(
			'hebergement'            => 'hebergement',
			'petit-dejeuner'         => 'breakfast',
			'petit-dejeuner-inclus'  => 'breakfast',
			'demi-pension'           => 'half_board',
			'pension-complete'       => 'full_board',
			'transfert'              => 'transfer',
			'transfert-optionnel'    => 'transfer',
			'activite-optionnelle'   => 'activity',
			'offre-famille'          => 'family',
			'conseils-locaux'        => 'assistance',
			'guide-optionnel'        => 'assistance',
			'assistance-ajinsafro'   => 'assistance',
			'support-reservation'    => 'assistance',
		);

		return $map[ $normalized ] ?? $normalized;
	}
}

if ( ! function_exists( 'ajth_get_accommodation_package_public_url' ) ) {
	/**
	 * Resolve a public URL for an accommodation package.
	 *
	 * If no dedicated detail page exists yet, fall back to the hebergement page
	 * with a stable `pack` query parameter rather than `#`.
	 *
	 * @param array<string, mixed> $row Package payload.
	 * @return string
	 */
	function ajth_get_accommodation_package_public_url( array $row ) {
		foreach ( array( 'url', 'detail_url', 'permalink', 'link' ) as $key ) {
			$value = trim( (string) ( $row[ $key ] ?? '' ) );
			if ( '' !== $value && '#' !== $value ) {
				return $value;
			}
		}

		$base = function_exists( 'ajth_get_hebergement_page_url' )
			? ajth_get_hebergement_page_url()
			: home_url( '/hebergement/' );

		$pack_key = trim( (string) ( $row['slug'] ?? '' ) );
		if ( '' === $pack_key ) {
			$pack_key = trim( (string) ( $row['id'] ?? '' ) );
		}

		return '' !== $pack_key ? add_query_arg( array( 'pack' => $pack_key ), $base ) : $base;
	}
}

if ( ! function_exists( 'ajth_get_accommodation_packages' ) ) {
	/**
	 * Returns accommodation packages from Laravel.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	function ajth_get_accommodation_packages() {
		$payload = ajth_fetch_laravel_catalog_json( '/accommodation-packages', 'ajth_accommodation_packages_v1' );
		$rows    = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : array();

		return array_values(
			array_map(
				static function ( $row ) {
					$row = is_array( $row ) ? $row : array();

					$pension_label = (string) ( $row['pension_type'] ?? 'Sans repas' );
					$type_label    = (string) ( $row['accommodation_type'] ?? 'Hôtel' );
					$badge         = trim( (string) ( $row['badge'] ?? '' ) );
					$includes      = isset( $row['includes'] ) && is_array( $row['includes'] ) ? array_values( $row['includes'] ) : array();

					$url = ajth_get_accommodation_package_public_url( $row );

					return array(
						'kind'         => 'pack',
						'id'           => (string) ( $row['slug'] ?? $row['id'] ?? '' ),
						'title'        => (string) ( $row['title'] ?? '' ),
						'city'         => (string) ( $row['city'] ?? '' ),
						'country'      => (string) ( $row['country'] ?? 'Maroc' ),
						'duration'     => sprintf( '%d jours / %d nuits', (int) ( $row['duration_days'] ?? 0 ), (int) ( $row['nights'] ?? 0 ) ),
						'days'         => (int) ( $row['duration_days'] ?? 0 ),
						'nights'       => (int) ( $row['nights'] ?? 0 ),
						'pension'      => ajth_map_pension_to_key( $pension_label ),
						'pensionLabel' => $pension_label,
						'type'         => ajth_map_accommodation_type_to_key( $type_label ),
						'typeLabel'    => $type_label,
						'price'        => isset( $row['price_from'] ) ? (float) $row['price_from'] : null,
						'oldPrice'     => null,
						'image'        => (string) ( $row['image_url'] ?? '' ),
						'badges'       => array_values( array_filter( array( $badge ) ) ),
						'includes'     => array_map( 'ajth_map_package_include_to_key', $includes ),
						'highlights'   => array_values(
							array_filter(
								array(
									( $row['nights'] ?? null ) ? ( (int) $row['nights'] . ' nuits' ) : '',
									$pension_label,
									(string) ( $row['country'] ?? '' ),
								)
							)
						),
						'description'  => (string) ( $row['short_description'] ?? '' ),
						'popular'      => ! empty( $row['is_featured'] ),
						'available'    => ! isset( $row['is_active'] ) || ! empty( $row['is_active'] ),
						'order'        => (int) ( $row['order'] ?? 0 ),
						'url'          => $url,
					);
				},
				$rows
			)
		);
	}
}
