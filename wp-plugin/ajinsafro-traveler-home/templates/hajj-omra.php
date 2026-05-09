<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'page-hajj-omra-ajinsafro';

		return $classes;
	}
);

get_header();

$settings         = function_exists( 'ajth_get_settings' ) ? ajth_get_settings() : array();
$page_url         = function_exists( 'ajth_get_hajj_omra_page_url' ) ? ajth_get_hajj_omra_page_url() : home_url( '/hajj-omra/' );
$fallback_image   = function_exists( 'ajth_hajj_omra_default_image_url' ) ? ajth_hajj_omra_default_image_url() : trailingslashit( AJTH_URL ) . 'assets/images/fallback-hajj-omra.svg';
$packages         = function_exists( 'ajth_get_hajj_omra_packages' ) ? ajth_get_hajj_omra_packages() : array();
$current_slug     = function_exists( 'ajth_get_current_hajj_omra_package_slug' ) ? ajth_get_current_hajj_omra_package_slug() : '';
$current_package  = $current_slug && function_exists( 'ajth_get_hajj_omra_package_by_slug' ) ? ajth_get_hajj_omra_package_by_slug( $current_slug ) : null;
$success_message  = '';
$error_message    = '';
$filter_type      = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$filter_city      = isset( $_GET['departure_city'] ) ? sanitize_text_field( wp_unslash( $_GET['departure_city'] ) ) : '';
$filter_budget    = isset( $_GET['budget'] ) ? absint( $_GET['budget'] ) : 0;
$filter_date      = isset( $_GET['departure_date'] ) ? sanitize_text_field( wp_unslash( $_GET['departure_date'] ) ) : '';
$posted_room_type = sanitize_key( wp_unslash( $_POST['room_type'] ?? '' ) );
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

$status_badge = static function ( $package ) {
	$status    = (string) ( $package['status'] ?? '' );
	$remaining = (int) ( $package['remaining_places'] ?? 0 );

	if ( 'expired' === $status ) {
		return array(
			'label' => 'Offre expiree',
			'class' => 'is-expired',
		);
	}

	if ( 'full' === $status || $remaining <= 0 ) {
		return array(
			'label' => 'Complet',
			'class' => 'is-full',
		);
	}

	if ( $remaining > 0 && $remaining <= 8 ) {
		return array(
			'label' => 'Places limitees',
			'class' => 'is-limited',
		);
	}

	return array(
		'label' => 'Disponible',
		'class' => 'is-available',
	);
};

$first_departure_label = static function ( array $package ) use ( $format_date ) {
	if ( ! empty( $package['departure_date'] ) ) {
		return $format_date( $package['departure_date'] );
	}

	foreach ( (array) ( $package['departures'] ?? array() ) as $departure ) {
		if ( ! empty( $departure['departure_date'] ) ) {
			return $format_date( $departure['departure_date'] );
		}
	}

	return 'Date sur demande';
};

$find_next_departure = static function ( array $package ) {
	foreach ( (array) ( $package['departures'] ?? array() ) as $departure ) {
		if ( ! empty( $departure['departure_date'] ) ) {
			return $departure;
		}
	}

	return null;
};

$whatsapp_link = static function ( array $package ) {
	$title = trim( (string) ( $package['title'] ?? 'Hajj & Omra' ) );
	$url   = trim( (string) ( $package['detail_url'] ?? '' ) );

	return 'https://wa.me/212660683464?text=' . rawurlencode( sprintf( 'Bonjour Ajinsafro, je souhaite recevoir plus d informations sur l offre "%s" %s', $title, $url !== '' ? '(' . $url . ')' : '' ) );
};

