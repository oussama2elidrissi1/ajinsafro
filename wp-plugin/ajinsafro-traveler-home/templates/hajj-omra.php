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
// Banniere image pilotee depuis l'admin ; sans elle, le bandeau d'origine reste.
$page_banner = function_exists('ajth_get_page_banner') ? ajth_get_page_banner('hajj-omra') : null;
$page_url         = function_exists( 'ajth_get_hajj_omra_page_url' ) ? ajth_get_hajj_omra_page_url() : home_url( '/hajj-omra/' );
if ( ajth_ho_locale() === 'ar' ) $page_url = add_query_arg( 'lang', 'ar', $page_url );
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
$filter_sort      = in_array( ( $_GET['sort'] ?? '' ), array( 'price', 'seats' ), true ) ? sanitize_key( wp_unslash( $_GET['sort'] ) ) : 'date';
$hide_full        = ! empty( $_GET['hide_full'] );
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
			'label' => ajth_ho_t('Offre expirée'),
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
			'label' => ajth_ho_t('Places limitées'),
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

// Numero general de l'agence : utilise quand l'offre n'en definit pas.
$default_whatsapp = '212660683464';

$whatsapp_link = static function ( array $package ) use ( $default_whatsapp ) {
	$title  = trim( (string) ( $package['title'] ?? ajth_ho_t('Hajj & Omra') ) );
	$url    = trim( (string) ( $package['detail_url'] ?? '' ) );
	$number = preg_replace( '/[^0-9]/', '', (string) ( $package['whatsapp_phone'] ?? '' ) );

	return 'https://wa.me/' . ( '' !== $number ? $number : $default_whatsapp ) . '?text=' . rawurlencode( sprintf( 'Bonjour Ajinsafro, je souhaite recevoir plus d informations sur l offre "%s" %s', $title, $url !== '' ? '(' . $url . ')' : '' ) );
};

$has_active_filters = '' !== $filter_type || '' !== $filter_city || $filter_budget > 0 || '' !== $filter_date || $hide_full || 'date' !== $filter_sort;

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

/**
 * Prochain depart utile d'une offre : sert au tri par date et au filtre « a partir du ».
 */
$next_departure_date = static function ( array $package ) {
	$dates = array();
	foreach ( (array) ( $package['departures'] ?? array() ) as $departure ) {
		if ( ! empty( $departure['departure_date'] ) ) {
			$dates[] = (string) $departure['departure_date'];
		}
	}
	if ( ! empty( $package['departure_date'] ) ) {
		$dates[] = (string) $package['departure_date'];
	}
	sort( $dates );

	return $dates[0] ?? '';
};

$matches_filters = static function ( $package ) use ( $filter_city, $filter_budget, $filter_date, $hide_full ) {
	if ( '' !== $filter_city && 0 !== strcasecmp( (string) ( $package['departure_city'] ?? '' ), $filter_city ) ) {
		return false;
	}

	if ( $filter_budget > 0 && isset( $package['price_from'] ) && is_numeric( $package['price_from'] ) && (float) $package['price_from'] > $filter_budget ) {
		return false;
	}

	// Date « a partir du » : l'offre passe des qu'un de ses departs tombe apres la date choisie.
	if ( '' !== $filter_date ) {
		$matches = false;
		foreach ( (array) ( $package['departures'] ?? array() ) as $departure ) {
			if ( ! empty( $departure['departure_date'] ) && (string) $departure['departure_date'] >= $filter_date ) {
				$matches = true;
				break;
			}
		}
		if ( ! $matches && (string) ( $package['departure_date'] ?? '' ) < $filter_date ) {
			return false;
		}
	}

	if ( $hide_full && (int) ( $package['remaining_places'] ?? 0 ) <= 0 ) {
		return false;
	}

	return true;
};

// Chaque pastille affiche son propre compte : le type est donc exclu du pre-filtrage.
$type_counts = array( '' => 0 );
foreach ( $packages as $package ) {
	if ( ! $matches_filters( $package ) ) {
		continue;
	}
	$type_counts['']++;
	$type = (string) ( $package['type'] ?? '' );
	if ( '' !== $type ) {
		$type_counts[ $type ] = ( $type_counts[ $type ] ?? 0 ) + 1;
	}
}

