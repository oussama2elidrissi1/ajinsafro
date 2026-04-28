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
			<main class="aj-activities-shell">
				<div class="aj-activities-container">
					<nav class="aj-activities-breadcrumb" aria-label="Fil d Ariane">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
						<span>/</span>
						<span>Activites</span>
					</nav>

					<section class="aj-hero">
						<div class="aj-hero-copy">
							<span class="aj-eyebrow">Marketplace d experiences Ajinsafro</span>
							<h1>Activites et experiences dans le monde</h1>
							<p>Decouvrez les meilleures activites selectionnees par Ajinsafro au Maroc et a l international.</p>
						</div>

						<form class="aj-hero-search" id="aj-activity-search-form">
							<label class="aj-field">
								<span>Pays</span>
								<select id="aj-hero-country" name="country">
									<option value="">Tous les pays</option>
								</select>
							</label>

							<label class="aj-field">
								<span>Ville</span>
								<select id="aj-hero-city" name="city">
									<option value="">Toutes les villes</option>
								</select>
							</label>

							<label class="aj-field">
								<span>Date</span>
								<input id="aj-hero-date" type="date" name="date">
							</label>

							<label class="aj-field">
								<span>Categorie</span>
								<select id="aj-hero-category" name="category">
									<option value="">Toutes les categories</option>
								</select>
							</label>

							<label class="aj-field">
								<span>Budget</span>
								<select id="aj-hero-budget" name="budget">
									<option value="">Tous les budgets</option>
									<option value="0-250">Jusqu a 250 DH</option>
									<option value="251-500">251 a 500 DH</option>
									<option value="501-800">501 a 800 DH</option>
									<option value="801-99999">800 DH et plus</option>
								</select>
							</label>

							<button class="aj-search-button" type="submit">Rechercher</button>
						</form>
					</section>

					<section class="aj-featured" aria-labelledby="aj-featured-title">
						<div class="aj-section-head">
							<div>
								<span class="aj-section-kicker">Selection Ajinsafro</span>
								<h2 id="aj-featured-title">Activites a la une</h2>
							</div>
							<p>Des experiences premium mises en avant pour inspirer le prochain depart.</p>
						</div>
						<div class="aj-featured-grid" id="aj-featured-grid"></div>
					</section>

					<section class="aj-catalog" aria-labelledby="aj-catalog-title">
						<div class="aj-section-head aj-section-head--catalog">
							<div>
								<span class="aj-section-kicker">Catalogue mondial</span>
								<h2 id="aj-catalog-title">Toutes les activites</h2>
							</div>
							<div class="aj-results-meta">
								<strong><span id="aj-results-count">0</span> activites</strong>
								<select id="aj-sort-select" class="aj-sort-select" aria-label="Trier les activites">
									<option value="featured">A la une</option>
									<option value="price-asc">Prix croissant</option>
									<option value="price-desc">Prix decroissant</option>
									<option value="rating-desc">Meilleure note</option>
									<option value="duration-asc">Duree la plus courte</option>
								</select>
							</div>
						</div>

						<div class="aj-catalog-layout">
							<aside class="aj-filters" aria-label="Filtres activites">
								<div class="aj-filter-card">
									<div class="aj-filter-head">
										<h3>Filtrer</h3>
										<button id="aj-reset-filters" type="button">Reinitialiser</button>
									</div>

									<div class="aj-filter-group">
										<label class="aj-filter-label" for="aj-filter-name">Rechercher par nom</label>
										<input id="aj-filter-name" type="text" placeholder="ex: quad, desert, Fes...">
									</div>

									<div class="aj-filter-group">
										<label class="aj-filter-label" for="aj-filter-country">Pays</label>
										<select id="aj-filter-country">
											<option value="">Tous les pays</option>
										</select>
									</div>

									<div class="aj-filter-group">
										<label class="aj-filter-label" for="aj-filter-city">Ville</label>
										<select id="aj-filter-city">
											<option value="">Toutes les villes</option>
										</select>
									</div>

									<div class="aj-filter-group">
										<span class="aj-filter-label">Categorie</span>
										<div id="aj-filter-categories"></div>
									</div>

									<div class="aj-filter-grid">
										<div class="aj-filter-group">
											<label class="aj-filter-label" for="aj-filter-price-min">Prix min</label>
											<input id="aj-filter-price-min" type="number" min="0" placeholder="0">
										</div>
										<div class="aj-filter-group">
											<label class="aj-filter-label" for="aj-filter-price-max">Prix max</label>
											<input id="aj-filter-price-max" type="number" min="0" placeholder="2000">
										</div>
									</div>

									<div class="aj-filter-group">
										<label class="aj-filter-label" for="aj-filter-duration">Duree</label>
										<select id="aj-filter-duration">
											<option value="">Toutes les durees</option>
											<option value="lt2">Moins de 2h</option>
											<option value="half">Demi-journee</option>
											<option value="full">Journee complete</option>
											<option value="multi">2 jours et plus</option>
										</select>
									</div>

									<div class="aj-filter-group">
										<span class="aj-filter-label">Options</span>
										<label class="aj-check"><input id="aj-filter-promo" type="checkbox"> Promotions uniquement</label>
										<label class="aj-check"><input id="aj-filter-available-today" type="checkbox"> Disponible aujourd hui</label>
										<label class="aj-check"><input id="aj-filter-instant-booking" type="checkbox"> Reservation instantanee</label>
										<label class="aj-check"><input id="aj-filter-with-guide" type="checkbox"> Avec guide</label>
										<label class="aj-check"><input id="aj-filter-transport" type="checkbox"> Transport inclus</label>
									</div>
								</div>
							</aside>

							<div class="aj-results">
								<div class="aj-active-filters" id="aj-active-filters"></div>
								<div class="aj-activities-grid" id="aj-activities-grid"></div>
								<div class="aj-empty-state" id="aj-empty-state" hidden>
									<h3>Aucune activite ne correspond a votre recherche</h3>
									<p>Essayez un autre pays, une autre ville ou elargissez votre budget.</p>
									<button id="aj-empty-reset" type="button">Voir toutes les activites</button>
								</div>
							</div>
						</div>
					</section>
				</div>
			</main>

			<button class="aj-mobile-filter-trigger" id="aj-open-mobile-filters" type="button">Filtres</button>

			<div class="aj-mobile-backdrop" id="aj-mobile-backdrop"></div>
			<aside class="aj-mobile-panel" id="aj-mobile-panel" aria-label="Filtres mobile">
				<div class="aj-mobile-panel-head">
					<h3>Filtres des activites</h3>
					<button id="aj-close-mobile-filters" type="button" aria-label="Fermer">x</button>
				</div>

				<div class="aj-mobile-panel-body">
					<div class="aj-filter-group">
						<label class="aj-filter-label" for="aj-mobile-name">Rechercher par nom</label>
						<input id="aj-mobile-name" type="text" placeholder="ex: quad, desert, Fes...">
					</div>

					<div class="aj-filter-group">
						<label class="aj-filter-label" for="aj-mobile-country">Pays</label>
						<select id="aj-mobile-country">
							<option value="">Tous les pays</option>
						</select>
					</div>

					<div class="aj-filter-group">
						<label class="aj-filter-label" for="aj-mobile-city">Ville</label>
						<select id="aj-mobile-city">
							<option value="">Toutes les villes</option>
						</select>
					</div>

					<div class="aj-filter-group">
						<span class="aj-filter-label">Categorie</span>
						<div id="aj-mobile-categories"></div>
					</div>

					<div class="aj-filter-grid">
						<div class="aj-filter-group">
							<label class="aj-filter-label" for="aj-mobile-price-min">Prix min</label>
							<input id="aj-mobile-price-min" type="number" min="0" placeholder="0">
						</div>
						<div class="aj-filter-group">
							<label class="aj-filter-label" for="aj-mobile-price-max">Prix max</label>
							<input id="aj-mobile-price-max" type="number" min="0" placeholder="2000">
						</div>
					</div>

					<div class="aj-filter-group">
						<label class="aj-filter-label" for="aj-mobile-duration">Duree</label>
						<select id="aj-mobile-duration">
							<option value="">Toutes les durees</option>
							<option value="lt2">Moins de 2h</option>
							<option value="half">Demi-journee</option>
							<option value="full">Journee complete</option>
							<option value="multi">2 jours et plus</option>
						</select>
					</div>

					<div class="aj-filter-group">
						<span class="aj-filter-label">Options</span>
						<label class="aj-check"><input id="aj-mobile-promo" type="checkbox"> Promotions uniquement</label>
						<label class="aj-check"><input id="aj-mobile-available-today" type="checkbox"> Disponible aujourd hui</label>
						<label class="aj-check"><input id="aj-mobile-instant-booking" type="checkbox"> Reservation instantanee</label>
						<label class="aj-check"><input id="aj-mobile-with-guide" type="checkbox"> Avec guide</label>
						<label class="aj-check"><input id="aj-mobile-transport" type="checkbox"> Transport inclus</label>
					</div>
				</div>

				<div class="aj-mobile-panel-actions">
					<button id="aj-apply-mobile-filters" type="button">Appliquer</button>
					<button id="aj-reset-mobile-filters" type="button">Reinitialiser</button>
				</div>
			</aside>
		</div>
	</div>
</div>

<?php get_footer(); ?>
