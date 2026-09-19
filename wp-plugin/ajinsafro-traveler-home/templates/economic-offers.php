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
// Banniere image pilotee depuis l'admin ; sans elle, le bandeau d'origine reste.
$page_banner = function_exists('ajth_get_page_banner') ? ajth_get_page_banner('formule-economique') : null;
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
$filter_sort     = isset( $_GET['sort'] ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'prix';
$filter_sort     = in_array( $filter_sort, array( 'prix', 'date', 'places' ), true ) ? $filter_sort : 'prix';
// Une case decochee n'envoie rien : le marqueur distingue « premiere visite »
// (on masque les expirees par defaut) de « case volontairement decochee ».
$filters_posted  = isset( $_GET['filtres'] );
$hide_expired    = $filters_posted ? ! empty( $_GET['masquer_expirees'] ) : true;
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
		return array( 'label' => 'Offre expirée', 'class' => 'is-expired', 'state' => 'expired' );
	}
	if ( in_array( $status, array( 'full' ), true ) || $remaining <= 0 ) {
		return array( 'label' => 'Complet', 'class' => 'is-full', 'state' => 'full' );
	}
	if ( in_array( $status, array( 'limited' ), true ) || $remaining <= 5 ) {
		return array( 'label' => 'Places limitées', 'class' => 'is-limited', 'state' => 'limited' );
	}

	return array( 'label' => 'Disponible', 'class' => 'is-available', 'state' => 'available' );
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
				// « Depart a partir du » : au moins un depart a cette date ou apres.
				$dates = array();
				foreach ( (array) ( $offer['departures'] ?? array() ) as $departure ) {
					if ( ! empty( $departure['departure_date'] ) ) {
						$dates[] = (string) $departure['departure_date'];
					}
				}
				if ( ! empty( $offer['departure_date'] ) ) {
					$dates[] = (string) $offer['departure_date'];
				}

				$matches = false;
				foreach ( $dates as $candidate ) {
					if ( $candidate >= $filter_date ) {
						$matches = true;
						break;
					}
				}
				if ( ! $matches ) {
					return false;
				}
			}

			return true;
		}
	)
);

if ( $hide_expired ) {
	$filtered_offers = array_values(
		array_filter(
			$filtered_offers,
			static function ( $offer ) use ( $status_badge ) {
				return 'expired' !== $status_badge( $offer )['state'];
			}
		)
	);
}

$sort_date_key = static function ( array $offer ) {
	$dates = array();
	foreach ( (array) ( $offer['departures'] ?? array() ) as $departure ) {
		if ( ! empty( $departure['departure_date'] ) ) {
			$dates[] = (string) $departure['departure_date'];
		}
	}
	if ( ! empty( $offer['departure_date'] ) ) {
		$dates[] = (string) $offer['departure_date'];
	}
	sort( $dates );

	// Sans date connue, l'offre part en fin de liste plutot qu'en tete.
	return ! empty( $dates[0] ) ? $dates[0] : '9999-12-31';
};

usort(
	$filtered_offers,
	static function ( $a, $b ) use ( $filter_sort, $sort_date_key ) {
		if ( 'places' === $filter_sort ) {
			return (int) ( $a['remaining_places'] ?? 0 ) <=> (int) ( $b['remaining_places'] ?? 0 );
		}
		if ( 'date' === $filter_sort ) {
			return strcmp( $sort_date_key( $a ), $sort_date_key( $b ) );
		}

		// Prix croissant : « sur demande » ne doit pas passer devant un vrai tarif.
		$pa = isset( $a['price_from'] ) && is_numeric( $a['price_from'] ) ? (float) $a['price_from'] : INF;
		$pb = isset( $b['price_from'] ) && is_numeric( $b['price_from'] ) ? (float) $b['price_from'] : INF;

		return $pa <=> $pb;
	}
);

/**
 * Tout ce dont la carte a besoin, calcule une fois par offre.
 */
