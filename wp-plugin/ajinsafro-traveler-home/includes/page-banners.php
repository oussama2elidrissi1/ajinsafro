<?php
/**
 * Bannieres de pages pilotees depuis l'admin Laravel (table page_banners).
 *
 * Lecture via l'API publique, avec un cache transient que Laravel invalide
 * a l'enregistrement (cle : ajth_page_banner_{page}_v1). Une API injoignable
 * ne doit pas ralentir la page : delai court et cache negatif.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'ajth_get_page_banner' ) ) {
	/**
	 * Banniere active d'une page, ou null si aucune n'est definie.
	 *
	 * @param string $page_key 'voyages', ...
	 * @return array{image_url:string,alt_text:string,link_url:string}|null
	 */
	function ajth_get_page_banner( $page_key ) {
		$page_key = sanitize_key( (string) $page_key );
		if ( '' === $page_key ) {
			return null;
		}

		$cache_key = 'ajth_page_banner_' . $page_key . '_v1';
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			// Cache negatif : l'absence de banniere est memorisee aussi.
			return ! empty( $cached['image_url'] ) ? $cached : null;
		}

		$base_url = function_exists( 'ajth_laravel_api_base_url' ) ? ajth_laravel_api_base_url() : '';
		if ( '' === $base_url ) {
			return null;
		}

		$response = wp_remote_get(
			$base_url . '/public/page-banners/' . rawurlencode( $page_key ),
			array(
				'timeout' => 3,
				'headers' => array( 'Accept' => 'application/json' ),
			)
		);

		$banner = null;
		$ttl    = MINUTE_IN_SECONDS; // Echec ou vide : on reessaie vite.

		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$payload = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			$data    = is_array( $payload ) && isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : null;

			$image_url = $data ? esc_url_raw( (string) ( $data['image_url'] ?? '' ) ) : '';
			if ( '' !== $image_url ) {
				$banner = array(
					'image_url' => $image_url,
					'alt_text'  => sanitize_text_field( (string) ( $data['alt_text'] ?? '' ) ),
					'link_url'  => esc_url_raw( (string) ( $data['link_url'] ?? '' ) ),
				);
			}

			// Reponse valide : le cache tient jusqu'a l'invalidation par Laravel.
			$ttl = 5 * MINUTE_IN_SECONDS;
		}

		set_transient( $cache_key, $banner ?? array( 'image_url' => '' ), $ttl );

		return $banner;
	}
}