$filtered_packages = array_values(
	array_filter(
		$packages,
		static function ( $package ) use ( $filter_type, $matches_filters ) {
			if ( '' !== $filter_type && (string) ( $package['type'] ?? '' ) !== $filter_type ) {
				return false;
			}

			return $matches_filters( $package );
		}
	)
);

usort(
	$filtered_packages,
	static function ( $a, $b ) use ( $filter_sort, $next_departure_date ) {
		if ( 'price' === $filter_sort ) {
			$left  = is_numeric( $a['price_from'] ?? null ) ? (float) $a['price_from'] : PHP_INT_MAX;
			$right = is_numeric( $b['price_from'] ?? null ) ? (float) $b['price_from'] : PHP_INT_MAX;

			return $left <=> $right;
		}

		if ( 'seats' === $filter_sort ) {
			return (int) ( $b['remaining_places'] ?? 0 ) <=> (int) ( $a['remaining_places'] ?? 0 );
		}

		return strcmp( $next_departure_date( $a ) ?: '9999', $next_departure_date( $b ) ?: '9999' );
	}
);

$count_seats   = static fn ( array $list ) => array_sum( array_map( static fn ( $package ) => max( 0, (int) ( $package['remaining_places'] ?? 0 ) ), $list ) );
$visible_seats = $count_seats( $filtered_packages );
$total_seats   = $count_seats( $packages );
$base_filters  = array(
	'lang'           => ajth_ho_locale(),
	'departure_city' => $filter_city,
	'departure_date' => $filter_date,
	'budget'         => $filter_budget > 0 ? (string) $filter_budget : '',
	'sort'           => 'date' !== $filter_sort ? $filter_sort : '',
	'hide_full'      => $hide_full ? '1' : '',
);
// Curseur de budget : borne haute arrondie au-dessus de l'offre la plus chere.
$currency_label = 'DH';
$budget_ceiling = 0;
foreach ( $packages as $package ) {
	if ( is_numeric( $package['price_from'] ?? null ) ) {
		$budget_ceiling = max( $budget_ceiling, (float) $package['price_from'] );
	}
	if ( ! empty( $package['currency'] ) ) {
		$currency_label = (string) $package['currency'];
	}
}
$budget_max = max( 10000, (int) ( ceil( $budget_ceiling / 5000 ) * 5000 ) );

$filter_link   = static function ( array $args ) use ( $page_url, $base_filters ) {
	$args = array_merge( $base_filters, $args );

	return add_query_arg( array_filter( $args, static fn ( $value ) => '' !== $value && null !== $value ), $page_url );
};
?>

