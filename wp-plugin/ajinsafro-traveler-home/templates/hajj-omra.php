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

$page_url        = function_exists( 'ajth_get_hajj_omra_page_url' ) ? ajth_get_hajj_omra_page_url() : home_url( '/hajj-omra/' );
$packages        = function_exists( 'ajth_get_hajj_omra_packages' ) ? ajth_get_hajj_omra_packages() : array();
$current_slug    = function_exists( 'ajth_get_current_hajj_omra_package_slug' ) ? ajth_get_current_hajj_omra_package_slug() : '';
$current_package = $current_slug && function_exists( 'ajth_get_hajj_omra_package_by_slug' ) ? ajth_get_hajj_omra_package_by_slug( $current_slug ) : null;
$success_message = '';
$error_message   = '';
$filter_type     = isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '';
$filter_city     = isset( $_GET['departure_city'] ) ? sanitize_text_field( wp_unslash( $_GET['departure_city'] ) ) : '';
$filter_budget   = isset( $_GET['budget'] ) ? (int) $_GET['budget'] : 0;
$filter_date     = isset( $_GET['departure_date'] ) ? sanitize_text_field( wp_unslash( $_GET['departure_date'] ) ) : '';

if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['ajth_hajj_omra_booking_request'] ) && $current_slug ) {
	$nonce = isset( $_POST['ajth_hajj_omra_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['ajth_hajj_omra_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'ajth_hajj_omra_booking_request' ) ) {
		$error_message = 'Votre session a expire. Merci de renvoyer votre demande.';
	} else {
		$payload = array(
			'full_name'             => sanitize_text_field( wp_unslash( $_POST['full_name'] ?? '' ) ),
			'phone'                 => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'email'                 => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'adults'                => max( 1, (int) ( $_POST['adults'] ?? 1 ) ),
			'children'              => max( 0, (int) ( $_POST['children'] ?? 0 ) ),
			'room_type'             => sanitize_key( wp_unslash( $_POST['room_type'] ?? '' ) ),
			'selected_departure_date' => sanitize_text_field( wp_unslash( $_POST['selected_departure_date'] ?? '' ) ),
			'message'               => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ),
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
			if ( $filter_type !== '' && (string) ( $package['type'] ?? '' ) !== $filter_type ) {
				return false;
			}

			if ( $filter_city !== '' && 0 !== strcasecmp( (string) ( $package['departure_city'] ?? '' ), $filter_city ) ) {
				return false;
			}

			if ( $filter_budget > 0 && isset( $package['price_from'] ) && (float) $package['price_from'] > $filter_budget ) {
				return false;
			}

			if ( $filter_date !== '' ) {
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

$status_badge = static function ( $package ) {
	$status = (string) ( $package['status'] ?? '' );
	$remaining = (int) ( $package['remaining_places'] ?? 0 );

	if ( 'expired' === $status ) {
		return array( 'label' => 'Offre expiree', 'class' => 'is-expired' );
	}
	if ( 'full' === $status || $remaining <= 0 ) {
		return array( 'label' => 'Complet', 'class' => 'is-full' );
	}
	if ( $remaining > 0 && $remaining <= 6 ) {
		return array( 'label' => 'Places limitees', 'class' => 'is-limited' );
	}

	return array( 'label' => 'Disponible', 'class' => 'is-available' );
};
?>

<div class="ajho-shell">
	<section class="ajho-hero" style="<?php echo ! empty( $current_package['main_image_url'] ) ? 'background-image:url(' . esc_url( $current_package['main_image_url'] ) . ');' : ''; ?>">
		<div class="ajho-hero__overlay"></div>
		<div class="ajho-container ajho-hero__content">
			<?php if ( $current_package ) : ?>
				<a class="ajho-back-link" href="<?php echo esc_url( $page_url ); ?>">← Retour aux offres</a>
				<h1><?php echo esc_html( $current_package['title'] ?? 'Hajj & Omra avec Ajinsafro' ); ?></h1>
				<p><?php echo esc_html( $current_package['short_description'] ?? 'Offres Hajj & Omra dynamiques avec Ajinsafro.' ); ?></p>
			<?php else : ?>
				<h1>Hajj & Omra avec Ajinsafro</h1>
				<p>Des offres claires, accompagnees et mises a jour depuis notre base de donnees pour vous aider a choisir le bon depart.</p>
				<form method="get" action="<?php echo esc_url( $page_url ); ?>" class="ajho-filters">
					<select name="type">
						<option value="">Type</option>
						<?php foreach ( $type_options as $value => $label ) : ?>
							<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $filter_type, $value ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<select name="departure_city">
						<option value="">Ville de depart</option>
						<?php foreach ( $city_options as $label ) : ?>
							<option value="<?php echo esc_attr( $label ); ?>" <?php selected( $filter_city, $label ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<input type="number" name="budget" min="0" value="<?php echo esc_attr( $filter_budget > 0 ? $filter_budget : '' ); ?>" placeholder="Budget max (DH)">
					<input type="date" name="departure_date" value="<?php echo esc_attr( $filter_date ); ?>">
					<button type="submit">Filtrer</button>
				</form>
			<?php endif; ?>
		</div>
	</section>

	<div class="ajho-container ajho-content">
		<?php if ( $success_message ) : ?>
			<div class="ajho-alert is-success"><?php echo esc_html( $success_message ); ?></div>
		<?php endif; ?>
		<?php if ( $error_message ) : ?>
			<div class="ajho-alert is-error"><?php echo esc_html( $error_message ); ?></div>
		<?php endif; ?>

		<?php if ( $current_slug && ! $current_package ) : ?>
			<div class="ajho-empty">
				<h2>Offre introuvable</h2>
				<p>Cette offre Hajj & Omra n est plus disponible ou n a pas encore ete publiee.</p>
				<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--primary">Retour au catalogue</a>
			</div>
		<?php elseif ( $current_package ) : ?>
			<div class="ajho-detail">
				<div class="ajho-detail__main">
					<?php if ( ! empty( $current_package['gallery'] ) ) : ?>
						<div class="ajho-gallery">
							<?php foreach ( $current_package['gallery'] as $image_url ) : ?>
								<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $current_package['title'] ?? 'Hajj & Omra' ); ?>">
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<div class="ajho-panels">
						<div class="ajho-panel">
							<h2>Description</h2>
							<p><?php echo nl2br( esc_html( (string) ( $current_package['description'] ?? '' ) ) ); ?></p>
						</div>

						<div class="ajho-panel">
							<h2>Departs disponibles</h2>
							<div class="ajho-table-wrap">
								<table class="ajho-table">
									<thead><tr><th>Depart</th><th>Retour</th><th>Statut</th><th>Places</th><th>Prix</th></tr></thead>
									<tbody>
									<?php foreach ( (array) ( $current_package['departures'] ?? array() ) as $departure ) : ?>
										<tr>
											<td><?php echo esc_html( $departure['departure_date'] ?? '—' ); ?></td>
											<td><?php echo esc_html( $departure['return_date'] ?? '—' ); ?></td>
											<td><?php echo esc_html( $departure['status_label'] ?? '—' ); ?></td>
											<td><?php echo esc_html( (string) ( $departure['remaining_places'] ?? 0 ) ); ?></td>
											<td><?php echo isset( $departure['price_from'] ) && null !== $departure['price_from'] ? esc_html( number_format( (float) $departure['price_from'], 0, ',', ' ' ) . ' ' . ( $current_package['currency'] ?? 'DH' ) ) : 'Sur demande'; ?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>

						<div class="ajho-panel">
							<h2>Prix par chambre</h2>
							<div class="ajho-table-wrap">
								<table class="ajho-table">
									<thead><tr><th>Type</th><th>Prix</th><th>Stock</th></tr></thead>
									<tbody>
									<?php foreach ( (array) ( $current_package['room_prices'] ?? array() ) as $room_price ) : ?>
										<tr>
											<td><?php echo esc_html( $room_price['room_type_label'] ?? $room_price['room_type'] ?? '—' ); ?></td>
											<td><?php echo isset( $room_price['price'] ) ? esc_html( number_format( (float) $room_price['price'], 0, ',', ' ' ) . ' ' . ( $current_package['currency'] ?? 'DH' ) ) : '—'; ?></td>
											<td><?php echo esc_html( (string) ( $room_price['stock'] ?? 0 ) ); ?></td>
										</tr>
									<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>

						<div class="ajho-panel">
							<h2>Programme jour par jour</h2>
							<div class="ajho-program">
								<?php foreach ( (array) ( $current_package['program_days'] ?? array() ) as $program_day ) : ?>
									<article class="ajho-program__item">
										<div class="ajho-program__day">Jour <?php echo esc_html( (string) ( $program_day['day_number'] ?? '' ) ); ?></div>
										<div class="ajho-program__body">
											<h3><?php echo esc_html( $program_day['title'] ?? 'Etape' ); ?></h3>
											<?php if ( ! empty( $program_day['city'] ) ) : ?><p class="ajho-program__city"><?php echo esc_html( $program_day['city'] ); ?></p><?php endif; ?>
											<p><?php echo esc_html( $program_day['description'] ?? '' ); ?></p>
										</div>
									</article>
								<?php endforeach; ?>
							</div>
						</div>

						<div class="ajho-grid-two">
							<div class="ajho-panel">
								<h2>Inclus</h2>
								<ul class="ajho-list">
									<?php foreach ( (array) ( $current_package['included_items'] ?? array() ) as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
							<div class="ajho-panel">
								<h2>Non inclus</h2>
								<ul class="ajho-list">
									<?php foreach ( (array) ( $current_package['excluded_items'] ?? array() ) as $item ) : ?>
										<li><?php echo esc_html( $item ); ?></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</div>

						<div class="ajho-grid-two">
							<div class="ajho-panel">
								<h2>Documents necessaires</h2>
								<p><?php echo nl2br( esc_html( (string) ( $current_package['required_documents'] ?? '' ) ) ); ?></p>
							</div>
							<div class="ajho-panel">
								<h2>Conditions de reservation</h2>
								<p><?php echo nl2br( esc_html( (string) ( $current_package['booking_conditions'] ?? '' ) ) ); ?></p>
							</div>
						</div>
					</div>
				</div>

				<aside class="ajho-detail__side">
					<div class="ajho-summary-card">
						<span class="ajho-summary-card__type"><?php echo esc_html( $current_package['type_label'] ?? 'Hajj & Omra' ); ?></span>
						<h2><?php echo esc_html( $current_package['title'] ?? '' ); ?></h2>
						<div class="ajho-summary-card__price">
							<?php echo isset( $current_package['price_from'] ) && null !== $current_package['price_from'] ? esc_html( number_format( (float) $current_package['price_from'], 0, ',', ' ' ) . ' ' . ( $current_package['currency'] ?? 'DH' ) ) : 'Sur demande'; ?>
						</div>
						<ul class="ajho-summary-card__facts">
							<li><strong>Duree</strong><span><?php echo esc_html( $current_package['duration_label'] ?? '—' ); ?></span></li>
							<li><strong>Ville de depart</strong><span><?php echo esc_html( $current_package['departure_city'] ?? '—' ); ?></span></li>
							<li><strong>Places restantes</strong><span><?php echo esc_html( (string) ( $current_package['remaining_places'] ?? 0 ) ); ?></span></li>
							<li><strong>Hotel Makkah</strong><span><?php echo esc_html( $current_package['makkah_hotel'] ?? '—' ); ?></span></li>
							<li><strong>Hotel Madinah</strong><span><?php echo esc_html( $current_package['madinah_hotel'] ?? '—' ); ?></span></li>
						</ul>
					</div>

					<div class="ajho-summary-card" id="reservation-form">
						<h2>Demander une reservation</h2>
						<form method="post" action="<?php echo esc_url( $page_url . $current_slug . '/' ); ?>" class="ajho-request-form">
							<?php wp_nonce_field( 'ajth_hajj_omra_booking_request', 'ajth_hajj_omra_nonce' ); ?>
							<input type="hidden" name="ajth_hajj_omra_booking_request" value="1">
							<input type="text" name="full_name" placeholder="Nom complet" value="<?php echo esc_attr( wp_unslash( $_POST['full_name'] ?? '' ) ); ?>" required>
							<input type="tel" name="phone" placeholder="Telephone" value="<?php echo esc_attr( wp_unslash( $_POST['phone'] ?? '' ) ); ?>" required>
							<input type="email" name="email" placeholder="Email" value="<?php echo esc_attr( wp_unslash( $_POST['email'] ?? '' ) ); ?>" required>
							<div class="ajho-request-form__cols">
								<input type="number" min="1" name="adults" placeholder="Adultes" value="<?php echo esc_attr( wp_unslash( $_POST['adults'] ?? '1' ) ); ?>">
								<input type="number" min="0" name="children" placeholder="Enfants" value="<?php echo esc_attr( wp_unslash( $_POST['children'] ?? '0' ) ); ?>">
							</div>
							<select name="room_type">
								<option value="">Type de chambre souhaite</option>
								<?php foreach ( (array) ( $current_package['room_prices'] ?? array() ) as $room_price ) : ?>
									<option value="<?php echo esc_attr( $room_price['room_type'] ?? '' ); ?>"><?php echo esc_html( $room_price['room_type_label'] ?? $room_price['room_type'] ?? '' ); ?></option>
								<?php endforeach; ?>
							</select>
							<select name="selected_departure_date">
								<option value="">Date de depart choisie</option>
								<?php foreach ( (array) ( $current_package['departures'] ?? array() ) as $departure ) : ?>
									<option value="<?php echo esc_attr( $departure['departure_date'] ?? '' ); ?>"><?php echo esc_html( $departure['departure_date'] ?? '' ); ?></option>
								<?php endforeach; ?>
							</select>
							<textarea name="message" rows="4" placeholder="Message"><?php echo esc_textarea( wp_unslash( $_POST['message'] ?? '' ) ); ?></textarea>
							<button type="submit" class="ajho-btn ajho-btn--primary">Envoyer la demande</button>
						</form>
					</div>
				</aside>
			</div>
		<?php else : ?>
			<section class="ajho-stats">
				<div><strong><?php echo esc_html( (string) count( $filtered_packages ) ); ?></strong><span>offres visibles</span></div>
				<div><strong><?php echo esc_html( (string) count( array_filter( $filtered_packages, static fn( $item ) => ! empty( $item['is_featured'] ) ) ) ); ?></strong><span>mises en avant</span></div>
				<div><strong><?php echo esc_html( (string) count( $city_options ) ); ?></strong><span>villes de depart</span></div>
			</section>

			<?php if ( empty( $filtered_packages ) ) : ?>
				<div class="ajho-empty">
					<h2>Aucune offre ne correspond a vos filtres</h2>
					<p>Essayez une autre ville de depart, un autre type ou un budget plus large.</p>
					<a href="<?php echo esc_url( $page_url ); ?>" class="ajho-btn ajho-btn--primary">Reinitialiser les filtres</a>
				</div>
			<?php else : ?>
				<div class="ajho-grid">
					<?php foreach ( $filtered_packages as $package ) : ?>
						<?php $badge = $status_badge( $package ); ?>
						<article class="ajho-card">
							<div class="ajho-card__media">
								<?php if ( ! empty( $package['main_image_url'] ) ) : ?>
									<img src="<?php echo esc_url( $package['main_image_url'] ); ?>" alt="<?php echo esc_attr( $package['title'] ?? 'Hajj & Omra' ); ?>">
								<?php endif; ?>
								<span class="ajho-chip ajho-chip--type"><?php echo esc_html( $package['type_label'] ?? 'Offre' ); ?></span>
								<span class="ajho-chip <?php echo esc_attr( $badge['class'] ); ?>"><?php echo esc_html( $badge['label'] ); ?></span>
							</div>
							<div class="ajho-card__body">
								<h3><?php echo esc_html( $package['title'] ?? '' ); ?></h3>
								<p><?php echo esc_html( $package['short_description'] ?? '' ); ?></p>
								<ul class="ajho-card__facts">
									<li><?php echo esc_html( $package['duration_label'] ?? '—' ); ?></li>
									<li><?php echo esc_html( $package['departure_city'] ?? '—' ); ?></li>
									<li><?php echo esc_html( $package['departure_date'] ?? 'Date sur demande' ); ?></li>
									<?php if ( ! empty( $package['makkah_hotel'] ) ) : ?><li><?php echo esc_html( $package['makkah_hotel'] ); ?></li><?php endif; ?>
									<?php if ( ! empty( $package['madinah_hotel'] ) ) : ?><li><?php echo esc_html( $package['madinah_hotel'] ); ?></li><?php endif; ?>
								</ul>
							</div>
							<div class="ajho-card__footer">
								<div>
									<small>Prix a partir de</small>
									<strong><?php echo isset( $package['price_from'] ) && null !== $package['price_from'] ? esc_html( number_format( (float) $package['price_from'], 0, ',', ' ' ) . ' ' . ( $package['currency'] ?? 'DH' ) ) : 'Sur demande'; ?></strong>
									<span><?php echo esc_html( (string) ( $package['remaining_places'] ?? 0 ) ); ?> places restantes</span>
								</div>
								<div class="ajho-card__actions">
									<a href="<?php echo esc_url( $package['detail_url'] ?? $page_url ); ?>" class="ajho-btn ajho-btn--primary">Voir details</a>
									<a href="<?php echo esc_url( $package['request_url'] ?? $page_url ); ?>" class="ajho-btn ajho-btn--secondary">Demander reservation</a>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>

<?php get_footer(); ?>
