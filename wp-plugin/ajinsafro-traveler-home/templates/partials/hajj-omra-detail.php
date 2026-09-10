<?php
/**
 * Public offer detail, adapted from Omra Ramadan - Detail.dc.html.
 * Receives the API payload and formatting helpers from hajj-omra.php.
 */
require_once __DIR__ . '/../../includes/hajj-omra-locale.php';
require_once __DIR__ . '/../../includes/hajj-omra-commercial-table.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_package = ajth_ho_localize_package( $current_package );
$locale = ajth_ho_locale();
if ( $locale === 'ar' ) {
    $format_date = static function ( $date ) {
        $timestamp = $date ? strtotime( $date ) : false;
        return $timestamp ? date( 'd/m/Y', $timestamp ) : ajth_ho_t('À confirmer');
    };
}
$formulas = (array) ( $current_package['formulas'] ?? array() );
$has_formulas = ! empty( $current_package['has_formulas'] ) || ! empty( $formulas );
$selected_formula = $formulas[0] ?? null;
foreach ( $formulas as $formula ) {
    if ( (string) $formula['id'] === (string) ( $_POST['formula_id'] ?? '' ) ) $selected_formula = $formula;
}
$detail_status = $status_badge( $current_package );
$currency      = $current_package['currency'] ?? 'DH';
$offer_title   = $current_package['title'] ?? ajth_ho_t('Hajj & Omra avec Ajinsafro');
$share_url     = $current_package['detail_url'] ?? ajth_get_hajj_omra_detail_url( $current_slug );
$gallery       = array_values( array_unique( array_filter( (array) ( $current_package['gallery'] ?? array() ) ) ) );
$hero_image    = $gallery[0] ?? $fallback_image;
$departures    = array_values( (array) ( $current_package['departures'] ?? array() ) );
usort( $departures, static function ( $a, $b ) {
	return strcmp( $a['departure_date'] ?? '', $b['departure_date'] ?? '' );
} );
$today = current_time( 'Y-m-d' );
$can_select_departure = static function ( $departure ) use ( $today ) {
	return 'published' === ( $departure['status'] ?? '' )
		&& (int) ( $departure['remaining_places'] ?? 0 ) > 0
		&& ! empty( $departure['departure_date'] )
		&& $departure['departure_date'] >= $today;
};
$selectable_departures = array_values( array_filter( $departures, $can_select_departure ) );
$selected_departure = $selectable_departures[0] ?? null;
foreach ( $selectable_departures as $departure ) {
	if ( $posted_departure === ( $departure['departure_date'] ?? '' ) ) {
		$selected_departure = $departure;
		break;
	}
}
if ( ! empty( $selected_formula['departure_id'] ) ) {
    $selected_departure = null;
    foreach ( $selectable_departures as $candidate ) if ( (string) ( $candidate['id'] ?? '' ) === (string) $selected_formula['departure_id'] ) $selected_departure = $candidate;
}
$selected_date  = $selected_departure['departure_date'] ?? '';
$selected_price = $selected_departure['price_from'] ?? $current_package['price_from'] ?? null;
$selected_seats = $selected_departure['remaining_places'] ?? $current_package['remaining_places'] ?? 0;
$rooms          = array_values( (array) ( $current_package['room_prices'] ?? array() ) );
if ( $has_formulas ) $rooms = array_values( (array) ( $selected_formula['prices'] ?? array() ) );
$selected_room  = null;
$best_room      = null;
$max_stock      = 1;
foreach ( $rooms as $room ) {
	$max_stock = max( $max_stock, (int) ( $room['stock'] ?? 0 ) );
	if ( (int) ( $room['stock'] ?? 0 ) > 0 && isset( $room['price'] ) && is_numeric( $room['price'] ) ) {
		if ( null === $best_room || (float) $room['price'] < (float) $best_room['price'] ) {
			$best_room = $room;
		}
		if ( $posted_room_type === ( $room['room_type'] ?? '' ) ) {
			$selected_room = $room;
		}
	}
}
$selected_room = $selected_room ?? $best_room;
$hero_best_room = $best_room;
if ( $has_formulas ) {
    $hero_best_room = null;
    foreach ( $formulas as $formula ) foreach ( (array) ( $formula['prices'] ?? array() ) as $price ) {
        if ( null === $hero_best_room || $price['price'] < $hero_best_room['price'] ) $hero_best_room = $price;
    }
}
if ( $has_formulas ) $selected_price = $current_package['price_from'] ?? null;
$selected_room_type = $selected_room['room_type'] ?? '';
$included_items = (array) ( $current_package['included_items'] ?? array() );
$excluded_items = (array) ( $current_package['excluded_items'] ?? array() );
$services = array();
foreach ( array( 'transport_included' => ajth_ho_t('Transport inclus'), 'visa_included' => ajth_ho_t('Visa inclus'), 'guidance_included' => ajth_ho_t('Encadrement Ajinsafro') ) as $field => $label ) {
	if ( ! empty( $current_package[ $field ] ) ) {
		$services[] = $label;
	}
}
if ( ! empty( $current_package['room_type'] ) ) {
	$services[] = 'Type de chambre : ' . $current_package['room_type'];
}
$section_links = array( 'presentation' => ajth_ho_t('Présentation'), 'departs' => ajth_ho_t('Départs'), 'tarifs' => ajth_ho_t('Tarifs'), 'programme-section' => ajth_ho_t('Programme'), 'inclus' => ajth_ho_t('Inclus / Exclus'), 'conditions' => ajth_ho_t('Conditions') );
if ( $has_formulas ) $section_links['tarifs'] = ajth_ho_t('Formules & hébergements');
$adults = max( 1, min( 20, (int) ( $_POST['adults'] ?? 1 ) ) );
$children = max( 0, min( 20, (int) ( $_POST['children'] ?? 0 ) ) );
$child_price = $current_package['child_price'] ?? null;
$estimate_rate = $selected_room['price'] ?? $selected_price;
$estimate = is_numeric( $estimate_rate ) ? (float) $estimate_rate * $adults : null;
if ( null !== $estimate && is_numeric( $child_price ) ) {
	$estimate += (float) $child_price * $children;
}
?>
<article class="ajod" lang="<?php echo esc_attr( $locale ); ?>" dir="<?php echo $locale === 'ar' ? 'rtl' : 'ltr'; ?>" data-ajod data-currency="<?php echo esc_attr( $currency ); ?>" data-child-price="<?php echo esc_attr( $child_price ?? '' ); ?>" data-base-price="<?php echo esc_attr( $current_package['price_from'] ?? '' ); ?>">
    <script type="application/json" data-formulas-json><?php echo json_encode( $formulas, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
    <script type="application/json" data-translations-json><?php echo json_encode( $locale === 'ar' ? ajth_ho_translations() : array(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?></script>
	<div class="ajod-container">
        <nav class="ajod-language" aria-label="Langue / اللغة"><a href="<?php echo esc_url( strtok( $share_url, '?' ) . '?lang=fr' ); ?>" lang="fr" <?php echo $locale === 'fr' ? 'aria-current="true"' : ''; ?>>Français</a><a href="<?php echo esc_url( strtok( $share_url, '?' ) . '?lang=ar' ); ?>" lang="ar" <?php echo $locale === 'ar' ? 'aria-current="true"' : ''; ?>>العربية</a></nav>
		<nav class="ajod-breadcrumb" aria-label="<?php echo esc_attr( ajth_ho_t( 'Fil d’Ariane' ) ); ?>">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( ajth_ho_t( 'Accueil' ) ); ?></a><span aria-hidden="true">/</span>
			<a href="<?php echo esc_url( $page_url ); ?>"><?php echo esc_html( ajth_ho_t( 'Hajj & Omra' ) ); ?></a><span aria-hidden="true">/</span>
			<span aria-current="page"><?php echo esc_html( $offer_title ); ?></span>
		</nav>
		<section class="ajod-hero" aria-labelledby="ajod-title">
			<div class="ajod-hero__visual">
				<img class="ajod-hero__image" src="<?php echo esc_url( $hero_image ); ?>" alt="" fetchpriority="high" data-fallback="<?php echo esc_url( $fallback_image ); ?>">
				<div class="ajod-hero__copy">
					<div class="ajod-badges"><span class="ajod-badge ajod-badge--accent"><?php echo esc_html( $current_package['type_label'] ?? ajth_ho_t('Hajj & Omra') ); ?></span><span class="ajod-badge <?php echo esc_attr( $detail_status['class'] ); ?>"><?php echo esc_html( ajth_ho_t( $detail_status['label'] ) ); ?></span></div>
					<h1 id="ajod-title"><?php echo esc_html( $offer_title ); ?></h1>
					<p><?php echo esc_html( $current_package['short_description'] ?? '' ); ?></p>
					<dl class="ajod-hero__facts">
						<div><dt><?php echo esc_html( ajth_ho_t( 'Départ' ) ); ?></dt><dd><?php echo esc_html( $current_package['departure_city'] ?? ajth_ho_t('À confirmer') ); ?></dd></div>
						<div><dt><?php echo esc_html( ajth_ho_t( 'Durée' ) ); ?></dt><dd><?php echo esc_html( $current_package['duration_label'] ?? ajth_ho_t('À confirmer') ); ?></dd></div>
						<div><dt><?php echo esc_html( ajth_ho_t( 'Places' ) ); ?></dt><dd><?php echo esc_html( (string) ( $current_package['remaining_places'] ?? 0 ) ); ?> <?php echo esc_html( ajth_ho_t('restantes') ); ?></dd></div>
					</dl>
				</div>
			</div>
			<div class="ajod-price-card">
				<div class="ajod-price-card__top">
					<div><div class="ajod-eyebrow"><?php echo esc_html( ajth_ho_t( 'Prix à partir de' ) ); ?></div><div class="ajod-price"><?php echo esc_html( $format_price( $current_package['price_from'] ?? null, $currency ) ); ?></div><p class="ajod-price-note"><?php echo esc_html( ajth_ho_t( 'par personne' ) ); ?><?php if ( $hero_best_room ) : ?> · <?php echo esc_html( ajth_ho_localized( $hero_best_room, 'room_type_label' ) ?: $hero_best_room['room_type'] ); ?><?php endif; ?></p></div>
					<?php if ( $hero_best_room ) : ?><span class="ajod-tag ajod-tag--warm"><?php echo esc_html( ajth_ho_t( 'Meilleur prix' ) ); ?></span><?php endif; ?>
				</div>
				<dl class="ajod-kv">
					<div><dt><?php echo esc_html( ajth_ho_t( 'Prochain départ' ) ); ?></dt><dd class="ajod-mono"><?php echo esc_html( $selected_departure ? $format_date( $selectable_departures[0]['departure_date'] ) : ajth_ho_t('Date sur demande') ); ?></dd></div>
					<div><dt><?php echo esc_html( ajth_ho_t( 'Hôtel Makkah' ) ); ?></dt><dd><?php echo esc_html( $current_package['makkah_hotel'] ?? ajth_ho_t('À confirmer') ); ?></dd></div>
					<div><dt><?php echo esc_html( ajth_ho_t( 'Hôtel Madinah' ) ); ?></dt><dd><?php echo esc_html( $current_package['madinah_hotel'] ?? ajth_ho_t('À confirmer') ); ?></dd></div>
					<div><dt><?php echo esc_html( ajth_ho_t( 'Repas' ) ); ?></dt><dd><?php echo esc_html( $current_package['meal_plan_label'] ?? ajth_ho_t('Selon offre') ); ?></dd></div>
				</dl>
				<div class="ajod-price-card__actions">
					<a class="ajod-button ajod-button--primary" href="#reservation-form"><?php echo esc_html( ajth_ho_t( 'Demander une réservation' ) ); ?></a>
					<div class="ajod-action-pair"><a class="ajod-button ajod-button--whatsapp" href="<?php echo esc_url( $whatsapp_link( $current_package ) ); ?>" target="_blank" rel="noopener">WhatsApp</a><button class="ajod-button ajod-button--outline" type="button" data-ajho-share data-share-url="<?php echo esc_url( $share_url ); ?>" data-share-title="<?php echo esc_attr( $offer_title ); ?>"><?php echo esc_html( ajth_ho_t( 'Partager' ) ); ?></button></div>
					<span class="ajod-sr-only" role="status" data-share-status></span>
				</div>
			</div>
		</section>
		<section class="ajod-gallery<?php echo count( $gallery ) < 2 ? ' ajod-gallery--single' : ''; ?>" aria-label="<?php echo esc_attr( ajth_ho_t( 'Photos de l’offre' ) ); ?>">
			<a class="ajod-gallery__main" href="<?php echo esc_url( $hero_image ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ajth_ho_t( 'Agrandir la photo principale' ) ); ?>">
				<img src="<?php echo esc_url( $hero_image ); ?>" alt="<?php echo esc_attr( $offer_title ); ?>" data-gallery-main data-fallback="<?php echo esc_url( $fallback_image ); ?>">
				<span class="ajod-gallery__count" data-gallery-count>1 / <?php echo esc_html( (string) max( 1, count( $gallery ) ) ); ?></span>
			</a>
			<?php if ( count( $gallery ) > 1 ) : ?>
				<div class="ajod-gallery__thumbs" aria-label="<?php echo esc_attr( ajth_ho_t( 'Choisir une photo' ) ); ?>">
					<?php foreach ( $gallery as $index => $image_url ) : ?>
						<button type="button" class="ajod-gallery__thumb" data-gallery-image="<?php echo esc_url( $image_url ); ?>" data-gallery-index="<?php echo esc_attr( $index + 1 ); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>" aria-label="Afficher la photo <?php echo esc_attr( $index + 1 ); ?>">
							<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $offer_title . ' — photo ' . ( $index + 1 ) ); ?>" loading="lazy" data-fallback="<?php echo esc_url( $fallback_image ); ?>">
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
	</div>
	<nav class="ajod-tabs" aria-label="<?php echo esc_attr( ajth_ho_t( 'Sections de l’offre' ) ); ?>"><div class="ajod-container ajod-tabs__inner">
		<?php foreach ( $section_links as $id => $label ) : ?><a href="#<?php echo esc_attr( $id ); ?>"<?php echo 'presentation' === $id ? ' aria-current="location"' : ''; ?>><?php echo esc_html( $label ); ?></a><?php endforeach; ?>
	</div></nav>
	<div class="ajod-container ajod-content">
		<div class="ajod-content__main">
			<section id="presentation" class="ajod-card">
				<div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Présentation' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Votre offre Hajj & Omra' ) ); ?></h2>
				<p class="ajod-prose"><?php echo esc_html( $current_package['description'] ?? $current_package['short_description'] ?? '' ); ?></p>
				<div class="ajod-info-grid">
					<div class="ajod-info-box"><h3><?php echo esc_html( ajth_ho_t( 'Hôtels' ) ); ?></h3><dl class="ajod-kv">
						<div><dt><?php echo esc_html( ajth_ho_t( 'Makkah' ) ); ?></dt><dd><?php echo esc_html( $current_package['makkah_hotel'] ?? ajth_ho_t('À confirmer') ); ?></dd></div><div><dt><?php echo esc_html( ajth_ho_t( 'Distance Haram' ) ); ?></dt><dd class="ajod-mono"><?php echo esc_html( $current_package['makkah_haram_distance'] ?? ajth_ho_t('À confirmer') ); ?></dd></div>
						<div><dt><?php echo esc_html( ajth_ho_t( 'Madinah' ) ); ?></dt><dd><?php echo esc_html( $current_package['madinah_hotel'] ?? ajth_ho_t('À confirmer') ); ?></dd></div><div><dt><?php echo esc_html( ajth_ho_t( 'Distance Haram' ) ); ?></dt><dd class="ajod-mono"><?php echo esc_html( $current_package['madinah_haram_distance'] ?? ajth_ho_t('À confirmer') ); ?></dd></div>
					</dl></div>
					<div class="ajod-info-box ajod-info-box--green"><h3><?php echo esc_html( ajth_ho_t( 'Services inclus' ) ); ?></h3><ul class="ajod-list"><?php foreach ( $services as $service ) : ?><li><?php echo esc_html( $service ); ?></li><?php endforeach; ?></ul><?php if ( ! $services ) : ?><p><?php echo esc_html( ajth_ho_t( 'Services à confirmer avec votre conseiller.' ) ); ?></p><?php endif; ?></div>
					<div class="ajod-info-box ajod-info-box--warm"><h3><?php echo esc_html( ajth_ho_t( 'Ce que comprend l’offre' ) ); ?></h3><ul class="ajod-list"><?php foreach ( array_slice( $included_items, 0, 4 ) as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul><?php if ( ! $included_items ) : ?><p><?php echo esc_html( ajth_ho_t( 'Prestations sur demande.' ) ); ?></p><?php endif; ?></div>
				</div>
			</section>
			<section id="departs" class="ajod-card">
				<div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Départs' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Dates disponibles' ) ); ?></h2>
				<fieldset class="ajod-departures"><legend class="ajod-sr-only"><?php echo esc_html( ajth_ho_t( 'Choisir un départ pour la demande de réservation' ) ); ?></legend>
					<?php foreach ( $departures as $index => $departure ) : ?>
						<?php $selectable = $can_select_departure( $departure ); $departure_date = $departure['departure_date'] ?? ''; ?>
						<label class="ajod-departure<?php echo $selectable ? '' : ' is-unavailable'; ?>">
							<input type="radio" name="ajod-departure-choice" value="<?php echo esc_attr( $departure_date ); ?>" <?php checked( $selected_date !== '' && $selected_date === $departure_date && $selectable ); ?> <?php disabled( ! $selectable ); ?> data-departure-choice>
							<span class="ajod-departure__dates"><span class="ajod-mono"><?php echo esc_html( $format_date( $departure_date ) ); ?> <span aria-hidden="true">→</span> <?php echo esc_html( $format_date( $departure['return_date'] ?? '' ) ); ?></span><small><?php echo esc_html( ( $current_package['duration_label'] ?? '' ) . ' · ' . ( $current_package['departure_city'] ?? '' ) ); ?></small></span>
							<span class="ajod-tag<?php echo $selectable ? ' ajod-tag--green' : ''; ?>"><?php echo $selectable ? ajth_ho_t('Disponible') : esc_html( ( (int) ( $departure['remaining_places'] ?? 0 ) <= 0 || 'full' === ( $departure['status'] ?? '' ) ) ? ajth_ho_t('Complet') : ajth_ho_t('Indisponible') ); ?></span>
							<span class="ajod-departure__seats"><small><?php echo esc_html( ajth_ho_t( 'Places' ) ); ?></small><span class="ajod-mono"><?php echo esc_html( (string) max( 0, (int) ( $departure['remaining_places'] ?? 0 ) ) ); ?></span></span>
							<span class="ajod-departure__price"><small><?php echo esc_html( ajth_ho_t( 'À partir de' ) ); ?></small><span class="ajod-mono"><?php echo esc_html( $format_price( $departure['price_from'] ?? $current_package['price_from'] ?? null, $currency ) ); ?></span></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<?php if ( ! $selectable_departures ) : ?><p class="ajod-prose"><?php echo esc_html( ajth_ho_t( 'Contactez-nous pour connaître les prochains départs disponibles.' ) ); ?></p><?php endif; ?>
			</section>
            <?php if ( $has_formulas ) : ?>
            <section id="tarifs" class="ajod-card"><h2><?php echo esc_html( ajth_ho_t('Formules & hébergements') ); ?></h2>
                <?php echo \Ajinsafro\HajjOmra\CommercialTable::render( $formulas, $locale, $currency ); ?>
                <?php if ( ! $formulas ) : ?><p><?php echo esc_html( ajth_ho_t('Aucune formule disponible pour le moment.') ); ?></p><?php endif; ?>
            </section>
            <?php else : ?>
			<section id="tarifs" class="ajod-card">
				<div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Tarifs' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Prix par chambre' ) ); ?></h2><div class="ajod-rooms">
					<?php foreach ( $rooms as $room ) : ?>
						<?php $stock = max( 0, (int) ( $room['stock'] ?? 0 ) ); $is_best = $best_room && $best_room['room_type'] === ( $room['room_type'] ?? '' ); ?>
						<div class="ajod-room<?php echo $is_best ? ' ajod-room--best' : ''; ?>">
							<div class="ajod-room__top"><h3><?php echo esc_html( ajth_ho_localized($room, 'room_type_label') ?: $room['room_type'] ?? ajth_ho_t('Chambre') ); ?></h3><span class="ajod-tag<?php echo $is_best ? ' ajod-tag--warm' : ''; ?>"><?php echo 0 === $stock ? ajth_ho_t('Complet') : ( $is_best ? ajth_ho_t('Meilleur prix') : ( $stock <= 2 ? ajth_ho_t('Dernières') : ajth_ho_t('Disponible') ) ); ?></span></div>
							<div class="ajod-room__price"><span class="ajod-mono"><?php echo esc_html( $format_price( $room['price'] ?? null, $currency ) ); ?></span><small><?php echo esc_html( ajth_ho_t( '/ pers.' ) ); ?></small></div>
							<div class="ajod-room__stock"><span><?php echo esc_html( ajth_ho_t( 'Stock' ) ); ?></span><span class="ajod-mono"><?php echo esc_html( (string) $stock ); ?> dispo.</span></div>
							<div class="ajod-stock-bar" aria-hidden="true"><span style="width:<?php echo esc_attr( (int) round( 100 * $stock / $max_stock ) ); ?>%"></span></div>
						</div>
					<?php endforeach; ?>
				</div><?php if ( ! $rooms ) : ?><p class="ajod-prose"><?php echo esc_html( ajth_ho_t( 'Les tarifs par chambre sont disponibles sur demande.' ) ); ?></p><?php endif; ?>
			</section>
            <?php endif; ?>
			<section id="programme-section" class="ajod-card">
				<div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Programme' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Jour par jour' ) ); ?></h2><ol class="ajod-timeline">
					<?php foreach ( (array) ( $current_package['program_days'] ?? array() ) as $day ) : ?>
						<li><div class="ajod-timeline__rail"><span class="ajod-day"><small><?php echo esc_html( ajth_ho_t( 'Jour' ) ); ?></small><span><?php echo esc_html( (string) ( $day['day_number'] ?? '' ) ); ?></span></span></div><div class="ajod-timeline__body"><div class="ajod-timeline__heading"><h3><?php echo esc_html( $day['title'] ?? ajth_ho_t('Étape') ); ?></h3><?php if ( ! empty( $day['city'] ) ) : ?><span><?php echo esc_html( $day['city'] ); ?></span><?php endif; ?></div><p class="ajod-prose"><?php echo esc_html( $day['description'] ?? '' ); ?></p><?php if ( ! empty( $day['image_url'] ) ) : ?><img class="ajod-timeline__image" src="<?php echo esc_url( $day['image_url'] ); ?>" alt="<?php echo esc_attr( $day['title'] ?? ajth_ho_t('Programme') ); ?>" loading="lazy" data-fallback="<?php echo esc_url( $fallback_image ); ?>"><?php endif; ?></div></li>
					<?php endforeach; ?>
				</ol><?php if ( empty( $current_package['program_days'] ) ) : ?><p class="ajod-prose"><?php echo esc_html( ajth_ho_t( 'Le programme détaillé sera confirmé par votre conseiller.' ) ); ?></p><?php endif; ?>
			</section>
			<section id="inclus" class="ajod-pair" aria-label="<?php echo esc_attr( ajth_ho_t( 'Prestations' ) ); ?>">
				<div class="ajod-card ajod-card--included"><div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Inclus' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Ce qui est inclus' ) ); ?></h2><ul class="ajod-list"><?php foreach ( $included_items as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul><?php if ( ! $included_items ) : ?><p class="ajod-prose"><?php echo esc_html( ajth_ho_t( 'Prestations à confirmer.' ) ); ?></p><?php endif; ?></div>
				<div class="ajod-card ajod-card--warm"><div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Exclusions' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Ce qui n’est pas inclus' ) ); ?></h2><ul class="ajod-list ajod-list--excluded"><?php foreach ( $excluded_items as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul><?php if ( ! $excluded_items ) : ?><p class="ajod-prose"><?php echo esc_html( ajth_ho_t( 'Exclusions à confirmer.' ) ); ?></p><?php endif; ?></div>
			</section>
			<section id="conditions" class="ajod-pair" aria-label="<?php echo esc_attr( ajth_ho_t( 'Documents et conditions' ) ); ?>">
				<div class="ajod-card ajod-card--green"><div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Documents' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Documents nécessaires' ) ); ?></h2><p class="ajod-prose"><?php echo esc_html( ( $current_package['required_documents'] ?? '' ) ?: ajth_ho_t('La liste des documents vous sera communiquée par votre conseiller.') ); ?></p></div>
				<div class="ajod-card ajod-card--conditions"><div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Conditions' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Conditions de réservation' ) ); ?></h2><p class="ajod-prose"><?php echo esc_html( ( $current_package['booking_conditions'] ?? '' ) ?: ajth_ho_t('Les conditions vous seront précisées avant confirmation.') ); ?></p></div>
			</section>
		</div>
		<aside class="ajod-sidebar" aria-label="<?php echo esc_attr( ajth_ho_t( 'Résumé et réservation' ) ); ?>">
			<section class="ajod-summary"><div class="ajod-eyebrow"><?php echo esc_html( ajth_ho_t( 'Résumé de l’offre' ) ); ?></div><h2><?php echo esc_html( $offer_title ); ?></h2><div class="ajod-price" data-summary-price><?php echo esc_html( $format_price( $selected_price, $currency ) ); ?></div><dl class="ajod-kv">
				<div><dt><?php echo esc_html( ajth_ho_t( 'Type' ) ); ?></dt><dd><?php echo esc_html( $current_package['type_label'] ?? ajth_ho_t('Hajj & Omra') ); ?></dd></div><div><dt><?php echo esc_html( ajth_ho_t( 'Ville de départ' ) ); ?></dt><dd><?php echo esc_html( $current_package['departure_city'] ?? ajth_ho_t('À confirmer') ); ?></dd></div><div><dt><?php echo esc_html( ajth_ho_t( 'Date' ) ); ?></dt><dd class="ajod-mono" data-summary-date><?php echo esc_html( $selected_date ? $format_date( $selected_date ) : ajth_ho_t('Date sur demande') ); ?></dd></div><div><dt><?php echo esc_html( ajth_ho_t( 'Places restantes' ) ); ?></dt><dd class="ajod-mono" data-summary-seats><?php echo esc_html( (string) $selected_seats ); ?></dd></div>
			</dl></section>
			<section id="reservation-form" class="ajod-card ajod-form-card">
				<div class="ajod-section-label"><?php echo esc_html( ajth_ho_t( 'Réservation' ) ); ?></div><h2><?php echo esc_html( ajth_ho_t( 'Demander une réservation' ) ); ?></h2><p class="ajod-prose"><?php echo esc_html( ajth_ho_t( 'Notre équipe vous accompagne à chaque étape.' ) ); ?></p>
				<?php if ( $success_message ) : ?><div class="ajod-alert ajod-alert--success" role="status"><?php echo esc_html( $success_message ); ?></div><?php endif; ?>
				<?php if ( $error_message ) : ?><div class="ajod-alert ajod-alert--error" role="alert"><?php echo esc_html( $error_message ); ?></div><?php endif; ?>
				<form method="post" action="<?php echo esc_url( $share_url . '#reservation-form' ); ?>" class="ajod-form" data-reservation-form>
					<?php wp_nonce_field( 'ajth_hajj_omra_booking_request', 'ajth_hajj_omra_nonce' ); ?>
					<input type="hidden" name="ajth_hajj_omra_booking_request" value="1">
                    <input type="hidden" name="departure_id" value="<?php echo esc_attr( $selected_departure['id'] ?? '' ); ?>">
                    <input type="hidden" name="tariff_id" value="<?php echo esc_attr( $selected_room['id'] ?? '' ); ?>">
                    <?php if ( $has_formulas ) : ?>
                    <label for="ajho-formula"><?php echo esc_html( ajth_ho_t('Formule') ); ?><select id="ajho-formula" name="formula_id" required>
                        <option value=""><?php echo esc_html( ajth_ho_t('Choisir une formule') ); ?></option>
                        <?php foreach ( $formulas as $formula ) : ?><option value="<?php echo esc_attr( $formula['id'] ); ?>" <?php selected( $selected_formula['id'] ?? '', $formula['id'] ); ?>><?php echo esc_html( ajth_ho_localized( $formula, 'name' ) ); ?></option><?php endforeach; ?>
                    </select></label>
                    <?php endif; ?>
					<label for="ajho-full-name"><?php echo esc_html( ajth_ho_t( 'Nom complet' ) ); ?><input id="ajho-full-name" type="text" name="full_name" maxlength="255" autocomplete="name" placeholder="<?php echo esc_attr( ajth_ho_t( 'Votre nom et prénom' ) ); ?>" value="<?php echo esc_attr( wp_unslash( $_POST['full_name'] ?? '' ) ); ?>" required></label>
					<label for="ajho-phone"><?php echo esc_html( ajth_ho_t( 'Téléphone' ) ); ?><input id="ajho-phone" type="tel" name="phone" maxlength="60" autocomplete="tel" placeholder="+212 6XX XXX XXX" value="<?php echo esc_attr( wp_unslash( $_POST['phone'] ?? '' ) ); ?>" required></label>
					<label for="ajho-email"><?php echo esc_html( ajth_ho_t( 'Email' ) ); ?><input id="ajho-email" type="email" name="email" maxlength="255" autocomplete="email" placeholder="vous@exemple.com" value="<?php echo esc_attr( wp_unslash( $_POST['email'] ?? '' ) ); ?>" required></label>
					<label for="ajho-departure"><?php echo esc_html( ajth_ho_t( 'Départ sélectionné' ) ); ?><select id="ajho-departure" dir="ltr" name="selected_departure_date">
						<option value="" data-label="<?php echo esc_attr( ajth_ho_t( 'Date sur demande' ) ); ?>" data-seats="<?php echo esc_attr( $current_package['remaining_places'] ?? 0 ); ?>"><?php echo esc_html( ajth_ho_t( 'Choisir un départ' ) ); ?></option>
						<?php foreach ( $selectable_departures as $departure ) : ?><option value="<?php echo esc_attr( $departure['departure_date'] ); ?>" data-id="<?php echo esc_attr( $departure['id'] ?? '' ); ?>" data-label="<?php echo esc_attr( $format_date( $departure['departure_date'] ) ); ?>" data-seats="<?php echo esc_attr( $departure['remaining_places'] ?? 0 ); ?>" data-price="<?php echo esc_attr( $departure['price_from'] ?? $current_package['price_from'] ?? '' ); ?>" <?php selected( $selected_date, $departure['departure_date'] ); ?>><?php echo esc_html( $format_date( $departure['departure_date'] ) . ' — ' . $format_price( $departure['price_from'] ?? $current_package['price_from'] ?? null, $currency ) ); ?></option><?php endforeach; ?>
					</select></label>
					<label for="ajho-room-type"><?php echo esc_html( ajth_ho_t( 'Type de chambre' ) ); ?><select id="ajho-room-type" name="room_type"><option value=""><?php echo esc_html( ajth_ho_t( 'Choisir une chambre' ) ); ?></option>
						<?php foreach ( $rooms as $room ) : ?><option value="<?php echo esc_attr( $room['room_type'] ?? '' ); ?>" data-tariff-id="<?php echo esc_attr( $room['id'] ?? '' ); ?>" data-price="<?php echo esc_attr( $room['price'] ?? '' ); ?>" <?php selected( $selected_room_type, $room['room_type'] ?? '' ); ?> <?php disabled( (int) ( $room['stock'] ?? 0 ) <= 0 ); ?>><?php echo esc_html( ( ajth_ho_localized($room, 'room_type_label') ?: $room['room_type'] ?? ajth_ho_t('Chambre') ) . ' — ' . $format_price( $room['price'] ?? null, $currency ) . ( (int) ( $room['stock'] ?? 0 ) <= 0 ? ' (complet)' : '' ) ); ?></option><?php endforeach; ?>
					</select></label>
					<div class="ajod-form__pair"><label for="ajho-adults"><?php echo esc_html( ajth_ho_t( 'Adultes' ) ); ?><input id="ajho-adults" type="number" name="adults" min="1" max="20" value="<?php echo esc_attr( $adults ); ?>" required></label><label for="ajho-children"><?php echo esc_html( ajth_ho_t( 'Enfants' ) ); ?><input id="ajho-children" type="number" name="children" min="0" max="20" value="<?php echo esc_attr( $children ); ?>"></label></div>
					<label for="ajho-message"><?php echo esc_html( ajth_ho_t( 'Message' ) ); ?><textarea id="ajho-message" name="message" rows="3" maxlength="3000" placeholder="<?php echo esc_attr( ajth_ho_t( 'Vos demandes, préférences, questions…' ) ); ?>"><?php echo esc_textarea( wp_unslash( $_POST['message'] ?? '' ) ); ?></textarea></label>
					<div class="ajod-estimate" role="status" aria-live="polite"><span><?php echo esc_html( ajth_ho_t( 'Estimation' ) ); ?></span><output class="ajod-mono" for="ajho-adults ajho-children ajho-room-type ajho-departure" data-estimate><?php echo esc_html( $format_price( $estimate, $currency ) ); ?></output></div>
					<p class="ajod-form__note" data-estimate-note><?php echo $children > 0 && ! is_numeric( $child_price ) ? ajth_ho_t('Tarif enfants à confirmer en complément. ') : ''; ?><?php echo esc_html( ajth_ho_t('Estimation indicative, confirmée par votre conseiller.') ); ?></p>
					<button type="submit" class="ajod-button ajod-button--primary"><?php echo esc_html( ajth_ho_t( 'Envoyer la demande' ) ); ?></button>
					<p class="ajod-form__note"><?php echo esc_html( ajth_ho_t( 'Vos coordonnées permettent à notre équipe de vous recontacter au sujet de cette demande.' ) ); ?></p>
				</form>
			</section>
			<section class="ajod-help"><h2><?php echo esc_html( ajth_ho_t( 'Une question sur cette offre ?' ) ); ?></h2><p><?php echo esc_html( ajth_ho_t( 'Nos conseillers vous accompagnent et répondent à vos questions sur WhatsApp.' ) ); ?></p><a class="ajod-button" href="<?php echo esc_url( $whatsapp_link( $current_package ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( ajth_ho_t( 'Écrire sur WhatsApp' ) ); ?></a></section>
		</aside>
	</div>
</article>