<div class="aj-home-wrap">
	<div id="aj-home" class="aj-home aj-hajj-omra-page">
		<?php if ( function_exists( 'ajth_render_site_header' ) ) : ?>
			<?php ajth_render_site_header( $settings ); ?>
		<?php endif; ?>

		<main class="ajho-page ajinsafro-page-container" lang="<?php echo esc_attr( ajth_ho_locale() ); ?>" dir="<?php echo ajth_ho_locale() === 'ar' ? 'rtl' : 'ltr'; ?>">
			<?php if ( $current_slug && ! $current_package ) : ?>
				<section class="ajho-hero ajho-hero--compact">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb" aria-label="Fil d Ariane">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( ajth_ho_t( 'Accueil' ) ); ?></a>
							<span>/</span>
							<a href="<?php echo esc_url( $page_url ); ?>"><?php echo esc_html( ajth_ho_t( 'Hajj & Omra' ) ); ?></a>
							<span>/</span>
							<span><?php echo esc_html( ajth_ho_t( 'Offre introuvable' ) ); ?></span>
						</nav>
						<div class="ajho-hero__inner">
							<div class="ajho-hero__copy">
								<h1><?php echo esc_html( ajth_ho_t( 'Offre Hajj & Omra introuvable' ) ); ?></h1>
								<p><?php echo esc_html( ajth_ho_t( 'Cette offre n est plus disponible ou n a pas encore ete publiee.' ) ); ?></p>
								<div class="ajho-hero__actions">
									<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--primary"><?php echo esc_html( ajth_ho_t( 'Retour au catalogue' ) ); ?></a>
								</div>
							</div>
						</div>
					</div>
				</section>
			<?php elseif ( $current_package ) : ?>
				<?php include AJTH_DIR . 'templates/partials/hajj-omra-detail.php'; ?>
			<?php else : ?>
				<section class="ajho-hero<?php echo $page_banner ? ' ajho-hero--banner' : ''; ?>">
					<div class="ajho-container">
						<nav class="ajho-breadcrumb ajho-breadcrumb--light" aria-label="<?php echo esc_attr( ajth_ho_t( 'Fil d’Ariane' ) ); ?>">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( ajth_ho_t( 'Accueil' ) ); ?></a>
							<span aria-hidden="true">/</span>
							<span aria-current="page"><?php echo esc_html( ajth_ho_t( 'Hajj & Omra' ) ); ?></span>
							<span class="ajho-hero__lang">
								<a href="<?php echo esc_url( add_query_arg( 'lang', 'fr', $page_url ) ); ?>" lang="fr" <?php echo ajth_ho_locale() === 'fr' ? 'aria-current="true"' : ''; ?>>Français</a>
								<a href="<?php echo esc_url( add_query_arg( 'lang', 'ar', $page_url ) ); ?>" lang="ar" dir="rtl" <?php echo ajth_ho_locale() === 'ar' ? 'aria-current="true"' : ''; ?>>العربية</a>
							</span>
						</nav>

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
						<div class="ajho-hero__inner">
							<div class="ajho-hero__copy">
								<h1><?php echo esc_html( ajth_ho_t( 'Hajj & Omra avec Ajinsafro' ) ); ?></h1>
								<p><?php echo esc_html( ajth_ho_t( 'Offres Omra, Hajj, Ramadan, Low Cost et Premium — hôtels, dates de départ et places restantes mis à jour en direct depuis notre base.' ) ); ?></p>
							</div>
							<dl class="ajho-hero__stats">
								<div>
									<dd><?php echo esc_html( (string) count( $packages ) ); ?></dd>
									<dt><?php echo esc_html( ajth_ho_t( 'offres au catalogue' ) ); ?></dt>
								</div>
								<div>
									<dd><?php echo esc_html( (string) $total_seats ); ?></dd>
									<dt><?php echo esc_html( ajth_ho_t( 'places disponibles' ) ); ?></dt>
								</div>
								<div>
									<dd><?php echo esc_html( (string) count( $city_options ) ); ?></dd>
									<dt><?php echo esc_html( ajth_ho_t( 'villes de départ' ) ); ?></dt>
								</div>
							</dl>
						</div>
						<?php endif; ?>
					</div>
				</section>

				<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="ajho-filters">
					<div class="ajho-container">
						<input type="hidden" name="lang" value="<?php echo esc_attr( ajth_ho_locale() ); ?>">
						<?php // Le type reste porte par les pastilles : on le conserve a la soumission. ?>
						<input type="hidden" name="type" value="<?php echo esc_attr( $filter_type ); ?>">
						<?php if ( $hide_full ) : ?><input type="hidden" name="hide_full" value="1"><?php endif; ?>

						<div class="ajho-filters__row">
							<label class="ajho-field">
								<span><?php echo esc_html( ajth_ho_t( 'Ville de départ' ) ); ?></span>
								<select name="departure_city">
									<option value=""><?php echo esc_html( ajth_ho_t( 'Toutes les villes' ) ); ?></option>
									<?php foreach ( $city_options as $label ) : ?>
										<option value="<?php echo esc_attr( $label ); ?>" <?php selected( $filter_city, $label ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</label>
							<label class="ajho-field">
								<span><?php echo esc_html( ajth_ho_t( 'Départ à partir du' ) ); ?></span>
								<input type="date" name="departure_date" value="<?php echo esc_attr( $filter_date ); ?>">
							</label>
							<label class="ajho-field ajho-field--range">
								<span>
									<?php echo esc_html( ajth_ho_t( 'Budget max' ) ); ?>
									<b data-budget-output><?php echo esc_html( $filter_budget > 0 ? number_format( $filter_budget, 0, ',', ' ' ) . ' ' . $currency_label : ajth_ho_t( 'Sans limite' ) ); ?></b>
								</span>
								<input type="range" name="budget" min="0" max="<?php echo esc_attr( $budget_max ); ?>" step="500"
									   value="<?php echo esc_attr( $filter_budget > 0 ? $filter_budget : 0 ); ?>"
									   data-budget-range data-currency="<?php echo esc_attr( $currency_label ); ?>"
									   data-unlimited="<?php echo esc_attr( ajth_ho_t( 'Sans limite' ) ); ?>">
							</label>
							<label class="ajho-field">
								<span><?php echo esc_html( ajth_ho_t( 'Trier par' ) ); ?></span>
								<select name="sort">
									<option value="date" <?php selected( $filter_sort, 'date' ); ?>><?php echo esc_html( ajth_ho_t( 'Date de départ' ) ); ?></option>
									<option value="price" <?php selected( $filter_sort, 'price' ); ?>><?php echo esc_html( ajth_ho_t( 'Prix croissant' ) ); ?></option>
									<option value="seats" <?php selected( $filter_sort, 'seats' ); ?>><?php echo esc_html( ajth_ho_t( 'Places restantes' ) ); ?></option>
								</select>
							</label>
							<button type="submit" class="ajho-btn ajho-btn--primary"><?php echo esc_html( ajth_ho_t( 'Filtrer' ) ); ?></button>
						</div>

						<div class="ajho-filters__row ajho-filters__row--tabs">
							<div class="ajho-tabs">
								<a class="ajho-tab <?php echo '' === $filter_type ? 'is-active' : ''; ?>" href="<?php echo esc_url( $filter_link( array( 'type' => '' ) ) ); ?>">
									<?php echo esc_html( ajth_ho_t( 'Toutes' ) ); ?><b><?php echo esc_html( (string) ( $type_counts[''] ?? 0 ) ); ?></b>
								</a>
								<?php foreach ( $type_options as $value => $label ) : ?>
									<a class="ajho-tab <?php echo $filter_type === $value ? 'is-active' : ''; ?>" href="<?php echo esc_url( $filter_link( array( 'type' => $value ) ) ); ?>">
										<?php echo esc_html( $label ); ?><b><?php echo esc_html( (string) ( $type_counts[ $value ] ?? 0 ) ); ?></b>
									</a>
								<?php endforeach; ?>
							</div>
							<a class="ajho-switch <?php echo $hide_full ? 'is-on' : ''; ?>" href="<?php echo esc_url( $filter_link( array( 'type' => $filter_type, 'hide_full' => $hide_full ? '' : '1' ) ) ); ?>"
							   role="switch" aria-checked="<?php echo $hide_full ? 'true' : 'false'; ?>">
								<span class="ajho-switch__track"><span class="ajho-switch__knob"></span></span>
								<?php echo esc_html( ajth_ho_t( 'Masquer les offres complètes' ) ); ?>
							</a>
						</div>
					</div>
				</form>
				<script>
				// Le curseur de budget affiche sa valeur; sans JS, le formulaire reste soumis tel quel.
				( function () {
					var range = document.querySelector( '[data-budget-range]' );
					var output = document.querySelector( '[data-budget-output]' );
					if ( ! range || ! output ) { return; }
					function refresh() {
						var value = Number( range.value );
						output.textContent = value > 0
							? value.toLocaleString( 'fr-FR' ).replace( /\u202f|\u00a0/g, ' ' ) + ' ' + range.dataset.currency
							: range.dataset.unlimited;
					}
					range.addEventListener( 'input', refresh );
					refresh();
				}() );
				</script>

				<div class="ajho-results">
					<div class="ajho-container">
						<div class="ajho-results__head">
							<h2><?php echo esc_html( ajth_ho_t( 'Offres disponibles' ) ); ?></h2>
							<p><b><?php echo esc_html( (string) count( $filtered_packages ) ); ?></b> <?php echo esc_html( ajth_ho_t( 'offre(s)' ) ); ?> · <?php echo esc_html( (string) $visible_seats ); ?> <?php echo esc_html( ajth_ho_t( 'place(s)' ) ); ?></p>
						</div>

						<?php if ( empty( $filtered_packages ) ) : ?>
							<div class="ajho-empty">
								<h3><?php echo esc_html( ajth_ho_t( 'Aucune offre pour ce filtre' ) ); ?></h3>
								<p><?php echo esc_html( ajth_ho_t( 'Choisissez un autre type ou affichez à nouveau les offres complètes.' ) ); ?></p>
								<?php if ( $has_active_filters ) : ?>
									<a href="<?php echo esc_url( add_query_arg( 'lang', ajth_ho_locale(), $page_url ) ); ?>" class="ajho-btn ajho-btn--primary"><?php echo esc_html( ajth_ho_t( 'Réinitialiser les filtres' ) ); ?></a>
								<?php endif; ?>
							</div>
						<?php else : ?>
							<div class="ajho-grid">
								<?php foreach ( $filtered_packages as $package ) : ?>
									<?php
									$badge       = $status_badge( $package );
									$image_url   = ! empty( $package['main_image_url'] ) ? (string) $package['main_image_url'] : $fallback_image;
									$detail_url  = ! empty( $package['detail_url'] ) ? (string) $package['detail_url'] : $page_url;
									$request_url = ! empty( $package['request_url'] ) ? (string) $package['request_url'] : $detail_url . '#reservation-form';
									$remaining   = max( 0, (int) ( $package['remaining_places'] ?? 0 ) );
									$total       = max( $remaining, (int) ( $package['total_places'] ?? 0 ) );
									$is_full     = $remaining <= 0;
									$is_last     = ! $is_full && $remaining <= 8;
									$fill        = $total > 0 ? max( 3, (int) round( ( $total - $remaining ) / $total * 100 ) ) : 100;
									?>
									<article class="ajho-card <?php echo $is_full ? 'is-sold-out' : ''; ?>">
										<a class="ajho-card__media" href="<?php echo esc_url( $detail_url ); ?>" tabindex="-1" aria-hidden="true">
											<img src="<?php echo esc_url( $image_url ); ?>" alt="" loading="lazy" onerror="this.onerror=null;this.closest('.ajho-card__media').classList.add('is-empty');">
											<span class="ajho-card__badges">
												<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $package['type_label'] ?? ajth_ho_t( 'Offre' ) ); ?></span>
												<span class="ajho-chip <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
											</span>
										</a>

										<div class="ajho-card__body">
											<?php $title_ar = ajth_ho_is_arabic( $package['title'] ?? '' ); $desc_ar = ajth_ho_is_arabic( $package['short_description'] ?? '' ); ?>
											<h3><a href="<?php echo esc_url( $detail_url ); ?>"<?php echo $title_ar ? ' class="ajho-ar" dir="rtl" lang="ar"' : ''; ?>><?php echo esc_html( $package['title'] ?? '' ); ?></a></h3>
											<p<?php echo $desc_ar ? ' class="ajho-ar" dir="rtl" lang="ar"' : ''; ?>><?php echo esc_html( $package['short_description'] ?? '' ); ?></p>

											<div class="ajho-card__facts">
												<div>
													<span><?php echo esc_html( ajth_ho_t( 'Durée' ) ); ?></span>
													<b><?php echo esc_html( $package['duration_label'] ?? ajth_ho_t( 'À confirmer' ) ); ?></b>
												</div>
												<div>
													<span><?php echo esc_html( ajth_ho_t( 'Départ' ) ); ?></span>
													<b><?php echo esc_html( $package['departure_city'] ?? ajth_ho_t( 'À confirmer' ) ); ?></b>
												</div>
												<div>
													<span><?php echo esc_html( ajth_ho_t( 'Date' ) ); ?></span>
													<b class="ajho-mono"><?php echo esc_html( $first_departure_label( $package ) ); ?></b>
												</div>
											</div>

											<dl class="ajho-card__hotels">
												<div>
													<dt><?php echo esc_html( ajth_ho_t( 'Makkah' ) ); ?></dt>
													<dd<?php echo ajth_ho_is_arabic( $package['makkah_hotel'] ?? '' ) ? ' class="ajho-ar" dir="rtl" lang="ar"' : ''; ?>><?php echo esc_html( $package['makkah_hotel'] ?? ajth_ho_t( 'À confirmer' ) ); ?></dd>
												</div>
												<div>
													<dt><?php echo esc_html( ajth_ho_t( 'Madinah' ) ); ?></dt>
													<dd<?php echo ajth_ho_is_arabic( $package['madinah_hotel'] ?? '' ) ? ' class="ajho-ar" dir="rtl" lang="ar"' : ''; ?>><?php echo esc_html( $package['madinah_hotel'] ?? ajth_ho_t( 'À confirmer' ) ); ?></dd>
												</div>
											</dl>

											<div class="ajho-card__meter">
												<div class="ajho-meter"><span style="width: <?php echo esc_attr( $fill ); ?>%"></span></div>
												<div class="ajho-card__seats">
													<span class="<?php echo $is_full ? 'is-full' : ( $is_last ? 'is-limited' : 'is-available' ); ?>">
														<?php echo $is_full ? esc_html( ajth_ho_t( 'Complet — liste d’attente' ) ) : esc_html( $remaining . ' ' . ajth_ho_t( 'places restantes' ) ); ?>
													</span>
													<?php if ( $total > 0 ) : ?>
														<span><?php echo esc_html( $total . ' ' . ajth_ho_t( 'places au total' ) ); ?></span>
													<?php endif; ?>
												</div>
											</div>

											<div class="ajho-card__footer">
												<div class="ajho-card__price">
													<small><?php echo esc_html( ajth_ho_t( 'dès' ) ); ?></small>
													<strong><?php echo esc_html( $format_price( $package['price_from'] ?? null, $package['currency'] ?? 'DH' ) ); ?></strong>
												</div>
												<div class="ajho-card__actions">
													<a href="<?php echo esc_url( $detail_url ); ?>" class="ajho-btn ajho-btn--ghost"><?php echo esc_html( ajth_ho_t( 'Détails' ) ); ?></a>
													<a href="<?php echo esc_url( $is_full ? $whatsapp_link( $package ) : $request_url ); ?>" class="ajho-btn ajho-btn--primary" <?php echo $is_full ? 'target="_blank" rel="noopener"' : ''; ?>>
														<?php echo esc_html( $is_full ? ajth_ho_t( 'Être alerté' ) : ajth_ho_t( 'Réserver' ) ); ?>
													</a>
												</div>
											</div>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<aside class="ajho-cta">
							<div>
								<h2><?php echo esc_html( ajth_ho_t( 'Vous ne trouvez pas la date qui vous convient ?' ) ); ?></h2>
								<p><?php echo esc_html( ajth_ho_t( 'Nos conseillers construisent votre programme Omra sur mesure : hôtel, durée, ville de départ et budget.' ) ); ?></p>
							</div>
							<div class="ajho-cta__actions">
								<a href="<?php echo esc_url( $whatsapp_link( array( 'title' => ajth_ho_t( 'Demande sur mesure' ), 'detail_url' => $page_url ) ) ); ?>" target="_blank" rel="noopener" class="ajho-btn ajho-btn--gold"><?php echo esc_html( ajth_ho_t( 'Demande sur mesure' ) ); ?></a>
								<a href="tel:+212539323874" class="ajho-btn ajho-btn--outline-light"><?php echo esc_html( ajth_ho_t( 'Nous appeler' ) ); ?></a>
							</div>
						</aside>
					</div>
				</div>
			<?php endif; ?>
		</main>
	</div>
</div>

<?php get_footer(); ?>
