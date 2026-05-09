<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'body_class', function ( $classes ) {
	$classes[] = 'page-activites-ajinsafro';
	return $classes;
} );

get_header();

$settings  = ajth_get_settings();
$page_url  = function_exists( 'ajth_get_activites_page_url' ) ? ajth_get_activites_page_url() : home_url( '/activites/' );
$offer_slug = function_exists( 'ajth_get_current_activity_offer_slug' ) ? ajth_get_current_activity_offer_slug() : '';
$offer      = $offer_slug && function_exists( 'ajth_get_activity_offer_by_slug' ) ? ajth_get_activity_offer_by_slug( $offer_slug ) : null;

if ( $offer ) {
	$title            = (string) ( $offer['title'] ?? 'Activité Ajinsafro' );
	$country          = trim( (string) ( $offer['country'] ?? '' ) );
	$city             = trim( (string) ( $offer['city'] ?? '' ) );
	$location         = trim( implode( ', ', array_filter( array( $city, $country ) ) ) );
	$category         = (string) ( $offer['category'] ?? 'Activité' );
	$duration         = (string) ( $offer['duration_label'] ?? '' );
	$badge            = (string) ( $offer['badge'] ?? '' );
	$description      = trim( (string) ( $offer['shortDescription'] ?? $offer['short_description'] ?? '' ) );
	$image            = trim( (string) ( $offer['image'] ?? $offer['image_url'] ?? '' ) );
	$includes         = isset( $offer['includes'] ) && is_array( $offer['includes'] ) ? $offer['includes'] : array();
	$price            = isset( $offer['price'] ) ? (float) $offer['price'] : ( isset( $offer['price_from'] ) ? (float) $offer['price_from'] : null );
	$currency         = (string) ( $offer['currency'] ?? 'DH' );
	$availability     = (string) ( $offer['availability'] ?? $offer['availability_label'] ?? 'Disponible' );
	$detail_url       = function_exists( 'ajth_get_activity_public_url' ) ? ajth_get_activity_public_url( $offer ) : $page_url;
	$reservation_url  = function_exists( 'ajth_get_activity_offer_reservation_url' ) ? ajth_get_activity_offer_reservation_url( $offer ) : $detail_url;
	$advice_url       = 'https://wa.me/212660683464?text=' . rawurlencode( sprintf( 'Bonjour Ajinsafro, j’aimerais être conseillé sur l’activité "%s". %s', $title, $detail_url ) );
	$price_label      = null !== $price ? number_format( $price, 0, ',', ' ' ) . ' ' . $currency : 'Sur demande';
	$similar_offers   = array();
	$all_activities   = function_exists( 'ajth_get_activities' ) ? ajth_get_activities( 250 ) : array();

	foreach ( $all_activities as $row ) {
		$row_slug = sanitize_title( (string) ( $row['slug'] ?? $row['id'] ?? '' ) );
		if ( $row_slug === $offer_slug ) {
			continue;
		}
		if ( $city !== '' && strtolower( (string) ( $row['city'] ?? '' ) ) !== strtolower( $city ) ) {
			continue;
		}
		$similar_offers[] = $row;
		if ( count( $similar_offers ) >= 3 ) {
			break;
		}
	}

	if ( count( $similar_offers ) < 3 ) {
		foreach ( $all_activities as $row ) {
			$row_slug = sanitize_title( (string) ( $row['slug'] ?? $row['id'] ?? '' ) );
			if ( $row_slug === $offer_slug ) {
				continue;
			}
			$already = false;
			foreach ( $similar_offers as $existing ) {
				$existing_slug = sanitize_title( (string) ( $existing['slug'] ?? $existing['id'] ?? '' ) );
				if ( $existing_slug === $row_slug ) {
					$already = true;
					break;
				}
			}
			if ( $already ) {
				continue;
			}
			$similar_offers[] = $row;
			if ( count( $similar_offers ) >= 3 ) {
				break;
			}
		}
	}
	?>
	<div class="aj-home-wrap">
		<div class="aj-home aj-activities-static-page">
			<?php ajth_render_site_header( $settings ); ?>

			<main class="aj-activities-shell aj-activity-detail-shell ajinsafro-page-container">
				<div class="aj-activities-container aj-activity-detail-container">
					<nav class="aj-activities-breadcrumb" aria-label="Fil d Ariane">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
						<span>/</span>
						<a href="<?php echo esc_url( $page_url ); ?>">Activites</a>
						<span>/</span>
						<span><?php echo esc_html( $title ); ?></span>
					</nav>

					<section class="aj-activity-detail-hero">
						<div class="aj-activity-detail-media">
							<?php if ( '' !== $image ) { ?>
								<img src="<?php echo esc_url( $image ); ?>" alt="<?php echo esc_attr( $title ); ?>">
							<?php } else { ?>
								<div class="aj-activity-detail-fallback">Ajinsafro</div>
							<?php } ?>
						</div>
						<div class="aj-activity-detail-copy">
							<a class="aj-activity-back" href="<?php echo esc_url( $page_url ); ?>">← Retour aux activités</a>
							<div class="aj-activity-topline">
								<span class="aj-category-badge"><?php echo esc_html( $category ); ?></span>
								<span class="aj-status-badge"><?php echo esc_html( $availability ); ?></span>
								<?php if ( '' !== $badge ) { ?>
									<span class="aj-badge"><?php echo esc_html( $badge ); ?></span>
								<?php } ?>
							</div>
							<h1><?php echo esc_html( $title ); ?></h1>
							<p class="aj-activity-detail-meta">
								<?php echo esc_html( $location !== '' ? $location : 'Destination à confirmer' ); ?>
								<?php if ( '' !== $duration ) { ?>
									<span>•</span>
									<?php echo esc_html( $duration ); ?>
								<?php } ?>
							</p>
							<p class="aj-activity-detail-summary">
								<?php echo esc_html( '' !== $description ? $description : 'Activité Ajinsafro à réserver avec accompagnement personnalisé.' ); ?>
							</p>
							<div class="aj-activity-detail-actions">
								<a class="aj-card-primary" href="<?php echo esc_url( $reservation_url ); ?>" target="_blank" rel="noopener">Réserver cette activité</a>
								<a class="aj-card-secondary" href="<?php echo esc_url( $advice_url ); ?>" target="_blank" rel="noopener">Demander conseil</a>
							</div>
						</div>
					</section>

					<section class="aj-activity-detail-layout">
						<div class="aj-activity-detail-main">
							<article class="aj-activity-detail-block">
								<div class="aj-section-head">
									<div>
										<span class="aj-section-kicker">Informations principales</span>
										<h2>Résumé de l'activité</h2>
									</div>
								</div>
								<div class="aj-activity-detail-specs">
									<div class="aj-activity-detail-spec"><strong>Pays</strong><span><?php echo esc_html( $country !== '' ? $country : 'N/A' ); ?></span></div>
									<div class="aj-activity-detail-spec"><strong>Ville</strong><span><?php echo esc_html( $city !== '' ? $city : 'N/A' ); ?></span></div>
									<div class="aj-activity-detail-spec"><strong>Catégorie</strong><span><?php echo esc_html( $category ); ?></span></div>
									<div class="aj-activity-detail-spec"><strong>Durée</strong><span><?php echo esc_html( '' !== $duration ? $duration : 'À confirmer' ); ?></span></div>
								</div>
							</article>

							<article class="aj-activity-detail-block">
								<div class="aj-section-head">
									<div>
										<span class="aj-section-kicker">Services inclus</span>
										<h2>Ce qui est inclus</h2>
									</div>
								</div>
								<?php if ( ! empty( $includes ) ) { ?>
									<ul class="aj-activity-detail-list">
										<?php foreach ( $includes as $line ) { ?>
											<li><?php echo esc_html( (string) $line ); ?></li>
										<?php } ?>
									</ul>
								<?php } else { ?>
									<p class="aj-pack-empty-copy">Les inclusions seront précisées avec votre conseiller Ajinsafro.</p>
								<?php } ?>
							</article>

							<article class="aj-activity-detail-block">
								<div class="aj-section-head">
									<div>
										<span class="aj-section-kicker">Description</span>
										<h2>Détails de l'expérience</h2>
									</div>
								</div>
								<div class="aj-activity-detail-description">
									<?php echo wpautop( esc_html( '' !== $description ? $description : 'Cette activité a été créée depuis l’administration Ajinsafro. Contactez-nous pour recevoir le déroulé complet, les horaires et les modalités de réservation.' ) ); ?>
								</div>
							</article>

							<?php if ( ! empty( $similar_offers ) ) { ?>
								<section class="aj-activity-detail-block">
									<div class="aj-section-head">
										<div>
											<span class="aj-section-kicker">Suggestions Ajinsafro</span>
											<h2>Activités similaires</h2>
										</div>
									</div>
									<div class="aj-activity-similar-grid">
										<?php foreach ( $similar_offers as $row ) {
											$row_title = (string) ( $row['title'] ?? '' );
											$row_url   = function_exists( 'ajth_get_activity_public_url' ) ? ajth_get_activity_public_url( $row ) : $page_url;
											$row_image = trim( (string) ( $row['image'] ?? '' ) );
											$row_place = trim( implode( ', ', array_filter( array( (string) ( $row['city'] ?? '' ), (string) ( $row['country'] ?? '' ) ) ) ) );
											$row_price = isset( $row['price'] ) ? (float) $row['price'] : null;
											?>
											<article class="aj-featured-card">
												<a class="aj-featured-visual aj-activity-visual-link" href="<?php echo esc_url( $row_url ); ?>">
													<?php if ( '' !== $row_image ) { ?>
														<img src="<?php echo esc_url( $row_image ); ?>" alt="<?php echo esc_attr( $row_title ); ?>" loading="lazy">
													<?php } else { ?>
														<div class="aj-activity-detail-fallback">Ajinsafro</div>
													<?php } ?>
													<span class="aj-badge"><?php echo esc_html( (string) ( $row['badge'] ?? 'Activité' ) ); ?></span>
													<?php if ( null !== $row_price ) { ?>
														<div class="aj-featured-price"><small>À partir de</small><?php echo esc_html( number_format( $row_price, 0, ',', ' ' ) . ' DH' ); ?></div>
													<?php } ?>
												</a>
												<div class="aj-featured-content">
													<div class="aj-inline-meta">
														<span><?php echo esc_html( $row_place !== '' ? $row_place : 'Destination' ); ?></span>
														<span><?php echo esc_html( (string) ( $row['durationLabel'] ?? $row['duration_label'] ?? '' ) ); ?></span>
													</div>
													<h3><a class="aj-featured-title-link" href="<?php echo esc_url( $row_url ); ?>"><?php echo esc_html( $row_title ); ?></a></h3>
													<p style="margin:0;color:var(--aj-muted);font-size:13px;line-height:1.5;"><?php echo esc_html( (string) ( $row['shortDescription'] ?? $row['short_description'] ?? '' ) ); ?></p>
													<a class="aj-featured-link" href="<?php echo esc_url( $row_url ); ?>">Voir l'activité</a>
												</div>
											</article>
										<?php } ?>
									</div>
								</section>
							<?php } ?>
						</div>

						<aside class="aj-activity-detail-sidebar">
							<div class="aj-activity-summary-card">
								<span class="aj-section-kicker">Récapitulatif prix</span>
								<h3><?php echo esc_html( $title ); ?></h3>
								<div class="aj-activity-summary-price"><?php echo esc_html( $price_label ); ?></div>
								<ul class="aj-activity-summary-list">
									<li><strong>Ville</strong><span><?php echo esc_html( $city !== '' ? $city : 'N/A' ); ?></span></li>
									<li><strong>Catégorie</strong><span><?php echo esc_html( $category ); ?></span></li>
									<li><strong>Durée</strong><span><?php echo esc_html( '' !== $duration ? $duration : 'N/A' ); ?></span></li>
									<li><strong>Disponibilité</strong><span class="aj-pack-summary-availability"><?php echo esc_html( $availability ); ?></span></li>
								</ul>
								<div class="aj-activity-detail-actions">
									<a class="aj-card-primary" href="<?php echo esc_url( $reservation_url ); ?>" target="_blank" rel="noopener">Réserver cette activité</a>
									<a class="aj-card-secondary" href="<?php echo esc_url( $advice_url ); ?>" target="_blank" rel="noopener">Demander conseil</a>
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
	<div id="aj-home" class="aj-home aj-activities-static-page">
		<?php ajth_render_site_header( $settings ); ?>

		<div class="aj-activities-static ajinsafro-page-container" id="aj-activities-static">
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
