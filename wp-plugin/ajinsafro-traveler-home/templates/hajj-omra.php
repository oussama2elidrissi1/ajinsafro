<?php
require_once __DIR__ . '/../includes/hajj-omra-locale.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( $classes ) {
		$classes[] = 'page-hajj-omra-ajinsafro';
		if ( function_exists( 'ajth_get_current_hajj_omra_package_slug' ) && ajth_get_current_hajj_omra_package_slug() ) {
			$classes[] = 'page-hajj-omra-detail';
		}

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
		return ajth_ho_t('Sur demande');
	}

	return number_format( (float) $amount, 0, ',', ' ' ) . ' ' . $currency;
};

$format_date = static function ( $date_value ) {
	$date_value = is_string( $date_value ) ? trim( $date_value ) : '';
	if ( '' === $date_value ) {
		return ajth_ho_t('A confirmer');
	}

	$timestamp = strtotime( $date_value );

	return $timestamp ? wp_date( 'd M Y', $timestamp ) : $date_value;
};

$status_badge = static function ( $package ) {
	$status    = (string) ( $package['status'] ?? '' );
	$remaining = (int) ( $package['remaining_places'] ?? 0 );

	if ( 'expired' === $status ) {
		return array(
			'label' => ajth_ho_t('Offre expiree'),
			'class' => 'is-expired',
		);
	}

	if ( 'full' === $status || $remaining <= 0 ) {
		return array(
			'label' => ajth_ho_t('Complet'),
			'class' => 'is-full',
		);
	}

	if ( $remaining > 0 && $remaining <= 8 ) {
		return array(
			'label' => ajth_ho_t('Places limitees'),
			'class' => 'is-limited',
		);
	}

	return array(
		'label' => ajth_ho_t('Disponible'),
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

	return ajth_ho_t('Date sur demande');
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
	$title = trim( (string) ( $package['title'] ?? ajth_ho_t('Hajj & Omra') ) );
	$url   = trim( (string) ( $package['detail_url'] ?? '' ) );

	return 'https://wa.me/212660683464?text=' . rawurlencode( sprintf( 'Bonjour Ajinsafro, je souhaite recevoir plus d informations sur l offre "%s" %s', $title, $url !== '' ? '(' . $url . ')' : '' ) );
};

$has_active_filters = '' !== $filter_type || '' !== $filter_city || $filter_budget > 0 || '' !== $filter_date;

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['ajth_hajj_omra_booking_request'] ) && $current_slug ) {
	$nonce = isset( $_POST['ajth_hajj_omra_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajth_hajj_omra_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'ajth_hajj_omra_booking_request' ) ) {
		$error_message = ajth_ho_t('Votre session a expire. Merci de renvoyer votre demande.');
	} else {
		$payload = array(
			'full_name'               => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
			'phone'                   => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'email'                   => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'adults'                  => max( 1, (int) ( $_POST['adults'] ?? 1 ) ),
			'children'                => max( 0, (int) ( $_POST['children'] ?? 0 ) ),
			'room_type'               => $posted_room_type,
			'formula_id'              => absint( $_POST['formula_id'] ?? 0 ) ?: '',
			'tariff_id'               => absint( $_POST['tariff_id'] ?? 0 ) ?: '',
			'departure_id'            => absint( $_POST['departure_id'] ?? 0 ) ?: '',
			'locale'                  => ajth_ho_locale(),
			'selected_departure_date' => $posted_departure,
			'message'                 => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
		);

		if ( '' === $payload['full_name'] || '' === $payload['phone'] || '' === $payload['email'] ) {
			$error_message = ajth_ho_t('Merci de renseigner votre nom, telephone et email.');
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
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( ajth_ho_t( 'Accueil' ) ); ?></a>
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
				<?php include AJTH_DIR . 'templates/partials/hajj-omra-detail.php'; ?>
			<?php else : ?>
				<section class="ajho-hero">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb ajho-breadcrumb--light" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( ajth_ho_t( 'Accueil' ) ); ?></a>
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
								<span><?php echo esc_html( ajth_ho_t( 'Type' ) ); ?></span>
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
										<a href="<?php echo esc_url( $detail_url ); ?>" class="ajho-card__media-link" aria-label="<?php echo esc_attr( $package['title'] ?? ajth_ho_t('Hajj & Omra') ); ?>">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $package['title'] ?? ajth_ho_t('Hajj & Omra') ); ?>" onerror="this.onerror=null;this.src='<?php echo esc_url( $fallback_image ); ?>';">
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
											<li><strong>Duree</strong><span><?php echo esc_html( $package['duration_label'] ?? ajth_ho_t('A confirmer') ); ?></span></li>
											<li><strong>Depart</strong><span><?php echo esc_html( $package['departure_city'] ?? ajth_ho_t('A confirmer') ); ?></span></li>
											<li><strong><?php echo esc_html( ajth_ho_t( 'Date' ) ); ?></strong><span><?php echo esc_html( $first_departure_label( $package ) ); ?></span></li>
											<li><strong><?php echo esc_html( ajth_ho_t( 'Makkah' ) ); ?></strong><span><?php echo esc_html( $package['makkah_hotel'] ?? ajth_ho_t('A confirmer') ); ?></span></li>
											<li><strong><?php echo esc_html( ajth_ho_t( 'Madinah' ) ); ?></strong><span><?php echo esc_html( $package['madinah_hotel'] ?? ajth_ho_t('A confirmer') ); ?></span></li>
											<li><strong><?php echo esc_html( ajth_ho_t( 'Places' ) ); ?></strong><span><?php echo esc_html( (string) ( $package['remaining_places'] ?? 0 ) ); ?> restantes</span></li>
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

<?php get_footer(); ?>
