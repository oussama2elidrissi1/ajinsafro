<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$settings = ajth_get_settings();
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-hebergement-booking-page">
        <?php ajth_render_site_header( $settings ); ?>

        <div class="aj-hebergement-booking" id="aj-hebergement-booking">
            <section class="hero">
                <div class="container">
                    <h1 class="hero-title">Trouvez l'hebergement ideal</h1>
                    <p class="hero-subtitle">Comparez les hotels, riads, appartements et villas disponibles avec des filtres avances et des prix clairs.</p>

                    <form class="search-panel" id="ajhb-search-form">
                        <div class="search-field">
                            <label for="ajhb-destination">Destination</label>
                            <input id="ajhb-destination" name="destination" type="text" placeholder="Ville, hotel, quartier...">
                        </div>
                        <div class="search-field">
                            <label for="ajhb-checkin">Arrivee</label>
                            <input id="ajhb-checkin" name="checkin" type="date">
                        </div>
                        <div class="search-field">
                            <label for="ajhb-checkout">Depart</label>
                            <input id="ajhb-checkout" name="checkout" type="date">
                        </div>
                        <div class="search-field">
                            <label for="ajhb-travelers">Voyageurs</label>
                            <select id="ajhb-travelers" name="travelers">
                                <option value="1">1 adulte, 1 chambre</option>
                                <option value="2">2 adultes, 1 chambre</option>
                                <option value="3">2 adultes, 1 enfant</option>
                                <option value="4">4 voyageurs, 2 chambres</option>
                            </select>
                        </div>
                        <button class="search-btn" type="submit">Rechercher</button>
                    </form>
                </div>
            </section>

            <main class="container main-grid">
                <aside class="filters" id="ajhb-desktop-filters" aria-label="Filtres">
                    <div class="map-card">
                        <button type="button">Voir sur la carte</button>
                    </div>
                    <div class="filter-title">
                        <h2>Filtrer par</h2>
                        <button class="clear-link" type="button" data-ajhb-action="reset">Tout effacer</button>
                    </div>

                    <div id="ajhb-filters-content"></div>
                </aside>

                <section class="results">
                    <div class="results-head">
                        <div class="results-topline">
                            <div>
                                <h2>Hebergements disponibles</h2>
                                <div class="result-count"><span id="ajhb-count">0</span> resultats trouves</div>
                            </div>
                            <label class="sort-wrap">Trier par
                                <select id="ajhb-sort-select">
                                    <option value="recommended">Recommandes</option>
                                    <option value="price-asc">Prix croissant</option>
                                    <option value="price-desc">Prix decroissant</option>
                                    <option value="rating-desc">Meilleures notes</option>
                                    <option value="stars-desc">Etoiles decroissantes</option>
                                    <option value="discount-desc">Promotions d'abord</option>
                                </select>
                            </label>
                        </div>
                        <div class="chips" id="ajhb-active-chips"></div>
                    </div>

                    <div class="deal-strip">
                        <div>
                            <strong>Connectez-vous pour voir les prix membres</strong>
                            <span>Profitez des reductions, favoris et offres Ajinsafro.</span>
                        </div>
                        <button type="button">Se connecter</button>
                    </div>

                    <div class="hotel-list" id="ajhb-hotel-list"></div>

                    <div class="empty-state" id="ajhb-empty-state">
                        <h3>Aucun hebergement trouve</h3>
                        <p>Essayez de modifier votre budget, votre destination ou vos equipements.</p>
                        <button class="primary-btn" type="button" data-ajhb-action="reset">Reinitialiser les filtres</button>
                    </div>
                </section>

                <aside class="ad-col" aria-label="Promotions">
                    <div class="ad-box">
                        <strong>Evasion premium au bord de mer</strong>
                        <button type="button">Decouvrir</button>
                    </div>
                    <div class="ad-box">
                        <strong>Sejours selectionnes par Ajinsafro</strong>
                        <button type="button">Reserver</button>
                    </div>
                </aside>
            </main>

            <button class="mobile-filter-btn" type="button" id="ajhb-open-filters">Filtres & tri</button>
            <div class="drawer-backdrop" id="ajhb-drawer-backdrop"></div>
            <aside class="mobile-drawer" id="ajhb-mobile-drawer" aria-label="Filtres mobile">
                <div class="drawer-head">
                    <h3>Filtres</h3>
                    <button type="button" id="ajhb-close-filters">x</button>
                </div>
                <div id="ajhb-mobile-filters-content"></div>
                <button class="primary-btn" type="button" id="ajhb-apply-mobile-filters" style="margin-top:14px;">Appliquer les filtres</button>
                <button class="secondary-btn" type="button" data-ajhb-action="reset" style="margin-top:8px;">Reinitialiser</button>
            </aside>
        </div>
    </div>
</div>

<?php get_footer(); ?>
