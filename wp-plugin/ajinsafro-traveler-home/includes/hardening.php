<?php
/**
 * Durcissement de la surface publique WordPress.
 *
 * Constaté à l'audit du 24/09/2026 : `/?author=1` redirigeait vers `/author/ajinsafro/` et
 * `/wp-json/wp/v2/users` listait le même identifiant — le login de l'administrateur était
 * lisible par n'importe qui, cible toute trouvée pour la force brute. L'éditeur de fichiers de
 * l'admin restait actif : un compte compromis y gagne un éditeur PHP dans le navigateur.
 *
 * Tout ici ne concerne que les visiteurs non connectés ; l'admin et les agents ne voient aucune
 * différence. Versionné dans le plugin plutôt que posé à la main sur le serveur, pour suivre
 * les déploiements et la migration.
 *
 * @package AjinsafroTravelerHome
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// L'éditeur de thèmes et d'extensions de l'admin : inutile en production, dangereux si un
// compte est compromis. Défini ici s'il ne l'est pas déjà dans wp-config.php.
if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}

/**
 * Retire les points de terminaison REST qui listent les utilisateurs, pour les visiteurs.
 *
 * @param array $endpoints Points de terminaison enregistrés.
 * @return array
 */
function ajth_hardening_hide_users_endpoints( $endpoints ) {
	if ( is_user_logged_in() ) {
		return $endpoints;
	}
	foreach ( array( '/wp/v2/users', '/wp/v2/users/(?P<id>[\d]+)', '/wp/v2/users/me' ) as $route ) {
		unset( $endpoints[ $route ] );
	}

	return $endpoints;
}
add_filter( 'rest_endpoints', 'ajth_hardening_hide_users_endpoints' );

/**
 * Bloque l'énumération des auteurs : `?author=N` et les archives `/author/{login}/`
 * renvoient une 404 aux visiteurs. Branché avant la redirection canonique, qui sinon
 * transforme `?author=1` en `/author/{login}/` et livre l'identifiant dans l'URL.
 */
function ajth_hardening_block_author_enumeration() {
	if ( is_user_logged_in() ) {
		return;
	}

	$is_numeric_author = isset( $_GET['author'] ) && is_numeric( $_GET['author'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! $is_numeric_author && ! is_author() ) {
		return;
	}

	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
add_action( 'template_redirect', 'ajth_hardening_block_author_enumeration', 0 );

/**
 * Empêche la redirection canonique de révéler le login à partir de `?author=N`.
 *
 * @param string $redirect_url URL cible calculée par WordPress.
 * @return string|false
 */
function ajth_hardening_no_author_canonical( $redirect_url ) {
	if ( ! is_user_logged_in() && isset( $_GET['author'] ) && is_numeric( $_GET['author'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return false;
	}

	return $redirect_url;
}
add_filter( 'redirect_canonical', 'ajth_hardening_no_author_canonical' );
