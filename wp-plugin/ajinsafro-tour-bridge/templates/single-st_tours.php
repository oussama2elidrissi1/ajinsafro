<?php
/**
 * Single Tour Template
 *
 * Override template for single st_tours post type
 * MakeMyTrip-inspired design with two-column layout
 *
 * @package AjinsafroTourBridge
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get tour data
$tour = AJTB_Template_Loader::get_tour_data();

// Fallback if no data
if (empty($tour)) {
    // Reset loading flag
    AJTB_Template_Loader::reset_loading();
    
    // Load 404
    get_template_part('404');
    return;
}

// Get theme header
get_header();
?>

<div class="ajtb-tour-page">
    <!-- Barre sticky titre du tour : visible au scroll, remplacée par la barre onglets quand on l’atteint -->
    <!-- Barre bleue en haut (Starting from / Travelling on / Rooms & Guests + SEARCH) -->
    <div class="ajtb-top-search-bar">
        <div class="aj-wide-container">
            <div class="ajtb-top-search-bar__inner">
                <?php ajtb_get_partial('searchbar', ['tour' => $tour, 'in_top_bar' => true]); ?>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <?php ajtb_get_partial('hero', ['tour' => $tour]); ?>

    <!-- Tabs sous les images (style capture : Aperçu | Itinéraire | …) -->
    <div class="ajtb-tabs-under-hero">
        <div class="aj-wide-container">
            <nav class="ajtb-tabs-nav" role="navigation" aria-label="<?php esc_attr_e('Sections du circuit', 'ajinsafro-tour-bridge'); ?>">
                <?php
                $has_itinerary_tab = !empty($tour['itinerary']) || !empty($tour['wp_program']['items']);
                $summary_days = [];
                if (!empty($tour['itinerary']) && is_array($tour['itinerary'])) {
                    $summary_list = function ($value) {
                        if (empty($value)) {
                            return [];
                        }
                        return (is_array($value) && isset($value[0]) && is_array($value[0])) ? $value : [$value];
                    };
                    $summary_pick = function ($row, $keys, $fallback = '') {
                        if (!is_array($row)) {
                            return $fallback;
                        }
                        foreach ($keys as $key) {
                            if (!empty($row[$key])) {
                                return trim((string) $row[$key]);
                            }
                        }
                        return $fallback;
                    };
                    $summary_transfer = function ($transfer, $fallback) use ($summary_pick) {
                        $name = $summary_pick($transfer, ['name', 'title', 'transfer_name', 'service_name', 'service_title', 'transfer_title']);
                        $from = $summary_pick($transfer, ['from_label', 'pickup_location']);
                        $to = $summary_pick($transfer, ['to_label', 'dropoff_location']);
                        if ($name !== '') {
                            return $name;
                        }
                        if ($from !== '' || $to !== '') {
                            return trim($from . ($from !== '' || $to !== '' ? ' → ' : '') . $to, " \t\n\r\0\x0B→");
                        }
                        return $fallback;
                    };
                    $summary_hotel = function ($hotel, $checkout = false) use ($summary_pick) {
                        $name = $summary_pick($hotel, ['hotel_name', 'name'], __('Hôtel', 'ajinsafro-tour-bridge'));
                        return $checkout
                            ? sprintf(__('Check-out from %s', 'ajinsafro-tour-bridge'), $name)
                            : sprintf(__('Check-in to %s', 'ajinsafro-tour-bridge'), $name);
                    };
                    $summary_flight = function ($flight, $fallback) use ($summary_pick) {
                        $from = $summary_pick($flight, ['from_city', 'depart_label', 'departure_city']);
                        $to = $summary_pick($flight, ['to_city', 'arrive_label', 'arrival_city']);
                        if ($from !== '' || $to !== '') {
                            return trim(sprintf(__('Flight %s → %s', 'ajinsafro-tour-bridge'), $from !== '' ? $from : '—', $to !== '' ? $to : '—'));
                        }
                        return $fallback;
                    };
                    foreach ($tour['itinerary'] as $index => $day) {
                        $entries = [];
                        $day_number = (int) ($day['day'] ?? ($index + 1));
                        $day_date_raw = trim((string) ($day['date'] ?? ''));
                        $day_date = ($day_date_raw !== '' && strtotime($day_date_raw) !== false)
                            ? date_i18n('M j, D', strtotime($day_date_raw))
                            : '';
                        $is_first = $index === 0;
                        $is_last = $index === (count($tour['itinerary']) - 1);
                        $day_flights = $summary_list($day['flight'] ?? []);
                        $day_flights_return = $summary_list($day['flight_return'] ?? []);
                        $day_transfer_list = $summary_list($day['transfer'] ?? []);
                        $day_transfer_return_list = $summary_list($day['transfer_return'] ?? []);
                        $day_hotels_list = isset($day['hotels']) && is_array($day['hotels']) ? $day['hotels'] : (!empty($day['hotel']) ? [$day['hotel']] : []);
                        $activities = isset($day['activities']) && is_array($day['activities']) ? $day['activities'] : [];

                        if ($is_first && !empty($day_flights)) {
                            foreach ($day_flights as $flight) {
                                $entries[] = ['type' => 'flight', 'text' => $summary_flight($flight, __('Outbound flight', 'ajinsafro-tour-bridge'))];
                            }
                        } elseif (!$is_first && !empty($day_flights)) {
                            foreach ($day_flights as $flight) {
                                $entries[] = ['type' => 'flight', 'text' => $summary_flight($flight, __('Flight', 'ajinsafro-tour-bridge'))];
                            }
                        }

                        foreach ($day_transfer_list as $transfer) {
                            $entries[] = ['type' => 'transfer', 'text' => $summary_transfer($transfer, __('Airport transfer', 'ajinsafro-tour-bridge'))];
                        }

                        foreach ($day_hotels_list as $hotel) {
                            $entries[] = ['type' => 'hotel', 'text' => $summary_hotel($hotel, false)];
                        }

                        foreach ($activities as $activity) {
                            if (empty($activity['is_included'])) {
                                continue;
                            }
                            $title = !empty($activity['title']) ? trim((string) $activity['title']) : __('Activité', 'ajinsafro-tour-bridge');
                            $entries[] = ['type' => 'activity', 'text' => $title];
                        }

                        if (!empty(trim((string) ($day['meals'] ?? '')))) {
                            $entries[] = ['type' => 'meal', 'text' => trim((string) $day['meals'])];
                        }

                        if ($is_last && !empty($day['hotel_checkout'])) {
                            foreach ($day_hotels_list as $hotel) {
                                $entries[] = ['type' => 'hotel', 'text' => $summary_hotel($hotel, true)];
                            }
                        }

                        foreach ($day_transfer_return_list as $transfer) {
                            $entries[] = ['type' => 'transfer', 'text' => $summary_transfer($transfer, __('Return transfer', 'ajinsafro-tour-bridge'))];
                        }

                        if ($is_last && !empty($day_flights_return)) {
                            foreach ($day_flights_return as $flight) {
                                $entries[] = ['type' => 'flight', 'text' => $summary_flight($flight, __('Return flight', 'ajinsafro-tour-bridge'))];
                            }
                        }

                        $summary_days[] = [
                            'day_number' => $day_number,
                            'day_date' => $day_date,
                            'entries' => $entries,
                        ];
                    }
                }
                $has_summary_tab = !empty($summary_days);
                ?>
                <a href="#overview" class="tab-link ajtb-tab-ape-it<?php echo $has_itinerary_tab ? '' : ' active'; ?>"><?php esc_html_e('Aperçu', 'ajinsafro-tour-bridge'); ?></a>
                <?php if (!empty($tour['categories']) || !empty($tour['tour_types'])): ?>
                    <a href="#categories" class="tab-link"><?php esc_html_e('Catégories', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <?php if (!empty($tour['locations'])): ?>
                    <a href="#destinations" class="tab-link"><?php esc_html_e('Destinations', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <?php
                $has_flights_section = (empty($tour['outboundFlight']) && empty($tour['inboundFlight'])) && (!empty($tour['flights']) || !empty($tour['all_flights']));
                if ($has_flights_section): ?>
                    <a href="#flights" class="tab-link"><?php esc_html_e('Vols', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <?php if ($has_itinerary_tab): ?>
                    <a href="#itinerary" class="tab-link ajtb-tab-ape-it active"><?php esc_html_e('Itinéraire', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <?php if ($has_summary_tab): ?>
                    <a href="#summary" class="tab-link"><?php esc_html_e('Summary', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <?php if (!empty($tour['inclusions']) || !empty($tour['exclusions'])): ?>
                    <a href="#include-exclude" class="tab-link"><?php esc_html_e('Inclus/Exclus', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
                <?php if (!empty($tour['faqs'])): ?>
                    <a href="#faq" class="tab-link"><?php esc_html_e('FAQ', 'ajinsafro-tour-bridge'); ?></a>
                <?php endif; ?>
            </nav>
        </div>
    </div>

    <!-- Main Content: wide container (MakeMyTrip-style) + 2-col grid, sidebar sticky -->
    <div class="aj-wide-container">
        <div class="ajtb-tour-layout">
            <!-- Left Column: Content -->
            <main class="ajtb-tour-main">
                <!-- Quick Info Bar -->
                <div class="ajtb-quick-info">
                    <?php if ($tour['duration_day'] > 0): ?>
                        <div class="info-item">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <polyline points="12,6 12,12 16,14"></polyline>
                            </svg>
                            <span><?php echo esc_html($tour['duration_day']); ?> jour<?php echo $tour['duration_day'] > 1 ? 's' : ''; ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ($tour['max_people'] > 0): ?>
                        <div class="info-item">
                            <svg viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" fill="none" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                            <span>Max <?php echo esc_html($tour['max_people']); ?> personnes</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($tour['rating'] > 0): ?>
                        <div class="info-item rating">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                                <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                            </svg>
                            <span><?php echo number_format($tour['rating'], 1); ?>/5</span>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($tour['type_tour'])): ?>
                        <div class="info-item type">
                            <span class="badge"><?php echo esc_html(ucfirst(str_replace('_', ' ', $tour['type_tour']))); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Destinations Section -->
                <?php if (!empty($tour['locations'])): ?>
                    <?php ajtb_get_partial('destinations', ['tour' => $tour]); ?>
                <?php endif; ?>

                <!-- Categories Section -->
                <?php if (!empty($tour['categories']) || !empty($tour['tour_types'])): ?>
                    <section class="ajtb-section ajtb-tab-panel ajtb-tab-panel-hidden" id="categories">
                        <h2 class="ajtb-section-title">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                                <path d="M20 12V7a2 2 0 0 0-2-2h-5"></path>
                                <polyline points="14 2 14 7 19 7"></polyline>
                                <path d="M4 7h6"></path>
                                <path d="M4 12h16"></path>
                                <path d="M4 17h10"></path>
                            </svg>
                            <?php esc_html_e('Catégories du Voyage', 'ajinsafro-tour-bridge'); ?>
                        </h2>
                        <?php if (!empty($tour['categories'])): ?>
                            <div class="ajtb-content-block">
                                <p><?php esc_html_e('Catégories définies depuis le back-office pour ce voyage.', 'ajinsafro-tour-bridge'); ?></p>
                            </div>
                            <div class="ajtb-tags">
                                <?php foreach ($tour['categories'] as $cat): ?>
                                    <a href="<?php echo esc_url($cat['link']); ?>" class="tag-item">
                                        <?php echo esc_html($cat['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($tour['tour_types'])): ?>
                            <div class="ajtb-quick-facts">
                                <h3 class="facts-title"><?php esc_html_e('Types de voyage', 'ajinsafro-tour-bridge'); ?></h3>
                                <div class="ajtb-tags">
                                    <?php foreach ($tour['tour_types'] as $type): ?>
                                        <a href="<?php echo esc_url($type['link']); ?>" class="tag-item type">
                                            <?php echo esc_html($type['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </section>
                <?php endif; ?>

                <!-- Overview Section (Aperçu du Circuit) -->
                <?php ajtb_get_partial('overview', ['tour' => $tour]); ?>

                <?php if ($has_summary_tab): ?>
                    <section class="ajtb-section ajtb-tab-panel ajtb-tab-panel-hidden" id="summary">
                        <h2 class="ajtb-section-title">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                                <path d="M3 4h18"></path>
                                <path d="M3 12h18"></path>
                                <path d="M3 20h18"></path>
                                <path d="M8 4v16"></path>
                                <path d="M14 4v16"></path>
                            </svg>
                            <?php esc_html_e('Summary', 'ajinsafro-tour-bridge'); ?>
                        </h2>

                        <div class="ajtb-summary-table">
                            <?php foreach ($summary_days as $summary_day): ?>
                                <?php
                                $entries = !empty($summary_day['entries'])
                                    ? $summary_day['entries']
                                    : [['type' => 'empty', 'text' => __('Programme non disponible pour ce jour.', 'ajinsafro-tour-bridge')]];
                                $entry_pairs = array_chunk($entries, 2);
                                ?>
                                <div class="ajtb-summary-day">
                                    <div class="ajtb-summary-day__meta">
                                        <div class="ajtb-summary-day__title"><?php echo esc_html('Day ' . (int) $summary_day['day_number']); ?></div>
                                        <?php if (!empty($summary_day['day_date'])): ?>
                                            <div class="ajtb-summary-day__date"><?php echo esc_html($summary_day['day_date']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ajtb-summary-day__content">
                                        <?php foreach ($entry_pairs as $pair): ?>
                                            <div class="ajtb-summary-row">
                                                <?php for ($cell_index = 0; $cell_index < 2; $cell_index++): ?>
                                                    <?php $entry = $pair[$cell_index] ?? null; ?>
                                                    <div class="ajtb-summary-cell<?php echo $entry ? '' : ' is-empty'; ?>">
                                                        <?php if ($entry): ?>
                                                            <span class="ajtb-summary-cell__icon ajtb-summary-cell__icon--<?php echo esc_attr($entry['type']); ?>" aria-hidden="true"></span>
                                                            <span class="ajtb-summary-cell__text"><?php echo esc_html($entry['text']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endfor; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Flights Section: only when NOT showing Laravel vols in programme (no standalone "Informations Vols") -->
                <?php if (empty($tour['outboundFlight']) && empty($tour['inboundFlight'])): ?>
                    <?php ajtb_get_partial('flights', ['tour' => $tour]); ?>
                <?php endif; ?>

                <!-- Itinerary (Programme du Circuit): Laravel days or fallback WP tours_program -->
                <?php if (!empty($tour['itinerary']) || !empty($tour['wp_program']['items'])): ?>
                    <?php ajtb_get_partial('itinerary', ['tour' => $tour]); ?>
                <?php endif; ?>

                <!-- Inclusions/Exclusions Section -->
                <?php if (!empty($tour['inclusions']) || !empty($tour['exclusions'])): ?>
                    <?php ajtb_get_partial('include-exclude', ['tour' => $tour]); ?>
                <?php endif; ?>

                <!-- FAQ Section -->
                <?php if (!empty($tour['faqs'])): ?>
                    <?php ajtb_get_partial('faq', ['tour' => $tour]); ?>
                <?php endif; ?>

                <!-- Gallery Section (hero_gallery + galerie générale) – avant la section Vidéo -->
                <?php
                $hero_gallery = !empty($tour['hero_gallery']) && is_array($tour['hero_gallery']) ? $tour['hero_gallery'] : [];
                $gallery = $tour['gallery'] ?? [];
                $gallery_ids_seen = [];
                $section_gallery = [];
                foreach ($hero_gallery as $img) {
                    $section_gallery[] = $img;
                    if (isset($img['url'])) $gallery_ids_seen[] = rtrim($img['url'], '/');
                }
                foreach ($gallery as $img) {
                    $u = isset($img['url']) ? rtrim($img['url'], '/') : '';
                    if ($u && !in_array($u, $gallery_ids_seen, true)) {
                        $section_gallery[] = $img;
                        $gallery_ids_seen[] = $u;
                    }
                }
                ?>
                <?php if (!empty($section_gallery)): ?>
                    <section class="ajtb-section" id="gallery">
                        <h2 class="ajtb-section-title">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21,15 16,10 5,21"></polyline>
                            </svg>
                            Galerie Photos
                        </h2>
                        <div class="ajtb-gallery-grid">
                            <?php foreach ($section_gallery as $image): ?>
                                <a href="<?php echo esc_url($image['url'] ?? '#'); ?>" class="gallery-item" data-lightbox="tour-gallery">
                                    <img src="<?php echo esc_url($image['medium'] ?? $image['thumbnail'] ?? $image['url'] ?? ''); ?>" 
                                         alt="<?php echo esc_attr($image['alt'] ?? ''); ?>" 
                                         loading="lazy">
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Video Section -->
                <?php if (!empty($tour['video'])): ?>
                    <section class="ajtb-section" id="video">
                        <h2 class="ajtb-section-title">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                                <polygon points="5,3 19,12 5,21 5,3"></polygon>
                            </svg>
                            Vidéo
                        </h2>
                        <div class="ajtb-video-container">
                            <?php echo wp_oembed_get($tour['video']); ?>
                        </div>
                    </section>
                <?php endif; ?>

                <!-- Cancellation Policy -->
                <?php if (!empty($tour['cancellation_policy'])): ?>
                    <section class="ajtb-section" id="policy">
                        <h2 class="ajtb-section-title">
                            <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" fill="none" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14,2 14,8 20,8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                            </svg>
                            Politique d'Annulation
                        </h2>
                        <div class="ajtb-policy-content">
                            <?php echo wp_kses_post($tour['cancellation_policy']); ?>
                        </div>
                    </section>
                <?php endif; ?>

            </main>

            <!-- Right Column: Booking Box (Sticky) -->
            <aside class="ajtb-tour-sidebar">
                <?php ajtb_get_partial('booking-box', ['tour' => $tour]); ?>
            </aside>
        </div>
    </div>
</div>

<?php
// Reset loading flag
AJTB_Template_Loader::reset_loading();

// Get theme footer
get_footer();
?>
