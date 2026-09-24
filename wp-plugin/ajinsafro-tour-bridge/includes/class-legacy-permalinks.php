<?php
/**
 * Conservation des URLs du catalogue historique ajinsafro.ma.
 *
 * L'ancien site servait les fiches sous /voyage-national/<slug>, /voyages-international/<slug>
 * et, pour certains programmes sans autre URL lisible, /voyages-organisees/<slug> — toujours
 * sans barre finale, avec l'identifiant du programme en fin de slug (…-91). Il exposait aussi
 * des URL techniques (onedeal.php?id=91, /team/buy.php?id=91) et parfois une route alternative.
 *
 * Le nouveau site doit répondre à toutes, à l'identique quand c'est possible (seul le domaine
 * change) et en 301 vers le chemin canonique sinon : c'est ce qui préserve le référencement
 * acquis par ajinsafro.ma lors du retour sur ce domaine.
 *
 * - Le préfixe canonique d'un tour est porté par la meta `_aj_legacy_path_prefix`, posée par
 *   Laravel (`legacy:push-wp`, `legacy:sync-permalinks`) depuis le chemin historique stocké.
 * - La fiche est retrouvée par l'identifiant en fin de slug (`_ajinsafro_legacy_id`), puis par
 *   `post_name` : un slug WordPress divergent ne casse pas l'URL.
 * - Le permalien généré n'a pas de barre finale, pour que canonical, og:url et sitemap soient
 *   strictement l'ancienne URL. Les deux formes (avec et sans barre) répondent 200.
 *
 * Un tour sans meta de préfixe garde le permalien natif.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AJTB_Legacy_Permalinks {

    /** Meta portant le préfixe de chemin historique canonique du programme. */
    const META_PATH_PREFIX = '_aj_legacy_path_prefix';

    /** Meta portant l'identifiant du programme sur l'ancien site. */
    const META_LEGACY_ID = '_ajinsafro_legacy_id';

    /** Query var marquant une requête servie par une URL historique. */
    const QUERY_VAR = 'aj_legacy_path';

    const QUERY_VAR_PREFIX = 'aj_legacy_prefix';

    const QUERY_VAR_SLUG = 'aj_legacy_slug';

    /** Préfixes canoniques : servis en 200 quand ils correspondent au tour. */
    const PREFIXES = array('voyage-national', 'voyages-international', 'voyages-organisees');

    /** Routes alternatives de l'ancien site : toujours redirigées vers le chemin canonique. */
    const ALIAS_PREFIXES = array('team');

    /** Scripts de l'ancien site dont l'URL portait l'identifiant du programme en paramètre. */
    const TECHNICAL_SCRIPTS = array('onedeal.php', 'buy.php');

    /** Option de suivi, pour ne vider les règles de réécriture qu'au besoin. */
    const RULES_VERSION_OPTION = 'ajtb_legacy_rules_version';

    const RULES_VERSION = '2';

    public static function boot() {
        add_action('init', array(__CLASS__, 'register_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'register_query_var'));
        add_action('parse_request', array(__CLASS__, 'route_legacy_request'), 0);
        add_action('template_redirect', array(__CLASS__, 'redirect_technical_urls'), 0);
        add_filter('post_type_link', array(__CLASS__, 'filter_permalink'), 10, 2);
        add_filter('redirect_canonical', array(__CLASS__, 'keep_legacy_path'), 10, 2);
    }

    /**
     * Capture /<prefixe>/<slug> et /<prefixe>/ pour tous les préfixes connus ; la résolution se
     * fait dans route_legacy_request(), pas par `name=`, afin de retrouver la fiche par son
     * identifiant historique même quand le slug WordPress diverge.
     */
    public static function register_rewrite_rules() {
        $prefixes = implode('|', array_map('preg_quote', array_merge(self::PREFIXES, self::ALIAS_PREFIXES)));
        add_rewrite_rule(
            '^(' . $prefixes . ')/([^/]+)/?$',
            'index.php?' . self::QUERY_VAR_PREFIX . '=$matches[1]&' . self::QUERY_VAR_SLUG . '=$matches[2]',
            'top'
        );
        add_rewrite_rule(
            '^(' . $prefixes . ')/?$',
            'index.php?' . self::QUERY_VAR_PREFIX . '=$matches[1]',
            'top'
        );
        if (get_option(self::RULES_VERSION_OPTION) !== self::RULES_VERSION) {
            flush_rewrite_rules(false);
            update_option(self::RULES_VERSION_OPTION, self::RULES_VERSION);
        }
    }

    public static function register_query_var($vars) {
        $vars[] = self::QUERY_VAR;
        $vars[] = self::QUERY_VAR_PREFIX;
        $vars[] = self::QUERY_VAR_SLUG;
        return $vars;
    }

    /**
     * Sert la fiche au chemin canonique, redirige vers lui depuis une variante, renvoie au
     * catalogue depuis un préfixe nu.
     *
     * @param WP $wp
     */
    public static function route_legacy_request($wp) {
        $prefix = isset($wp->query_vars[self::QUERY_VAR_PREFIX]) ? (string) $wp->query_vars[self::QUERY_VAR_PREFIX] : '';
        if ($prefix === '') {
            return;
        }
        $slug = isset($wp->query_vars[self::QUERY_VAR_SLUG]) ? (string) $wp->query_vars[self::QUERY_VAR_SLUG] : '';

        if ($slug === '') {
            self::redirect(self::catalogue_url());
        }

        $post = self::resolve_post($slug);
        if (!$post) {
            $wp->query_vars = array('error' => '404');
            return;
        }

        $canonical_prefix = self::path_prefix_for($post->ID);
        if ($canonical_prefix === '' && in_array($prefix, self::PREFIXES, true)) {
            // Tour sans meta : la première URL historique qui le trouve fait foi.
            $canonical_prefix = $prefix;
        }

        if ($canonical_prefix === '' || $prefix !== $canonical_prefix || $slug !== $post->post_name) {
            $target = $canonical_prefix !== ''
                ? home_url('/' . $canonical_prefix . '/' . $post->post_name)
                : get_permalink($post);
            self::redirect($target);
        }

        $wp->query_vars = array(
            'post_type' => AJTB_POST_TYPE,
            'name' => $post->post_name,
            self::QUERY_VAR => 1,
        );
    }

    /**
     * onedeal.php?id=91 et buy.php?id=91 : 301 vers la fiche. Aucun de ces scripts n'existe
     * sur le nouveau site ; sans cette règle, Apache confie la requête à WordPress qui répond 404.
     */
    public static function redirect_technical_urls() {
        $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
        $path = (string) wp_parse_url($uri, PHP_URL_PATH);
        // À la racine (onedeal.php) comme sous un répertoire (/team/buy.php) : seul le script compte.
        if (!in_array(basename($path), self::TECHNICAL_SCRIPTS, true)) {
            return;
        }
        $legacy_id = isset($_GET['id']) ? (int) $_GET['id'] : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $post = $legacy_id > 0 ? self::find_by_legacy_id($legacy_id) : null;
        self::redirect($post ? get_permalink($post) : self::catalogue_url());
    }

    /** Permalien historique sans barre finale, strictement l'ancienne URL. */
    public static function filter_permalink($permalink, $post) {
        if (!$post || $post->post_type !== AJTB_POST_TYPE) {
            return $permalink;
        }
        $prefix = self::path_prefix_for($post->ID);
        if ($prefix === '' || $post->post_name === '') {
            return $permalink;
        }
        return home_url('/' . $prefix . '/' . $post->post_name);
    }

    /** WordPress ne doit ni ajouter de barre finale ni renvoyer vers le permalien natif. */
    public static function keep_legacy_path($redirect_url, $requested_url) {
        if (get_query_var(self::QUERY_VAR)) {
            return false;
        }
        return $redirect_url;
    }

    public static function path_prefix_for($post_id) {
        $prefix = (string) get_post_meta((int) $post_id, self::META_PATH_PREFIX, true);
        return in_array($prefix, self::PREFIXES, true) ? $prefix : '';
    }

    /**
     * Fiche correspondant à un slug historique : par l'identifiant en fin de slug, puis par nom.
     *
     * @return WP_Post|null
     */
    private static function resolve_post($slug) {
        if (preg_match('/-(\d+)$/', $slug, $m)) {
            $post = self::find_by_legacy_id((int) $m[1]);
            if ($post) {
                return $post;
            }
        }
        $found = get_posts(array(
            'post_type' => AJTB_POST_TYPE,
            'post_status' => 'publish',
            'name' => sanitize_title($slug),
            'numberposts' => 1,
            'no_found_rows' => true,
        ));
        return $found ? $found[0] : null;
    }

    /**
     * @return WP_Post|null
     */
    private static function find_by_legacy_id($legacy_id) {
        $found = get_posts(array(
            'post_type' => AJTB_POST_TYPE,
            'post_status' => 'publish',
            'meta_key' => self::META_LEGACY_ID,
            'meta_value' => (string) (int) $legacy_id,
            'numberposts' => 1,
            'no_found_rows' => true,
            'orderby' => 'ID',
            'order' => 'ASC',
        ));
        return $found ? $found[0] : null;
    }

    private static function catalogue_url() {
        return home_url('/voyages/');
    }

    private static function redirect($url) {
        nocache_headers();
        wp_safe_redirect($url, 301);
        exit;
    }
}