$has_active_filters = '' !== $filter_type || '' !== $filter_city || $filter_budget > 0 || '' !== $filter_date;

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['ajth_hajj_omra_booking_request'] ) && $current_slug ) {
	$nonce = isset( $_POST['ajth_hajj_omra_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajth_hajj_omra_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'ajth_hajj_omra_booking_request' ) ) {
		$error_message = 'Votre session a expire. Merci de renvoyer votre demande.';
	} else {
		$payload = array(
			'full_name'               => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
			'phone'                   => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'email'                   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'adults'                  => max( 1, (int) ( $_POST['adults'] ?? 1 ) ),
			'children'                => max( 0, (int) ( $_POST['children'] ?? 0 ) ),
			'room_type'               => $posted_room_type,
			'selected_departure_date' => $posted_departure,
			'message'                 => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
		);

		if ( '' === $payload['full_name'] || '' === $payload['phone'] || '' === $payload['email'] ) {
			$error_message = 'Merci de renseigner votre nom, telephone et email.';
		} else {
			$result = ajth_submit_hajj_omra_booking_request( $current_slug, $payload );
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

foreach ( $packages as $package ) {
	if ( ! empty( $package['type'] ) && ! empty( $package['type_label'] ) ) {
		$type_options[ $package['type'] ] = $package['type_label'];
	}

	if ( ! empty( $package['departure_city'] ) ) {
		$city_options[ sanitize_title( (string) $package['departure_city'] ) ] = (string) $package['departure_city'];
	}
}

asort( $type_options );
asort( $city_options );

$filtered_packages = array_values(
	array_filter(
		$packages,
		static function ( $package ) use ( $filter_type, $filter_city, $filter_budget, $filter_date ) {
			if ( '' !== $filter_type && (string) ( $package['type'] ?? '' ) !== $filter_type ) {
				return false;
			}

			if ( '' !== $filter_city && 0 !== strcasecmp( (string) ( $package['departure_city'] ?? '' ), $filter_city ) ) {
				return false;
			}

			if ( $filter_budget > 0 && isset( $package['price_from'] ) && is_numeric( $package['price_from'] ) && (float) $package['price_from'] > $filter_budget ) {
				return false;
			}

			if ( '' !== $filter_date ) {
				$matches = false;
				foreach ( (array) ( $package['departures'] ?? array() ) as $departure ) {
					if ( ! empty( $departure['departure_date'] ) && (string) $departure['departure_date'] === $filter_date ) {
						$matches = true;
						break;
					}
				}

				if ( ! $matches && (string) ( $package['departure_date'] ?? '' ) !== $filter_date ) {
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
			<?php if ( $current_slug && ! $current_package ) : ?>
				<section class="ajho-hero ajho-hero--compact">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span>/</span>
							<a href="<?php echo esc_url( $page_url ); ?>">Hajj & Omra</a>
							<span>/</span>
							<span>Offre introuvable</span>
						</nav>
						<div class="ajho-hero__inner">
							<div class="ajho-hero__copy">
								<h1>Offre Hajj & Omra introuvable</h1>
								<p>Cette offre n est plus disponible ou n a pas encore ete publiee.</p>
								<div class="ajho-hero__actions">
									<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--primary">Retour au catalogue</a>
								</div>
							</div>
						</div>
					</div>
				</section>
			<?php elseif ( $current_package ) : ?>
				<?php
				$detail_status  = $status_badge( $current_package );
				$next_departure = $find_next_departure( $current_package );
				$gallery        = array_values( array_filter( (array) ( $current_package['gallery'] ?? array() ) ) );
				if ( empty( $gallery ) ) {
					$gallery[] = $fallback_image;
				}
				$hero_image  = ! empty( $gallery[0] ) ? $gallery[0] : $fallback_image;
				$share_url   = ! empty( $current_package['detail_url'] ) ? (string) $current_package['detail_url'] : ajth_get_hajj_omra_detail_url( $current_slug );
				$share_title = trim( (string) ( $current_package['title'] ?? 'Hajj & Omra avec Ajinsafro' ) );
				$thumb_gallery = array_slice( $gallery, 1, 4 );
				while ( count( $thumb_gallery ) < 4 ) {
					$thumb_gallery[] = $fallback_image;
				}
				$offer_highlights = array_values( array_filter( array_slice( (array) ( $current_package['included_items'] ?? array() ), 0, 4 ) ) );
				if ( empty( $offer_highlights ) ) {
					if ( ! empty( $current_package['transport_included'] ) ) {
						$offer_highlights[] = 'Transport inclus';
					}
					if ( ! empty( $current_package['visa_included'] ) ) {
						$offer_highlights[] = 'Visa inclus';
					}
					if ( ! empty( $current_package['guidance_included'] ) ) {
						$offer_highlights[] = 'Encadrement Ajinsafro';
					}
					if ( ! empty( $current_package['meal_plan_label'] ) ) {
						$offer_highlights[] = (string) $current_package['meal_plan_label'];
					}
				}
				$timeline_icon = static function ( array $program_day ) {
					$source = trim( (string) ( $program_day['city'] ?? $program_day['title'] ?? '' ) );
					if ( '' === $source ) {
						return 'AJ';
					}

					$words = preg_split( '/\s+/', $source ) ?: array();
					$abbr  = '';
					foreach ( $words as $word ) {
						$word = trim( (string) $word );
						if ( '' === $word ) {
							continue;
						}
						$abbr .= strtoupper( substr( $word, 0, 1 ) );
						if ( strlen( $abbr ) >= 2 ) {
							break;
						}
					}

					if ( '' === $abbr ) {
						$abbr = strtoupper( substr( $source, 0, 2 ) );
					}

					return substr( $abbr, 0, 2 );
				};
				$departure_status_badge = static function ( array $departure ) {
					$status    = (string) ( $departure['status'] ?? '' );
					$remaining = isset( $departure['remaining_places'] ) ? (int) $departure['remaining_places'] : (int) ( $departure['available_places'] ?? 0 );

					if ( 'expired' === $status ) {
						return array(
							'label' => 'Expiree',
							'class' => 'is-expired',
						);
					}

					if ( 'full' === $status || $remaining <= 0 ) {
						return array(
							'label' => 'Complet',
							'class' => 'is-full',
						);
					}

					if ( $remaining > 0 && $remaining <= 8 ) {
						return array(
							'label' => 'Limite',
							'class' => 'is-limited',
						);
					}

					return array(
						'label' => 'Publiee',
						'class' => 'is-available',
					);
				};
				?>
				<section class="ajho-hero ajho-hero--detail" style="background-image:linear-gradient(120deg, rgba(8, 46, 85, 0.76), rgba(11, 77, 141, 0.72)), url('<?php echo esc_url( $hero_image ); ?>');">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb ajho-breadcrumb--light" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span>/</span>
							<a href="<?php echo esc_url( $page_url ); ?>">Hajj & Omra</a>
							<span>/</span>
							<span><?php echo esc_html( $current_package['title'] ?? 'Offre' ); ?></span>
						</nav>

						<div class="ajho-hero__inner ajho-hero__inner--detail ajho-hero__inner--source">
							<div class="ajho-hero__copy">
								<div class="ajho-hero__badges">
									<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $current_package['type_label'] ?? 'Hajj & Omra' ); ?></span>
									<span class="ajho-chip <?php echo esc_attr( $detail_status['class'] ); ?>"><?php echo esc_html( $detail_status['label'] ); ?></span>
								</div>
								<h1><?php echo esc_html( $current_package['title'] ?? 'Hajj & Omra avec Ajinsafro' ); ?></h1>
								<p><?php echo esc_html( $current_package['short_description'] ?? 'Offre Hajj & Omra accompagnee par Ajinsafro, avec tarifs et disponibilites dynamiques.' ); ?></p>

								<div class="ajho-hero__facts">
									<div><strong>Depart</strong><span><?php echo esc_html( $current_package['departure_city'] ?? 'A confirmer' ); ?></span></div>
									<div><strong>Duree</strong><span><?php echo esc_html( $current_package['duration_label'] ?? 'A confirmer' ); ?></span></div>
									<div><strong>Places</strong><span><?php echo esc_html( (string) ( $current_package['remaining_places'] ?? 0 ) ); ?> restantes</span></div>
								</div>

								<div class="ajho-hero__actions">
									<a href="#reservation-form" class="ajho-btn ajho-btn--primary">Demander reservation</a>
									<a href="#programme-section" class="ajho-btn ajho-btn--secondary">Voir le programme</a>
								</div>
							</div>

							<div class="ajho-hero__aside">
								<div class="ajho-summary-card ajho-summary-card--hero ajho-price-card">
									<div class="ajho-price-card__badge">Meilleur prix</div>
									<div class="ajho-price-card__label">Prix a partir de</div>
									<div class="ajho-price-card__value"><?php echo esc_html( $format_price( $current_package['price_from'] ?? null, $current_package['currency'] ?? 'DH' ) ); ?></div>
									<ul class="ajho-price-list">
										<li><strong>Prochain depart</strong><span><?php echo esc_html( $first_departure_label( $current_package ) ); ?></span></li>
										<li><strong>Hotel Makkah</strong><span><?php echo esc_html( $current_package['makkah_hotel'] ?? 'A confirmer' ); ?></span></li>
										<li><strong>Hotel Madinah</strong><span><?php echo esc_html( $current_package['madinah_hotel'] ?? 'A confirmer' ); ?></span></li>
										<li><strong>Repas</strong><span><?php echo esc_html( $current_package['meal_plan_label'] ?? 'Selon offre' ); ?></span></li>
									</ul>
									<div class="ajho-small-actions">
										<a href="<?php echo esc_url( $whatsapp_link( $current_package ) ); ?>" class="ajho-small-btn ajho-small-btn--whatsapp" target="_blank" rel="noopener">WhatsApp</a>
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
									<img src="<?php echo esc_url( $gallery[0] ); ?>" alt="<?php echo esc_attr( $current_package['title'] ?? 'Hajj & Omra' ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
									<div class="ajho-gallery-count">1 / <?php echo esc_html( (string) count( $gallery ) ); ?></div>
								</div>
								<div class="ajho-thumb-grid">
									<?php foreach ( $thumb_gallery as $index => $image_url ) : ?>
										<figure class="ajho-thumb<?php echo $image_url === $fallback_image ? ' ajho-thumb--fallback' : ''; ?>">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( ( $current_package['title'] ?? 'Hajj & Omra' ) . ' photo ' . ( $index + 2 ) ); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
										</figure>
									<?php endforeach; ?>
								</div>
							</section>

							<div class="ajho-card">
								<div class="ajho-section-label">Presentation</div>
								<h2 class="ajho-card-title">Votre offre Hajj & Omra</h2>
								<p class="ajho-muted"><?php echo esc_html( (string) ( $current_package['description'] ?? $current_package['short_description'] ?? '' ) ); ?></p>

								<div class="ajho-info-grid-3">
									<div class="ajho-mini-box">
										<h4>Hotels</h4>
										<div class="ajho-kv-list">
											<div class="ajho-kv"><strong>Makkah</strong><span><?php echo esc_html( $current_package['makkah_hotel'] ?? 'A confirmer' ); ?></span></div>
											<div class="ajho-kv"><strong>Distance Haram</strong><span><?php echo esc_html( $current_package['makkah_haram_distance'] ?? 'A confirmer' ); ?></span></div>
											<div class="ajho-kv"><strong>Madinah</strong><span><?php echo esc_html( $current_package['madinah_hotel'] ?? 'A confirmer' ); ?></span></div>
											<div class="ajho-kv"><strong>Distance Haram</strong><span><?php echo esc_html( $current_package['madinah_haram_distance'] ?? 'A confirmer' ); ?></span></div>
										</div>
									</div>

									<div class="ajho-mini-box">
										<h4>Services inclus</h4>
										<ul class="ajho-check-list">
											<li><?php echo ! empty( $current_package['transport_included'] ) ? 'Transport inclus' : 'Transport non inclus'; ?></li>
											<li><?php echo ! empty( $current_package['visa_included'] ) ? 'Visa inclus' : 'Visa non inclus'; ?></li>
											<li><?php echo ! empty( $current_package['guidance_included'] ) ? 'Encadrement Ajinsafro' : 'Encadrement sur demande'; ?></li>
											<li><?php echo esc_html( 'Type chambre : ' . ( $current_package['room_type'] ?? 'Selon disponibilite' ) ); ?></li>
										</ul>
									</div>

									<div class="ajho-mini-box">
										<h4>Ce que comprend l offre</h4>
										<ul class="ajho-check-list">
											<?php foreach ( $offer_highlights as $highlight ) : ?>
												<li><?php echo esc_html( $highlight ); ?></li>
											<?php endforeach; ?>
										</ul>
									</div>
								</div>
							</div>

							<div class="ajho-card">
								<div class="ajho-section-label">Departs</div>
								<h2 class="ajho-card-title">Dates disponibles</h2>
								<div class="ajho-panel__head">
								</div>
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
											<?php foreach ( (array) ( $current_package['departures'] ?? array() ) as $departure ) : ?>
												<?php $departure_badge = $departure_status_badge( (array) $departure ); ?>
												<tr>
													<td><?php echo esc_html( $format_date( $departure['departure_date'] ?? '' ) ); ?></td>
													<td><?php echo esc_html( $format_date( $departure['return_date'] ?? '' ) ); ?></td>
													<td><span class="ajho-status-pill <?php echo esc_attr( $departure_badge['class'] ); ?>"><?php echo esc_html( $departure_badge['label'] ); ?></span></td>
													<td><?php echo esc_html( (string) ( $departure['remaining_places'] ?? 0 ) ); ?></td>
													<td class="ajho-table__price"><?php echo esc_html( $format_price( $departure['price_from'] ?? null, $current_package['currency'] ?? 'DH' ) ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>

							<div class="ajho-card">
								<div class="ajho-section-label">Tarifs</div>
								<h2 class="ajho-card-title">Prix par chambre</h2>
								<div class="ajho-table-wrap">
									<table class="ajho-table">
										<thead>
											<tr>
												<th>Type de chambre</th>
												<th>Prix</th>
												<th>Stock</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ( (array) ( $current_package['room_prices'] ?? array() ) as $room_price ) : ?>
												<tr>
													<td><?php echo esc_html( $room_price['room_type_label'] ?? $room_price['room_type'] ?? 'Chambre' ); ?></td>
													<td class="ajho-table__price"><?php echo esc_html( $format_price( $room_price['price'] ?? null, $current_package['currency'] ?? 'DH' ) ); ?></td>
													<td><?php echo esc_html( (string) ( $room_price['stock'] ?? 0 ) ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>

							<div class="ajho-card" id="programme-section">
								<div class="ajho-section-label">Programme</div>
								<h2 class="ajho-card-title">Jour par jour</h2>
								<div class="ajho-timeline">
									<?php if ( ! empty( $current_package['program_days'] ) ) : ?>
										<?php foreach ( (array) ( $current_package['program_days'] ?? array() ) as $program_day ) : ?>
											<article class="ajho-timeline-item">
												<div class="ajho-day-badge">Jour <?php echo esc_html( (string) ( $program_day['day_number'] ?? '' ) ); ?></div>
												<div class="ajho-timeline-content">
													<div class="ajho-program__top ajho-program__top--timeline">
														<div>
															<h3><?php echo esc_html( $program_day['title'] ?? 'Etape' ); ?></h3>
															<?php if ( ! empty( $program_day['city'] ) ) : ?>
																<small><?php echo esc_html( $program_day['city'] ); ?></small>
															<?php endif; ?>
														</div>
														<?php if ( ! empty( $program_day['image_url'] ) ) : ?>
															<img src="<?php echo esc_url( $program_day['image_url'] ); ?>" alt="<?php echo esc_attr( $program_day['title'] ?? 'Programme' ); ?>" loading="lazy" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
														<?php endif; ?>
													</div>
													<p><?php echo esc_html( $program_day['description'] ?? '' ); ?></p>
												</div>
												<div class="ajho-timeline-icon"><?php echo esc_html( $timeline_icon( $program_day ) ); ?></div>
											</article>
										<?php endforeach; ?>
									<?php else : ?>
										<div class="ajho-program__empty">Le programme detaille sera confirme par nos equipes Ajinsafro apres validation de votre depart.</div>
									<?php endif; ?>
								</div>
							</div>

							<div class="ajho-bottom-grid">
								<div class="ajho-card">
									<div class="ajho-section-label">Inclus</div>
									<h2 class="ajho-card-title ajho-card-title--small">Ce qui est inclus</h2>
									<ul class="ajho-check-list">
										<?php foreach ( (array) ( $current_package['included_items'] ?? array() ) as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="ajho-card">
									<div class="ajho-section-label">Exclusions</div>
									<h2 class="ajho-card-title ajho-card-title--small">Ce qui n est pas inclus</h2>
									<ul class="ajho-x-list">
										<?php foreach ( (array) ( $current_package['excluded_items'] ?? array() ) as $item ) : ?>
											<li><?php echo esc_html( $item ); ?></li>
										<?php endforeach; ?>
									</ul>
								</div>
								<div class="ajho-card">
									<div class="ajho-section-label">Documents</div>
									<h2 class="ajho-card-title ajho-card-title--small">Documents necessaires</h2>
									<p class="ajho-muted"><?php echo esc_html( (string) ( $current_package['required_documents'] ?? '' ) ); ?></p>
								</div>
								<div class="ajho-card">
									<div class="ajho-section-label">Conditions</div>
									<h2 class="ajho-card-title ajho-card-title--small">Conditions de reservation</h2>
									<p class="ajho-muted"><?php echo esc_html( (string) ( $current_package['booking_conditions'] ?? '' ) ); ?></p>
								</div>
							</div>
						</div>

						<aside class="ajho-sidebar">
							<div class="ajho-card ajho-offer-box ajho-offer-box--featured">
								<h3>Resume de l offre</h3>
								<h2><?php echo esc_html( $current_package['title'] ?? '' ); ?></h2>
								<div class="ajho-sidebar-price"><?php echo esc_html( $format_price( $current_package['price_from'] ?? null, $current_package['currency'] ?? 'DH' ) ); ?></div>
								<div class="ajho-kv"><strong>Type</strong><span><?php echo esc_html( $current_package['type_label'] ?? 'Hajj & Omra' ); ?></span></div>
								<div class="ajho-kv"><strong>Ville de depart</strong><span><?php echo esc_html( $current_package['departure_city'] ?? 'A confirmer' ); ?></span></div>
								<div class="ajho-kv"><strong>Date</strong><span><?php echo esc_html( $first_departure_label( $current_package ) ); ?></span></div>
								<div class="ajho-kv"><strong>Places restantes</strong><span><?php echo esc_html( (string) ( $current_package['remaining_places'] ?? 0 ) ); ?></span></div>
							</div>

							<div class="ajho-card ajho-form-card" id="reservation-form">
								<div class="ajho-section-label">Reservation</div>
								<h3>Demander une reservation</h3>
								<form method="post" action="<?php echo esc_url( $current_package['detail_url'] ?? $page_url ); ?>" class="ajho-form-grid">
									<?php wp_nonce_field( 'ajth_hajj_omra_booking_request', 'ajth_hajj_omra_nonce' ); ?>
									<input type="hidden" name="ajth_hajj_omra_booking_request" value="1">
									<div class="ajho-field">
										<label for="ajho-full-name">Nom complet</label>
										<input id="ajho-full-name" type="text" name="full_name" value="<?php echo esc_attr( wp_unslash( $_POST['full_name'] ?? '' ) ); ?>" required>
									</div>
									<div class="ajho-field">
										<label for="ajho-phone">Telephone</label>
										<input id="ajho-phone" type="tel" name="phone" value="<?php echo esc_attr( wp_unslash( $_POST['phone'] ?? '' ) ); ?>" required>
									</div>
									<div class="ajho-field">
										<label for="ajho-email">Email</label>
										<input id="ajho-email" type="email" name="email" value="<?php echo esc_attr( wp_unslash( $_POST['email'] ?? '' ) ); ?>" required>
									</div>
									<div class="ajho-field">
										<label for="ajho-departure">Depart selectionne</label>
										<select id="ajho-departure" name="selected_departure_date">
											<option value="">Choisir un depart</option>
											<?php foreach ( (array) ( $current_package['departures'] ?? array() ) as $departure ) : ?>
												<option value="<?php echo esc_attr( $departure['departure_date'] ?? '' ); ?>" <?php selected( $posted_departure, $departure['departure_date'] ?? '' ); ?>>
													<?php echo esc_html( $format_date( $departure['departure_date'] ?? '' ) ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="ajho-field">
										<label for="ajho-room-type">Type de chambre</label>
										<select id="ajho-room-type" name="room_type">
											<option value="">Choisir une chambre</option>
											<?php foreach ( (array) ( $current_package['room_prices'] ?? array() ) as $room_price ) : ?>
												<option value="<?php echo esc_attr( $room_price['room_type'] ?? '' ); ?>" <?php selected( $posted_room_type, $room_price['room_type'] ?? '' ); ?>>
													<?php echo esc_html( $room_price['room_type_label'] ?? $room_price['room_type'] ?? '' ); ?>
												</option>
											<?php endforeach; ?>
										</select>
									</div>
									<div class="ajho-two-cols">
										<div class="ajho-field">
											<label for="ajho-adults">Adultes</label>
											<input id="ajho-adults" type="number" min="1" name="adults" value="<?php echo esc_attr( wp_unslash( $_POST['adults'] ?? '1' ) ); ?>">
										</div>
										<div class="ajho-field">
											<label for="ajho-children">Enfants</label>
											<input id="ajho-children" type="number" min="0" name="children" value="<?php echo esc_attr( wp_unslash( $_POST['children'] ?? '0' ) ); ?>">
										</div>
									</div>
									<div class="ajho-field">
										<label for="ajho-message">Message</label>
										<textarea id="ajho-message" name="message" rows="5" placeholder="Vos demandes, preferences, questions..."><?php echo esc_textarea( wp_unslash( $_POST['message'] ?? '' ) ); ?></textarea>
									</div>
									<button type="submit" class="ajho-btn ajho-btn--primary ajho-btn--full">Envoyer la demande</button>
								</form>
								<div class="ajho-submit-note">Reponse rapide garantie par notre equipe.<br>Vos donnees sont 100% confidentielles.</div>
							</div>
						</aside>
					</section>
			<?php else : ?>
				<section class="ajho-hero">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb ajho-breadcrumb--light" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Accueil</a>
							<span>/</span>
							<span>Hajj & Omra</span>
						</nav>

						<div class="ajho-hero__inner">
							<div class="ajho-hero__copy">
								<span class="ajho-kicker ajho-kicker--light">Selection Ajinsafro</span>
								<h1>Hajj & Omra avec Ajinsafro</h1>
								<p>Retrouvez nos offres Omra, Hajj, Ramadan, Low Cost et Premium avec un affichage clair, des prix dynamiques et des departs mis a jour depuis notre base.</p>
							</div>
							<div class="ajho-hero__aside">
								<div class="ajho-hero-card">
									<strong><?php echo esc_html( (string) count( $packages ) ); ?></strong>
									<span>offres dynamiques</span>
									<p>Catalogue synchronise avec le back-office Ajinsafro.</p>
								</div>
							</div>
						</div>

						<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="ajho-search-panel">
							<label class="ajho-search-field">
								<span>Type</span>
								<select name="type">
									<option value="">Tous les types</option>
									<?php foreach ( $type_options as $value => $label ) : ?>
										<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ajho-search-field">
								<span>Ville de depart</span>
								<select name="departure_city">
									<option value="">Toutes les villes</option>
									<?php foreach ( $city_options as $label ) : ?>
										<option value="<?php echo esc_attr( $label ); ?>" <?php selected( $filter_city, $label ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ajho-search-field">
								<span>Budget max</span>
								<input type="number" name="budget" min="0" value="<?php echo esc_attr( $filter_budget > 0 ? $filter_budget : '' ); ?>" placeholder="20000">
							</label>
							<label class="ajho-search-field">
								<span>Date de depart</span>
								<input type="date" name="departure_date" value="<?php echo esc_attr( $filter_date ); ?>">
							</label>
							<div class="ajho-search-actions">
								<button type="submit" class="ajho-btn ajho-btn--primary">Filtrer</button>
								<?php if ( $has_active_filters ) : ?>
									<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--ghost">Reinitialiser</a>
								<?php endif; ?>
							</div>
						</form>
					</div>
				</section>

				<div class="ajho-container ajho-content">
					<section class="ajho-stats">
						<div>
							<strong><?php echo esc_html( (string) count( $filtered_packages ) ); ?></strong>
							<span>offres visibles</span>
						</div>
						<div>
							<strong><?php echo esc_html( (string) count( array_filter( $filtered_packages, static function ( $item ) { return ! empty( $item['is_featured'] ); } ) ) ); ?></strong>
							<span>offres a la une</span>
						</div>
						<div>
							<strong><?php echo esc_html( (string) count( $city_options ) ); ?></strong>
							<span>villes de depart</span>
						</div>
					</section>

					<section class="ajho-results-head">
						<div>
							<span class="ajho-kicker">Catalogue officiel</span>
							<h2>Offres Hajj & Omra disponibles</h2>
						</div>
						<p>Des offres Ajinsafro pensees pour une lecture rapide: image, hotels, depart, places restantes, prix et acces direct a la reservation.</p>
					</section>

					<?php if ( empty( $filtered_packages ) ) : ?>
						<div class="ajho-empty">
							<h2>Aucune offre ne correspond a vos filtres</h2>
							<p>Essayez une autre ville de depart, un autre budget ou reinitialisez vos criteres.</p>
							<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--primary">Reinitialiser les filtres</a>
						</div>
					<?php else : ?>
						<div class="ajho-grid">
							<?php foreach ( $filtered_packages as $package ) : ?>
								<?php
								$badge       = $status_badge( $package );
								$image_url   = ! empty( $package['main_image_url'] ) ? (string) $package['main_image_url'] : $fallback_image;
								$detail_url  = ! empty( $package['detail_url'] ) ? (string) $package['detail_url'] : $page_url;
								$request_url = ! empty( $package['request_url'] ) ? (string) $package['request_url'] : $detail_url . '#reservation-form';
								?>
								<article class="ajho-card">
									<div class="ajho-card__media">
										<a href="<?php echo esc_url( $detail_url ); ?>" class="ajho-card__media-link" aria-label="<?php echo esc_attr( $package['title'] ?? 'Hajj & Omra' ); ?>">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $package['title'] ?? 'Hajj & Omra' ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
										</a>
										<div class="ajho-card__badges">
											<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $package['type_label'] ?? 'Offre' ); ?></span>
											<span class="ajho-chip <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
										</div>
									</div>

									<div class="ajho-card__body">
										<h3><a href="<?php echo esc_url( $detail_url ); ?>"><?php echo esc_html( $package['title'] ?? '' ); ?></a></h3>
										<p><?php echo esc_html( $package['short_description'] ?? '' ); ?></p>
										<ul class="ajho-card__facts">
											<li><strong>Duree</strong><span><?php echo esc_html( $package['duration_label'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Depart</strong><span><?php echo esc_html( $package['departure_city'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Date</strong><span><?php echo esc_html( $first_departure_label( $package ) ); ?></span></li>
											<li><strong>Makkah</strong><span><?php echo esc_html( $package['makkah_hotel'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Madinah</strong><span><?php echo esc_html( $package['madinah_hotel'] ?? 'A confirmer' ); ?></span></li>
											<li><strong>Places</strong><span><?php echo esc_html( (string) ( $package['remaining_places'] ?? 0 ) ); ?> restantes</span></li>
										</ul>
									</div>

									<div class="ajho-card__footer">
										<div class="ajho-card__price">
											<small>Prix a partir de</small>
											<strong><?php echo esc_html( $format_price( $package['price_from'] ?? null, $package['currency'] ?? 'DH' ) ); ?></strong>
										</div>
										<div class="ajho-card__actions">
											<a href="<?php echo esc_url( $detail_url ); ?>" class="ajho-btn ajho-btn--primary">Voir details</a>
											<a href="<?php echo esc_url( $request_url ); ?>" class="ajho-btn ajho-btn--secondary">Demander reservation</a>
										</div>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</main>
	</div>
</div>

<?php if ( $current_package ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-ajho-share]').forEach(function (button) {
    button.addEventListener('click', function () {
      var url = button.getAttribute('data-share-url') || window.location.href;
      var title = button.getAttribute('data-share-title') || document.title;

      if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(function () {});
        return;
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(url).then(function () {
          button.textContent = 'Lien copie';
          window.setTimeout(function () {
            button.textContent = button.classList.contains('ajho-btn--ghost') ? 'Partager cette offre' : 'Partager';
          }, 1800);
        }).catch(function () {
          window.prompt('Copiez ce lien :', url);
        });
        return;
      }

      window.prompt('Copiez ce lien :', url);
    });
  });
});
</script>
<?php endif; ?>

<?php get_footer(); ?>
