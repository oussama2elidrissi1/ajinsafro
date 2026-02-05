<?php
/**
 * Single Tour Template
 *
 * Template for displaying a single st_tours post.
 * Overrides the theme's single-st_tours.php template.
 *
 * Available variables:
 * - $tourData: Assembled tour data (wp + laravel sections)
 *
 * @package AjinsafroBridge\Templates
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get tour data (set by TemplateOverride or via helper)
global $tourData;

if (!$tourData) {
    $tourData = ajbridge_get_tour_data();
}

// Fallback if no tour data
if (!$tourData) {
    get_template_part('404');
    return;
}

// Extract common data for easier access
$wp = $tourData['wp'];
$laravel = $tourData['laravel'];
$pricing = $tourData['pricing'];
$itinerary = $tourData['itinerary'];

get_header();
?>

<div class="ajbridge-tour-single">
    <!-- Hero Section -->
    <?php ajbridge_partial('hero', ['tourData' => $tourData]); ?>

    <div class="ajbridge-tour-content">
        <div class="ajbridge-container">
            <div class="ajbridge-row">
                <!-- Main Content Column -->
                <div class="ajbridge-col-main">
                    <!-- Tour Title & Address -->
                    <div class="ajbridge-tour-header">
                        <h1 class="ajbridge-tour-title"><?php echo esc_html($wp['title']); ?></h1>
                        
                        <?php if (!empty($wp['address'])): ?>
                            <div class="ajbridge-tour-location">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                    <circle cx="12" cy="10" r="3"></circle>
                                </svg>
                                <span><?php echo esc_html($wp['address']); ?></span>
                            </div>
                        <?php endif; ?>

                        <!-- Meta info (duration, rating, etc.) -->
                        <div class="ajbridge-tour-meta">
                            <?php if ($wp['duration_day'] > 0): ?>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                    <?php echo esc_html($wp['duration_day']); ?> <?php echo $wp['duration_day'] > 1 ? 'jours' : 'jour'; ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($wp['max_people'] > 0): ?>
                                <span class="meta-item">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="9" cy="7" r="4"></circle>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                    </svg>
                                    Max <?php echo esc_html($wp['max_people']); ?> personnes
                                </span>
                            <?php endif; ?>

                            <?php if ($wp['rate_review'] > 0): ?>
                                <span class="meta-item meta-rating">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                        <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                    </svg>
                                    <?php echo number_format($wp['rate_review'], 1); ?>/5
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Badges from Laravel -->
                        <?php if ($tourData['has_badges']): ?>
                            <div class="ajbridge-tour-badges">
                                <?php foreach ($laravel['badges'] as $badge): ?>
                                    <span class="tour-badge" style="background-color: <?php echo esc_attr($badge['bg_color']); ?>; color: <?php echo esc_attr($badge['color']); ?>;">
                                        <?php echo esc_html($badge['label']); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Gallery Section -->
                    <?php if ($tourData['has_gallery']): ?>
                        <div class="ajbridge-tour-gallery">
                            <h3>Galerie Photos</h3>
                            <div class="gallery-grid">
                                <?php foreach ($wp['gallery'] as $image): ?>
                                    <a href="<?php echo esc_url($image['url']); ?>" class="gallery-item" data-lightbox="tour-gallery">
                                        <img src="<?php echo esc_url($image['medium'] ?? $image['url']); ?>" 
                                             alt="<?php echo esc_attr($image['alt']); ?>" 
                                             loading="lazy">
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tour Description -->
                    <div class="ajbridge-tour-description">
                        <h3>Description</h3>
                        <div class="description-content">
                            <?php echo $wp['content']; ?>
                        </div>
                    </div>

                    <!-- Itinerary Section -->
                    <?php if ($tourData['has_itinerary']): ?>
                        <?php ajbridge_partial('itinerary', ['itinerary' => $itinerary, 'source' => $tourData['_sources']['itinerary']]); ?>
                    <?php endif; ?>

                    <!-- Inclusions & Exclusions -->
                    <?php if ($tourData['has_inclusions'] || $tourData['has_exclusions']): ?>
                        <?php ajbridge_partial('include-exclude', [
                            'inclusions' => $tourData['inclusions'],
                            'exclusions' => $tourData['exclusions'],
                        ]); ?>
                    <?php endif; ?>

                    <!-- Video Section -->
                    <?php if ($tourData['has_video']): ?>
                        <div class="ajbridge-tour-video">
                            <h3>Vidéo</h3>
                            <div class="video-container">
                                <?php echo wp_oembed_get($wp['video']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- FAQs Section -->
                    <?php if ($tourData['has_faqs'] && is_array($wp['faqs'])): ?>
                        <div class="ajbridge-tour-faqs">
                            <h3>Questions Fréquentes</h3>
                            <div class="faq-list">
                                <?php foreach ($wp['faqs'] as $index => $faq): ?>
                                    <div class="faq-item">
                                        <button class="faq-question" data-toggle="faq-<?php echo $index; ?>">
                                            <span><?php echo esc_html($faq['question'] ?? $faq['title'] ?? ''); ?></span>
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polyline points="6,9 12,15 18,9"></polyline>
                                            </svg>
                                        </button>
                                        <div class="faq-answer" id="faq-<?php echo $index; ?>">
                                            <?php echo wp_kses_post($faq['answer'] ?? $faq['content'] ?? ''); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Map Section -->
                    <?php if ($tourData['has_map']): ?>
                        <div class="ajbridge-tour-map">
                            <h3>Localisation</h3>
                            <div id="tour-map" 
                                 data-lat="<?php echo esc_attr($wp['map']['lat']); ?>" 
                                 data-lng="<?php echo esc_attr($wp['map']['lng']); ?>" 
                                 data-zoom="<?php echo esc_attr($wp['map']['zoom']); ?>">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Cancellation Policy -->
                    <?php if (!empty($wp['cancellation_policy'])): ?>
                        <div class="ajbridge-tour-policy">
                            <h3>Politique d'Annulation</h3>
                            <div class="policy-content">
                                <?php echo wp_kses_post($wp['cancellation_policy']); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Sidebar Column -->
                <div class="ajbridge-col-sidebar">
                    <!-- Pricing Box -->
                    <?php ajbridge_partial('pricing-box', [
                        'pricing' => $pricing,
                        'tourData' => $tourData,
                    ]); ?>

                    <!-- Departure Dates (if available) -->
                    <?php if ($tourData['has_departures']): ?>
                        <div class="ajbridge-sidebar-box departures-box">
                            <h4>Prochains Départs</h4>
                            <div class="departures-list">
                                <?php 
                                $departures = array_slice($laravel['departures'], 0, 5);
                                foreach ($departures as $departure): 
                                    $date = new DateTime($departure['departure_date']);
                                ?>
                                    <div class="departure-item <?php echo $departure['status']; ?>">
                                        <div class="departure-date">
                                            <span class="day"><?php echo $date->format('d'); ?></span>
                                            <span class="month"><?php echo $date->format('M Y'); ?></span>
                                        </div>
                                        <div class="departure-info">
                                            <span class="spots">
                                                <?php echo $departure['available_spots']; ?>/<?php echo $departure['max_spots']; ?> places
                                            </span>
                                            <?php if ($departure['status'] === 'available'): ?>
                                                <span class="status available">Disponible</span>
                                            <?php else: ?>
                                                <span class="status <?php echo $departure['status']; ?>">
                                                    <?php echo ucfirst($departure['status']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Categories -->
                    <?php if (!empty($wp['categories'])): ?>
                        <div class="ajbridge-sidebar-box categories-box">
                            <h4>Catégories</h4>
                            <div class="categories-list">
                                <?php foreach ($wp['categories'] as $cat): ?>
                                    <a href="<?php echo esc_url($cat['link']); ?>" class="category-tag">
                                        <?php echo esc_html($cat['name']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
