<?php
/**
 * Economic offers catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_get_economic_offers_page_url' ) ) {
	function ajth_get_economic_offers_page_url() {
		$page = get_page_by_path( 'formule-economique' );
		if ( $page instanceof WP_Post ) {
			$url = get_permalink( $page );
			if ( $url ) {
				return $url;
			}
		}

		return home_url( '/formule-economique/' );
	}
}

if ( ! function_exists( 'ajth_economic_offers_default_image_url' ) ) {
	function ajth_economic_offers_default_image_url() {
		return trailingslashit( AJTH_URL ) . 'assets/images/fallback-hajj-omra.svg';
	}
}

if ( ! function_exists( 'ajth_economic_offers_normalize_image_url' ) ) {
	function ajth_economic_offers_normalize_image_url( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return '';
		}

		if ( function_exists( 'ajth_normalize_storage_url' ) ) {
			$url = ajth_normalize_storage_url( $url );
		}

		return $url;
	}
}

if ( ! function_exists( 'ajth_get_economic_offer_detail_url' ) ) {
	function ajth_get_economic_offer_detail_url( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return ajth_get_economic_offers_page_url();
		}

		return untrailingslashit( ajth_get_economic_offers_page_url() ) . '/' . $slug . '/';
	}
}

if ( ! function_exists( 'ajth_prepare_economic_offer_payload' ) ) {
	function ajth_prepare_economic_offer_payload( array $offer ) {
		$slug = sanitize_title( (string) ( $offer['slug'] ?? '' ) );

		$main_image = ajth_economic_offers_normalize_image_url( $offer['main_image_url'] ?? '' );
		$fallback   = ajth_economic_offers_normalize_image_url( $offer['fallback_image_url'] ?? '' );
		$gallery    = array();

		foreach ( (array) ( $offer['gallery'] ?? array() ) as $image_url ) {
			$image_url = ajth_economic_offers_normalize_image_url( $image_url );
			if ( '' !== $image_url ) {
				$gallery[] = $image_url;
			}
		}

		foreach ( array( $main_image, $fallback ) as $priority_image ) {
			if ( '' !== $priority_image ) {
				array_unshift( $gallery, $priority_image );
			}
		}

		$gallery = array_values( array_unique( array_filter( $gallery ) ) );

		if ( '' === $main_image ) {
			$main_image = ! empty( $gallery[0] ) ? $gallery[0] : ajth_economic_offers_default_image_url();
		}

		if ( empty( $gallery ) ) {
			$gallery = array( $main_image );
		}

		$offer['slug']              = $slug;
		$offer['main_image_url']    = $main_image;
		$offer['fallback_image_url'] = '' !== $fallback ? $fallback : ajth_economic_offers_default_image_url();
		$offer['gallery']           = $gallery;
		$offer['detail_url']        = ajth_get_economic_offer_detail_url( $slug );
		$offer['request_url']       = ajth_get_economic_offer_detail_url( $slug ) . '#reservation-form';

		return $offer;
	}
}

if ( ! function_exists( 'ajth_is_economic_offers_context' ) ) {
	function ajth_is_economic_offers_context() {
		return is_page( 'formule-economique' ) || is_page( 'low-cost' ) || (bool) get_query_var( 'ajth_economic_offer' );
	}
}

if ( ! function_exists( 'ajth_get_current_economic_offer_slug' ) ) {
	function ajth_get_current_economic_offer_slug() {
		$slug = get_query_var( 'ajth_economic_offer' );

		return is_string( $slug ) ? sanitize_title( $slug ) : '';
	}
}

if ( ! function_exists( 'ajth_register_economic_offer_routes' ) ) {
	function ajth_register_economic_offer_routes() {
		add_rewrite_tag( '%ajth_economic_offer%', '([^&]+)' );
		add_rewrite_rule( '^low-cost/?$', 'index.php?pagename=formule-economique', 'top' );
		add_rewrite_rule( '^formule-economique/([^/]+)/?$', 'index.php?pagename=formule-economique&ajth_economic_offer=$matches[1]', 'top' );
		add_rewrite_rule( '^low-cost/([^/]+)/?$', 'index.php?pagename=formule-economique&ajth_economic_offer=$matches[1]', 'top' );
	}
	add_action( 'init', 'ajth_register_economic_offer_routes', 33 );
}

if ( ! function_exists( 'ajth_query_vars_economic_offer' ) ) {
	function ajth_query_vars_economic_offer( $vars ) {
		$vars[] = 'ajth_economic_offer';

		return $vars;
	}
	add_filter( 'query_vars', 'ajth_query_vars_economic_offer' );
}

if ( ! function_exists( 'ajth_maybe_flush_rewrite_rules_economic_offer' ) ) {
	function ajth_maybe_flush_rewrite_rules_economic_offer() {
		if ( get_option( 'ajth_economic_offer_routing_flush_v1' ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( 'ajth_economic_offer_routing_flush_v1', '1', true );
	}
	add_action( 'init', 'ajth_maybe_flush_rewrite_rules_economic_offer', 99 );
}

if ( ! function_exists( 'ajth_ensure_economic_offers_page' ) ) {
	function ajth_ensure_economic_offers_page() {
		$page = get_page_by_path( 'formule-economique' );
		if ( $page instanceof WP_Post ) {
			if ( 'trash' === $page->post_status ) {
				wp_untrash_post( (int) $page->ID );
			}
			if ( 'publish' !== $page->post_status ) {
				wp_update_post(
					array(
						'ID'          => (int) $page->ID,
						'post_status' => 'publish',
					)
				);
			}

			return;
		}

		wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_status'  => 'publish',
				'post_title'   => 'Formule Économique',
				'post_name'    => 'formule-economique',
				'post_content' => '<!-- Ajinsafro Traveler Home : template economic-offers (plugin). -->',
			)
		);
	}
	add_action( 'init', 'ajth_ensure_economic_offers_page', 20 );
}

if ( ! function_exists( 'ajth_get_economic_offers' ) ) {
	function ajth_get_economic_offers() {
		$payload = ajth_fetch_laravel_catalog_json( '/public/economic-offers', 'ajth_economic_offers_v1', 300 );
		$items   = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : array();

		return array_values(
			array_map(
				'ajth_prepare_economic_offer_payload',
				array_filter( $items, 'is_array' )
			)
		);
	}
}

if ( ! function_exists( 'ajth_get_economic_offer_by_slug' ) ) {
	function ajth_get_economic_offer_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}

		$payload = ajth_fetch_laravel_catalog_json( '/public/economic-offers/' . rawurlencode( $slug ), 'ajth_economic_offer_' . $slug . '_v1', 180 );
		$item    = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : null;

		return is_array( $item ) ? ajth_prepare_economic_offer_payload( $item ) : null;
	}
}

if ( ! function_exists( 'ajth_submit_economic_offer_request' ) ) {
	function ajth_submit_economic_offer_request( $slug, array $payload ) {
		$slug     = sanitize_title( (string) $slug );
		$base_url = ajth_laravel_api_base_url();

		if ( '' === $slug || '' === $base_url ) {
			return new WP_Error( 'ajth_economic_offer_api', 'La connexion avec l API Formule Economique est indisponible.' );
		}

		$response = wp_remote_post(
			untrailingslashit( $base_url ) . '/public/economic-offers/' . rawurlencode( $slug ) . '/requests',
			array(
				'timeout' => 15,
				'headers' => array(
					'Accept' => 'application/json',
				),
				'body'    => $payload,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( $status < 200 || $status >= 300 ) {
			$message = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : 'La demande n a pas pu etre envoyee.';

			return new WP_Error( 'ajth_economic_offer_submit', $message, $data );
		}

		return is_array( $data ) ? $data : array();
	}
}
