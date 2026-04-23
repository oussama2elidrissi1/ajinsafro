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
                <a class="ajtb-v1-recap-step" href="#ajtb-v1-step-price">
                    <span class="ajtb-v1-recap-step-index">3</span>
                    <span class="ajtb-v1-recap-step-label">Récapitulatif du prix</span>
                </a>
                <a class="ajtb-v1-recap-step" href="#ajtb-v1-step-confirmation">
                    <span class="ajtb-v1-recap-step-index">4</span>
                    <span class="ajtb-v1-recap-step-label">Confirmation</span>
                </a>
            </nav>

            <section class="ajtb-v1-recap-grid">
                <div class="ajtb-v1-recap-main">
                    <article class="ajtb-v1-card ajtb-v1-recap-tour" id="ajtb-v1-step-tour">
                        <div class="ajtb-v1-recap-tour-media">
                            <img src="<?php echo esc_url($hero_main); ?>" alt="<?php echo esc_attr($tour_title); ?>" loading="eager">
                        </div>
                        <div class="ajtb-v1-recap-tour-body">
                            <div class="ajtb-v1-recap-tour-meta">
                                <span class="ajtb-v1-pill"><?php echo esc_html($duration_label !== '' ? $duration_label : 'Durée'); ?></span>
                                <span class="ajtb-v1-pill"><?php echo esc_html($destination); ?></span>
                                <span class="ajtb-v1-pill ajtb-v1-pill--id">ID #<?php echo esc_html((string) $tour_id); ?></span>
                            </div>
                            <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 1</span>Résumé du voyage</h2>
                            <ul class="ajtb-v1-recap-tour-points">
                                <li>Destination : <strong><?php echo esc_html($destination); ?></strong></li>
                                <li>Durée : <strong><?php echo esc_html($duration_label !== '' ? $duration_label : '—'); ?></strong></li>
                            </ul>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-selection" id="ajtb-v1-step-selection">
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 2</span>Votre sélection</h2>
                        <div class="ajtb-v1-recap-edit-grid" id="ajtb-v1-recap-edit-grid">
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
                                <span class="ajtb-v1-search-label">Voyageurs</span>
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
                        </div>

                        <dl class="ajtb-v1-recap-dl ajtb-v1-recap-dl--readonly" id="ajtb-v1-recap-selection">
                            <div><dt>Hébergement</dt><dd data-ajtb-recap-field="hotel">—</dd></div>
                            <div><dt>Vol</dt><dd data-ajtb-recap-field="flight">—</dd></div>
                            <div><dt>Transferts</dt><dd data-ajtb-recap-field="transfers">—</dd></div>
                            <div><dt>Activités</dt><dd data-ajtb-recap-field="activities">—</dd></div>
                            <div><dt>Options / suppléments</dt><dd class="ajtb-v1-recap-clamp" data-ajtb-recap-field="options">—</dd></div>
                        </dl>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-room">
                        <h2 class="ajtb-v1-recap-section-title">Choisissez votre chambre</h2>
                        <div id="ajtb-v1-room-picker" class="ajtb-v1-room-alloc">
                            <p class="ajtb-v1-recap-muted">Sélectionnez une date et une ville de départ pour voir les chambres disponibles.</p>
                        </div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-extras">
                        <h2 class="ajtb-v1-recap-section-title">Suppléments & extras</h2>
                        <div id="ajtb-v1-extras-picker" class="ajtb-v1-choice-list">
                            <p class="ajtb-v1-recap-muted">Les extras disponibles s’affichent selon le voyage.</p>
                        </div>
                        <div class="ajtb-v1-extras-assign" id="ajtb-v1-extras-assign"></div>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-finalize" id="ajtb-v1-step-confirmation">
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 4</span>Confirmation</h2>
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

                        <button type="button" class="ajtb-v1-recap-btn ajtb-v1-recap-btn--primary" data-ajtb-recap-action="final-submit">Confirmer la réservation</button>
                        <p class="ajtb-v1-recap-note">La réservation sera créée en statut “pending” dans Laravel.</p>
                    </article>

                    <article class="ajtb-v1-card ajtb-v1-recap-details">
                        <h2 class="ajtb-v1-recap-section-title">Détails du voyage</h2>
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
                </div>

                <aside class="ajtb-v1-recap-sidebar" id="ajtb-v1-step-price">
                    <article class="ajtb-v1-side-card ajtb-v1-recap-price">
                        <h2 class="ajtb-v1-recap-section-title"><span class="ajtb-v1-recap-step-kicker">Étape 3</span>Récapitulatif du prix</h2>
                        <div class="ajtb-v1-recap-total" aria-live="polite">
                            <span>Total de votre réservation</span>
                            <strong><span data-ajtb-recap-field="total">—</span> <small data-ajtb-recap-field="currency">MAD</small></strong>
                        </div>

                        <div class="ajtb-v1-recap-price-detail" data-ajtb-recap-field="priceDetail">
                            <div class="ajtb-v1-recap-price-line">
                                <span>Adulte</span>
                                <strong data-ajtb-recap-field="priceAdults">—</strong>
                            </div>
                            <div class="ajtb-v1-recap-price-line" data-ajtb-recap-row="children" hidden>
                                <span>Enfant</span>
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
                            <div class="ajtb-v1-recap-price-line ajtb-v1-recap-price-line--total">
                                <span>Total</span>
                                <strong><span data-ajtb-recap-field="totalLine">—</span> <small data-ajtb-recap-field="currencyLine">MAD</small></strong>
                            </div>
                        </div>

                        <p class="ajtb-v1-recap-note">Vérifiez le total avant de confirmer.</p>
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

