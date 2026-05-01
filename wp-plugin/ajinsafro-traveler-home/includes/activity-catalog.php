<?php
/**
 * Laravel-backed activities catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_activity_parse_duration_hours' ) ) {
	/**
	 * Convert a human duration label into an approximate number of hours.
	 *
	 * @param string $label Duration label.
	 * @return int
	 */
	function ajth_activity_parse_duration_hours( $label ) {
		$text = remove_accents( strtolower( trim( (string) $label ) ) );

		if ( '' === $text ) {
			return 0;
		}

		if ( false !== strpos( $text, 'demi-journee' ) || false !== strpos( $text, 'demi journee' ) ) {
			return 5;
		}

		if ( false !== strpos( $text, 'journee complete' ) ) {
			return 8;
		}

		if ( preg_match( '/(\d+)/', $text, $matches ) ) {
			$value = (int) $matches[1];
			if ( false !== strpos( $text, 'jour' ) ) {
				return $value * 24;
			}

			return $value;
		}

		return 0;
	}
}

if ( ! function_exists( 'ajth_activity_slugify_label' ) ) {
	/**
	 * Slugify a plain label for lightweight front-end matching.
	 *
	 * @param string $value Raw label.
	 * @return string
	 */
	function ajth_activity_slugify_label( $value ) {
		$value = remove_accents( strtolower( trim( (string) $value ) ) );
		$value = preg_replace( '/[^a-z0-9]+/', '-', $value );

		return trim( (string) $value, '-' );
	}
}

if ( ! function_exists( 'ajth_get_activity_public_url' ) ) {
	/**
	 * Canonical detail URL for an activity offer.
	 *
	 * @param string $slug Activity slug.
	 * @return string
	 */
	function ajth_get_activity_detail_url( $slug ) {
		$slug = sanitize_title( (string) $slug );
		$base = function_exists( 'ajth_get_activites_page_url' )
			? ajth_get_activites_page_url()
			: home_url( '/activites/' );

		if ( '' === $slug ) {
			return $base;
		}

		return trailingslashit( trailingslashit( $base ) . 'activite/' . rawurlencode( $slug ) );
	}

	/**
	 * Resolve a navigable public URL for an activity.
	 *
	 * @param array<string, mixed> $row Source row.
	 * @return string
	 */
	function ajth_get_activity_public_url( array $row ) {
		$slug = trim( (string) ( $row['slug'] ?? '' ) );
		if ( '' !== $slug ) {
			return ajth_get_activity_detail_url( $slug );
		}

		$base = function_exists( 'ajth_get_activites_page_url' )
			? ajth_get_activites_page_url()
			: home_url( '/activites/' );
		$fallback_key = (string) ( $row['id'] ?? '' );

		return '' !== $fallback_key ? add_query_arg( array( 'activity' => $fallback_key ), $base ) : $base;
	}
}

if ( ! function_exists( 'ajth_get_current_activity_offer_slug' ) ) {
	/**
	 * Resolve current activity offer slug from rewrite or query.
	 *
	 * @return string
	 */
	function ajth_get_current_activity_offer_slug() {
		$slug = get_query_var( 'ajth_activite_offer', '' );
		if ( '' === $slug && isset( $_GET['activity'] ) ) {
			$slug = wp_unslash( $_GET['activity'] );
		}

		return sanitize_title( (string) $slug );
	}
}

if ( ! function_exists( 'ajth_get_activities' ) ) {
	/**
	 * Returns activity offers from Laravel, normalized for the WordPress front.
	 *
	 * @param int   $limit Number of items to return.
	 * @param array $args  Kept for future CRUD compatibility.
	 * @return array<int, array<string, mixed>>
	 */
	function ajth_get_activities( $limit = 12, array $args = array() ) {
		$payload = function_exists( 'ajth_fetch_laravel_catalog_json' )
			? ajth_fetch_laravel_catalog_json( '/activity-offers', 'ajth_activity_offers_v1' )
			: array();

		$rows = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : array();

		$items = array_map(
			static function ( $row ) {
				$row          = is_array( $row ) ? $row : array();
				$includes     = isset( $row['includes'] ) && is_array( $row['includes'] ) ? array_values( $row['includes'] ) : array();
				$includesText = implode( ' ', array_map( 'strtolower', $includes ) );
				$badge        = trim( (string) ( $row['badge'] ?? '' ) );
				$duration     = (string) ( $row['duration_label'] ?? '' );

				$url = ajth_get_activity_public_url( $row );

				return array(
					'id'                 => (int) ( $row['id'] ?? 0 ),
					'title'              => (string) ( $row['title'] ?? '' ),
					'slug'               => (string) ( $row['slug'] ?? '' ),
					'country'            => (string) ( $row['country'] ?? '' ),
					'city'               => (string) ( $row['city'] ?? '' ),
					'category'           => (string) ( $row['category'] ?? '' ),
					'duration_hours'     => ajth_activity_parse_duration_hours( $duration ),
					'duration_label'     => $duration,
					'price'              => isset( $row['price_from'] ) ? (float) $row['price_from'] : null,
					'image'              => (string) ( $row['image_url'] ?? '' ),
					'featured'           => ! empty( $row['is_featured'] ),
					'rating'             => ! empty( $row['is_featured'] ) ? 4.8 : 4.6,
					'reviews'            => ! empty( $row['is_featured'] ) ? 140 : 64,
					'includes'           => $includes,
					'availability'       => (string) ( $row['availability_label'] ?? 'Disponible' ),
					'available_today'    => false !== stripos( (string) ( $row['availability_label'] ?? '' ), 'disponible' ),
					'instant_booking'    => ! empty( $row['is_featured'] ),
					'with_guide'         => false !== strpos( $includesText, 'guide' ),
					'transport_included' => false !== strpos( $includesText, 'transport' ),
					'badge'              => $badge,
					'url'                => $url,
					'booking_url'        => $url,
					'short_description'  => (string) ( $row['short_description'] ?? '' ),
				);
			},
			$rows
		);

		$items = array_values( array_filter( $items, static fn ( $item ) => ! empty( $item['title'] ) ) );

		return array_slice( $items, 0, max( 1, (int) $limit ) );
	}
}

if ( ! function_exists( 'ajth_get_activity_offer_by_slug' ) ) {
	/**
	 * Find one activity offer by slug.
	 *
	 * @param string $slug Requested slug.
	 * @return array<string, mixed>|null
	 */
	function ajth_get_activity_offer_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}

		foreach ( ajth_get_activities( 250 ) as $row ) {
			$row_slug = sanitize_title( (string) ( $row['slug'] ?? $row['id'] ?? '' ) );
			if ( $row_slug === $slug ) {
				return $row;
			}
		}

		return null;
	}
}

if ( ! function_exists( 'ajth_get_activity_offer_reservation_url' ) ) {
	/**
	 * Temporary reservation CTA for activity offers.
	 *
	 * @param array<string, mixed> $row Activity payload.
	 * @return string
	 */
	function ajth_get_activity_offer_reservation_url( array $row ) {
		$title = trim( (string) ( $row['title'] ?? 'Activité Ajinsafro' ) );
		$url   = trim( (string) ( $row['url'] ?? ajth_get_activity_public_url( $row ) ) );
		$text  = rawurlencode( sprintf( 'Bonjour Ajinsafro, je souhaite réserver l’activité "%s". Voici le lien: %s', $title, $url ) );

		return 'https://wa.me/212660683464?text=' . $text;
	}
}
