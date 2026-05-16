<?php
/**
 * Tunnel de reservation V2 - Recap
 * Template utilise sur ?ajtb_recap=1
 *
 * @package AjinsafroTravelerHome
 */

if (! defined('ABSPATH')) {
    exit;
}

$tour_id = (int) get_queried_object_id();
if ($tour_id <= 0) {
    $tour_id = (int) get_the_ID();
}
if ($tour_id <= 0) {
    get_template_part('404');
    return;
}

$tour_data = class_exists('AJTB_V1_Data_Provider')
    ? AJTB_V1_Data_Provider::build($tour_id)
    : [];

$tour_title = isset($tour_data['title']) ? (string) $tour_data['title'] : trim((string) get_the_title($tour_id));
if ($tour_title === '') {
    $tour_title = __('Voyage Ajinsafro', 'ajinsafro-traveler-home');
}

$destination = isset($tour_data['destination']) ? (string) $tour_data['destination'] : 'Destination';
$duration_label = isset($tour_data['duration_label']) ? (string) $tour_data['duration_label'] : '';
$hero_main = $tour_data['hero']['main'] ?? (AJTH_URL . 'assets/images/tour-v1/hero-main.svg');
$recap_back_url = get_permalink($tour_id) ?: home_url('/');

$search_places = !empty($tour_data['search']['place_options']) && is_array($tour_data['search']['place_options'])
    ? $tour_data['search']['place_options']
    : [];
$search_dates = !empty($tour_data['search']['date_options']) && is_array($tour_data['search']['date_options'])
    ? $tour_data['search']['date_options']
    : [];
$date_prices = !empty($tour_data['search']['date_prices']) && is_array($tour_data['search']['date_prices'])
    ? $tour_data['search']['date_prices']
    : [];
$pricing = !empty($tour_data['pricing']) && is_array($tour_data['pricing']) ? $tour_data['pricing'] : [];
$base_adult = isset($pricing['adult_price']) ? (float) $pricing['adult_price'] : 0.0;
$base_child = isset($pricing['child_price']) ? (float) $pricing['child_price'] : 0.0;
$currency = isset($pricing['currency_symbol']) ? (string) $pricing['currency_symbol'] : 'MAD';
$days = !empty($tour_data['days']) && is_array($tour_data['days']) ? $tour_data['days'] : [];
$inclusions = !empty($tour_data['inclusions']) && is_array($tour_data['inclusions']) ? $tour_data['inclusions'] : [];
$exclusions = !empty($tour_data['exclusions']) && is_array($tour_data['exclusions']) ? $tour_data['exclusions'] : [];
$policy_items = !empty($tour_data['policy_items']) && is_array($tour_data['policy_items']) ? $tour_data['policy_items'] : [];
$product_type = isset($tour_data['product_type']) ? (string) $tour_data['product_type'] : 'Voyage de groupe';
$has_flights = !empty($tour_data['flights']);
$has_hotels = !empty($tour_data['hotels']) || !empty($tour_data['accommodations']);

$booking_slug = get_post_field('post_name', $tour_id);
$booking_url = 'https://booking.ajinsafro.net/voyages/' . rawurlencode((string) $booking_slug);

get_header();
?>

