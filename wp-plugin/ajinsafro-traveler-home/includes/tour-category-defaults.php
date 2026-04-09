<?php

/**
 * Default tour category / theme slugs for the voyages catalog filter.
 *
 * Terms are created on plugin activation and when an admin visits the dashboard
 * after a plugin update (new default slugs). The catalog only reads terms.
 */
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Ordered list of default theme slugs => display labels (Ajinsafro catalog).
 *
 * @return array<string, string>
 */
function ajth_get_default_tour_theme_definitions()
{
    return [
        'week-end' => __('Week-end', 'ajinsafro-traveler-home'),
        'sejour' => __('Séjour', 'ajinsafro-traveler-home'),
        'circuit' => __('Circuit', 'ajinsafro-traveler-home'),
        'omra' => __('Omra', 'ajinsafro-traveler-home'),
        'hajj' => __('Hajj', 'ajinsafro-traveler-home'),
        'voyage-organise' => __('Voyage organisé', 'ajinsafro-traveler-home'),
        'famille' => __('Famille', 'ajinsafro-traveler-home'),
        'groupe' => __('Groupe', 'ajinsafro-traveler-home'),
        'city-break' => __('City break', 'ajinsafro-traveler-home'),
        'plage-detente' => __('Plage & détente', 'ajinsafro-traveler-home'),
        'culture-decouverte' => __('Culture & découverte', 'ajinsafro-traveler-home'),
        'aventure' => __('Aventure', 'ajinsafro-traveler-home'),
        'luxe' => __('Luxe', 'ajinsafro-traveler-home'),
        'promo' => __('Promo', 'ajinsafro-traveler-home'),
    ];
}

/**
 * Insert missing default terms (idempotent). Requires manage_terms capability.
 */
function ajth_ensure_default_tours_cat_terms()
{
    if (! taxonomy_exists('tours_cat')) {
        return;
    }

    $tax = get_taxonomy('tours_cat');
    if (! $tax || ! current_user_can($tax->cap->manage_terms)) {
        return;
    }

    $definitions = ajth_get_default_tour_theme_definitions();
    foreach ($definitions as $slug => $name) {
        if (get_term_by('slug', $slug, 'tours_cat')) {
            continue;
        }
        $insert = wp_insert_term(
            $name,
            'tours_cat',
            ['slug' => $slug]
        );
        if (is_wp_error($insert) && $insert->get_error_code() !== 'term_exists') {
            continue;
        }
    }
}

/**
 * After plugin update: admin visit creates any new default terms, then stores version.
 */
function ajth_admin_maybe_sync_default_tour_categories()
{
    if (! is_admin() || wp_doing_ajax()) {
        return;
    }
    if (! taxonomy_exists('tours_cat')) {
        return;
    }
    $tax = get_taxonomy('tours_cat');
    if (! $tax || ! current_user_can($tax->cap->manage_terms)) {
        return;
    }
    $saved = get_option('ajth_tour_cat_defaults_plugin_version', '');
    if ($saved === AJTH_VERSION) {
        return;
    }
    ajth_ensure_default_tours_cat_terms();
    update_option('ajth_tour_cat_defaults_plugin_version', AJTH_VERSION, false);
}

add_action('admin_init', 'ajth_admin_maybe_sync_default_tour_categories', 5);

/**
 * Build ordered term objects: defaults first (by slug order), then other DB terms by name.
 *
 * @return array<int, WP_Term>
 */
function ajth_get_catalog_tour_category_terms_ordered()
{
    $all = get_terms(
        [
            'taxonomy' => 'tours_cat',
            'hide_empty' => false,
        ]
    );

    if (is_wp_error($all) || empty($all)) {
        return [];
    }

    $by_slug = [];
    foreach ($all as $t) {
        if ($t instanceof WP_Term) {
            $by_slug[$t->slug] = $t;
        }
    }

    $ordered = [];
    $used_slug = [];

    foreach (array_keys(ajth_get_default_tour_theme_definitions()) as $slug) {
        if (! empty($by_slug[$slug])) {
            $ordered[] = $by_slug[$slug];
            $used_slug[$slug] = true;
        }
    }

    $extras = [];
    foreach ($by_slug as $slug => $term) {
        if (! empty($used_slug[$slug])) {
            continue;
        }
        $extras[] = $term;
    }

    usort(
        $extras,
        static function ($a, $b) {
            return strcasecmp($a->name, $b->name);
        }
    );

    return array_merge($ordered, $extras);
}

/**
 * Plugin activation: seed default themes (runs when an admin activates the plugin).
 */
function ajth_activate_default_tour_categories()
{
    ajth_ensure_default_tours_cat_terms();
    if (taxonomy_exists('tours_cat')) {
        update_option('ajth_tour_cat_defaults_plugin_version', AJTH_VERSION, false);
    }
}
