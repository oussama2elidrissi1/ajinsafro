<?php
/**
 * Hajj & Omra catalog helpers.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_get_hajj_omra_page_url' ) ) {
	function ajth_get_hajj_omra_page_url() {
		$page = get_page_by_path( 'hajj-omra' );
		if ( $page instanceof WP_Post ) {
			$url = get_permalink( $page );
			if ( $url ) {
				return $url;
			}
		}

		return home_url( '/hajj-omra/' );
	}
}

if ( ! function_exists( 'ajth_is_hajj_omra_context' ) ) {
	function ajth_is_hajj_omra_context() {
		return is_page( 'hajj-omra' ) || (bool) get_query_var( 'ajth_hajj_omra_package' );
	}
}

if ( ! function_exists( 'ajth_get_current_hajj_omra_package_slug' ) ) {
	function ajth_get_current_hajj_omra_package_slug() {
		$slug = get_query_var( 'ajth_hajj_omra_package' );

		return is_string( $slug ) ? sanitize_title( $slug ) : '';
	}
}

if ( ! function_exists( 'ajth_register_hajj_omra_routes' ) ) {
	function ajth_register_hajj_omra_routes() {
		add_rewrite_tag( '%ajth_hajj_omra_package%', '([^&]+)' );
		add_rewrite_rule( '^hajj-omra/([^/]+)/?$', 'index.php?pagename=hajj-omra&ajth_hajj_omra_package=$matches[1]', 'top' );
	}
	add_action( 'init', 'ajth_register_hajj_omra_routes', 33 );
}

if ( ! function_exists( 'ajth_query_vars_hajj_omra_package' ) ) {
	function ajth_query_vars_hajj_omra_package( $vars ) {
		$vars[] = 'ajth_hajj_omra_package';

		return $vars;
	}
	add_filter( 'query_vars', 'ajth_query_vars_hajj_omra_package' );
}

if ( ! function_exists( 'ajth_maybe_flush_rewrite_rules_hajj_omra' ) ) {
	function ajth_maybe_flush_rewrite_rules_hajj_omra() {
		if ( get_option( 'ajth_hajj_omra_routing_flush_v1' ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( 'ajth_hajj_omra_routing_flush_v1', '1', true );
	}
	add_action( 'init', 'ajth_maybe_flush_rewrite_rules_hajj_omra', 99 );
}

if ( ! function_exists( 'ajth_ensure_hajj_omra_page' ) ) {
	function ajth_ensure_hajj_omra_page() {
		$page = get_page_by_path( 'hajj-omra' );
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
				'post_title'   => 'Hajj & Omra',
				'post_name'    => 'hajj-omra',
				'post_content' => '<!-- Ajinsafro Traveler Home : template hajj-omra (plugin). -->',
			)
		);
	}
	add_action( 'init', 'ajth_ensure_hajj_omra_page', 20 );
}

if ( ! function_exists( 'ajth_get_hajj_omra_packages' ) ) {
	function ajth_get_hajj_omra_packages() {
		$payload = ajth_fetch_laravel_catalog_json( '/public/hajj-omra/packages', 'ajth_hajj_omra_packages_v1', 300 );
		$items   = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : array();

		return array_values( array_filter( $items, 'is_array' ) );
	}
}

if ( ! function_exists( 'ajth_get_hajj_omra_package_by_slug' ) ) {
	function ajth_get_hajj_omra_package_by_slug( $slug ) {
		$slug = sanitize_title( (string) $slug );
		if ( '' === $slug ) {
			return null;
		}

		$payload = ajth_fetch_laravel_catalog_json( '/public/hajj-omra/packages/' . rawurlencode( $slug ), 'ajth_hajj_omra_package_' . $slug . '_v1', 180 );
		$item    = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : null;

		return is_array( $item ) ? $item : null;
	}
}

if ( ! function_exists( 'ajth_submit_hajj_omra_booking_request' ) ) {
	function ajth_submit_hajj_omra_booking_request( $slug, array $payload ) {
		$slug     = sanitize_title( (string) $slug );
		$base_url = ajth_laravel_api_base_url();

		if ( '' === $slug || '' === $base_url ) {
			return new WP_Error( 'ajth_hajj_omra_api', 'La connexion avec l API Hajj & Omra est indisponible.' );
		}

		$response = wp_remote_post(
			untrailingslashit( $base_url ) . '/public/hajj-omra/packages/' . rawurlencode( $slug ) . '/booking-requests',
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

			return new WP_Error( 'ajth_hajj_omra_submit', $message, $data );
		}

		return is_array( $data ) ? $data : array();
	}
}