<div class="ajtb-page" id="ajtb-page" data-ajtb-recap-root data-tour-id="<?php echo esc_attr((string) $tour_id); ?>">
    <main class="ajtb-main">
        <div class="ajtb-container">
            <div class="ajtb-back">
                <a href="<?php echo esc_url($recap_back_url); ?>">&lsaquo; <?php echo esc_html__('Revenir au voyage', 'ajinsafro-traveler-home'); ?></a>
            </div>

            <h1 class="ajtb-title"><?php echo esc_html($tour_title); ?></h1>
            <div class="ajtb-title-line"></div>

            <!-- Stepper -->
            <div class="ajtb-stepper" id="ajtb-stepper" role="tablist" aria-label="<?php echo esc_attr__('Progression de la reservation', 'ajinsafro-traveler-home'); ?>">
                <button class="ajtb-step-btn is-active" data-step-target="1" type="button" role="tab" aria-selected="true">
                    <span class="ajtb-step-number">1</span>
                    <span>
                        <span class="ajtb-step-title"><?php echo esc_html__('Resume du voyage', 'ajinsafro-traveler-home'); ?></span>
                        <span class="ajtb-step-subtitle"><?php echo esc_html__('Vos informations de base', 'ajinsafro-traveler-home'); ?></span>
                    </span>
                </button>
                <button class="ajtb-step-btn" data-step-target="2" type="button" role="tab" aria-selected="false">
                    <span class="ajtb-step-number">2</span>
                    <span>
                        <span class="ajtb-step-title"><?php echo esc_html__('Votre selection', 'ajinsafro-traveler-home'); ?></span>
                        <span class="ajtb-step-subtitle"><?php echo esc_html__('Choix des prestations', 'ajinsafro-traveler-home'); ?></span>
                    </span>
                </button>
                <button class="ajtb-step-btn" data-step-target="3" type="button" role="tab" aria-selected="false">
                    <span class="ajtb-step-number">3</span>
                    <span>
                        <span class="ajtb-step-title"><?php echo esc_html__('Voyageurs', 'ajinsafro-traveler-home'); ?></span>
                        <span class="ajtb-step-subtitle"><?php echo esc_html__('Informations voyageurs', 'ajinsafro-traveler-home'); ?></span>
                    </span>
                </button>
                <button class="ajtb-step-btn" data-step-target="4" type="button" role="tab" aria-selected="false">
                    <span class="ajtb-step-number">4</span>
                    <span>
                        <span class="ajtb-step-title"><?php echo esc_html__('Prix et confirmation', 'ajinsafro-traveler-home'); ?></span>
                        <span class="ajtb-step-subtitle"><?php echo esc_html__('Verification et paiement', 'ajinsafro-traveler-home'); ?></span>
                    </span>
                </button>
            </div>

            <div class="ajtb-booking-grid">
                <div class="ajtb-content">

                    <!-- ETAPE 1 -->
                    <section class="ajtb-step-content is-active" data-step="1" data-ajtb-step-panel="ajtb-v1-step-tour">
                        <div class="ajtb-panel">
                            <div class="ajtb-step-label"><?php echo esc_html__('Etape 1', 'ajinsafro-traveler-home'); ?></div>
                            <h2 class="ajtb-section-title"><?php echo esc_html__('Resume du voyage', 'ajinsafro-traveler-home'); ?></h2>
                            <p class="ajtb-section-desc">
                                <?php echo esc_html__('Verifiez les informations principales de votre voyage avant de continuer.', 'ajinsafro-traveler-home'); ?>
                            </p>

                            <div class="ajtb-hero">
                                <img src="<?php echo esc_url($hero_main); ?>" alt="<?php echo esc_attr($tour_title); ?>" loading="eager" />
                                <div class="ajtb-hero-badge"><?php echo esc_html($destination); ?></div>
                            </div>

                            <h3><?php echo esc_html__('Votre voyage', 'ajinsafro-traveler-home'); ?></h3>

                            <div class="ajtb-chip-row">
                                <span class="ajtb-chip"><?php echo esc_html($duration_label !== '' ? $duration_label : '5 jours / 4 nuits'); ?></span>
                                <span class="ajtb-chip"><?php echo esc_html($destination); ?></span>
                                <span class="ajtb-chip"><?php echo esc_html($product_type); ?></span>
                                <?php if ($has_flights): ?><span class="ajtb-chip"><?php echo esc_html__('Vol inclus', 'ajinsafro-traveler-home'); ?></span><?php endif; ?>
                                <?php if ($has_hotels): ?><span class="ajtb-chip"><?php echo esc_html__('Hotel inclus', 'ajinsafro-traveler-home'); ?></span><?php endif; ?>
                            </div>

                            <div class="ajtb-info-grid">
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Date de voyage', 'ajinsafro-traveler-home'); ?></small>
                                    <strong data-ajtb-recap-field="date">-</strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Ville de depart', 'ajinsafro-traveler-home'); ?></small>
                                    <strong data-ajtb-recap-field="departure">-</strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Voyageurs', 'ajinsafro-traveler-home'); ?></small>
                                    <strong data-ajtb-recap-field="people">2 adultes</strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Hebergement', 'ajinsafro-traveler-home'); ?></small>
                                    <strong><?php echo esc_html($has_hotels ? 'Inclus' : 'Non indique'); ?></strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Vol', 'ajinsafro-traveler-home'); ?></small>
                                    <strong><?php echo esc_html($has_flights ? 'Inclus' : 'Non indique'); ?></strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Type', 'ajinsafro-traveler-home'); ?></small>
                                    <strong><?php echo esc_html($product_type); ?></strong>
                                </div>
                            </div>

                            <div class="ajtb-mini-grid">
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Prestations incluses', 'ajinsafro-traveler-home'); ?></h4>
                                    <?php if (!empty($inclusions)): ?>
                                        <?php foreach (array_slice($inclusions, 0, 5) as $line): ?>
                                            <div class="ajtb-line"><span><?php echo esc_html((string) $line); ?></span><strong><?php echo esc_html__('Inclus', 'ajinsafro-traveler-home'); ?></strong></div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="ajtb-line"><span><?php echo esc_html__('Hebergement', 'ajinsafro-traveler-home'); ?></span><strong><?php echo esc_html__('Inclus', 'ajinsafro-traveler-home'); ?></strong></div>
                                        <div class="ajtb-line"><span><?php echo esc_html__('Assistance', 'ajinsafro-traveler-home'); ?></span><strong>AjinSafro</strong></div>
                                        <div class="ajtb-line"><span><?php echo esc_html__('Prestations du programme', 'ajinsafro-traveler-home'); ?></span><strong><?php echo esc_html__('Inclus', 'ajinsafro-traveler-home'); ?></strong></div>
                                    <?php endif; ?>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Options selectionnees', 'ajinsafro-traveler-home'); ?></h4>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Extras', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="priceExtras">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Activites', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="priceActivities">-</strong></div>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Statut', 'ajinsafro-traveler-home'); ?></h4>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Disponibilite', 'ajinsafro-traveler-home'); ?></span><strong><?php echo esc_html__('A confirmer', 'ajinsafro-traveler-home'); ?></strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Reservation', 'ajinsafro-traveler-home'); ?></span><strong><?php echo esc_html__('En attente', 'ajinsafro-traveler-home'); ?></strong></div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- ETAPE 2 -->
                    <section class="ajtb-step-content" data-step="2" data-ajtb-step-panel="ajtb-v1-step-selection">
                        <div class="ajtb-panel">
                            <div class="ajtb-step-label"><?php echo esc_html__('Etape 2', 'ajinsafro-traveler-home'); ?></div>
                            <h2 class="ajtb-section-title"><?php echo esc_html__('Votre selection', 'ajinsafro-traveler-home'); ?></h2>
                            <p class="ajtb-section-desc">
                                <?php echo esc_html__('Verifiez vos choix de voyage, prestations, chambres et options.', 'ajinsafro-traveler-home'); ?>
                            </p>

                            <div class="ajtb-info-grid" id="ajtb-v1-recap-edit-grid">
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Ville de depart', 'ajinsafro-traveler-home'); ?></small>
                                    <?php if (!empty($search_places)): ?>
                                        <strong>
                                            <select class="ajtb-search-select" id="ajtb-v1-search-from" aria-label="<?php echo esc_attr__('Lieux de depart', 'ajinsafro-traveler-home'); ?>">
                                                <?php foreach ($search_places as $place_option): ?>
                                                    <?php
                                                    $pid = isset($place_option['id']) ? (int) $place_option['id'] : 0;
                                                    $pname = isset($place_option['name']) ? trim((string) $place_option['name']) : '';
                                                    if ($pname === '') { continue; }
                                                    ?>
                                                    <option value="<?php echo esc_attr((string) $pid); ?>" data-place-name="<?php echo esc_attr($pname); ?>">
                                                        <?php echo esc_html($pname); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </strong>
                                    <?php else: ?>
                                        <strong>--</strong>
                                    <?php endif; ?>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Date de voyage', 'ajinsafro-traveler-home'); ?></small>
                                    <?php if (!empty($search_dates)): ?>
                                        <strong>
                                            <select class="ajtb-search-select" id="ajtb-v1-search-date" aria-label="<?php echo esc_attr__('Dates de depart', 'ajinsafro-traveler-home'); ?>">
                                                <?php foreach ($search_dates as $date_option): ?>
                                                    <?php
                                                    $dv = isset($date_option['value']) ? trim((string) $date_option['value']) : '';
                                                    $dd = isset($date_option['display']) ? (string) $date_option['display'] : $dv;
                                                    if ($dv === '') { continue; }
                                                    ?>
                                                    <option value="<?php echo esc_attr($dv); ?>"><?php echo esc_html($dd !== '' ? $dd : $dv); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </strong>
                                    <?php else: ?>
                                        <strong>--</strong>
                                    <?php endif; ?>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Nombre de personnes', 'ajinsafro-traveler-home'); ?></small>
                                    <strong>
                                        <div class="ajtb-guests-picker" id="ajtb-v1-guests-picker" data-max-adults="20" data-max-children="8" data-max-total="28">
                                            <button type="button" class="ajtb-guest-trigger" id="ajtb-v1-guest-trigger" aria-expanded="false">
                                                <span id="ajtb-v1-guest-summary">2 adultes</span>
                                            </button>
                                            <div class="ajtb-guest-popover" id="ajtb-v1-guest-popover" hidden>
                                                <div class="ajtb-guest-row">
                                                    <div>
                                                        <strong><?php echo esc_html__('Adultes', 'ajinsafro-traveler-home'); ?></strong>
                                                        <span><?php echo esc_html__('Age 12+', 'ajinsafro-traveler-home'); ?></span>
                                                    </div>
                                                    <div class="ajtb-guest-stepper">
                                                        <button type="button" data-ajtb-guest-action="minus" data-ajtb-guest-target="adults">-</button>
                                                        <span id="ajtb-v1-guest-adults-value">2</span>
                                                        <button type="button" data-ajtb-guest-action="plus" data-ajtb-guest-target="adults">+</button>
                                                    </div>
                                                </div>
                                                <div class="ajtb-guest-row">
                                                    <div>
                                                        <strong><?php echo esc_html__('Enfants', 'ajinsafro-traveler-home'); ?></strong>
                                                        <span><?php echo esc_html__('Age 2-11', 'ajinsafro-traveler-home'); ?></span>
                                                    </div>
                                                    <div class="ajtb-guest-stepper">
                                                        <button type="button" data-ajtb-guest-action="minus" data-ajtb-guest-target="children">-</button>
                                                        <span id="ajtb-v1-guest-children-value">0</span>
                                                        <button type="button" data-ajtb-guest-action="plus" data-ajtb-guest-target="children">+</button>
                                                    </div>
                                                </div>
                                                <button type="button" class="ajtb-guest-apply" id="ajtb-v1-guest-apply"><?php echo esc_html__('Appliquer', 'ajinsafro-traveler-home'); ?></button>
                                            </div>
                                            <input type="hidden" id="ajtb-v1-guest-adults-input" value="2">
                                            <input type="hidden" id="ajtb-v1-guest-children-input" value="0">
                                        </div>
                                    </strong>
                                </div>
                            </div>

                            <div class="ajtb-mini-grid" id="ajtb-v1-recap-selection">
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Voyage', 'ajinsafro-traveler-home'); ?></h4>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Depart', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="departure">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Date', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="date">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Voyageurs', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="people">2 adultes</strong></div>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Prestations', 'ajinsafro-traveler-home'); ?></h4>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Hebergement', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="hotel">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Vol', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="flight">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Transferts', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="transfers">-</strong></div>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Options selectionnees', 'ajinsafro-traveler-home'); ?></h4>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Extras', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="priceExtras">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Supplements chambre', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="priceRoom">-</strong></div>
                                    <div class="ajtb-line"><span><?php echo esc_html__('Activites ajoutees', 'ajinsafro-traveler-home'); ?></span><strong data-ajtb-recap-field="priceActivities">-</strong></div>
                                </div>
                            </div>

                            <h3 style="margin-top:26px;"><?php echo esc_html__('Repartition des chambres', 'ajinsafro-traveler-home'); ?></h3>
                            <div id="ajtb-v1-room-picker" class="ajtb-room-list">
                                <p class="ajtb-muted"><?php echo esc_html__('Selectionnez une date et une ville de depart pour voir les chambres disponibles.', 'ajinsafro-traveler-home'); ?></p>
                            </div>

                            <h3 style="margin-top:26px;"><?php echo esc_html__('Extras et supplements', 'ajinsafro-traveler-home'); ?></h3>
                            <div id="ajtb-v1-extras-picker" class="ajtb-options">
                                <p class="ajtb-muted"><?php echo esc_html__('Les extras disponibles s\'affichent selon le voyage.', 'ajinsafro-traveler-home'); ?></p>
                            </div>
                            <div class="ajtb-extras-assign" id="ajtb-v1-extras-assign"></div>

                            <h3 style="margin-top:26px;"><?php echo esc_html__('Programme / Itineraire', 'ajinsafro-traveler-home'); ?></h3>
                            <?php if (!empty($days)): ?>
                                <div class="ajtb-accordion">
                                    <?php foreach (array_slice($days, 0, 12) as $day): ?>
                                        <?php
                                        $day_num = (int) ($day['day'] ?? 0);
                                        $day_title = (string) ($day['title'] ?? ('Jour ' . $day_num));
                                        $day_desc = trim((string) ($day['description'] ?? ''));
                                        ?>
                                        <details class="ajtb-accordion-row" <?php echo $day_num === 1 ? 'open' : ''; ?>>
                                            <summary>
                                                <span><strong>J<?php echo esc_html((string) $day_num); ?></strong> <?php echo esc_html($day_title); ?></span>
                                                <span><?php echo esc_html__('Voir', 'ajinsafro-traveler-home'); ?></span>
                                            </summary>
                                            <?php if ($day_desc !== ''): ?>
                                                <div class="ajtb-accordion-body">
                                                    <p><?php echo esc_html($day_desc); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="ajtb-muted"><?php echo esc_html__('Le programme detaille sera affiche des que les donnees sont disponibles.', 'ajinsafro-traveler-home'); ?></p>
                            <?php endif; ?>

                            <h3 style="margin-top:26px;"><?php echo esc_html__('Prestations incluses / exclusions', 'ajinsafro-traveler-home'); ?></h3>
                            <div class="ajtb-mini-grid">
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Inclus', 'ajinsafro-traveler-home'); ?></h4>
                                    <ul class="ajtb-list">
                                        <?php if (!empty($inclusions)): ?>
                                            <?php foreach (array_slice($inclusions, 0, 10) as $line): ?>
                                                <li><?php echo esc_html((string) $line); ?></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li><?php echo esc_html__('Hebergement selon formule', 'ajinsafro-traveler-home'); ?></li>
                                            <li><?php echo esc_html__('Assistance Ajinsafro', 'ajinsafro-traveler-home'); ?></li>
                                            <li><?php echo esc_html__('Prestations du programme', 'ajinsafro-traveler-home'); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Non inclus', 'ajinsafro-traveler-home'); ?></h4>
                                    <ul class="ajtb-list">
                                        <?php if (!empty($exclusions)): ?>
                                            <?php foreach (array_slice($exclusions, 0, 10) as $line): ?>
                                                <li><?php echo esc_html((string) $line); ?></li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li><?php echo esc_html__('Depenses personnelles', 'ajinsafro-traveler-home'); ?></li>
                                            <li><?php echo esc_html__('Options non selectionnees', 'ajinsafro-traveler-home'); ?></li>
                                            <li><?php echo esc_html__('Prestations hors contrat', 'ajinsafro-traveler-home'); ?></li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>

                            <h3 style="margin-top:26px;"><?php echo esc_html__('Conditions d\'annulation / modification', 'ajinsafro-traveler-home'); ?></h3>
                            <?php if (!empty($policy_items)): ?>
                                <ul class="ajtb-list">
                                    <?php foreach (array_slice($policy_items, 0, 8) as $line): ?>
                                        <li><?php echo esc_html((string) $line); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="ajtb-mini-grid">
                                    <div class="ajtb-mini-card">
                                        <div class="ajtb-line"><span>J-30 et plus</span><strong><?php echo esc_html__('Conditions standard', 'ajinsafro-traveler-home'); ?></strong></div>
                                    </div>
                                    <div class="ajtb-mini-card">
                                        <div class="ajtb-line"><span>J-29 a J-8</span><strong><?php echo esc_html__('Frais progressifs', 'ajinsafro-traveler-home'); ?></strong></div>
                                    </div>
                                    <div class="ajtb-mini-card">
                                        <div class="ajtb-line"><span>J-7 et moins</span><strong><?php echo esc_html__('Regles renforcees', 'ajinsafro-traveler-home'); ?></strong></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </section>

                    <!-- ETAPE 3 -->
                    <section class="ajtb-step-content" data-step="3" data-ajtb-step-panel="ajtb-v1-step-confirmation">
                        <div class="ajtb-panel">
                            <div class="ajtb-step-label"><?php echo esc_html__('Etape 3', 'ajinsafro-traveler-home'); ?></div>
                            <h2 class="ajtb-section-title"><?php echo esc_html__('Details des voyageurs', 'ajinsafro-traveler-home'); ?></h2>
                            <p class="ajtb-section-desc">
                                <?php echo esc_html__('Vos donnees sont securisees et utilisees uniquement pour votre reservation.', 'ajinsafro-traveler-home'); ?>
                            </p>

                            <div class="ajtb-form-grid">
                                <div class="ajtb-field">
                                    <label for="ajtb-client-first"><?php echo esc_html__('Prenom', 'ajinsafro-traveler-home'); ?> *</label>
                                    <input type="text" id="ajtb-client-first" autocomplete="given-name" placeholder="<?php echo esc_attr__('Prenom', 'ajinsafro-traveler-home'); ?>">
                                </div>
                                <div class="ajtb-field">
                                    <label for="ajtb-client-last"><?php echo esc_html__('Nom', 'ajinsafro-traveler-home'); ?> *</label>
                                    <input type="text" id="ajtb-client-last" autocomplete="family-name" placeholder="<?php echo esc_attr__('Nom', 'ajinsafro-traveler-home'); ?>">
                                </div>
                                <div class="ajtb-field">
                                    <label for="ajtb-client-phone"><?php echo esc_html__('Telephone', 'ajinsafro-traveler-home'); ?></label>
                                    <input type="tel" id="ajtb-client-phone" autocomplete="tel" placeholder="+212 ...">
                                </div>
                                <div class="ajtb-field">
                                    <label for="ajtb-client-email"><?php echo esc_html__('Email', 'ajinsafro-traveler-home'); ?></label>
                                    <input type="email" id="ajtb-client-email" autocomplete="email" placeholder="email@exemple.com">
                                </div>
                            </div>

                            <div class="ajtb-companion-header">
                                <div>
                                    <h3 style="margin:0;"><?php echo esc_html__('Accompagnants', 'ajinsafro-traveler-home'); ?></h3>
                                    <p class="ajtb-section-desc" style="margin:5px 0 0;">
                                        <?php echo esc_html__('Ajoutez les personnes qui voyageront avec vous.', 'ajinsafro-traveler-home'); ?>
                                    </p>
                                </div>
                                <div class="ajtb-companion-actions">
                                    <button class="ajtb-btn ajtb-btn-prev" type="button" data-ajtb-recap-action="add-adult">+ <?php echo esc_html__('Adulte', 'ajinsafro-traveler-home'); ?></button>
                                    <button class="ajtb-btn ajtb-btn-prev" type="button" data-ajtb-recap-action="add-child">+ <?php echo esc_html__('Enfant', 'ajinsafro-traveler-home'); ?></button>
                                </div>
                            </div>

                            <div id="ajtb-recap-companions-list" data-ajtb-companions></div>

                            <div class="ajtb-alert">
                                <?php echo esc_html__('Assurez-vous que les noms correspondent a ceux indiques sur les pieces d\'identite.', 'ajinsafro-traveler-home'); ?>
                            </div>
                        </div>
                    </section>

                    <!-- ETAPE 4 -->
                    <section class="ajtb-step-content" data-step="4" data-ajtb-step-panel="ajtb-v1-step-price">
                        <div class="ajtb-panel">
                            <div class="ajtb-step-label"><?php echo esc_html__('Etape 4', 'ajinsafro-traveler-home'); ?></div>
                            <h2 class="ajtb-section-title"><?php echo esc_html__('Prix et confirmation', 'ajinsafro-traveler-home'); ?></h2>
                            <p class="ajtb-section-desc">
                                <?php echo esc_html__('Verifiez le detail du prix dans la colonne de droite puis confirmez votre reservation.', 'ajinsafro-traveler-home'); ?>
                            </p>

                            <div class="ajtb-mini-grid">
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Total mis a jour', 'ajinsafro-traveler-home'); ?></h4>
                                    <p><?php echo esc_html__('Le total est mis a jour en temps reel selon vos selections.', 'ajinsafro-traveler-home'); ?></p>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Reservation en attente', 'ajinsafro-traveler-home'); ?></h4>
                                    <p><?php echo esc_html__('Votre reservation reste en attente jusqu\'a validation finale par notre equipe.', 'ajinsafro-traveler-home'); ?></p>
                                </div>
                                <div class="ajtb-mini-card">
                                    <h4><?php echo esc_html__('Champs obligatoires', 'ajinsafro-traveler-home'); ?></h4>
                                    <p><?php echo esc_html__('Le bouton de confirmation est actif uniquement lorsque tous les champs sont valides.', 'ajinsafro-traveler-home'); ?></p>
                                </div>
                            </div>

                            <h3><?php echo esc_html__('Ce qui est inclus dans votre selection', 'ajinsafro-traveler-home'); ?></h3>
                            <div class="ajtb-info-grid">
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Hebergement', 'ajinsafro-traveler-home'); ?></small>
                                    <strong><?php echo esc_html__('Inclus', 'ajinsafro-traveler-home'); ?></strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Petit-dejeuner', 'ajinsafro-traveler-home'); ?></small>
                                    <strong><?php echo esc_html__('Inclus', 'ajinsafro-traveler-home'); ?></strong>
                                </div>
                                <div class="ajtb-info-card">
                                    <small><?php echo esc_html__('Assistance', 'ajinsafro-traveler-home'); ?></small>
                                    <strong>AjinSafro 24/7</strong>
                                </div>
                            </div>

                            <div class="ajtb-checklist">
                                <div class="ajtb-check-item"><?php echo esc_html__('Informations voyageurs completes', 'ajinsafro-traveler-home'); ?></div>
                                <div class="ajtb-check-item"><?php echo esc_html__('Selection validee', 'ajinsafro-traveler-home'); ?></div>
                                <div class="ajtb-check-item"><?php echo esc_html__('Total mis a jour', 'ajinsafro-traveler-home'); ?></div>
                                <div class="ajtb-check-item"><?php echo esc_html__('Reservation en attente de validation finale', 'ajinsafro-traveler-home'); ?></div>
                            </div>
                        </div>
                    </section>

                    <!-- Navigation -->
                    <div class="ajtb-bottom-navigation is-first-step" id="ajtb-bottom-navigation">
                        <button class="ajtb-btn ajtb-btn-prev" id="ajtb-prev-btn" type="button" style="display:none;">
                            &larr; <?php echo esc_html__('Precedent', 'ajinsafro-traveler-home'); ?>
                        </button>
                        <div class="ajtb-step-progress-text">
                            <?php echo esc_html__('Etape', 'ajinsafro-traveler-home'); ?> <span id="ajtb-current-step-text">1</span> / 4
                        </div>
                        <button class="ajtb-btn ajtb-btn-next" id="ajtb-next-btn" type="button">
                            <?php echo esc_html__('Suivant', 'ajinsafro-traveler-home'); ?> &rarr;
                        </button>
                    </div>
                </div>

                <!-- Sidebar prix -->
                <aside class="ajtb-price-card" id="ajtb-price-sidebar">
                    <h3><?php echo esc_html__('Recapitulatif du prix', 'ajinsafro-traveler-home'); ?></h3>

                    <div class="ajtb-price-total" aria-live="polite">
                        <small><?php echo esc_html__('Total de votre reservation', 'ajinsafro-traveler-home'); ?></small>
                        <strong><span data-ajtb-recap-field="total">--</span> <small data-ajtb-recap-field="currency"><?php echo esc_html($currency); ?></small></strong>
                        <span><?php echo esc_html__('Tout inclus', 'ajinsafro-traveler-home'); ?></span>
                    </div>

                    <div class="ajtb-price-breakdown" data-ajtb-recap-field="priceDetail">
                        <div class="ajtb-price-row">
                            <span><?php echo esc_html__('Adultes', 'ajinsafro-traveler-home'); ?></span>
                            <strong data-ajtb-recap-field="priceAdults">--</strong>
                        </div>
                        <div class="ajtb-price-row" data-ajtb-recap-row="children" hidden>
                            <span><?php echo esc_html__('Enfants', 'ajinsafro-traveler-home'); ?></span>
                            <strong data-ajtb-recap-field="priceChildren">--</strong>
                        </div>
                        <div class="ajtb-price-row" data-ajtb-recap-row="activities">
                            <span><?php echo esc_html__('Activites', 'ajinsafro-traveler-home'); ?></span>
                            <strong data-ajtb-recap-field="priceActivities">--</strong>
                        </div>
                        <div class="ajtb-price-row" data-ajtb-recap-row="extras">
                            <span><?php echo esc_html__('Extras', 'ajinsafro-traveler-home'); ?></span>
                            <strong data-ajtb-recap-field="priceExtras">--</strong>
                        </div>
                        <div class="ajtb-price-row" data-ajtb-recap-row="room" hidden>
                            <span><?php echo esc_html__('Supplements chambre', 'ajinsafro-traveler-home'); ?></span>
                            <strong data-ajtb-recap-field="priceRoom">--</strong>
                        </div>
                        <div class="ajtb-price-row" data-ajtb-recap-row="demiDouble" hidden>
                            <span><?php echo esc_html__('Demi-double', 'ajinsafro-traveler-home'); ?></span>
                            <strong data-ajtb-recap-field="demiDoubleStatus">--</strong>
                        </div>
                        <div class="ajtb-price-row ajtb-price-row--total">
                            <span><?php echo esc_html__('Total', 'ajinsafro-traveler-home'); ?></span>
                            <strong><span data-ajtb-recap-field="totalLine">--</span> <small data-ajtb-recap-field="currencyLine"><?php echo esc_html($currency); ?></small></strong>
                        </div>
                    </div>

                    <button type="button" class="ajtb-confirm" id="ajtb-final-submit" data-ajtb-recap-action="final-submit">
                        <?php echo esc_html__('Confirmer la reservation', 'ajinsafro-traveler-home'); ?>
                    </button>

                    <p class="ajtb-price-note" data-ajtb-recap-field="pendingNote">
                        <?php echo esc_html__('Reservation creee en statut en attente jusqu\'a validation finale.', 'ajinsafro-traveler-home'); ?>
                    </p>
                </aside>
            </div>
        </div>
    </main>
