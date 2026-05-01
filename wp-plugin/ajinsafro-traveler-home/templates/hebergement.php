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

$current_pack_slug = function_exists('ajth_get_current_accommodation_pack_slug')
    ? ajth_get_current_accommodation_pack_slug()
    : '';
$current_pack = ($current_pack_slug && function_exists('ajth_get_accommodation_package_by_slug'))
    ? ajth_get_accommodation_package_by_slug($current_pack_slug)
    : null;

if ($current_pack) {
    $pack_title = (string) ($current_pack['title'] ?? 'Pack hébergement Ajinsafro');
    $pack_city = trim((string) ($current_pack['city'] ?? ''));
    $pack_country = trim((string) ($current_pack['country'] ?? 'Maroc'));
    $pack_location = trim(implode(', ', array_filter(array($pack_city, $pack_country))));
    $pack_type = (string) ($current_pack['accommodation_type'] ?? $current_pack['typeLabel'] ?? 'Hébergement');
    $pack_duration = (string) ($current_pack['duration_label'] ?? $current_pack['duration'] ?? '');
    $pack_board = (string) ($current_pack['pension_type'] ?? $current_pack['pensionLabel'] ?? '');
    $pack_price = isset($current_pack['price_from']) ? (float) $current_pack['price_from'] : (isset($current_pack['price']) ? (float) $current_pack['price'] : null);
    $pack_currency = (string) ($current_pack['currency'] ?? 'DH');
    $pack_description = trim((string) ($current_pack['short_description'] ?? $current_pack['description'] ?? ''));
    $pack_image = trim((string) ($current_pack['image_url'] ?? $current_pack['image'] ?? ''));
    $pack_includes = isset($current_pack['includes']) && is_array($current_pack['includes']) ? $current_pack['includes'] : array();
    $pack_url = function_exists('ajth_get_accommodation_package_public_url') ? ajth_get_accommodation_package_public_url($current_pack) : $page_url;
    $reservation_url = function_exists('ajth_get_accommodation_package_reservation_url') ? ajth_get_accommodation_package_reservation_url($current_pack) : $pack_url;
    $advice_url = 'https://wa.me/212660683464?text=' . rawurlencode(sprintf('Bonjour Ajinsafro, j’aimerais être conseillé sur le pack "%s". %s', $pack_title, $pack_url));
    $price_label = $pack_price !== null ? number_format($pack_price, 0, ',', ' ') . ' ' . $pack_currency : 'Sur demande';

    $similar_packs = array();
    if (function_exists('ajth_get_accommodation_packages')) {
        $all_packs = ajth_get_accommodation_packages();
        foreach ($all_packs as $pack_row) {
            $row_slug = sanitize_title((string) ($pack_row['slug'] ?? $pack_row['id'] ?? ''));
            if ($row_slug === $current_pack_slug) {
                continue;
            }

            if ($pack_city !== '' && strtolower((string) ($pack_row['city'] ?? '')) !== strtolower($pack_city)) {
                continue;
            }

            $similar_packs[] = $pack_row;
            if (count($similar_packs) >= 3) {
                break;
            }
        }

        if (count($similar_packs) < 3) {
            foreach ($all_packs as $pack_row) {
                $row_slug = sanitize_title((string) ($pack_row['slug'] ?? $pack_row['id'] ?? ''));
                if ($row_slug === $current_pack_slug) {
                    continue;
                }
                $already = false;
                foreach ($similar_packs as $existing) {
                    $existing_slug = sanitize_title((string) ($existing['slug'] ?? $existing['id'] ?? ''));
                    if ($existing_slug === $row_slug) {
                        $already = true;
                        break;
                    }
                }
                if ($already) {
                    continue;
                }
                $similar_packs[] = $pack_row;
                if (count($similar_packs) >= 3) {
                    break;
                }
            }
        }
    }
    ?>
    <div class="aj-home-wrap">
        <div class="aj-home aj-hebergement-booking-page">
            <?php ajth_render_site_header($settings); ?>

            <main class="aj-hebergement-shell aj-pack-page-shell">
                <div class="aj-hebergement-container aj-pack-page-container">
                    <nav class="aj-hebergement-breadcrumb" aria-label="Fil d'Ariane">
                        <a href="<?php echo esc_url(home_url('/')); ?>">Accueil</a>
                        <span>/</span>
                        <a href="<?php echo esc_url($page_url); ?>">Hébergement</a>
                        <span>/</span>
                        <span><?php echo esc_html($pack_title); ?></span>
                    </nav>

                    <section class="aj-pack-hero">
                        <div class="aj-pack-hero-media">
                            <?php if ($pack_image !== '') { ?>
                                <img src="<?php echo esc_url($pack_image); ?>" alt="<?php echo esc_attr($pack_title); ?>">
                            <?php } else { ?>
                                <div class="aj-pack-hero-fallback">Ajinsafro</div>
                            <?php } ?>
                        </div>
                        <div class="aj-pack-hero-copy">
                            <a class="aj-pack-back" href="<?php echo esc_url($page_url); ?>">← Retour aux packs hébergement</a>
                            <div class="aj-pack-topline">
                                <span class="aj-category-badge"><?php echo esc_html($pack_type); ?></span>
                                <span class="aj-status-badge"><?php echo !empty($current_pack['is_active']) ? 'Disponible' : 'À confirmer'; ?></span>
                                <?php if ($pack_board !== '') { ?>
                                    <span class="aj-category-badge"><?php echo esc_html($pack_board); ?></span>
                                <?php } ?>
                            </div>
                            <h1><?php echo esc_html($pack_title); ?></h1>
                            <p class="aj-pack-hero-meta">
                                <?php echo esc_html($pack_location !== '' ? $pack_location : 'Maroc'); ?>
                                <?php if ($pack_duration !== '') { ?>
                                    <span>•</span>
                                    <?php echo esc_html($pack_duration); ?>
                                <?php } ?>
                            </p>
                            <p class="aj-pack-hero-summary">
                                <?php echo esc_html($pack_description !== '' ? $pack_description : 'Pack hébergement Ajinsafro prêt à réserver avec services inclus et accompagnement personnalisé.'); ?>
                            </p>
                            <div class="aj-pack-hero-actions">
                                <a class="aj-pack-reserve" href="<?php echo esc_url($reservation_url); ?>" target="_blank" rel="noopener">Réserver ce pack</a>
                                <a class="aj-pack-secondary" href="<?php echo esc_url($advice_url); ?>" target="_blank" rel="noopener">Demander conseil</a>
                            </div>
                        </div>
                    </section>

                    <section class="aj-pack-layout">
                        <div class="aj-pack-main">
                            <article class="aj-pack-block">
                                <div class="aj-pack-block-head">
                                    <span class="aj-section-kicker">Informations principales</span>
                                    <h2>Résumé du pack</h2>
                                </div>
                                <div class="aj-pack-specs">
                                    <div class="aj-pack-spec">
                                        <strong>Destination</strong>
                                        <span><?php echo esc_html($pack_location !== '' ? $pack_location : 'Maroc'); ?></span>
                                    </div>
                                    <div class="aj-pack-spec">
                                        <strong>Type d'hébergement</strong>
                                        <span><?php echo esc_html($pack_type); ?></span>
                                    </div>
                                    <div class="aj-pack-spec">
                                        <strong>Durée</strong>
                                        <span><?php echo esc_html($pack_duration !== '' ? $pack_duration : 'À confirmer'); ?></span>
                                    </div>
                                    <div class="aj-pack-spec">
                                        <strong>Pension</strong>
                                        <span><?php echo esc_html($pack_board !== '' ? $pack_board : 'Non précisée'); ?></span>
                                    </div>
                                </div>
                            </article>

                            <article class="aj-pack-block">
                                <div class="aj-pack-block-head">
                                    <span class="aj-section-kicker">Services inclus</span>
                                    <h2>Ce que comprend le pack</h2>
                                </div>
                                <?php if (!empty($pack_includes)) { ?>
                                    <ul class="aj-pack-detail-list aj-pack-services-grid">
                                        <?php foreach ($pack_includes as $line) { ?>
                                            <li><?php echo esc_html((string) $line); ?></li>
                                        <?php } ?>
                                    </ul>
                                <?php } else { ?>
                                    <p class="aj-pack-empty-copy">Les services inclus seront confirmés avec votre conseiller Ajinsafro.</p>
                                <?php } ?>
                            </article>

                            <article class="aj-pack-block">
                                <div class="aj-pack-block-head">
                                    <span class="aj-section-kicker">Description</span>
                                    <h2>Détails du séjour</h2>
                                </div>
                                <div class="aj-pack-description">
                                    <?php echo wpautop(esc_html($pack_description !== '' ? $pack_description : 'Ce pack hébergement a été créé depuis l’administration Ajinsafro. Contactez-nous pour recevoir le programme détaillé, les options de chambre et les conseils personnalisés.')); ?>
                                </div>
                            </article>

                            <?php if (!empty($similar_packs)) { ?>
                                <section class="aj-pack-block">
                                    <div class="aj-pack-block-head">
                                        <span class="aj-section-kicker">Suggestions Ajinsafro</span>
                                        <h2>Packs similaires</h2>
                                    </div>
                                    <div class="aj-pack-similar-grid">
                                        <?php foreach ($similar_packs as $pack_row) {
                                            $row_title = (string) ($pack_row['title'] ?? '');
                                            $row_url = function_exists('ajth_get_accommodation_package_public_url') ? ajth_get_accommodation_package_public_url($pack_row) : $page_url;
                                            $row_image = trim((string) ($pack_row['image_url'] ?? $pack_row['image'] ?? ''));
                                            $row_location = trim(implode(', ', array_filter(array((string) ($pack_row['city'] ?? ''), (string) ($pack_row['country'] ?? '')))));
                                            $row_price = isset($pack_row['price_from']) ? (float) $pack_row['price_from'] : (isset($pack_row['price']) ? (float) $pack_row['price'] : null);
                                            ?>
                                            <article class="aj-featured-card">
                                                <a class="aj-featured-visual aj-featured-visual-link" href="<?php echo esc_url($row_url); ?>">
                                                    <?php if ($row_image !== '') { ?>
                                                        <img src="<?php echo esc_url($row_image); ?>" alt="<?php echo esc_attr($row_title); ?>" loading="lazy">
                                                    <?php } else { ?>
                                                        <div class="aj-pack-hero-fallback">Ajinsafro</div>
                                                    <?php } ?>
                                                    <span class="aj-badge">Pack</span>
                                                    <?php if ($row_price !== null) { ?>
                                                        <div class="aj-featured-price"><small>À partir de</small><?php echo esc_html(number_format($row_price, 0, ',', ' ') . ' DH'); ?></div>
                                                    <?php } ?>
                                                </a>
                                                <div class="aj-featured-content">
                                                    <div class="aj-inline-meta">
                                                        <span><?php echo esc_html($row_location !== '' ? $row_location : 'Maroc'); ?></span>
                                                        <span><?php echo esc_html((string) ($pack_row['accommodation_type'] ?? $pack_row['typeLabel'] ?? 'Hébergement')); ?></span>
                                                    </div>
                                                    <h3><a class="aj-featured-title-link" href="<?php echo esc_url($row_url); ?>"><?php echo esc_html($row_title); ?></a></h3>
                                                    <p style="margin:0;color:var(--aj-muted);font-size:13px;line-height:1.5;"><?php echo esc_html((string) ($pack_row['short_description'] ?? $pack_row['description'] ?? '')); ?></p>
                                                    <a class="aj-featured-link" href="<?php echo esc_url($row_url); ?>">Voir le pack</a>
                                                </div>
                                            </article>
                                        <?php } ?>
                                    </div>
                                </section>
                            <?php } ?>
                        </div>

                        <aside class="aj-pack-sidebar">
                            <div class="aj-pack-summary-card">
                                <span class="aj-section-kicker">Récapitulatif prix</span>
                                <h3><?php echo esc_html($pack_title); ?></h3>
                                <div class="aj-pack-summary-price"><?php echo esc_html($price_label); ?></div>
                                <ul class="aj-pack-summary-list">
                                    <li><strong>Ville</strong><span><?php echo esc_html($pack_city !== '' ? $pack_city : 'N/A'); ?></span></li>
                                    <li><strong>Pays</strong><span><?php echo esc_html($pack_country !== '' ? $pack_country : 'Maroc'); ?></span></li>
                                    <li><strong>Durée</strong><span><?php echo esc_html($pack_duration !== '' ? $pack_duration : 'N/A'); ?></span></li>
                                    <li><strong>Pension</strong><span><?php echo esc_html($pack_board !== '' ? $pack_board : 'N/A'); ?></span></li>
                                    <li><strong>Disponibilité</strong><span class="aj-pack-summary-availability"><?php echo !empty($current_pack['is_active']) ? 'Active' : 'À confirmer'; ?></span></li>
                                </ul>
                                <div class="aj-pack-summary-actions">
                                    <a class="aj-pack-reserve" href="<?php echo esc_url($reservation_url); ?>" target="_blank" rel="noopener">Réserver ce pack</a>
                                    <a class="aj-pack-secondary" href="<?php echo esc_url($advice_url); ?>" target="_blank" rel="noopener">Demander conseil</a>
                                </div>
                            </div>
                        </aside>
                    </section>
                </div>
            </main>
        </div>
    </div>
    <?php
    get_footer();
    return;
}
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
