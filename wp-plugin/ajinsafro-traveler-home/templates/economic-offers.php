<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'page-economic-offers-ajinsafro';
		$classes[] = 'page-hajj-omra-ajinsafro';

		return $classes;
	}
);

get_header();

$settings        = function_exists( 'ajth_get_settings' ) ? ajth_get_settings() : array();
$page_url        = function_exists( 'ajth_get_economic_offers_page_url' ) ? ajth_get_economic_offers_page_url() : home_url( '/formule-economique/' );
$fallback_image  = function_exists( 'ajth_economic_offers_default_image_url' ) ? ajth_economic_offers_default_image_url() : trailingslashit( AJTH_URL ) . 'assets/images/fallback-hajj-omra.svg';
$offers          = function_exists( 'ajth_get_economic_offers' ) ? ajth_get_economic_offers() : array();
$current_slug    = function_exists( 'ajth_get_current_economic_offer_slug' ) ? ajth_get_current_economic_offer_slug() : '';
$current_offer   = $current_slug && function_exists( 'ajth_get_economic_offer_by_slug' ) ? ajth_get_economic_offer_by_slug( $current_slug ) : null;
$success_message = '';
$error_message   = '';
$filter_type     = isset( $_GET['offer_type'] ) ? sanitize_key( wp_unslash( $_GET['offer_type'] ) ) : '';
$filter_city     = isset( $_GET['departure_city'] ) ? sanitize_text_field( wp_unslash( $_GET['departure_city'] ) ) : '';
$filter_budget   = isset( $_GET['budget'] ) ? absint( $_GET['budget'] ) : 0;
$filter_date     = isset( $_GET['departure_date'] ) ? sanitize_text_field( wp_unslash( $_GET['departure_date'] ) ) : '';
$filter_dest     = isset( $_GET['destination'] ) ? sanitize_text_field( wp_unslash( $_GET['destination'] ) ) : '';
$posted_departure = sanitize_text_field( wp_unslash( $_POST['selected_departure_date'] ?? '' ) );

$format_price = static function ( $amount, $currency = 'DH' ) {
	if ( null === $amount || '' === $amount || ! is_numeric( $amount ) ) {
		return 'Sur demande';
	}

	return number_format( (float) $amount, 0, ',', ' ' ) . ' ' . $currency;
};

$format_date = static function ( $date_value ) {
	$date_value = is_string( $date_value ) ? trim( $date_value ) : '';
	if ( '' === $date_value ) {
		return 'A confirmer';
	}

	$timestamp = strtotime( $date_value );

	return $timestamp ? wp_date( 'd M Y', $timestamp ) : $date_value;
};

$status_badge = static function ( array $offer ) {
	$status    = (string) ( $offer['availability_status'] ?? $offer['status'] ?? '' );
	$remaining = (int) ( $offer['remaining_places'] ?? 0 );

	if ( in_array( $status, array( 'expired' ), true ) ) {
		return array( 'label' => 'Offre expiree', 'class' => 'is-expired' );
	}
	if ( in_array( $status, array( 'full' ), true ) || $remaining <= 0 ) {
		return array( 'label' => 'Complet', 'class' => 'is-full' );
	}
	if ( in_array( $status, array( 'limited' ), true ) || $remaining <= 5 ) {
		return array( 'label' => 'Places limitees', 'class' => 'is-limited' );
	}

	return array( 'label' => 'Disponible', 'class' => 'is-available' );
};

$first_departure_label = static function ( array $offer ) use ( $format_date ) {
	if ( ! empty( $offer['departure_date'] ) ) {
		return $format_date( $offer['departure_date'] );
	}
	foreach ( (array) ( $offer['departures'] ?? array() ) as $departure ) {
		if ( ! empty( $departure['departure_date'] ) ) {
			return $format_date( $departure['departure_date'] );
		}
	}

	return 'Date sur demande';
};

$find_next_departure = static function ( array $offer ) {
	foreach ( (array) ( $offer['departures'] ?? array() ) as $departure ) {
		if ( ! empty( $departure['departure_date'] ) ) {
			return $departure;
		}
	}

	return null;
};

$whatsapp_link = static function ( array $offer ) {
	$title = trim( (string) ( $offer['title'] ?? 'Formule Economique' ) );
	$url   = trim( (string) ( $offer['detail_url'] ?? '' ) );

	return 'https://wa.me/212660683464?text=' . rawurlencode( sprintf( 'Bonjour Ajinsafro, je souhaite recevoir plus d informations sur l offre "%s" %s', $title, $url !== '' ? '(' . $url . ')' : '' ) );
};