$card_presentation = static function ( array $offer ) use ( $status_badge, $find_next_departure, $format_price, $first_departure_label, $page_url ) {
	$badge      = $status_badge( $offer );
	$state      = $badge['state'];
	$is_expired = 'expired' === $state;

	$departure = $find_next_departure( $offer );
	$capacity  = (int) ( $departure['total_places'] ?? 0 );
	$remaining = null !== ( $departure['remaining_places'] ?? null )
		? (int) $departure['remaining_places']
		: (int) ( $offer['remaining_places'] ?? 0 );
	$remaining = max( 0, $remaining );

	// Jauge seulement si la capacite est connue : sinon la barre mentirait.
	$fill_pct = $capacity > 0 ? (int) round( ( max( 0, $capacity - $remaining ) / $capacity ) * 100 ) : null;

	if ( $is_expired ) {
		$seats_label = 'Départ passé';
	} elseif ( $remaining > 1 ) {
		$seats_label = $remaining . ' places restantes';
	} elseif ( 1 === $remaining ) {
		$seats_label = '1 place restante';
	} else {
		$seats_label = 'Complet';
	}

	$detail_url  = ! empty( $offer['detail_url'] ) ? (string) $offer['detail_url'] : ajth_get_economic_offer_detail_url( $offer['slug'] ?? '' );
	$image_url   = ! empty( $offer['main_image_url'] ) ? (string) $offer['main_image_url'] : '';

	return array(
		'state'       => $state,
		'state_label' => $badge['label'],
		'expired'     => $is_expired,
		'fill_pct'    => $fill_pct,
		'seats_label' => $seats_label,
		'detail_url'  => $detail_url,
		'image_url'   => $image_url,
		'date_label'  => $first_departure_label( $offer ),
		'price_label' => $format_price( $offer['price_from'] ?? null, $offer['currency'] ?? 'DH' ),
		'cta_label'   => $is_expired ? 'Offre similaire' : 'Voir l’offre',
		'cta_url'     => $is_expired ? $page_url : $detail_url,
	);
};

$visible_count  = count( $filtered_offers );
$promo_count    = count(
	array_filter(
		$filtered_offers,
		static function ( $offer ) {
			return ! empty( $offer['is_promoted'] );
		}
	)
);

if ( 0 === $visible_count ) {
	$count_label = 'Aucune offre visible avec ces filtres';
} else {
	$count_label = $visible_count . ( $visible_count > 1 ? ' offres visibles' : ' offre visible' );
	if ( $promo_count > 0 ) {
		$count_label .= ' · ' . $promo_count . ( $promo_count > 1 ? ' promotions' : ' promotion' );
	}
}

// Raccourcis de destination : les plus representees dans le catalogue.
$quick_tags = array();
$destination_counts = array();
foreach ( $offers as $offer ) {
	$destination = trim( (string) ( $offer['destination'] ?? '' ) );
	if ( '' !== $destination ) {
		$destination_counts[ $destination ] = ( $destination_counts[ $destination ] ?? 0 ) + 1;
	}
}
arsort( $destination_counts );
$quick_tags = array_slice( array_keys( $destination_counts ), 0, 4 );

