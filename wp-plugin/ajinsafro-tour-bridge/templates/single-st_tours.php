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
    <!-- Hero Section -->
    <?php ajtb_get_partial('hero', ['tour' => $tour]); ?>

    <!-- Main Content: wide container (MakeMyTrip-style) + 2-col grid, sidebar sticky -->
    <div class="aj-wide-container">
        <div class="ajtb-tour-layout">
            <!-- Left Column: Content -->
            <main class="ajtb-tour-main">
                <!-- Search Bar (MakeMyTrip style: Starting from / Travelling on / Rooms & Guests) -->
                <?php ajtb_get_partial('searchbar', ['tour' => $tour]); ?>

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

                <!-- Navigation Tabs -->
                <nav class="ajtb-tabs-nav">
                    <a href="#overview" class="tab-link active">Aperçu</a>
                    <?php if (!empty($tour['locations'])): ?>
                        <a href="#destinations" class="tab-link">Destinations</a>
                    <?php endif; ?>
                    <?php
                    $has_flights_section = (empty($tour['outboundFlight']) && empty($tour['inboundFlight'])) && (!empty($tour['flights']) || !empty($tour['all_flights']));
                    if ($has_flights_section): ?>
                        <a href="#flights" class="tab-link">Vols</a>
                    <?php endif; ?>
                    <?php if (!empty($tour['itinerary']) || !empty($tour['wp_program']['items'])): ?>
                        <a href="#itinerary" class="tab-link">Itinéraire</a>
                    <?php endif; ?>
                    <?php if (!empty($tour['inclusions']) || !empty($tour['exclusions'])): ?>
                        <a href="#include-exclude" class="tab-link">Inclus/Exclus</a>
                    <?php endif; ?>
                    <?php if (!empty($tour['faqs'])): ?>
                        <a href="#faq" class="tab-link">FAQ</a>
                    <?php endif; ?>
                </nav>

                <!-- Destinations Section -->
                <?php if (!empty($tour['locations'])): ?>
                    <?php ajtb_get_partial('destinations', ['tour' => $tour]); ?>
                <?php endif; ?>

                <!-- Overview Section (Aperçu du Circuit) -->
                <?php ajtb_get_partial('overview', ['tour' => $tour]); ?>

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