$has_active_filters = '' !== $filter_type || '' !== $filter_city || '' !== $filter_dest || $filter_budget > 0 || '' !== $filter_date;

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['ajth_economic_offer_request'] ) && $current_slug ) {
	$nonce = isset( $_POST['ajth_economic_offer_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajth_economic_offer_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'ajth_economic_offer_request' ) ) {
		$error_message = 'Votre session a expire. Merci de renvoyer votre demande.';
	} else {
		$payload = array(
			'full_name'               => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
			'phone'                   => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'email'                   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'adults'                  => max( 1, (int) ( $_POST['adults'] ?? 1 ) ),
			'children'                => max( 0, (int) ( $_POST['children'] ?? 0 ) ),
			'selected_departure_date' => $posted_departure,
			'message'                 => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
		);

		if ( '' === $payload['full_name'] || '' === $payload['phone'] || '' === $payload['email'] ) {
			$error_message = 'Merci de renseigner votre nom, telephone et email.';
		} else {
			$result = ajth_submit_economic_offer_request( $current_slug, $payload );
			if ( is_wp_error( $result ) ) {
				$error_message = $result->get_error_message();
			} else {
				$success_message = ! empty( $result['message'] ) ? (string) $result['message'] : 'Votre demande a ete envoyee avec succes.';
			}
		}
	}
}

$type_options = array();
$city_options = array();
$destination_options = array();

foreach ( $offers as $offer ) {
	if ( ! empty( $offer['offer_type'] ) && ! empty( $offer['type_label'] ) ) {
		$type_options[ $offer['offer_type'] ] = $offer['type_label'];
	}
	if ( ! empty( $offer['departure_city'] ) ) {
		$city_options[ sanitize_title( (string) $offer['departure_city'] ) ] = (string) $offer['departure_city'];
	}
	if ( ! empty( $offer['destination'] ) ) {
		$destination_options[ sanitize_title( (string) $offer['destination'] ) ] = (string) $offer['destination'];
	}
}

asort( $type_options );
asort( $city_options );
asort( $destination_options );

$filtered_offers = array_values(
	array_filter(
		$offers,
		static function ( $offer ) use ( $filter_type, $filter_city, $filter_dest, $filter_budget, $filter_date ) {
			if ( '' !== $filter_type && (string) ( $offer['offer_type'] ?? '' ) !== $filter_type ) {
				return false;
			}
			if ( '' !== $filter_city && 0 !== strcasecmp( (string) ( $offer['departure_city'] ?? '' ), $filter_city ) ) {
				return false;
			}
			if ( '' !== $filter_dest && false === stripos( (string) ( $offer['destination'] ?? '' ), $filter_dest ) ) {
				return false;
			}
			if ( $filter_budget > 0 && isset( $offer['price_from'] ) && is_numeric( $offer['price_from'] ) && (float) $offer['price_from'] > $filter_budget ) {
				return false;
			}
			if ( '' !== $filter_date ) {
				$matches = false;
				foreach ( (array) ( $offer['departures'] ?? array() ) as $departure ) {
					if ( ! empty( $departure['departure_date'] ) && (string) $departure['departure_date'] === $filter_date ) {
						$matches = true;
						break;
					}
				}
				if ( ! $matches && (string) ( $offer['departure_date'] ?? '' ) !== $filter_date ) {
					return false;
				}
			}

			return true;
		}
	)
);
?>

<div class="aj-home-wrap">
	<div id="aj-home" class="aj-home aj-hajj-omra-page">
		<?php if ( function_exists( 'ajth_render_site_header' ) ) : ?>
			<?php ajth_render_site_header( $settings ); ?>
		<?php endif; ?>

		<main class="ajho-page ajinsafro-page-container">
			<?php if ( $current_slug && ! $current_offer ) : ?>
				<section class="ajho-hero ajho-hero--compact">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span>/</span>
							<a href="<?php echo esc_url( $page_url ); ?>">Formule Economique</a>
							<span>/</span>
							<span>Offre introuvable</span>
						</nav>
						<div class="ajho-hero__inner">
							<div class="ajho-hero__copy">
								<h1>Offre economique introuvable</h1>
								<p>Cette offre n est plus disponible ou n a pas encore ete publiee.</p>
								<div class="ajho-hero__actions">
									<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--primary">Retour au catalogue</a>
								</div>
							</div>
						</div>
					</div>
				</section>
			<?php elseif ( $current_offer ) : ?>
				<?php
				$detail_status   = $status_badge( $current_offer );
				$next_departure  = $find_next_departure( $current_offer );
				$gallery         = array_values( array_filter( (array) ( $current_offer['gallery'] ?? array() ) ) );
				if ( empty( $gallery ) ) {
					$gallery[] = $fallback_image;
				}
				$hero_image      = ! empty( $gallery[0] ) ? $gallery[0] : $fallback_image;
				$share_url       = ! empty( $current_offer['detail_url'] ) ? (string) $current_offer['detail_url'] : ajth_get_economic_offer_detail_url( $current_slug );
				$share_title     = trim( (string) ( $current_offer['title'] ?? 'Formule Economique Ajinsafro' ) );
				$thumb_gallery   = array_slice( $gallery, 1, 4 );
				while ( count( $thumb_gallery ) < 4 ) {
					$thumb_gallery[] = $fallback_image;
				}
				$included_items = array_values( array_filter( (array) ( $current_offer['included_items'] ?? array() ) ) );
				$excluded_items = array_values( array_filter( (array) ( $current_offer['excluded_items'] ?? array() ) ) );
				if ( empty( $included_items ) ) {
					foreach ( array(
						! empty( $current_offer['transport_included'] ) ? 'Transport inclus' : null,
						! empty( $current_offer['flight_included'] ) ? 'Vol inclus' : null,
						! empty( $current_offer['hotel_included'] ) ? 'Hotel inclus' : null,
						! empty( $current_offer['transfer_included'] ) ? 'Transfert inclus' : null,
					) as $fallback_item ) {
						if ( $fallback_item ) {
							$included_items[] = $fallback_item;
						}
					}
				}
				if ( empty( $included_items ) ) {
					$included_items[] = 'Details communiques par nos conseillers Ajinsafro.';
				}
				if ( empty( $excluded_items ) ) {
					$excluded_items[] = 'Les exclusions seront confirmees avant validation.';
				}
				?>
				<section class="ajho-hero ajho-hero--detail" style="background-image:linear-gradient(120deg, rgba(8, 46, 85, 0.76), rgba(11, 77, 141, 0.72)), url('<?php echo esc_url( $hero_image ); ?>');">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb ajho-breadcrumb--light" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span>/</span>
							<a href="<?php echo esc_url( $page_url ); ?>">Formule Economique</a>
							<span>/</span>
							<span><?php echo esc_html( $current_offer['title'] ?? 'Offre' ); ?></span>
						</nav>

						<div class="ajho-hero__inner ajho-hero__inner--detail ajho-hero__inner--source">
							<div class="ajho-hero__copy">
								<div class="ajho-hero__badges">
									<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $current_offer['type_label'] ?? 'Offre' ); ?></span>
									<span class="ajho-chip <?php echo esc_attr( $detail_status['class'] ); ?>"><?php echo esc_html( $detail_status['label'] ); ?></span>
									<?php if ( ! empty( $current_offer['is_promoted'] ) ) : ?>
										<span class="ajho-chip is-limited">Promotion</span>
									<?php endif; ?>
								</div>
								<h1><?php echo esc_html( $current_offer['title'] ?? 'Formule Economique Ajinsafro' ); ?></h1>
								<p><?php echo esc_html( $current_offer['short_description'] ?? 'Offre Ajinsafro a petit budget, avec disponibilites et tarifs dynamiques.' ); ?></p>

								<div class="ajho-hero__facts">
									<div><strong>Depart</strong><span><?php echo esc_html( $current_offer['departure_city'] ?? 'A confirmer' ); ?></span></div>
									<div><strong>Duree</strong><span><?php echo esc_html( $current_offer['duration_label'] ?? 'A confirmer' ); ?></span></div>
									<div><strong>Places</strong><span><?php echo esc_html( (string) ( $current_offer['remaining_places'] ?? 0 ) ); ?> restantes</span></div>
								</div>

								<div class="ajho-hero__actions">
									<a href="#reservation-form" class="ajho-btn ajho-btn--primary">Demander reservation</a>
									<a href="#departures-section" class="ajho-btn ajho-btn--secondary">Voir les departs</a>
								</div>
							</div>

							<div class="ajho-hero__aside">
								<div class="ajho-summary-card ajho-summary-card--hero ajho-price-card">
									<div class="ajho-price-card__badge">Meilleur prix</div>
									<div class="ajho-price-card__label">Prix a partir de</div>
									<div class="ajho-price-card__value"><?php echo esc_html( $format_price( $current_offer['price_from'] ?? null, $current_offer['currency'] ?? 'DH' ) ); ?></div>
									<ul class="ajho-price-list">
										<li><strong>Prochain depart</strong><span><?php echo esc_html( $first_departure_label( $current_offer ) ); ?></span></li>
										<li><strong>Destination</strong><span><?php echo esc_html( $current_offer['destination'] ?? 'A confirmer' ); ?></span></li>
										<li><strong>Hebergement</strong><span><?php echo esc_html( $current_offer['hotel_name'] ?? 'Selon offre' ); ?></span></li>
										<li><strong>Repas</strong><span><?php echo esc_html( $current_offer['meal_plan_label'] ?? 'Selon offre' ); ?></span></li>
									</ul>
									<div class="ajho-small-actions">
										<a href="<?php echo esc_url( $whatsapp_link( $current_offer ) ); ?>" class="ajho-small-btn ajho-small-btn--whatsapp" target="_blank" rel="noopener">WhatsApp</a>
										<button type="button" class="ajho-small-btn ajho-small-btn--share ajho-share-btn" data-ajho-share data-share-url="<?php echo esc_url( $share_url ); ?>" data-share-title="<?php echo esc_attr( $share_title ); ?>">Partager</button>
									</div>
								</div>
							</div>
						</div>
					</div>
				</section>

				<div class="ajho-container ajho-content ajho-content--detail">
					<?php if ( $success_message ) : ?>
						<div class="ajho-alert is-success"><?php echo esc_html( $success_message ); ?></div>
					<?php endif; ?>
					<?php if ( $error_message ) : ?>
						<div class="ajho-alert is-error"><?php echo esc_html( $error_message ); ?></div>
					<?php endif; ?>

					<section class="ajho-content-area">
						<div class="ajho-content-left">
							<section class="ajho-gallery-wrap">
								<div class="ajho-main-gallery">
									<img src="<?php echo esc_url( $gallery[0] ); ?>" alt="<?php echo esc_attr( $current_offer['title'] ?? 'Formule Economique' ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
									<div class="ajho-gallery-count">1 / <?php echo esc_html( (string) count( $gallery ) ); ?></div>
								</div>
								<div class="ajho-thumb-grid">
									<?php foreach ( $thumb_gallery as $index => $image_url ) : ?>
										<figure class="ajho-thumb<?php echo $image_url === $fallback_image ? ' ajho-thumb--fallback' : ''; ?>">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( ( $current_offer['title'] ?? 'Offre' ) . ' photo ' . ( $index + 2 ) ); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
										</figure>
									<?php endforeach; ?>
								</div>
							</section>

							<div class="ajho-card">
								<div class="ajho-section-label">Presentation</div>
								<h2 class="ajho-card-title">Votre offre Formule Economique</h2>
								<p class="ajho-muted"><?php echo esc_html( (string) ( $current_offer['description'] ?? $current_offer['short_description'] ?? '' ) ); ?></p>

								<div class="ajho-info-grid-3">
									<div class="ajho-mini-box">
										<h4>Hebergement</h4>
										<div class="ajho-kv-list">
											<div class="ajho-kv"><strong>Type</strong><span><?php echo esc_html( $current_offer['accommodation_type'] ?? 'Selon offre' ); ?></span></div>
											<div class="ajho-kv"><strong>Hotel</strong><span><?php echo esc_html( $current_offer['hotel_name'] ?? 'A confirmer' ); ?></span></div>
											<div class="ajho-kv"><strong>Categorie</strong><span><?php echo esc_html( $current_offer['hotel_category'] ?? 'A confirmer' ); ?></span></div>
											<div class="ajho-kv"><strong>Distance cle</strong><span><?php echo esc_html( $current_offer['key_distance'] ?? 'A confirmer' ); ?></span></div>
										</div>
									</div>
									<div class="ajho-mini-box">
										<h4>Services inclus</h4>
										<ul class="ajho-check-list">
											<li><?php echo ! empty( $current_offer['transport_included'] ) ? 'Transport inclus' : 'Transport selon offre'; ?></li>
											<li><?php echo ! empty( $current_offer['flight_included'] ) ? 'Vol inclus' : 'Vol selon offre'; ?></li>
											<li><?php echo ! empty( $current_offer['hotel_included'] ) ? 'Hotel inclus' : 'Hotel selon offre'; ?></li>
											<li><?php echo ! empty( $current_offer['guide_included'] ) ? 'Guide inclus' : 'Guide sur demande'; ?></li>
										</ul>
									</div>
									<div class="ajho-mini-box">
										<h4>Ce que comprend l offre</h4>
										<ul class="ajho-check-list">
											<?php foreach ( array_slice( $included_items, 0, 4 ) as $item ) : ?>
												<li><?php echo esc_html( $item ); ?></li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
							</div>

							<div class="ajho-card" id="departures-section">
								<div class="ajho-section-label">Departs</div>
								<h2 class="ajho-card-title">Dates disponibles</h2>
								<div class="ajho-table-wrap">
									<table class="ajho-table">
										<thead>
											<tr>
												<th>Depart</th>
												<th>Retour</th>
												<th>Statut</th>
												<th>Places</th>
												<th>Prix a partir de</th>
											</tr>
										</thead>
										<tbody>
											<?php if ( ! empty( $current_offer['departures'] ) ) : ?>
												<?php foreach ( (array) ( $current_offer['departures'] ?? array() ) as $departure ) : ?>
													<?php $departure_badge = $status_badge( array( 'availability_status' => $departure['status'] ?? '', 'remaining_places' => $departure['remaining_places'] ?? 0 ) ); ?>
													<tr>
														<td><?php echo esc_html( $format_date( $departure['departure_date'] ?? '' ) ); ?></td>
														<td><?php echo esc_html( $format_date( $departure['return_date'] ?? '' ) ); ?></td>
														<td><span class="ajho-status-pill <?php echo esc_attr( $departure_badge['class'] ); ?>"><?php echo esc_html( $departure_badge['label'] ); ?></span></td>
														<td><?php echo esc_html( (string) ( $departure['remaining_places'] ?? 0 ) ); ?></td>
														<td class="ajho-table__price"><?php echo esc_html( $format_price( $departure['price_from'] ?? null, $current_offer['currency'] ?? 'DH' ) ); ?></td>
													</tr>
												<?php endforeach; ?>
											<?php else : ?>
												<tr>
													<td colspan="5">Les prochains departs seront confirmes par nos agents.</td>
												</tr>
											<?php endif; ?>
										</tbody>
									</table>
								</div>
							</div>

							<div class="ajho-card">
								<div class="ajho-section-label">Tarifs</div>
								<h2 class="ajho-card-title">Prix variables</h2>
								<div class="ajho-table-wrap">
									<table class="ajho-table">
										<thead>
											<tr>
												<th>Libelle</th>
												<th>Type</th>
												<th>Prix</th>
												<th>Stock</th>
											</tr>
										</thead>
										<tbody>
											<?php if ( ! empty( $current_offer['prices'] ) ) : ?>
												<?php foreach ( (array) ( $current_offer['prices'] ?? array() ) as $price_row ) : ?>
													<tr>
														<td><?php echo esc_html( $price_row['label'] ?? 'Offre' ); ?></td>
														<td><?php echo esc_html( $price_row['type'] ?? 'Selon offre' ); ?></td>
														<td class="ajho-table__price"><?php echo esc_html( $format_price( $price_row['price'] ?? null, $current_offer['currency'] ?? 'DH' ) ); ?></td>
														<td><?php echo esc_html( (string) ( $price_row['stock'] ?? 0 ) ); ?></td>
													</tr>
												<?php endforeach; ?>
											<?php else : ?>
												<tr>
													<td colspan="4">Tarification detaillee disponible sur demande.</td>
												</tr>
											<?php endif; ?>
										</tbody>
									</table>
								</div>
							</div>

							<div class="ajho-bottom-grid">
								<div class="ajho-card">
									<div class="ajho-section-label">Inclus</div>
									<h2 class="ajho-card-title ajho-card-title--small">Ce qui est inclus</h2>
									<ul class="ajho-check-list">
										<?php foreach ( $included_items as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="ajho-card">
									<div class="ajho-section-label">Exclusions</div>
									<h2 class="ajho-card-title ajho-card-title--small">Ce qui n est pas inclus</h2>
									<ul class="ajho-x-list">
										<?php foreach ( $excluded_items as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="ajho-card">
									<div class="ajho-section-label">Documents</div>
									<h2 class="ajho-card-title ajho-card-title--small">Documents necessaires</h2>
									<p class="ajho-muted"><?php echo esc_html( (string) ( $current_offer['required_documents'] ?? '' ) ); ?></p>
								</div>
								<div class="ajho-card">
									<div class="ajho-section-label">Conditions</div>
									<h2 class="ajho-card-title ajho-card-title--small">Conditions</h2>
									<p class="ajho-muted"><?php echo esc_html( (string) ( $current_offer['payment_conditions'] ?? $current_offer['cancellation_conditions'] ?? '' ) ); ?></p>
								</div>
							</div>
						</div>

						<aside class="ajho-sidebar">
							<div class="ajho-card ajho-offer-box ajho-offer-box--featured">
								<h3>Resume de l offre</h3>
								<h2><?php echo esc_html( $current_offer['title'] ?? '' ); ?></h2>
								<div class="ajho-sidebar-price"><?php echo esc_html( $format_price( $current_offer['price_from'] ?? null, $current_offer['currency'] ?? 'DH' ) ); ?></div>
								<div class="ajho-kv"><strong>Type</strong><span><?php echo esc_html( $current_offer['type_label'] ?? 'Offre' ); ?></span></div>
								<div class="ajho-kv"><strong>Ville de depart</strong><span><?php echo esc_html( $current_offer['departure_city'] ?? 'A confirmer' ); ?></span></div>
								<div class="ajho-kv"><strong>Date</strong><span><?php echo esc_html( $first_departure_label( $current_offer ) ); ?></span></div>
								<div class="ajho-kv"><strong>Places restantes</strong><span><?php echo esc_html( (string) ( $current_offer['remaining_places'] ?? 0 ) ); ?></span></div>
							</div>

							<div class="ajho-card ajho-form-card" id="reservation-form">
								<div class="ajho-section-label">Reservation</div>
								<h3>Demander une reservation</h3>
								<form method="post" action="<?php echo esc_url( $current_offer['detail_url'] ?? $page_url ); ?>" class="ajho-form-grid">
									<?php wp_nonce_field( 'ajth_economic_offer_request', 'ajth_economic_offer_nonce' ); ?>
									<input type="hidden" name="ajth_economic_offer_request" value="1">
									<div class="ajho-field">
										<label for="ajeo-full-name">Nom complet</label>
										<input id="ajeo-full-name" type="text" name="full_name" value="<?php echo esc_attr( wp_unslash( $_POST['full_name'] ?? '' ) ); ?>" required>
									</div>
									<div class="ajho-field">
										<label for="ajeo-phone">Telephone</label>
										<input id="ajeo-phone" type="tel" name="phone" value="<?php echo esc_attr( wp_unslash( $_POST['phone'] ?? '' ) ); ?>" required>
									</div>
									<div class="ajho-field">
										<label for="ajeo-email">Email</label>
										<input id="ajeo-email" type="email" name="email" value="<?php echo esc_attr( wp_unslash( $_POST['email'] ?? '' ) ); ?>" required>
									</div>
									<div class="ajho-field">
										<label for="ajeo-departure">Depart selectionne</label>
										<select id="ajeo-departure" name="selected_departure_date">
											<option value="">Choisir un depart</option>
											<?php foreach ( (array) ( $current_offer['departures'] ?? array() ) as $departure ) : ?>
												<option value="<?php echo esc_attr( $departure['departure_date'] ?? '' ); ?>" <?php selected( $posted_departure, $departure['departure_date'] ?? '' ); ?>>
													<?php echo esc_html( $format_date( $departure['departure_date'] ?? '' ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="ajho-two-cols">
										<div class="ajho-field">
											<label for="ajeo-adults">Adultes</label>
											<input id="ajeo-adults" type="number" min="1" name="adults" value="<?php echo esc_attr( wp_unslash( $_POST['adults'] ?? '1' ) ); ?>">
										</div>
										<div class="ajho-field">
											<label for="ajeo-children">Enfants</label>
											<input id="ajeo-children" type="number" min="0" name="children" value="<?php echo esc_attr( wp_unslash( $_POST['children'] ?? '0' ) ); ?>">
										</div>
									</div>
									<div class="ajho-field">
										<label for="ajeo-message">Message</label>
										<textarea id="ajeo-message" name="message" rows="5" placeholder="Vos demandes, preferences, questions..."><?php echo esc_textarea( wp_unslash( $_POST['message'] ?? '' ) ); ?></textarea>
									</div>
									<button type="submit" class="ajho-btn ajho-btn--primary ajho-btn--full">Envoyer la demande</button>
								</form>
								<div class="ajho-submit-note">Reponse rapide garantie par notre equipe.<br>Vos donnees sont 100% confidentielles.</div>
							</div>
						</aside>
					</section>
				</div>
			<?php else : ?>
				<section class="ajho-hero">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb ajho-breadcrumb--light" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span>/</span>
							<span>Formule Economique</span>
						</nav>
						<div class="ajho-hero__inner">
							<div class="ajho-hero__copy">
								<div class="ajho-kicker ajho-kicker--light">Offres petit budget Ajinsafro</div>
								<h1>Formule Economique Ajinsafro</h1>
								<p>Des offres de voyage accessibles, selectionnees pour profiter au meilleur prix.</p>
								<div class="ajho-hero__actions">
									<a href="#offers-grid" class="ajho-btn ajho-btn--primary">Voir les offres</a>
									<a href="<?php echo esc_url( 'https://wa.me/212660683464' ); ?>" target="_blank" rel="noopener" class="ajho-btn ajho-btn--secondary">Demander conseil</a>
								</div>
							</div>
							<div class="ajho-hero-card">
								<strong>Formule Economique</strong>
								<span><?php echo esc_html( number_format( count( $offers ), 0, ',', ' ' ) ); ?> offres dynamiques</span>
								<p>Voyages, omra, hebergement, activites et derniere minute, mis a jour depuis la base Laravel.</p>
							</div>
						</div>
					</div>
				</section>

				<div class="ajho-container ajho-content">
					<section class="ajho-search-panel-wrap">
						<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="ajho-search-panel">
							<label class="ajho-search-field">
								<span>Type</span>
								<select name="offer_type">
									<option value="">Tous</option>
									<?php foreach ( $type_options as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ajho-search-field">
								<span>Destination</span>
								<input type="text" name="destination" value="<?php echo esc_attr( $filter_dest ); ?>" placeholder="Marrakech, Dakhla, Istanbul">
							</label>
							<label class="ajho-search-field">
								<span>Budget max</span>
								<input type="number" name="budget" value="<?php echo esc_attr( $filter_budget ?: '' ); ?>" placeholder="4500">
							</label>
							<label class="ajho-search-field">
								<span>Date</span>
								<input type="date" name="departure_date" value="<?php echo esc_attr( $filter_date ); ?>">
							</label>
							<label class="ajho-search-field">
								<span>Ville de depart</span>
								<select name="departure_city">
									<option value="">Toutes</option>
									<?php foreach ( $city_options as $city ) : ?>
										<option value="<?php echo esc_attr( $city ); ?>" <?php selected( $filter_city, $city ); ?>><?php echo esc_html( $city ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<div class="ajho-search-actions">
								<button type="submit" class="ajho-btn ajho-btn--primary">Filtrer</button>
								<?php if ( $has_active_filters ) : ?>
									<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--ghost">Reinitialiser</a>
								<?php endif; ?>
							</div>
						</form>
					</section>

					<section class="ajho-stats">
						<div><strong><?php echo esc_html( number_format( count( $filtered_offers ), 0, ',', ' ' ) ); ?></strong><span>offres visibles</span></div>
						<div><strong><?php echo esc_html( number_format( count( array_filter( $filtered_offers, static fn( $offer ) => ! empty( $offer['is_promoted'] ) ) ), 0, ',', ' ' ) ); ?></strong><span>promotions</span></div>
						<div><strong><?php echo esc_html( number_format( count( array_filter( $filtered_offers, static fn( $offer ) => ! empty( $offer['is_featured'] ) ) ), 0, ',', ' ' ) ); ?></strong><span>mises en avant</span></div>
					</section>

					<section class="ajho-results-head">
						<div>
							<div class="ajho-section-label">Catalogue</div>
							<h2>Offres Formule Economique</h2>
							<p>Des offres dynamiques, connectees a votre base Laravel, sans contenu statique.</p>
						</div>
					</section>

					<?php if ( empty( $filtered_offers ) ) : ?>
						<div class="ajho-empty">
							<h3>Aucune offre ne correspond a vos filtres</h3>
							<p>Essayez une autre ville de depart, une autre date ou un budget plus large.</p>
						</div>
					<?php else : ?>
						<section id="offers-grid" class="ajho-grid">
							<?php foreach ( $filtered_offers as $offer ) : ?>
								<?php
								$card_status  = $status_badge( $offer );
								$detail_url   = ! empty( $offer['detail_url'] ) ? (string) $offer['detail_url'] : ajth_get_economic_offer_detail_url( $offer['slug'] ?? '' );
								$request_url  = ! empty( $offer['request_url'] ) ? (string) $offer['request_url'] : $detail_url . '#reservation-form';
								$image_url    = ! empty( $offer['main_image_url'] ) ? (string) $offer['main_image_url'] : $fallback_image;
								?>
								<article class="ajho-card">
									<div class="ajho-card__media">
										<a href="<?php echo esc_url( $detail_url ); ?>" class="ajho-card__media-link">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $offer['title'] ?? 'Offre economique' ); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
										</a>
										<div class="ajho-card__badges">
											<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $offer['type_label'] ?? 'Offre' ); ?></span>
											<?php if ( ! empty( $offer['is_promoted'] ) ) : ?>
												<span class="ajho-chip is-limited">Promotion</span>
											<?php else : ?>
												<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $offer['category_label'] ?? 'Economique' ); ?></span>
											<?php endif; ?>
											<span class="ajho-chip <?php echo esc_attr( $card_status['class'] ); ?>"><?php echo esc_html( $card_status['label'] ); ?></span>
										</div>
									</div>
									<div class="ajho-card__body">
										<h3><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $offer['title'] ?? 'Offre economique' ); ?></a></h3>
										<p><?php echo esc_html( $offer['short_description'] ?? 'Offre Ajinsafro avec prix et disponibilites dynamiques.' ); ?></p>
										<ul class="ajho-card__facts">
											<li><strong>Destination</strong><span><?php echo esc_html( $offer['destination'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Duree</strong><span><?php echo esc_html( $offer['duration_label'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Ville de depart</strong><span><?php echo esc_html( $offer['departure_city'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Date</strong><span><?php echo esc_html( $first_departure_label( $offer ) ); ?></span></li>
											<li><strong>Places restantes</strong><span><?php echo esc_html( (string) ( $offer['remaining_places'] ?? 0 ) ); ?></span></li>
											<li><strong>Prix</strong><span class="ajho-card__price"><?php echo esc_html( $format_price( $offer['price_from'] ?? null, $offer['currency'] ?? 'DH' ) ); ?></span></li>
										</ul>
									</div>
									<div class="ajho-card__footer">
										<div>
											<?php if ( ! empty( $offer['old_price'] ) && is_numeric( $offer['old_price'] ) ) : ?>
												<div class="ajho-card__old-price"><?php echo esc_html( $format_price( $offer['old_price'], $offer['currency'] ?? 'DH' ) ); ?></div>
											<?php endif; ?>
											<div class="ajho-card__price"><?php echo esc_html( $format_price( $offer['price_from'] ?? null, $offer['currency'] ?? 'DH' ) ); ?></div>
										</div>
										<div class="ajho-card__actions">
											<a href="<?php echo esc_url( $detail_url ); ?>" class="ajho-btn ajho-btn--primary">Voir details</a>
											<a href="<?php echo esc_url( $request_url ); ?>" class="ajho-btn ajho-btn--secondary">Demander reservation</a>
										</div>
									</div>
								</article>
							<?php endforeach; ?>
						</section>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</main>

		<?php if ( function_exists( 'ajth_render_site_footer' ) ) : ?>
			<?php ajth_render_site_footer( $settings ); ?>
		<?php endif; ?>
	</div>
</div>

<script>
document.addEventListener('click', function (event) {
    const shareButton = event.target.closest('[data-ajho-share]');
    if (!shareButton) {
        return;
    }
    const shareUrl = shareButton.getAttribute('data-share-url') || window.location.href;
    const shareTitle = shareButton.getAttribute('data-share-title') || document.title;

    if (navigator.share) {
        navigator.share({ title: shareTitle, url: shareUrl }).catch(function () {});
        return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(shareUrl).then(function () {
            shareButton.textContent = 'Lien copie';
            setTimeout(function () {
                shareButton.textContent = 'Partager';
            }, 1600);
        });
    }
});
</script>

<?php
get_footer();