$conseil_url = 'https://wa.me/212660683464?text=' . rawurlencode( 'Bonjour Ajinsafro, je cherche une offre Formule Économique.' );
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
				<section class="aj-eco-hero<?php echo $page_banner ? ' aj-eco-hero--banner' : ''; ?>">
					<?php if ( $page_banner ) : ?>
						<?php if ('' !== $page_banner['link_url']) { ?>
						    <a class="aj-page-banner" href="<?php echo esc_url($page_banner['link_url']); ?>">
						        <img src="<?php echo esc_url($page_banner['image_url']); ?>" alt="<?php echo esc_attr($page_banner['alt_text']); ?>" width="2073" height="758" fetchpriority="high" decoding="async">
						    </a>
						<?php } else { ?>
						    <div class="aj-page-banner">
						        <img src="<?php echo esc_url($page_banner['image_url']); ?>" alt="<?php echo esc_attr($page_banner['alt_text']); ?>" width="2073" height="758" fetchpriority="high" decoding="async">
						    </div>
						<?php } ?>
					<?php else : ?>
					<div class="aj-eco-hero__card">
						<nav class="aj-eco-breadcrumb" aria-label="Fil d’Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span aria-hidden="true">/</span>
							<span class="is-current">Formule Économique</span>
						</nav>

						<div class="aj-eco-hero__inner">
							<div class="aj-eco-hero__copy">
								<span class="aj-eco-kicker aj-eco-kicker--hero">Offres petit budget Ajinsafro</span>
								<h1>Formule Économique Ajinsafro</h1>
								<p>Des offres de voyage accessibles, sélectionnées pour profiter au meilleur prix.</p>
								<div class="aj-eco-hero__actions">
									<a href="#offres" class="aj-eco-btn aj-eco-btn--accent">Voir les offres</a>
									<a href="<?php echo esc_url( $conseil_url ); ?>" target="_blank" rel="noopener" class="aj-eco-btn aj-eco-btn--outline-light">Demander conseil</a>
								</div>
							</div>

							<form class="aj-eco-quick" method="get" action="<?php echo esc_url( $page_url ); ?>">
								<span class="aj-eco-quick__title">Recherche rapide</span>
								<label class="aj-eco-quick__field">
									<span>Où voulez-vous partir ?</span>
									<input type="text" name="destination" value="<?php echo esc_attr( $filter_dest ); ?>" placeholder="Agadir, Dakhla, Istanbul…">
								</label>
								<input type="hidden" name="filtres" value="1">
								<?php if ( $hide_expired ) : ?>
									<input type="hidden" name="masquer_expirees" value="1">
								<?php endif; ?>
								<?php if ( ! empty( $quick_tags ) ) : ?>
									<div class="aj-eco-quick__tags">
										<?php foreach ( $quick_tags as $tag ) : ?>
											<a class="aj-eco-tag" href="<?php echo esc_url( add_query_arg( array( 'destination' => $tag, 'filtres' => '1' ) + ( $hide_expired ? array( 'masquer_expirees' => '1' ) : array() ), $page_url ) ); ?>#offres"><?php echo esc_html( $tag ); ?></a>
										<?php endforeach; ?>
									</div>
								<?php endif; ?>
							</form>
						</div>
					</div>
					<?php endif; ?>
				</section>

				<section id="offres" class="aj-eco-section<?php echo $page_banner ? ' aj-eco-section--under-banner' : ''; ?>">
					<form class="aj-eco-filters" method="get" action="<?php echo esc_url( $page_url ); ?>" data-aj-eco-filters>
						<input type="hidden" name="filtres" value="1">

						<div class="aj-eco-filters__grid">
							<label class="aj-eco-field">
								<span class="aj-eco-field__label">Type d’offre</span>
								<select name="offer_type" data-aj-eco-auto>
									<option value="">Tous les types</option>
									<?php foreach ( $type_options as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="aj-eco-field">
								<span class="aj-eco-field__label">Destination</span>
								<input type="text" name="destination" value="<?php echo esc_attr( $filter_dest ); ?>" placeholder="Marrakech, Dakhla, Istanbul…">
							</label>
							<label class="aj-eco-field">
								<span class="aj-eco-field__label">Budget max (DH)</span>
								<input type="number" name="budget" min="0" step="100" value="<?php echo esc_attr( $filter_budget > 0 ? (string) $filter_budget : '' ); ?>" placeholder="4500">
							</label>
							<label class="aj-eco-field">
								<span class="aj-eco-field__label">Départ à partir du</span>
								<input type="date" name="departure_date" value="<?php echo esc_attr( $filter_date ); ?>" data-aj-eco-auto>
							</label>
							<label class="aj-eco-field">
								<span class="aj-eco-field__label">Ville de départ</span>
								<select name="departure_city" data-aj-eco-auto>
									<option value="">Toutes</option>
									<?php foreach ( $city_options as $city ) : ?>
										<option value="<?php echo esc_attr( $city ); ?>" <?php selected( $filter_city, $city ); ?>><?php echo esc_html( $city ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
						</div>

						<div class="aj-eco-filters__row">
							<label class="aj-eco-check">
								<input type="checkbox" name="masquer_expirees" value="1" <?php checked( $hide_expired ); ?> data-aj-eco-auto>
								<span>Masquer les offres expirées</span>
							</label>

							<label class="aj-eco-sort">
								<span class="aj-eco-sort__label">Trier par</span>
								<select name="sort" data-aj-eco-auto>
									<option value="prix" <?php selected( $filter_sort, 'prix' ); ?>>Prix croissant</option>
									<option value="date" <?php selected( $filter_sort, 'date' ); ?>>Date de départ</option>
									<option value="places" <?php selected( $filter_sort, 'places' ); ?>>Places restantes</option>
								</select>
							</label>

							<div class="aj-eco-filters__actions">
								<button type="submit" class="aj-eco-btn aj-eco-btn--primary">Filtrer</button>
								<a href="<?php echo esc_url( $page_url ); ?>#offres" class="aj-eco-reset">Réinitialiser</a>
							</div>
						</div>
					</form>

					<div class="aj-eco-results-head">
						<div class="aj-eco-results-head__copy">
							<span class="aj-eco-kicker">Catalogue</span>
							<h2>Offres Formule Économique</h2>
						</div>
						<p class="aj-eco-count"><?php echo esc_html( $count_label ); ?></p>
					</div>

					<?php if ( empty( $filtered_offers ) ) : ?>
						<div class="aj-eco-empty">
							<h3>Aucune offre ne correspond à ces filtres</h3>
							<p>Élargissez le budget ou la ville de départ, ou laissez-nous vos critères : un conseiller vous rappelle avec les offres à venir.</p>
							<div class="aj-eco-empty__actions">
								<a href="<?php echo esc_url( $page_url ); ?>#offres" class="aj-eco-btn aj-eco-btn--primary">Réinitialiser les filtres</a>
								<a href="<?php echo esc_url( $conseil_url ); ?>" target="_blank" rel="noopener" class="aj-eco-btn aj-eco-btn--outline">Être rappelé</a>
							</div>
						</div>
					<?php else : ?>
						<div class="aj-eco-grid">
							<?php foreach ( $filtered_offers as $offer ) : ?>
								<?php $card = $card_presentation( $offer ); ?>
								<article class="aj-eco-card aj-eco-card--<?php echo esc_attr( $card['state'] ); ?><?php echo $card['expired'] ? ' is-expired' : ''; ?>">
									<div class="aj-eco-card__media">
										<?php if ( '' !== $card['image_url'] ) : ?>
											<img src="<?php echo esc_url( $card['image_url'] ); ?>" alt="<?php echo esc_attr( $offer['title'] ?? 'Offre Formule Économique' ); ?>" loading="lazy">
										<?php else : ?>
											<span class="aj-eco-card__placeholder">Visuel à venir</span>
										<?php endif; ?>
										<span class="aj-eco-card__kind"><?php echo esc_html( $offer['type_label'] ?? 'Offre' ); ?></span>
										<span class="aj-eco-state aj-eco-state--<?php echo esc_attr( $card['state'] ); ?>"><?php echo esc_html( $card['state_label'] ); ?></span>
									</div>

									<div class="aj-eco-card__body">
										<h3><a href="<?php echo esc_url( $card['detail_url'] ); ?>"><?php echo esc_html( $offer['title'] ?? 'Offre Formule Économique' ); ?></a></h3>
										<?php if ( ! empty( $offer['short_description'] ) ) : ?>
											<p><?php echo esc_html( $offer['short_description'] ); ?></p>
										<?php endif; ?>

										<dl class="aj-eco-card__facts">
											<div>
												<dt>Destination</dt>
												<dd><?php echo esc_html( $offer['destination'] ?? 'À confirmer' ); ?></dd>
											</div>
											<div>
												<dt>Durée</dt>
												<dd><?php echo esc_html( $offer['duration_label'] ?? 'À confirmer' ); ?></dd>
											</div>
											<div>
												<dt>Départ de</dt>
												<dd><?php echo esc_html( $offer['departure_city'] ?? 'À confirmer' ); ?></dd>
											</div>
											<div>
												<dt>Date</dt>
												<dd><?php echo esc_html( $card['date_label'] ); ?></dd>
											</div>
										</dl>

										<div class="aj-eco-card__seats">
											<?php if ( null !== $card['fill_pct'] ) : ?>
												<span class="aj-eco-gauge" role="img" aria-label="<?php echo esc_attr( $card['seats_label'] ); ?>">
													<span class="aj-eco-gauge__fill" style="width:<?php echo esc_attr( (string) $card['fill_pct'] ); ?>%;"></span>
												</span>
											<?php endif; ?>
											<span class="aj-eco-card__seats-label"><?php echo esc_html( $card['seats_label'] ); ?></span>
										</div>
									</div>

									<div class="aj-eco-card__footer">
										<span class="aj-eco-card__price">
											<small>dès</small>
											<strong><?php echo esc_html( $card['price_label'] ); ?></strong>
										</span>
										<a href="<?php echo esc_url( $card['cta_url'] ); ?>" class="aj-eco-btn aj-eco-btn--<?php echo $card['expired'] ? 'outline' : 'primary'; ?> aj-eco-card__cta"><?php echo esc_html( $card['cta_label'] ); ?></a>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>
		</main>

		<?php if ( function_exists( 'ajth_render_site_footer' ) ) : ?>
			<?php ajth_render_site_footer( $settings ); ?>
		<?php endif; ?>
	</div>
</div>

<script>
// Les controles a choix ferme relancent la recherche sans passer par le bouton.
document.addEventListener('change', function (event) {
    const control = event.target.closest('[data-aj-eco-auto]');
    if (!control) {
        return;
    }
    const form = control.closest('[data-aj-eco-filters]');
    if (form) {
        form.submit();
    }
});

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
