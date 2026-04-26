<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

$settings = ajth_get_settings();
?>

<div class="aj-home-wrap">
    <div id="aj-home" class="aj-home aj-activities-static-page">
        <?php ajth_render_site_header( $settings ); ?>

        <div class="aj-activities-static" id="aj-activities-static">
            <main class="container">
                <div class="breadcrumb">Accueil / Activites / Maroc</div>

                <section class="hero-row">
                    <div>
                        <h1 class="page-title">Activites en plein air au Maroc</h1>
                    </div>
                    <div class="hero-actions">
                        <button class="light-btn" type="button">Favoris</button>
                        <button class="dark-btn" type="button">Voir la carte</button>
                    </div>
                </section>

                <form class="search-box" id="ajas-main-search-form">
                    <div class="search-input">
                        <label for="ajas-destination-input">Destination</label>
                        <input id="ajas-destination-input" type="text" placeholder="Marrakech, Agafay, Fes...">
                    </div>
                    <div class="search-input">
                        <label for="ajas-date-input">Date</label>
                        <input id="ajas-date-input" type="date">
                    </div>
                    <div class="search-input">
                        <label for="ajas-travelers-select">Voyageurs</label>
                        <select id="ajas-travelers-select">
                            <option>2 adultes</option>
                            <option>1 adulte</option>
                            <option>Famille</option>
                            <option>Groupe</option>
                        </select>
                    </div>
                    <div class="search-input">
                        <label for="ajas-category-top">Categorie</label>
                        <select id="ajas-category-top">
                            <option value="">Toutes</option>
                            <option value="desert">Desert</option>
                            <option value="quad">Quad</option>
                            <option value="balloon">Montgolfiere</option>
                            <option value="cultural">Culture</option>
                            <option value="water">Mer & bateau</option>
                            <option value="nature">Nature & Atlas</option>
                        </select>
                    </div>
                    <button class="search-submit" type="submit">Rechercher</button>
                </form>

                <div class="layout">
                    <aside class="filters" id="ajas-desktop-filters-shell" aria-label="Filtres activites">
                        <div class="filter-head">
                            <h2>Filtres</h2>
                            <button class="reset-btn" type="button" data-ajas-reset>Effacer</button>
                        </div>
                        <div id="ajas-filters-desktop"></div>
                        <div class="ad-vertical">
                            <strong>Explorez le Maroc autrement</strong>
                            <button type="button">Voir les offres</button>
                        </div>
                    </aside>

                    <section class="results-column">
                        <div class="results-head">
                            <div class="results-title">
                                <strong>Nos meilleures experiences selectionnees</strong>
                                <span><span id="ajas-result-count">0</span> activites trouvees</span>
                            </div>
                            <select class="sort-select" id="ajas-sort-select">
                                <option value="recommended">Trier par recommande</option>
                                <option value="price-asc">Prix croissant</option>
                                <option value="price-desc">Prix decroissant</option>
                                <option value="rating-desc">Meilleures notes</option>
                                <option value="duration-asc">Duree courte</option>
                                <option value="discount-desc">Promotions d'abord</option>
                            </select>
                        </div>

                        <div class="chips" id="ajas-active-chips"></div>
                        <div class="activities-list" id="ajas-activity-list"></div>
                        <div class="empty" id="ajas-empty-state">
                            <h3>Aucune activite trouvee</h3>
                            <p>Modifiez les filtres ou reinitialisez la recherche.</p>
                            <button class="dark-btn" type="button" data-ajas-reset>Reinitialiser</button>
                        </div>

                        <section class="reviews-section">
                            <h2>Des experiences tres appreciees</h2>
                            <div class="review-grid">
                                <article class="review-card">
                                    <strong>5/5 Excellent</strong>
                                    <p>Organisation fluide, guide professionnel et experience tres complete pour decouvrir Marrakech.</p>
                                </article>
                                <article class="review-card">
                                    <strong>5/5 Inoubliable</strong>
                                    <p>Le coucher de soleil dans le desert d'Agafay etait magnifique. Tres bon rapport qualite-prix.</p>
                                </article>
                                <article class="review-card">
                                    <strong>4/5 Tres bien</strong>
                                    <p>Reservation simple, transport a l'heure et equipe agreable. Recommande pour les familles.</p>
                                </article>
                            </div>
                        </section>
                    </section>
                </div>
            </main>

            <button class="mobile-filter-button" id="ajas-open-drawer" type="button">Filtres & tri</button>
            <div class="drawer-bg" id="ajas-drawer-bg"></div>
            <aside class="drawer" id="ajas-drawer" aria-label="Filtres mobile">
                <div class="drawer-title">
                    <h3>Filtres</h3>
                    <button class="close-btn" id="ajas-close-drawer" type="button">x</button>
                </div>
                <div id="ajas-filters-mobile"></div>
                <div class="drawer-actions">
                    <button class="apply-btn" id="ajas-apply-mobile" type="button">Appliquer les filtres</button>
                    <button class="drawer-reset" type="button" data-ajas-reset>Reinitialiser</button>
                </div>
            </aside>
        </div>
    </div>
</div>

<?php get_footer(); ?>
