<?php
/**
 * Tour recap step (V1).
 *
 * This page is a validation step before any booking/reservation.
 */
if (!defined('ABSPATH')) {
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
    $tour_title = __('Voyage Ajinsafro', 'ajinsafro-tour-bridge');
}

$destination = isset($tour_data['destination']) ? (string) $tour_data['destination'] : 'Destination';
$duration_label = isset($tour_data['duration_label']) ? (string) $tour_data['duration_label'] : '';
$hero_main = $tour_data['hero']['main'] ?? (AJTB_PLUGIN_URL . 'assets/images/tour-v1/hero-main.svg');
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

<div class="ajtb-v1-page ajtb-v1-recap" id="ajtb-v1-page" data-ajtb-recap-root data-tour-id="<?php echo esc_attr((string) $tour_id); ?>">
    <main class="ajtb-v1-main">
        <div class="ajtb-v1-container">
            <section class="ajtb-v1-recap-head">
                <div>
                    <p class="ajtb-v1-kicker">Récapitulatif</p>
                    <h1 class="ajtb-v1-title"><?php echo esc_html($tour_title); ?></h1>
                    <p class="ajtb-v1-recap-subtitle">Vérifiez vos choix avant de confirmer.</p>
                </div>
            </section>

            <nav class="ajtb-v1-recap-steps" aria-label="Progression de la réservation">
                <a class="ajtb-v1-recap-step is-active" href="#ajtb-v1-step-tour">
                    <span class="ajtb-v1-recap-step-index">1</span>
                    <span class="ajtb-v1-recap-step-label">Résumé du voyage</span>
                </a>
                <a class="ajtb-v1-recap-step" href="#ajtb-v1-step-selection">
                    <span class="ajtb-v1-recap-step-index">2</span>
                    <span class="ajtb-v1-recap-step-label">Votre sélection</span>
                </a>
                <a class="ajtb-v1-recap-step" href="#ajtb-v1-step-confirmation">
                    <span class="ajtb-v1-recap-step-index">3</span>
                    <span class="ajtb-v1-recap-step-label">Voyageurs</span>
                </a>
                <a class="ajtb-v1-recap-step" href="#ajtb-v1-step-price">
                    <span class="ajtb-v1-recap-step-index">4</span>
                    <span class="ajtb-v1-recap-step-label">Prix et confirmation</span>
                </a>
            </nav>

            <div class="ajtb-v1-recap-step-actions" aria-label="Navigation des étapes">
                <span class="ajtb-v1-step-actions-label">Navigation des étapes</span>
                <div class="ajtb-v1-step-actions-buttons">
                    <button type="button" class="ajtb-v1-recap-mini-btn ajtb-v1-recap-mini-btn--prev" data-ajtb-step-action="prev">Précédent</button>
                    <button type="button" class="ajtb-v1-recap-mini-btn ajtb-v1-recap-mini-btn--next" data-ajtb-step-action="next">Suivant</button>
                </div>
            </div>

            <section class="ajtb-v1-recap-grid">
                <div class="ajtb-v1-recap-main">
                    <article class="ajtb-v1-card ajtb-v1-recap-tour ajtb-v1-recap-hero" id="ajtb-v1-step-tour" data-ajtb-step-panel="ajtb-v1-step-tour">
                        <div class="ajtb-v1-recap-tour-media">
                            <img src="<?php echo esc_url($hero_main); ?>" alt="<?php echo esc_attr($tour_title); ?>" loading="eager">
                        </div>
                        <div class="ajtb-v1-recap-tour-body">
                            <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 1</span>Résumé du voyage</h2>
                            <h3 class="ajtb-v1-recap-hero-title"><?php echo esc_html($tour_title); ?></h3>
                            <p class="ajtb-v1-recap-hero-price">À partir de <strong><?php echo esc_html(number_format_i18n($base_adult, 0)); ?> <?php echo esc_html($currency); ?></strong></p>

                            <div class="ajtb-v1-recap-tour-meta">
                                <span class="ajtb-v1-pill"><?php echo esc_html($duration_label !== '' ? $duration_label : 'Durée'); ?></span>
                                <span class="ajtb-v1-pill"><?php echo esc_html($destination); ?></span>
                                <span class="ajtb-v1-pill ajtb-v1-pill--id">ID #<?php echo esc_html((string) $tour_id); ?></span>
                                <span class="ajtb-v1-pill"><?php echo esc_html($product_type); ?></span>
                                <?php if ($has_flights): ?><span class="ajtb-v1-pill">Vol inclus</span><?php endif; ?>
                                <?php if ($has_hotels): ?><span class="ajtb-v1-pill">Hôtel inclus</span><?php endif; ?>
                            </div>

                            <div class="ajtb-v1-recap-hero-grid">
                                <div><span>Destination</span><strong><?php echo esc_html($destination); ?></strong></div>
                                <div><span>Durée</span><strong><?php echo esc_html($duration_label !== '' ? $duration_label : '-'); ?></strong></div>
                                <div><span>Départ</span><strong data-ajtb-recap-field="departure">-</strong></div>
                                <div><span>Date</span><strong data-ajtb-recap-field="date">-</strong></div>
                                <div><span>Voyageurs</span><strong data-ajtb-recap-field="people">2 adultes</strong></div>
                                <div><span>Type</span><strong><?php echo esc_html($product_type); ?></strong></div>
                            </div>

                            <div class="ajtb-v1-step1-selection-preview">
                                <p class="ajtb-v1-recap-section-note">Vérifiez vos choix de voyage, prestations et options dans des cadres clairs.</p>
                                <div class="ajtb-v1-selection-groups">
                                    <section class="ajtb-v1-selection-group">
                                        <h3><span>Voyage</span></h3>
                                        <dl>
                                            <div><dt>Départ</dt><dd data-ajtb-recap-field="departure">-</dd></div>
                                            <div><dt>Date de voyage</dt><dd data-ajtb-recap-field="date">-</dd></div>
                                            <div><dt>Voyageurs</dt><dd data-ajtb-recap-field="people">2 adultes</dd></div>
                                            <div><dt>Adultes / enfants</dt><dd data-ajtb-recap-field="guests">2 adulte(s)</dd></div>
                                        </dl>
                                    </section>

                                    <section class="ajtb-v1-selection-group">
                                        <h3><span>Prestations</span></h3>
                                        <dl>
                                            <div><dt>Hébergement</dt><dd data-ajtb-recap-field="hotel">-</dd></div>
                                            <div><dt>Vol</dt><dd data-ajtb-recap-field="flight">-</dd></div>
                                            <div><dt>Transferts</dt><dd data-ajtb-recap-field="transfers">-</dd></div>
                                            <div><dt>Activités</dt><dd class="ajtb-v1-recap-clamp" data-ajtb-recap-field="activities">-</dd></div>
                                        </dl>
                                    </section>

                                    <section class="ajtb-v1-selection-group">
                                        <h3><span>Options sélectionnées</span></h3>
                                        <dl>
                                            <div><dt>Options / suppléments</dt><dd class="ajtb-v1-recap-clamp" data-ajtb-recap-field="options">-</dd></div>
                                            <div><dt>Extras</dt><dd data-ajtb-recap-field="priceExtras">-</dd></div>
                                            <div><dt>Suppléments chambre</dt><dd data-ajtb-recap-field="priceRoom">-</dd></div>
                                            <div><dt>Activités ajoutées</dt><dd data-ajtb-recap-field="priceActivities">-</dd></div>
                                        </dl>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-selection" id="ajtb-v1-step-selection" data-ajtb-step-panel="ajtb-v1-step-selection" hidden>
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 2</span>Votre sélection</h2>
                        <p class="ajtb-v1-recap-section-note">Vérifiez vos choix de voyage, prestations et options dans des cadres clairs.</p>
                        <div class="ajtb-v1-recap-selection-head" id="ajtb-v1-recap-edit-grid">
                            <div class="ajtb-v1-recap-edit-card">
                                <span class="ajtb-v1-search-label">Ville de départ</span>
                                <?php if (!empty($search_places)): ?>
                                    <span class="ajtb-v1-search-value ajtb-v1-search-value--select">
                                        <select class="ajtb-v1-search-select" id="ajtb-v1-search-from" aria-label="Lieux de départ">
                                            <?php foreach ($search_places as $place_option): ?>
                                                <?php
                                                $pid = isset($place_option['id']) ? (int) $place_option['id'] : 0;
                                                $pname = isset($place_option['name']) ? trim((string) $place_option['name']) : '';
                                                $pcode = isset($place_option['code']) ? trim((string) $place_option['code']) : '';
                                                if ($pname === '') {
                                                    continue;
                                                }
                                                ?>
                                                <option value="<?php echo esc_attr((string) $pid); ?>" data-place-name="<?php echo esc_attr($pname); ?>" data-place-code="<?php echo esc_attr($pcode); ?>">
                                                    <?php echo esc_html($pname); ?><?php echo $pcode !== '' ? esc_html(' (' . $pcode . ')') : ''; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <strong aria-hidden="true">▾</strong>
                                    </span>
                                <?php else: ?>
                                    <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text">—</span><strong aria-hidden="true">▾</strong></span>
                                <?php endif; ?>
                            </div>

                            <div class="ajtb-v1-recap-edit-card">
                                <span class="ajtb-v1-search-label">Date de voyage</span>
                                <?php if (!empty($search_dates)): ?>
                                    <span class="ajtb-v1-search-value ajtb-v1-search-value--select">
                                        <select class="ajtb-v1-search-select" id="ajtb-v1-search-date" aria-label="Dates de départ">
                                            <?php foreach ($search_dates as $date_option): ?>
                                                <?php
                                                $dv = isset($date_option['value']) ? trim((string) $date_option['value']) : '';
                                                $dd = isset($date_option['display']) ? (string) $date_option['display'] : $dv;
                                                if ($dv === '') {
                                                    continue;
                                                }
                                                ?>
                                                <option value="<?php echo esc_attr($dv); ?>"><?php echo esc_html($dd !== '' ? $dd : $dv); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <strong aria-hidden="true">▾</strong>
                                    </span>
                                <?php else: ?>
                                    <span class="ajtb-v1-search-value"><span class="ajtb-v1-search-text">—</span><strong aria-hidden="true">▾</strong></span>
                                <?php endif; ?>
                            </div>

                            <div class="ajtb-v1-recap-edit-card ajtb-v1-recap-edit-card--full">
                                <span class="ajtb-v1-search-label">Nombre de personnes</span>
                                <div class="ajtb-v1-guests-picker" data-max-adults="20" data-max-children="8" data-max-total="28">
                                    <button type="button" class="ajtb-v1-guest-trigger" id="ajtb-v1-guest-trigger" aria-expanded="false">
                                        <span class="ajtb-v1-search-value">
                                            <span class="ajtb-v1-search-text" id="ajtb-v1-guest-summary">2 adultes</span>
                                            <strong aria-hidden="true">▾</strong>
                                        </span>
                                    </button>
                                    <div class="ajtb-v1-guest-popover" id="ajtb-v1-guest-popover" hidden>
                                        <div class="ajtb-v1-guest-row">
                                            <div>
                                                <strong>Adultes</strong>
                                                <span>Age 12+</span>
                                            </div>
                                            <div class="ajtb-v1-guest-stepper">
                                                <button type="button" data-ajtb-guest-action="minus" data-ajtb-guest-target="adults">-</button>
                                                <span id="ajtb-v1-guest-adults-value">2</span>
                                                <button type="button" data-ajtb-guest-action="plus" data-ajtb-guest-target="adults">+</button>
                                            </div>
                                        </div>
                                        <div class="ajtb-v1-guest-row">
                                            <div>
                                                <strong>Enfants</strong>
                                                <span>Age 2-11</span>
                                            </div>
                                            <div class="ajtb-v1-guest-stepper">
                                                <button type="button" data-ajtb-guest-action="minus" data-ajtb-guest-target="children">-</button>
                                                <span id="ajtb-v1-guest-children-value">0</span>
                                                <button type="button" data-ajtb-guest-action="plus" data-ajtb-guest-target="children">+</button>
                                            </div>
                                        </div>
                                        <button type="button" class="ajtb-v1-guest-apply" id="ajtb-v1-guest-apply">Appliquer</button>
                                    </div>
                                    <input type="hidden" id="ajtb-v1-guest-adults-input" value="2">
                                    <input type="hidden" id="ajtb-v1-guest-children-input" value="0">
                                </div>
                            </div>

                            <div class="ajtb-v1-recap-people-banner">
                                <span>Nombre de personnes</span>
                                <strong data-ajtb-recap-field="people">2 adultes</strong>
                            </div>
                        </div>

                        <div class="ajtb-v1-selection-groups" id="ajtb-v1-recap-selection">
                            <section class="ajtb-v1-selection-group">
                                <h3><span>Voyage</span></h3>
                                <dl>
                                    <div><dt>Départ</dt><dd data-ajtb-recap-field="departure">-</dd></div>
                                    <div><dt>Date de voyage</dt><dd data-ajtb-recap-field="date">-</dd></div>
                                    <div><dt>Voyageurs</dt><dd data-ajtb-recap-field="people">2 adultes</dd></div>
                                    <div><dt>Adultes / enfants</dt><dd data-ajtb-recap-field="guests">2 adulte(s)</dd></div>
                                </dl>
                            </section>

                            <section class="ajtb-v1-selection-group">
                                <h3><span>Prestations</span></h3>
                                <dl>
                                    <div><dt>Hébergement</dt><dd data-ajtb-recap-field="hotel">-</dd></div>
                                    <div><dt>Vol</dt><dd data-ajtb-recap-field="flight">-</dd></div>
                                    <div><dt>Transferts</dt><dd data-ajtb-recap-field="transfers">-</dd></div>
                                    <div><dt>Activités</dt><dd class="ajtb-v1-recap-clamp" data-ajtb-recap-field="activities">-</dd></div>
                                </dl>
                            </section>

                            <section class="ajtb-v1-selection-group">
                                <h3><span>Options sélectionnées</span></h3>
                                <dl>
                                    <div><dt>Options / suppléments</dt><dd class="ajtb-v1-recap-clamp" data-ajtb-recap-field="options">-</dd></div>
                                    <div><dt>Extras</dt><dd data-ajtb-recap-field="priceExtras">-</dd></div>
                                    <div><dt>Suppléments chambre</dt><dd data-ajtb-recap-field="priceRoom">-</dd></div>
                                    <div><dt>Activités ajoutées</dt><dd data-ajtb-recap-field="priceActivities">-</dd></div>
                                </dl>
                            </section>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-room" data-ajtb-step-panel="ajtb-v1-step-selection" hidden>
                        <h2 class="ajtb-v1-recap-section-title">Répartition des chambres</h2>
                        <div id="ajtb-v1-room-picker" class="ajtb-v1-room-alloc">
                            <p class="ajtb-v1-recap-muted">Sélectionnez une date et une ville de départ pour voir les chambres disponibles.</p>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-extras" data-ajtb-step-panel="ajtb-v1-step-selection" hidden>
                        <h2 class="ajtb-v1-recap-section-title">Extras et suppléments</h2>
                        <div id="ajtb-v1-extras-picker" class="ajtb-v1-choice-list">
                            <p class="ajtb-v1-recap-muted">Les extras disponibles s'affichent selon le voyage.</p>
                        </div>
                        <div class="ajtb-v1-extras-assign" id="ajtb-v1-extras-assign"></div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-finalize" id="ajtb-v1-step-confirmation" data-ajtb-step-panel="ajtb-v1-step-confirmation" hidden>
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 3</span>Détails des voyageurs</h2>
                        <div class="ajtb-v1-recap-form">
                            <div class="ajtb-v1-recap-form-row">
                                <label>Prénom *</label>
                                <input type="text" id="ajtb-client-first" autocomplete="given-name" placeholder="Prénom">
                            </div>
                            <div class="ajtb-v1-recap-form-row">
                                <label>Nom *</label>
                                <input type="text" id="ajtb-client-last" autocomplete="family-name" placeholder="Nom">
                            </div>
                            <div class="ajtb-v1-recap-form-row">
                                <label>Téléphone</label>
                                <input type="tel" id="ajtb-client-phone" autocomplete="tel" placeholder="+212 ...">
                            </div>
                            <div class="ajtb-v1-recap-form-row">
                                <label>Email</label>
                                <input type="email" id="ajtb-client-email" autocomplete="email" placeholder="email@exemple.com">
                            </div>
                        </div>

                        <div class="ajtb-v1-recap-companions">
                            <div class="ajtb-v1-recap-companions-head">
                                <h3>Accompagnants</h3>
                                <div class="ajtb-v1-recap-companion-actions">
                                    <button type="button" class="ajtb-v1-recap-mini-btn" data-ajtb-recap-action="add-adult">+ Adulte</button>
                                    <button type="button" class="ajtb-v1-recap-mini-btn" data-ajtb-recap-action="add-child">+ Enfant</button>
                                </div>
                            </div>
                            <div id="ajtb-recap-companions-list" data-ajtb-companions></div>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-details" data-ajtb-step-panel="ajtb-v1-step-selection" hidden>
                        <h2 class="ajtb-v1-recap-section-title">Programme / Itinéraire</h2>
                        <?php if (!empty($days)): ?>
                            <div class="ajtb-v1-recap-days">
                                <?php foreach (array_slice($days, 0, 12) as $day): ?>
                                    <?php
                                    $day_num = (int) ($day['day'] ?? 0);
                                    $day_title = (string) ($day['title'] ?? ('Jour ' . $day_num));
                                    $day_desc = trim((string) ($day['description'] ?? ''));
                                    ?>
                                    <details class="ajtb-v1-recap-day" <?php echo $day_num === 1 ? 'open' : ''; ?>>
                                        <summary>
                                            <strong>J<?php echo esc_html((string) $day_num); ?></strong>
                                            <span><?php echo esc_html($day_title); ?></span>
                                        </summary>
                                        <?php if ($day_desc !== ''): ?>
                                            <div class="ajtb-v1-recap-day-body">
                                                <p><?php echo esc_html($day_desc); ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </details>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="ajtb-v1-recap-muted">Le programme détaillé sera affiché dès que les données sont disponibles.</p>
                        <?php endif; ?>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-features" data-ajtb-step-panel="ajtb-v1-step-selection" hidden>
                        <h2 class="ajtb-v1-recap-section-title">Prestations incluses / exclusions</h2>
                        <div class="ajtb-v1-recap-features-grid">
                            <div>
                                <h3>Inclus</h3>
                                <ul class="ajtb-v1-recap-list">
                                    <?php if (!empty($inclusions)): ?>
                                        <?php foreach (array_slice($inclusions, 0, 10) as $line): ?>
                                            <li><?php echo esc_html((string) $line); ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>Hébergement selon formule</li>
                                        <li>Assistance Ajinsafro</li>
                                        <li>Prestations du programme</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div>
                                <h3>Non inclus</h3>
                                <ul class="ajtb-v1-recap-list">
                                    <?php if (!empty($exclusions)): ?>
                                        <?php foreach (array_slice($exclusions, 0, 10) as $line): ?>
                                            <li><?php echo esc_html((string) $line); ?></li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li>Dépenses personnelles</li>
                                        <li>Options non sélectionnées</li>
                                        <li>Prestations hors contrat</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-policy" data-ajtb-step-panel="ajtb-v1-step-selection" hidden>
                        <h2 class="ajtb-v1-recap-section-title">Conditions d'annulation / modification</h2>
                        <?php if (!empty($policy_items)): ?>
                            <ul class="ajtb-v1-recap-list">
                                <?php foreach (array_slice($policy_items, 0, 8) as $line): ?>
                                    <li><?php echo esc_html((string) $line); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="ajtb-v1-recap-policy-timeline">
                                <div><strong>J-30 et plus</strong><span>Conditions standard de modification/annulation.</span></div>
                                <div><strong>Entre J-29 et J-8</strong><span>Frais progressifs selon prestations engagees.</span></div>
                                <div><strong>J-7 et moins</strong><span>Dossier traité en priorité avec règles renforcées.</span></div>
                            </div>
                        <?php endif; ?>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-checkout" id="ajtb-v1-step-price" data-ajtb-step-panel="ajtb-v1-step-price" hidden>
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 4</span>Prix et confirmation</h2>
                        <p class="ajtb-v1-recap-muted">Vérifiez le détail du prix dans la colonne de droite puis confirmez votre réservation.</p>
                        <ul class="ajtb-v1-recap-list">
                            <li>Le total est mis à jour en temps réel selon vos sélections.</li>
                            <li>La réservation reste en attente si une demi-double est choisie.</li>
                            <li>Le bouton de confirmation est actif uniquement quand tous les champs obligatoires sont valides.</li>
                        </ul>
                    </article>
                </div>

                <aside class="ajtb-v1-recap-sidebar" id="ajtb-v1-price-sidebar">
                    <article class="ajtb-v1-side-card ajtb-v1-recap-price">
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 4</span>Récapitulatif du prix</h2>
                        <div class="ajtb-v1-recap-total" aria-live="polite">
                            <span>Total de votre réservation</span>
                            <strong><span data-ajtb-recap-field="total">—</span> <small data-ajtb-recap-field="currency">MAD</small></strong>
                        </div>

                        <div class="ajtb-v1-recap-price-detail" data-ajtb-recap-field="priceDetail">
                            <div class="ajtb-v1-recap-price-line">
                                <span>Adultes</span>
                                <strong data-ajtb-recap-field="priceAdults">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line" data-ajtb-recap-row="children" hidden>
                                <span>Enfants</span>
                                <strong data-ajtb-recap-field="priceChildren">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line" data-ajtb-recap-row="activities">
                                <span>Activités</span>
                                <strong data-ajtb-recap-field="priceActivities">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line" data-ajtb-recap-row="extras">
                                <span>Extras</span>
                                <strong data-ajtb-recap-field="priceExtras">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line" data-ajtb-recap-row="room" hidden>
                                <span>Suppléments chambre</span>
                                <strong data-ajtb-recap-field="priceRoom">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line" data-ajtb-recap-row="demiDouble" hidden>
                                <span>Demi-double</span>
                                <strong data-ajtb-recap-field="demiDoubleStatus">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line ajtb-v1-recap-price-line--total">
                                <span>Total</span>
                                <strong><span data-ajtb-recap-field="totalLine">—</span> <small data-ajtb-recap-field="currencyLine">MAD</small></strong>
                            </div>
                        </div>

                        <p class="ajtb-v1-recap-note">Le récap de prix reste visible pendant votre parcours.</p>
                        <button type="button" class="ajtb-v1-recap-btn ajtb-v1-recap-btn--primary ajtb-v1-recap-submit" data-ajtb-recap-action="final-submit">Confirmer la réservation</button>
                        <p class="ajtb-v1-recap-note" data-ajtb-recap-field="pendingNote">Si une demi-double est choisie, la réservation sera créée en attente de jumelage dans Laravel.</p>
                    </article>
                </aside>
            </section>
        </div>
    </main>
</div>

<div class="modal fade" id="ajtb-account-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Votre compte client Ajinsafro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="ajtb-account-modal-message">Votre réservation est confirmée.</p>
                <div class="border rounded p-3 bg-light">
                    <div class="mb-2">
                        <div class="text-muted small">Login</div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <code id="ajtb-account-login">—</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-ajtb-copy="#ajtb-account-login">Copier</button>
                        </div>
                    </div>
                    <div>
                        <div class="text-muted small">Mot de passe</div>
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <code id="ajtb-account-password">—</code>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-ajtb-copy="#ajtb-account-password">Copier</button>
                        </div>
                        <div class="form-text">Note: ce mot de passe est affiché juste après création. Conservez-le.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a class="btn btn-primary" href="https://booking.ajinsafro.net/login">Se connecter</a>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Fermer</button>
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
