<?php
/**
 * Conservation des URLs du catalogue historique ajinsafro.ma.
 *
 * L'ancien site servait les fiches sous /voyage-national/<slug> et /voyages-international/<slug>.
 * Le nouveau site les sert sous le rewrite natif du post type (/voyages/<slug>). Cette classe
 * rejoue les deux anciens préfixes pour que l'URL publique soit strictement identique à celle
 * de `.ma` : seul le domaine change.
 *
 * Le préfixe à utiliser est porté par le post, dans la meta `_aj_legacy_path_prefix`, posée par
 * Laravel (`legacy:push-wp`, `legacy:merge`). Un tour sans cette meta garde le permalien natif.
 */

if (!defined('ABSPATH')) {
    exit;
}

final class AJTB_Legacy_Permalinks {

    /** Meta portant le préfixe de chemin historique du programme. */
    const META_PATH_PREFIX = '_aj_legacy_path_prefix';

    /** Query var marquant une requête arrivée par une URL historique. */
    const QUERY_VAR = 'aj_legacy_path';

    /** Préfixes de chemin de l'ancien site. */
    const PREFIXES = array('voyage-national', 'voyages-international');

    /** Option de suivi, pour ne vider les règles de réécriture qu'au besoin. */
    const RULES_VERSION_OPTION = 'ajtb_legacy_rules_version';

    const RULES_VERSION = '1';

    public static function boot() {
        add_action('init', array(__CLASS__, 'register_rewrite_rules'));
        add_filter('query_vars', array(__CLASS__, 'register_query_var'));
        add_filter('post_type_link', array(__CLASS__, 'filter_permalink'), 10, 2);
        add_filter('redirect_canonical', array(__CLASS__, 'keep_legacy_path'), 10, 2);
    }

    /**
     * Rejoue /voyage-national/<slug> et /voyages-international/<slug> vers le tour correspondant.
     */
    public static function register_rewrite_rules() {
        add_rewrite_rule(
            '^(' . implode('|', self::PREFIXES) . ')/([^/]+)/?$',
            'index.php?post_type=' . AJTB_POST_TYPE . '&name=$matches[2]&' . self::QUERY_VAR . '=1',
            'top'
        );

        if (get_option(self::RULES_VERSION_OPTION) !== self::RULES_VERSION) {
            flush_rewrite_rules(false);
            update_option(self::RULES_VERSION_OPTION, self::RULES_VERSION);
        }
    }

    public static function register_query_var($vars) {
        $vars[] = self::QUERY_VAR;

        return $vars;
    }

    /**
     * Le permalien d'un programme historique est son ancien chemin `.ma`, à l'identique.
     */
    public static function filter_permalink($permalink, $post) {
        if (!$post || $post->post_type !== AJTB_POST_TYPE) {
            return $permalink;
        }

        $prefix = self::path_prefix_for($post->ID);
        if ($prefix === '' || $post->post_name === '') {
            return $permalink;
        }

        return home_url('/' . $prefix . '/' . $post->post_name . '/');
    }

    /**
     * Sans cela, WordPress renverrait l'ancienne URL vers le permalien natif /voyages/<slug>.
     */
    public static function keep_legacy_path($redirect_url, $requested_url) {
        if (get_query_var(self::QUERY_VAR)) {
            return false;
        }

        return $redirect_url;
    }

    /**
     * Préfixe historique du post, ou chaîne vide si le tour n'en a pas.
     */
    public static function path_prefix_for($post_id) {
        $prefix = (string) get_post_meta((int) $post_id, self::META_PATH_PREFIX, true);

        return in_array($prefix, self::PREFIXES, true) ? $prefix : '';
    }
}
