<?php
/**
 * URL canonique /voyages/ + redirections des anciennes URLs (?post_type=st_tours, archive CPT).
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Paramètres GET conservés lors d'une redirection vers le listing (hors post_type).
 *
 * @return list<string>
 */
function ajth_voyages_list_allowed_query_vars(): array
{
    return [
        's',
        'location_name',
        'cat',
        'tag',
        'dest',
        'featured',
        'depart_date',
        'duration_min',
        'duration_max',
        'price_min',
        'price_max',
        'catalog_orderby',
        'paged',
        'page',
    ];
}

/**
 * URL absolue du catalogue voyages (page « Voyages » si elle existe, sinon /voyages/).
 *
 * @return string
 */
function ajth_get_voyages_canonical_url(): string
{
    if (function_exists('ajth_get_voyages_page_url')) {
        return ajth_get_voyages_page_url();
    }

    $page = get_page_by_path('voyages');
    if ($page instanceof WP_Post) {
        $url = get_permalink($page);
        if ($url) {
            return $url;
        }
    }

    return home_url('/voyages/');
}

/**
 * Reconstruit l’URL du listing avec uniquement les paramètres utiles (sans post_type=st_tours).
 *
 * @return string
 */
function ajth_build_voyages_list_url_from_request(): string
{
    $base = ajth_get_voyages_canonical_url();
    $args = [];

    foreach (ajth_voyages_list_allowed_query_vars() as $key) {
        if (! isset($_GET[$key])) {
            continue;
        }
        $raw = wp_unslash($_GET[$key]);
        if (is_array($raw)) {
            continue;
        }
        $val = sanitize_text_field((string) $raw);
        if ($val === '') {
            continue;
        }
        $args[$key] = $val;
    }

    if ($args === []) {
        return $base;
    }

    return add_query_arg($args, $base);
}

/**
 * Redirige les anciennes URLs WordPress du catalogue tours vers /voyages/ (params utiles conservés).
 */
function ajth_redirect_legacy_voyages_urls(): void
{
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    if (is_feed() || is_embed()) {
        return;
    }

    if (is_singular('st_tours')) {
        return;
    }

    $canon_path = wp_parse_url(ajth_get_voyages_canonical_url(), PHP_URL_PATH);
    $canon_path = $canon_path ? untrailingslashit((string) $canon_path) : '/voyages';

    // 1) ?post_type=st_tours (accueil, recherche, ou /voyages/ avec paramètres parasites)
    if (isset($_GET['post_type']) && (string) wp_unslash($_GET['post_type']) === 'st_tours') {
        wp_safe_redirect(ajth_build_voyages_list_url_from_request(), 301);
        exit;
    }

    // 2) Archive native du CPT (permalink différent de la page « Voyages »)
    if (is_post_type_archive('st_tours')) {
        $req = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $parsed_req = wp_parse_url($req);
        $path_only = isset($parsed_req['path']) ? untrailingslashit((string) $parsed_req['path']) : '';
        if ($path_only !== '' && $path_only !== $canon_path) {
            wp_safe_redirect(ajth_build_voyages_list_url_from_request(), 301);
            exit;
        }
    }
}

add_action('template_redirect', 'ajth_redirect_legacy_voyages_urls', 1);