</div>

<div class="modal fade" id="ajtb-account-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?php echo esc_html__('Votre compte client Ajinsafro', 'ajinsafro-traveler-home'); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="ajtb-account-modal-message"><?php echo esc_html__('Votre reservation est confirmee.', 'ajinsafro-traveler-home'); ?></p>
                <div class="border rounded p-3 bg-light">
                    <div class="mb-2">
                        <div class="text-muted small">Login</div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <code id="ajtb-account-login">--</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-ajtb-copy="#ajtb-account-login"><?php echo esc_html__('Copier', 'ajinsafro-traveler-home'); ?></button>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small"><?php echo esc_html__('Mot de passe', 'ajinsafro-traveler-home'); ?></div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <code id="ajtb-account-password">--</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-ajtb-copy="#ajtb-account-password"><?php echo esc_html__('Copier', 'ajinsafro-traveler-home'); ?></button>
                        </div>
                        <div class="form-text"><?php echo esc_html__('Note: ce mot de passe est affiche juste apres creation. Conservez-le.', 'ajinsafro-traveler-home'); ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a class="btn btn-primary" href="https://booking.ajinsafro.net/login"><?php echo esc_html__('Se connecter', 'ajinsafro-traveler-home'); ?></a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?php echo esc_html__('Fermer', 'ajinsafro-traveler-home'); ?></button>
            </div>
        </div>
    </div>
</div>

<script>
window.ajtbRecapBase = <?php echo wp_json_encode([
    'tourId' => (int) $tour_id,
    'tourTitle' => (string) $tour_title,
    'destination' => (string) $destination,
    'duration' => (string) $duration_label,
    'permalink' => (string) ($recap_back_url ?: ''),
    'bookingUrl' => (string) $booking_url,
    'pricing' => [
        'adult' => $base_adult,
        'child' => $base_child,
        'currency' => $currency,
    ],
    'datePrices' => $date_prices,
]); ?>;
</script>

<?php get_footer(); ?>
