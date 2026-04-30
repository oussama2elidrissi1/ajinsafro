<?php
if (!defined('ABSPATH')) {
    exit;
}

add_filter('body_class', function ($classes) {
    $classes[] = 'page-hebergement-ajinsafro';
    return $classes;
});

get_header();

$settings = ajth_get_settings();

$page_url = function_exists('ajth_get_hebergement_page_url')
    ? ajth_get_hebergement_page_url()
    : home_url('/hebergement/');

// Build unique lists for hero dropdowns from all items
$all_items = array();
if (function_exists('getAjinsafroHebergements')) {
    $all_items = getAjinsafroHebergements(200, array('posts_per_page' => 200));
}

$destinations = array();
$types = array();
foreach ($all_items as $item) {
    if (!empty($item['location'])) {
        $destinations[] = $item['location'];
    }
    if (!empty($item['category'])) {
        $types[] = $item['category'];
    }
}
$destinations = array_values(array_unique($destinations));
$types = array_values(array_unique($types));
sort($destinations);
sort($types);
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-hebergement-booking-page">
        <?php ajth_render_site_header($settings); ?>

        <div class="aj-hebergement-booking" id="aj-hebergement-booking">
            <main class="aj-hebergement-shell">
                <div class="aj-hebergement-container">
                    <nav class="aj-hebergement-breadcrumb" aria-label="Fil d'Ariane">
                        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                        <span>/</span>
                        <span>Hébergement</span>
                    </nav>

                    <section class="aj-hero">
                        <div class="aj-hero-copy">
                            <span class="aj-eyebrow">Sélection Ajinsafro</span>
                            <h1>Trouvez l'hébergement idéal</h1>
                            <p>Comparez les hôtels, riads, appartements et villas disponibles avec des filtres avancés et des prix clairs.</p>
                        </div>

                        <form class="aj-hero-search" id="ajhb-search-form" method="get" action="<?php echo esc_url($page_url); ?>">
                            <label class="aj-field">
                                <span>Destination</span>
                                <input id="ajhb-destination" name="destination" type="text" placeholder="Ville, hôtel, quartier..." value="<?php echo esc_attr(isset($_GET['destination']) ? sanitize_text_field(wp_unslash($_GET['destination'])) : ''); ?>">
                            </label>

                            <label class="aj-field">
                                <span>Date</span>
                                <input id="ajhb-date" name="date" type="date" value="<?php echo esc_attr(isset($_GET['date']) ? sanitize_text_field(wp_unslash($_GET['date'])) : ''); ?>">
                            </label>

                            <label class="aj-field">
                                <span>Type d'hébergement</span>
                                <select id="ajhb-type" name="type">
                                    <option value="">Tous les types</option>
                                    <?php foreach ($types as $t) { ?>
                                        <option value="<?php echo esc_attr($t); ?>" <?php selected(isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '', $t); ?>><?php echo esc_html($t); ?></option>
                                    <?php } ?>
                                </select>
                            </label>

                            <label class="aj-field">
                                <span>Étoiles</span>
                                <select id="ajhb-stars" name="stars">
                                    <option value="">Toutes les étoiles</option>
                                    <option value="1">1 étoile</option>
                                    <option value="2">2 étoiles</option>
                                    <option value="3">3 étoiles</option>
                                    <option value="4">4 étoiles</option>
                                    <option value="5">5 étoiles</option>
                                </select>
                            </label>

                            <button class="aj-search-button" type="submit">Rechercher</button>
                        </form>
                    </section>

                    <section class="aj-pack-detail" id="ajhb-pack-detail-section" hidden>
                        <div class="aj-pack-detail-inner" id="ajhb-pack-detail"></div>
                    </section>

                    <section class="aj-featured" id="ajhb-featured-section" aria-labelledby="aj-featured-title">
                        <div class="aj-section-head">
                            <div>
                                <span class="aj-section-kicker">Sélection Ajinsafro</span>
                                <h2 id="aj-featured-title">Packs d'hébergement à la une</h2>
                            </div>
                            <p>Des packs hébergement soigneusement sélectionnés avec services inclus pour un séjour sans souci.</p>
                        </div>
                        <div class="aj-featured-grid" id="ajhb-featured-grid"></div>
                    </section>

                    <section class="aj-catalog" id="ajhb-catalog-section" aria-labelledby="aj-catalog-title" hidden>
                        <div class="aj-section-head aj-section-head--catalog">
                            <div>
                                <span class="aj-section-kicker">Catalogue complet</span>
                                <h2 id="aj-catalog-title">Hébergements disponibles</h2>
                            </div>
                            <div class="aj-results-meta">
                                <strong><span id="ajhb-count">0</span> résultats</strong>
                                <div class="aj-view-toggle" id="ajhb-view-toggle" role="group" aria-label="Mode d'affichage des résultats">
                                    <button type="button" class="aj-view-btn is-active" id="ajhb-view-list" data-view="list" aria-pressed="true">
                                        <span aria-hidden="true">☰</span>
                                        <span>Liste</span>
                                    </button>
                                    <button type="button" class="aj-view-btn" id="ajhb-view-grid" data-view="grid" aria-pressed="false">
                                        <span aria-hidden="true">▦</span>
                                        <span>Grille</span>
                                    </button>
                                </div>
                                <select id="ajhb-sort-select" class="aj-sort-select" aria-label="Trier les hébergements">
                                    <option value="recommended">Recommandés</option>
                                    <option value="price-asc">Prix croissant</option>
                                    <option value="price-desc">Prix décroissant</option>
                                    <option value="rating-desc">Mieux notés</option>
                                    <option value="stars-desc">Étoiles décroissantes</option>
                                </select>
                            </div>
                        </div>

                        <div class="aj-catalog-layout">
                            <aside class="aj-filters" id="ajhb-desktop-filters" aria-label="Filtres hébergements">
                                <div class="aj-filter-card">
                                    <div class="aj-filter-head">
                                        <h3>Filtrer</h3>
                                        <button id="ajhb-reset-filters" type="button">Réinitialiser</button>
                                    </div>

                                    <div class="aj-filter-group">
                                        <label class="aj-filter-label" for="ajhb-filter-name">Rechercher par nom</label>
                                        <input id="ajhb-filter-name" type="text" placeholder="ex: riad, marina, Fès...">
                                    </div>

                                    <div class="aj-filter-group">
                                        <label class="aj-filter-label" for="ajhb-filter-destination">Destination</label>
                                        <select id="ajhb-filter-destination">
                                            <option value="">Toutes les destinations</option>
                                            <?php foreach ($destinations as $d) { ?>
                                                <option value="<?php echo esc_attr($d); ?>"><?php echo esc_html($d); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="aj-filter-group">
                                        <label class="aj-filter-label" for="ajhb-filter-type">Type d'hébergement</label>
                                        <select id="ajhb-filter-type">
                                            <option value="">Tous les types</option>
                                            <?php foreach ($types as $t) { ?>
                                                <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="aj-filter-group">
                                        <span class="aj-filter-label">Étoiles</span>
                                        <div class="aj-stars-checks" id="ajhb-filter-stars">
                                            <label class="aj-check"><input type="checkbox" value="1"> <span class="aj-star-label">1 étoile</span></label>
                                            <label class="aj-check"><input type="checkbox" value="2"> <span class="aj-star-label">2 étoiles</span></label>
                                            <label class="aj-check"><input type="checkbox" value="3"> <span class="aj-star-label">3 étoiles</span></label>
                                            <label class="aj-check"><input type="checkbox" value="4"> <span class="aj-star-label">4 étoiles</span></label>
                                            <label class="aj-check"><input type="checkbox" value="5"> <span class="aj-star-label">5 étoiles</span></label>
                                        </div>
                                    </div>

                                    <div class="aj-filter-grid">
                                        <div class="aj-filter-group">
                                            <label class="aj-filter-label" for="ajhb-filter-price-min">Prix min</label>
                                            <input id="ajhb-filter-price-min" type="number" min="0" placeholder="0">
                                        </div>
                                        <div class="aj-filter-group">
                                            <label class="aj-filter-label" for="ajhb-filter-price-max">Prix max</label>
                                            <input id="ajhb-filter-price-max" type="number" min="0" placeholder="10000">
                                        </div>
                                    </div>

                                    <div class="aj-filter-group">
                                        <span class="aj-filter-label">Disponibilité</span>
                                        <label class="aj-check"><input id="ajhb-filter-popular" type="checkbox"> Sélection Ajinsafro</label>
                                        <label class="aj-check"><input id="ajhb-filter-available" type="checkbox"> Disponible uniquement</label>
                                        <label class="aj-check"><input id="ajhb-filter-promo" type="checkbox"> Promotions</label>
                                    </div>

                                    <div class="aj-filter-group">
                                        <span class="aj-filter-label">Services</span>
                                        <label class="aj-check"><input id="ajhb-filter-wifi" type="checkbox" value="wifi"> Wi-Fi</label>
                                        <label class="aj-check"><input id="ajhb-filter-pool" type="checkbox" value="pool"> Piscine</label>
                                        <label class="aj-check"><input id="ajhb-filter-parking" type="checkbox" value="parking"> Parking</label>
                                        <label class="aj-check"><input id="ajhb-filter-breakfast" type="checkbox" value="breakfast"> Petit-déjeuner</label>
                                        <label class="aj-check"><input id="ajhb-filter-ac" type="checkbox" value="air_conditioning"> Climatisation</label>
                                    </div>
                                </div>
                            </aside>

                            <div class="aj-results">
                                <div class="aj-active-filters" id="ajhb-active-filters"></div>
                                <div class="aj-hebergements-grid" id="ajhb-results-grid"></div>
                                <div class="aj-empty-state" id="ajhb-empty-state" hidden>
                                    <h3>Aucun hébergement trouvé pour ces critères.</h3>
                                    <p>Essayez une autre destination, un autre type d'hébergement ou réinitialisez les filtres.</p>
                                    <button id="ajhb-empty-reset" type="button">Réinitialiser les filtres</button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </main>

            <button class="aj-mobile-filter-trigger" id="ajhb-open-filters" type="button">Filtres</button>

            <div class="aj-mobile-backdrop" id="ajhb-mobile-backdrop"></div>
            <aside class="aj-mobile-panel" id="ajhb-mobile-panel" aria-label="Filtres mobile">
                <div class="aj-mobile-panel-head">
                    <h3>Filtres</h3>
                    <button id="ajhb-close-mobile-filters" type="button" aria-label="Fermer">×</button>
                </div>

                <div class="aj-mobile-panel-body">
                    <div class="aj-filter-group">
                        <label class="aj-filter-label" for="ajhb-mobile-name">Rechercher par nom</label>
                        <input id="ajhb-mobile-name" type="text" placeholder="ex: riad, marina, Fès...">
                    </div>

                    <div class="aj-filter-group">
                        <label class="aj-filter-label" for="ajhb-mobile-destination">Destination</label>
                        <select id="ajhb-mobile-destination">
                            <option value="">Toutes les destinations</option>
                            <?php foreach ($destinations as $d) { ?>
                                <option value="<?php echo esc_attr($d); ?>"><?php echo esc_html($d); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="aj-filter-group">
                        <label class="aj-filter-label" for="ajhb-mobile-type">Type d'hébergement</label>
                        <select id="ajhb-mobile-type">
                            <option value="">Tous les types</option>
                            <?php foreach ($types as $t) { ?>
                                <option value="<?php echo esc_attr($t); ?>"><?php echo esc_html($t); ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="aj-filter-group">
                        <span class="aj-filter-label">Étoiles</span>
                        <div class="aj-stars-checks" id="ajhb-mobile-stars">
                            <label class="aj-check"><input type="checkbox" value="1"> <span class="aj-star-label">1 étoile</span></label>
                            <label class="aj-check"><input type="checkbox" value="2"> <span class="aj-star-label">2 étoiles</span></label>
                            <label class="aj-check"><input type="checkbox" value="3"> <span class="aj-star-label">3 étoiles</span></label>
                            <label class="aj-check"><input type="checkbox" value="4"> <span class="aj-star-label">4 étoiles</span></label>
                            <label class="aj-check"><input type="checkbox" value="5"> <span class="aj-star-label">5 étoiles</span></label>
                        </div>
                    </div>

                    <div class="aj-filter-grid">
                        <div class="aj-filter-group">
                            <label class="aj-filter-label" for="ajhb-mobile-price-min">Prix min</label>
                            <input id="ajhb-mobile-price-min" type="number" min="0" placeholder="0">
                        </div>
                        <div class="aj-filter-group">
                            <label class="aj-filter-label" for="ajhb-mobile-price-max">Prix max</label>
                            <input id="ajhb-mobile-price-max" type="number" min="0" placeholder="10000">
                        </div>
                    </div>

                    <div class="aj-filter-group">
                        <span class="aj-filter-label">Disponibilité</span>
                        <label class="aj-check"><input id="ajhb-mobile-popular" type="checkbox"> Sélection Ajinsafro</label>
                        <label class="aj-check"><input id="ajhb-mobile-available" type="checkbox"> Disponible uniquement</label>
                        <label class="aj-check"><input id="ajhb-mobile-promo" type="checkbox"> Promotions</label>
                    </div>

                    <div class="aj-filter-group">
                        <span class="aj-filter-label">Services</span>
                        <label class="aj-check"><input id="ajhb-mobile-wifi" type="checkbox" value="wifi"> Wi-Fi</label>
                        <label class="aj-check"><input id="ajhb-mobile-pool" type="checkbox" value="pool"> Piscine</label>
                        <label class="aj-check"><input id="ajhb-mobile-parking" type="checkbox" value="parking"> Parking</label>
                        <label class="aj-check"><input id="ajhb-mobile-breakfast" type="checkbox" value="breakfast"> Petit-déjeuner</label>
                        <label class="aj-check"><input id="ajhb-mobile-ac" type="checkbox" value="air_conditioning"> Climatisation</label>
                    </div>
                </div>

                <div class="aj-mobile-panel-actions">
                    <button id="ajhb-apply-mobile-filters" type="button">Appliquer</button>
                    <button id="ajhb-reset-mobile-filters" type="button">Réinitialiser</button>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php get_footer(); ?>
